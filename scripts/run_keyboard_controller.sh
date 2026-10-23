#!/usr/bin/env bash
# Run the Duckietown keyboard controller UI on this Duckiebot.
# Serves http://<hostname>:8090/app/ for Mission Control embed / toolbar link.
set -euo pipefail

NAME="${KEYBOARD_CONTROLLER_NAME:-dashboard-keyboard-controller}"
PORT="${KEYBOARD_CONTROLLER_PORT:-8090}"
IMAGE="${KEYBOARD_CONTROLLER_IMAGE:-duckietown/dt-duckietown-viewer:ente}"
VEHICLE_NAME="${VEHICLE_NAME:-$(hostname -s 2>/dev/null || echo duckiebot)}"

# Prefer docker0 bridge IP so the container can reach host services.
if [[ -z "${VEHICLE_IP:-}" ]]; then
  if ip -4 addr show docker0 >/dev/null 2>&1; then
    VEHICLE_IP="$(ip -4 addr show docker0 | awk '/inet /{print $2}' | cut -d/ -f1 | head -1)"
  fi
fi
VEHICLE_IP="${VEHICLE_IP:-172.17.0.1}"

docker rm -f "$NAME" >/dev/null 2>&1 || true
# Publish only on the docker bridge. The dashboard proxies /keyboard-controller/
# after a login check. Do not bind 0.0.0.0, and keep Docker's default seccomp profile.
docker run -d --name "$NAME" --restart unless-stopped \
  -p "${VEHICLE_IP}:${PORT}:8000" \
  -v /data:/data:ro \
  -v /data/ramdisk/dtps:/dtps:rw \
  -v /var/run/avahi-daemon/socket:/var/run/avahi-daemon/socket \
  -e DT_LAUNCHER=keyboard_controller \
  -e MODULE=keyboard_controller \
  -e VEHICLE_NAME="$VEHICLE_NAME" \
  -e VEHICLE_IP="$VEHICLE_IP" \
  -e TITLE="Keyboard Controller" \
  --add-host "${VEHICLE_NAME}.local:${VEHICLE_IP}" \
  "$IMAGE"

echo "Keyboard controller on http://${VEHICLE_IP}:${PORT}/app/ (dashboard proxy: /keyboard-controller/app/)"
