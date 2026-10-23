#!/usr/bin/env python3
"""Minimal wpa_supplicant control helper for the Duckiebot dashboard.

Talks to the UNIX control socket under /var/run/wpa_supplicant (must be
mounted into the dashboard container). Commands are JSON on stdin:

  {"action":"scan"}
  {"action":"connect","ssid":"...","psk":"..."}   # psk optional for open/known
  {"action":"status"}

Prints a single JSON object on stdout. Never echoes passphrases.
"""

from __future__ import print_function

import json
import os
import socket
import stat
import sys
import time


CTRL_DIR = os.environ.get("WPA_CTRL_DIR", "/var/run/wpa_supplicant")
# Client bind path must be visible to host wpa_supplicant (shared mount),
# not container-local /tmp — otherwise replies never arrive.
# Prefer /data (already mounted into the dashboard) so we do not write
# client sockets next to the server sockets under /var/run/wpa_supplicant.
CLIENT_DIR = os.environ.get("WPA_CTRL_CLIENT_DIR", "/data/wpa_ctrl")


class WpaCtrl(object):
    def __init__(self, iface_path):
        if not os.path.isdir(CLIENT_DIR):
            os.makedirs(CLIENT_DIR, mode=0o755)
        self._local = os.path.join(
            CLIENT_DIR, "wpa_ctrl_%s_%s" % (os.getpid(), int(time.time() * 1000))
        )
        try:
            os.unlink(self._local)
        except OSError:
            pass
        self._sock = socket.socket(socket.AF_UNIX, socket.SOCK_DGRAM)
        self._sock.bind(self._local)
        try:
            os.chmod(self._local, 0o666)
        except OSError:
            pass
        self._sock.connect(iface_path)
        self._sock.settimeout(8.0)

    def request(self, cmd, timeout=8.0):
        self._sock.settimeout(timeout)
        self._sock.send(cmd.encode("utf-8"))
        data = self._sock.recv(65535)
        return data.decode("utf-8", "replace")

    def close(self):
        try:
            self._sock.close()
        finally:
            try:
                os.unlink(self._local)
            except OSError:
                pass


def find_iface():
    if not os.path.isdir(CTRL_DIR):
        return None
    preferred = []
    other = []
    try:
        names = sorted(os.listdir(CTRL_DIR))
    except OSError:
        # Common when /var/run/wpa_supplicant is mounted but still root-only
        # (drwxr-x---). Caller surfaces a clear JSON error.
        return None
    for name in names:
        if name.startswith(".") or name.startswith("wpa_ctrl_") or name.startswith("cli_"):
            continue
        path = os.path.join(CTRL_DIR, name)
        # Control interfaces are UNIX sockets; ignore leftover regular files.
        try:
            mode = os.stat(path).st_mode
        except OSError:
            continue
        if not stat.S_ISSOCK(mode):
            continue
        if name.startswith("p2p-"):
            other.append(path)
        else:
            preferred.append(path)
    choices = preferred or other
    return choices[0] if choices else None


def quote_value(value):
    return '"' + value.replace("\\", "\\\\").replace('"', '\\"') + '"'


def parse_scan_results(text):
    networks = {}
    lines = text.strip().splitlines()
    if not lines:
        return []
    for line in lines[1:]:
        parts = line.split("\t")
        if len(parts) < 5:
            continue
        bssid, freq, signal, flags, ssid = parts[0], parts[1], parts[2], parts[3], parts[4]
        if not ssid:
            continue
        try:
            signal_i = int(signal)
        except ValueError:
            signal_i = -100
        try:
            freq_i = int(freq)
        except ValueError:
            freq_i = 0
        secured = "WPA" in flags or "WEP" in flags or "SAE" in flags or "RSN" in flags
        # open networks still have [ESS]; treat absence of WPA/WEP/SAE as open
        open_net = not secured
        entry = {
            "ssid": ssid,
            "signal": signal_i,
            "frequency": freq_i,
            "secured": not open_net,
            "flags": flags,
            "bssid": bssid,
        }
        prev = networks.get(ssid)
        if prev is None or entry["signal"] > prev["signal"]:
            networks[ssid] = entry
    return sorted(networks.values(), key=lambda n: n["signal"], reverse=True)


def parse_list_networks(text):
    known = {}
    lines = text.strip().splitlines()
    for line in lines[1:]:
        parts = line.split("\t")
        if len(parts) < 4:
            continue
        net_id, ssid, _bssid, flags = parts[0], parts[1], parts[2], parts[3]
        if not ssid:
            continue
        known[ssid] = {
            "id": int(net_id),
            "ssid": ssid,
            "current": "CURRENT" in flags,
            "disabled": "DISABLED" in flags,
        }
    return known


def parse_status(text):
    status = {}
    for line in text.splitlines():
        if "=" not in line:
            continue
        key, val = line.split("=", 1)
        status[key] = val
    return {
        "ssid": status.get("ssid") or None,
        "ip": status.get("ip_address") or None,
        "state": status.get("wpa_state") or None,
        "freq": status.get("freq") or None,
    }


def do_scan(ctrl):
    known = parse_list_networks(ctrl.request("LIST_NETWORKS"))
    trigger = ctrl.request("SCAN").strip()
    if trigger not in ("OK", "FAIL-BUSY"):
        return {"ok": False, "error": "Wi-Fi scan failed: %s" % trigger}
    # Wait briefly for results; FAIL-BUSY means a scan is already running.
    deadline = time.time() + 4.0
    results_text = ""
    while time.time() < deadline:
        time.sleep(0.7)
        results_text = ctrl.request("SCAN_RESULTS")
        if results_text.strip().count("\n") > 0:
            break
    networks = parse_scan_results(results_text)
    for net in networks:
        info = known.get(net["ssid"])
        net["known"] = info is not None
        net["current"] = bool(info and info.get("current"))
    # Include known networks that are out of range so users can still switch.
    seen = {n["ssid"] for n in networks}
    for ssid, info in known.items():
        if ssid in seen:
            continue
        networks.append({
            "ssid": ssid,
            "signal": None,
            "frequency": None,
            "secured": True,
            "flags": "",
            "bssid": None,
            "known": True,
            "current": bool(info.get("current")),
            "out_of_range": True,
        })
    status = parse_status(ctrl.request("STATUS"))
    return {
        "ok": True,
        "networks": networks,
        "current": status,
        "available": True,
    }


def do_connect(ctrl, ssid, psk):
    if not ssid:
        return {"ok": False, "error": "SSID is required"}
    if len(ssid) > 32:
        return {"ok": False, "error": "SSID is too long"}
    if psk is not None and psk != "" and (len(psk) < 8 or len(psk) > 63):
        return {"ok": False, "error": "Password must be 8–63 characters"}

    known = parse_list_networks(ctrl.request("LIST_NETWORKS"))
    net_id = None
    if ssid in known:
        net_id = known[ssid]["id"]
        if psk:
            resp = ctrl.request("SET_NETWORK %d psk %s" % (net_id, quote_value(psk))).strip()
            if resp != "OK":
                return {"ok": False, "error": "Could not update Wi-Fi password"}
    else:
        add = ctrl.request("ADD_NETWORK").strip()
        try:
            net_id = int(add)
        except ValueError:
            return {"ok": False, "error": "Could not create Wi-Fi profile"}
        resp = ctrl.request("SET_NETWORK %d ssid %s" % (net_id, quote_value(ssid))).strip()
        if resp != "OK":
            ctrl.request("REMOVE_NETWORK %d" % net_id)
            return {"ok": False, "error": "Could not set SSID"}
        if psk:
            resp = ctrl.request("SET_NETWORK %d psk %s" % (net_id, quote_value(psk))).strip()
            if resp != "OK":
                ctrl.request("REMOVE_NETWORK %d" % net_id)
                return {"ok": False, "error": "Could not set Wi-Fi password"}
        else:
            # Open network
            for cmd in (
                "SET_NETWORK %d key_mgmt NONE" % net_id,
            ):
                resp = ctrl.request(cmd).strip()
                if resp != "OK":
                    ctrl.request("REMOVE_NETWORK %d" % net_id)
                    return {"ok": False, "error": "Could not configure open network"}

    for cmd in (
        "ENABLE_NETWORK %d" % net_id,
        "SELECT_NETWORK %d" % net_id,
    ):
        resp = ctrl.request(cmd).strip()
        if resp != "OK":
            return {"ok": False, "error": "Could not select network (%s)" % cmd}

    # Persist when allowed (update_config=1 on Duckiebots).
    save = ctrl.request("SAVE_CONFIG").strip()
    status = parse_status(ctrl.request("STATUS"))
    return {
        "ok": True,
        "selected": ssid,
        "network_id": net_id,
        "saved": save == "OK",
        "status": status,
        "warning": (
            "The robot is switching Wi-Fi. This page will disconnect until "
            "your computer joins the same network."
        ),
    }


def main():
    try:
        raw = sys.stdin.read()
        req = json.loads(raw) if raw.strip() else {}
    except Exception as exc:
        print(json.dumps({"ok": False, "error": "Invalid request: %s" % exc}))
        return 1

    action = (req.get("action") or "").strip().lower()
    iface = find_iface()
    if not iface:
        # Distinguish missing mount from root-only directory permissions.
        perm_hint = ""
        if os.path.isdir(CTRL_DIR) and not os.access(CTRL_DIR, os.R_OK | os.X_OK):
            perm_hint = (
                " Socket directory is not readable; on the robot run: "
                "sudo chmod 755 /var/run/wpa_supplicant && "
                "sudo chmod 666 /var/run/wpa_supplicant/wlan0 "
                "/var/run/wpa_supplicant/p2p-dev-wlan0"
            )
        print(json.dumps({
            "ok": False,
            "available": False,
            "error": (
                "Wi-Fi control socket not available. Mount "
                "/var/run/wpa_supplicant into the dashboard container."
                + perm_hint
            ),
        }))
        return 2

    ctrl = None
    try:
        ctrl = WpaCtrl(iface)
        if action == "scan":
            result = do_scan(ctrl)
        elif action == "connect":
            result = do_connect(ctrl, req.get("ssid") or "", req.get("psk"))
        elif action == "status":
            result = {"ok": True, "current": parse_status(ctrl.request("STATUS")), "available": True}
        else:
            result = {"ok": False, "error": "Unknown action"}
    except Exception as exc:
        result = {"ok": False, "error": "Wi-Fi helper failed: %s" % exc}
    finally:
        if ctrl is not None:
            ctrl.close()

    print(json.dumps(result))
    return 0 if result.get("ok") else 1


if __name__ == "__main__":
    sys.exit(main())
