<?php
/**
 * Modern Overview tab (default).
 *
 * Shared meter + chip + strip language for temperature, CPU/RAM/Disk,
 * battery, connection, and power/thermal. Legacy Chart.js version:
 *   pages/robot/legacy/info_chartjs_overview.php
 * Toggle via RobotUIFeatures::modern_overview() / robot_ui/modern_overview.
 *
 * @see ../../ui_features.php
 */
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$update_hz = 0.5;

$image_template = Core::getImageURL('robots/thumbnails/{0}_all.jpg', 'duckietown');
$network_snapshot = Duckiebot::getNetworkSnapshot();
$dbot_hostname = Duckiebot::getDuckiebotHostname();
?>

<style type="text/css">
    .robot-overview {
        max-width: var(--r-max, 1040px);
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .robot-overview-identity {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 18px;
        align-items: baseline;
        padding: 10px 12px;
        background: var(--r-surface, #f8f9fb);
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        font-size: var(--r-fs-xs, 10px);
        font-weight: var(--r-fw-semibold, 600);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
        color: var(--r-muted, #6b7280);
    }
    .robot-overview-identity .meta-item strong {
        color: var(--r-text, #111827);
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        letter-spacing: 0;
        text-transform: none;
        margin-left: 4px;
    }

    .robot-overview-layout {
        display: grid;
        grid-template-columns: minmax(200px, 260px) minmax(0, 1fr);
        gap: 12px;
        align-items: stretch;
    }
    @media (max-width: 820px) {
        .robot-overview-layout {
            grid-template-columns: 1fr;
        }
        .robot-overview-thumb {
            max-width: 280px;
            margin: 0 auto;
            width: 100%;
        }
    }

    .robot-overview-thumb {
        position: relative;
        aspect-ratio: 1 / 1;
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        overflow: hidden;
        min-height: 0;
    }
    .robot-overview-thumb img {
        position: absolute;
        inset: 0;
        margin: auto;
        max-width: 92%;
        max-height: 92%;
        object-fit: contain;
    }

    .robot-metrics {
        display: grid;
        grid-template-columns: 1fr 1fr;
        grid-template-rows: 1fr 1fr;
        gap: 12px;
        min-height: 0;
        height: 100%;
    }
    @media (max-width: 560px) {
        .robot-metrics {
            grid-template-columns: 1fr;
            grid-template-rows: none;
        }
    }

    .robot-metric {
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        padding: 12px 14px;
        min-height: 0;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 10px;
    }
    .robot-metric-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        flex: 0 0 auto;
    }
    .robot-metric-label {
        margin: 0;
        font-size: var(--r-fs-xs, 10px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
    }
    .robot-metric-value {
        font-size: var(--r-fs-value, 22px);
        font-weight: var(--r-fw-bold, 700);
        color: var(--r-text, #111827);
        letter-spacing: var(--r-tracking-tight, -0.02em);
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .robot-metric-sub {
        margin-left: 6px;
        font-size: var(--r-fs-sm, 11px);
        color: var(--r-muted, #6b7280);
        font-weight: var(--r-fw-medium, 500);
    }

    .robot-meter {
        height: 8px;
        background: var(--r-track, #eef0f3);
        border-radius: var(--r-radius-pill, 999px);
        overflow: hidden;
        flex: 0 0 auto;
    }
    .robot-meter > span {
        display: block;
        height: 100%;
        width: 0%;
        border-radius: var(--r-radius-pill, 999px);
        background: var(--r-fill, #2c5686);
        transition: width 0.35s ease, background-color 0.35s ease;
    }
    .robot-meter.is-ok > span { background: var(--r-ok, #047857); }
    .robot-meter.is-warn > span { background: var(--r-warn, #b45309); }
    .robot-meter.is-bad > span { background: var(--r-bad, #b91c1c); }

    .robot-temp-gauge {
        flex: 0 0 auto;
        margin-top: auto;
    }
    .robot-temp-track {
        position: relative;
        display: flex;
        height: 8px;
        border-radius: var(--r-radius-pill, 999px);
        overflow: visible;
        background: var(--r-track, #eef0f3);
    }
    .robot-temp-track-fill {
        display: flex;
        width: 100%;
        height: 100%;
        border-radius: var(--r-radius-pill, 999px);
        overflow: hidden;
    }
    .robot-temp-zone { height: 100%; }
    .robot-temp-zone.z-cool { width: 50%; background: #60a5fa; }
    .robot-temp-zone.z-ok   { width: 20%; background: #34d399; }
    .robot-temp-zone.z-warm { width: 15%; background: #fbbf24; }
    .robot-temp-zone.z-hot  { width: 15%; background: #f87171; }
    .robot-temp-marker {
        position: absolute;
        top: -4px;
        width: 3px;
        height: 16px;
        margin-left: -1.5px;
        background: #111827;
        border-radius: 2px;
        box-shadow: 0 0 0 2px #fff, 0 1px 2px rgba(0,0,0,0.25);
        transition: left 0.35s ease;
        left: 0%;
        z-index: 2;
        pointer-events: none;
    }
    .robot-temp-scale {
        position: relative;
        height: 14px;
        margin-top: 4px;
        font-size: 9px;
        color: var(--r-muted, #6b7280);
        font-variant-numeric: tabular-nums;
    }
    .robot-temp-scale > span {
        position: absolute;
        top: 0;
        transform: translateX(-50%);
        white-space: nowrap;
    }
    .robot-temp-scale > span:first-child { transform: translateX(0); }
    .robot-temp-scale > span:last-child { transform: translateX(-100%); }
    .robot-metric-sub.is-cool { color: #2563eb; }
    .robot-metric-sub.is-ok { color: var(--r-ok, #047857); }
    .robot-metric-sub.is-warn { color: var(--r-warn, #b45309); }
    .robot-metric-sub.is-bad { color: var(--r-bad, #b91c1c); }

    .robot-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: var(--r-radius-pill, 999px);
        border: 1px solid var(--r-border, #e6e8eb);
        background: var(--r-surface, #f8f9fb);
        color: var(--r-text, #111827);
        font-size: var(--r-fs-sm, 11px);
        font-weight: var(--r-fw-semibold, 600);
        line-height: 1.2;
    }
    .robot-chip.is-ok {
        border-color: var(--r-ok-border, #bbf7d0);
        background: var(--r-ok-bg, #ecfdf3);
        color: var(--r-ok, #047857);
    }
    .robot-chip.is-warn {
        border-color: var(--r-warn-border, #fde68a);
        background: var(--r-warn-bg, #fffbeb);
        color: var(--r-warn, #b45309);
    }
    .robot-chip.is-bad {
        border-color: var(--r-bad-border, #fecaca);
        background: var(--r-bad-bg, #fef2f2);
        color: var(--r-bad, #b91c1c);
    }
    .robot-chip.is-hidden {
        display: none;
    }

    .robot-strip {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 14px;
        padding: 10px 12px;
        background: var(--r-surface, #f8f9fb);
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        font-size: var(--r-fs-md, 12px);
        color: var(--r-muted, #6b7280);
    }
    .robot-strip .strip-label {
        font-size: var(--r-fs-xs, 10px);
        font-weight: var(--r-fw-semibold, 600);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
    }
    .robot-strip .strip-item {
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
        white-space: nowrap;
    }
    .robot-strip .strip-item[hidden] {
        display: none !important;
    }
    .robot-strip .strip-item strong {
        color: var(--r-text, #111827);
        font-weight: var(--r-fw-semibold, 600);
    }

    .robot-batt-diag {
        padding: 12px 14px;
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
    }
    .robot-batt-diag-head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 10px;
        margin-bottom: 10px;
    }
    .robot-batt-diag-head .batt-diag-label {
        font-size: var(--r-fs-xs, 10px);
        font-weight: var(--r-fw-semibold, 600);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
        color: var(--r-muted, #6b7280);
    }
    .robot-batt-diag-head .robot-metric-value {
        margin-left: auto;
    }
    .robot-batt-diag .robot-meter {
        margin: 0 0 12px;
    }
    .robot-batt-diag-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px 12px;
        padding-top: 12px;
        border-top: 1px solid var(--r-border, #e6e8eb);
    }
    @media (max-width: 720px) {
        .robot-batt-diag-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 420px) {
        .robot-batt-diag-grid {
            grid-template-columns: 1fr;
        }
    }
    .robot-batt-diag-item span {
        display: block;
        font-size: var(--r-fs-xs, 10px);
        font-weight: var(--r-fw-semibold, 600);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
        color: var(--r-muted, #6b7280);
        margin-bottom: 2px;
    }
    .robot-batt-diag-item strong {
        display: block;
        font-size: var(--r-fs-lg, 13px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-text, #111827);
        font-variant-numeric: tabular-nums;
        letter-spacing: var(--r-tracking-tight, -0.02em);
    }
    .robot-batt-diag.is-missing .robot-batt-diag-grid,
    .robot-batt-diag.is-missing .robot-meter {
        opacity: 0.45;
    }
</style>


<div class="robot-overview">
    <div class="robot-overview-identity robot-info-container">
        <span class="meta-item">Host<strong><?php echo htmlspecialchars($dbot_hostname) ?></strong></span>
        <span class="meta-item">Type<strong id="robot_type"><img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height:12px"></strong></span>
        <span class="meta-item">Config<strong id="robot_configuration"><img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height:12px"></strong></span>
        <span class="meta-item">Board<strong id="hardware_board"><img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height:12px"></strong></span>
        <span class="meta-item">Model<strong id="hardware_model"><img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height:12px"></strong></span>
        <span class="meta-item">Firmware<strong id="firmware_info"><img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height:12px"></strong></span>
    </div>

    <div class="robot-overview-layout">
        <div class="robot-overview-thumb robot-thumbnail-container">
            <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
        </div>

        <div class="robot-metrics">
            <div class="robot-metric" id="metric_temp">
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">CPU temperature</h4>
                    <div>
                        <span class="robot-metric-value" id="_robot_temp_value">-</span>
                        <span class="robot-metric-sub" id="_robot_temp_status"></span>
                    </div>
                </div>
                <div class="robot-temp-gauge">
                    <div class="robot-temp-track">
                        <div class="robot-temp-track-fill">
                            <span class="robot-temp-zone z-cool"></span>
                            <span class="robot-temp-zone z-ok"></span>
                            <span class="robot-temp-zone z-warm"></span>
                            <span class="robot-temp-zone z-hot"></span>
                        </div>
                        <span class="robot-temp-marker" id="_robot_temp_marker"></span>
                    </div>
                    <div class="robot-temp-scale">
                        <span style="left:0%">0</span>
                        <span style="left:50%">50</span>
                        <span style="left:70%">70</span>
                        <span style="left:85%">85</span>
                        <span style="left:100%">100°C</span>
                    </div>
                </div>
            </div>

            <div class="robot-metric" id="metric_cpu">
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">CPU</h4>
                    <span class="robot-metric-value" id="_robot_pcpu_value">-</span>
                </div>
                <div class="robot-meter" id="_robot_pcpu_meter"><span></span></div>
            </div>

            <div class="robot-metric" id="metric_ram">
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">RAM</h4>
                    <span class="robot-metric-value" id="_robot_ram_value">-</span>
                </div>
                <div class="robot-meter" id="_robot_ram_meter"><span></span></div>
            </div>

            <div class="robot-metric" id="metric_disk">
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">Disk</h4>
                    <span class="robot-metric-value" id="_robot_disk_value">-</span>
                </div>
                <div class="robot-meter" id="_robot_disk_meter"><span></span></div>
            </div>
        </div>
    </div>

    <div class="robot-batt-diag" id="robot_battery_diag" aria-live="polite">
        <div class="robot-batt-diag-head">
            <span class="batt-diag-label">Battery</span>
            <span class="robot-chip" id="batt_diag_present"><i class="fa fa-circle-o" aria-hidden="true"></i> Checking</span>
            <span class="robot-chip" id="batt_diag_charging">-</span>
            <span class="robot-metric-value" id="_robot_batt_value">-</span>
        </div>
        <div class="robot-meter" id="_robot_batt_meter"><span></span></div>
        <div class="robot-batt-diag-grid" id="metric_batt">
            <div class="robot-batt-diag-item"><span>Cell</span><strong id="batt_diag_cell">-</strong></div>
            <div class="robot-batt-diag-item"><span>Input</span><strong id="batt_diag_input">-</strong></div>
            <div class="robot-batt-diag-item"><span>Current</span><strong id="batt_diag_current">-</strong></div>
            <div class="robot-batt-diag-item"><span>Pack temp</span><strong id="batt_diag_temp">-</strong></div>
            <div class="robot-batt-diag-item"><span>USB 1</span><strong id="batt_diag_usb1">-</strong></div>
            <div class="robot-batt-diag-item"><span>USB 2</span><strong id="batt_diag_usb2">-</strong></div>
            <div class="robot-batt-diag-item"><span>Cycles</span><strong id="batt_diag_cycles">-</strong></div>
            <div class="robot-batt-diag-item"><span>Time left</span><strong id="batt_diag_tte">-</strong></div>
        </div>
    </div>

    <div class="robot-strip" id="robot_network" aria-live="polite">
        <span class="strip-label">Connection</span>
        <span class="robot-chip" id="net_status"><i class="fa fa-circle-o" aria-hidden="true"></i> Checking</span>
        <span class="strip-item">Link <strong id="net_kind">-</strong></span>
        <span class="strip-item" id="net_ssid_item" hidden>SSID <strong id="net_name">-</strong></span>
        <span class="strip-item">Network IP <strong id="net_ip">-</strong></span>
    </div>

    <div class="robot-strip robot-health-bits-container" id="robot_power_thermal" aria-live="polite">
        <span class="strip-label">Power / thermal</span>
        <span class="robot-chip is-ok" id="pt_summary_ok">
            <i class="fa fa-check-circle" aria-hidden="true"></i> Power &amp; thermal OK
        </span>
        <span class="robot-chip is-bad is-hidden" id="under-voltage-now" data-pt-key="under-voltage-now">
            Under-voltage
        </span>
        <span class="robot-chip is-bad is-hidden" id="freq-capped-now" data-pt-key="freq-capped-now">
            CPU capped
        </span>
        <span class="robot-chip is-bad is-hidden" id="throttling-now" data-pt-key="throttling-now">
            Throttling
        </span>
        <span class="robot-chip is-warn is-hidden" id="under-voltage-occurred" data-pt-key="under-voltage-occurred">
            Under-voltage (earlier)
        </span>
        <span class="robot-chip is-warn is-hidden" id="freq-capped-occurred" data-pt-key="freq-capped-occurred">
            CPU capped (earlier)
        </span>
        <span class="robot-chip is-warn is-hidden" id="throttling-occurred" data-pt-key="throttling-occurred">
            Throttling (earlier)
        </span>
    </div>
</div>


<script type="text/javascript">

    const PT_KEYS = [
        'under-voltage-now',
        'freq-capped-now',
        'throttling-now',
        'under-voltage-occurred',
        'freq-capped-occurred',
        'throttling-occurred'
    ];

    const NETWORK_BOOT = <?php echo json_encode($network_snapshot, JSON_UNESCAPED_SLASHES); ?>;

    function _meter_level(pct) {
        if (pct >= 90) return 'is-bad';
        if (pct >= 75) return 'is-warn';
        return 'is-ok';
    }

    function _set_meter(meter_id, pct, invert_ok) {
        let el = $(meter_id);
        let level = invert_ok ? _meter_level(100 - pct) : _meter_level(pct);
        if (invert_ok) {
            if (pct >= 40) level = 'is-ok';
            else if (pct >= 20) level = 'is-warn';
            else level = 'is-bad';
        }
        el.removeClass('is-ok is-warn is-bad').addClass(level);
        el.children('span').css('width', Math.max(0, Math.min(100, pct)).toFixed(1) + '%');
    }

    function _temp_status(temp) {
        if (temp < 50) return { label: 'Cool', cls: 'is-cool' };
        if (temp < 70) return { label: 'Normal', cls: 'is-ok' };
        if (temp < 85) return { label: 'Warm', cls: 'is-warn' };
        return { label: 'Hot', cls: 'is-bad' };
    }

    function _update_power_thermal(throttling) {
        let flags = throttling && typeof throttling === 'object' ? throttling : {};
        let anyIssue = false;
        PT_KEYS.forEach(function (key) {
            let active = !!flags[key];
            let chip = $('#' + key);
            if (!chip.length) return;
            chip.toggleClass('is-hidden', !active);
            if (active) anyIssue = true;
        });
        $('#pt_summary_ok').toggleClass('is-hidden', anyIssue);
    }

    function formatHardwareModel(raw, robot_configuration, memory_bytes) {
        if (raw === undefined || raw === null) raw = '';
        var s = String(raw).trim();
        if (/nano\s*4\s*gb/i.test(s)) return 'Nano 4GB';
        if (/nano\s*2\s*gb/i.test(s)) return 'Nano 2GB';
        if (/orin\s*nano/i.test(s)) return s.replace(/nvidia\s+/i, '');
        var mem = Number(memory_bytes);
        var is_nano = /^nano$/i.test(s) || /jetson\s*nano/i.test(s) || /tegra210/i.test(s);
        if (is_nano) {
            if (/2\s*gb/i.test(s) || (isFinite(mem) && mem > 0 && mem < 3000000000)) return 'Nano 2GB';
            return 'Nano 4GB';
        }
        var cfg = String(robot_configuration || '').toUpperCase();
        if (cfg === 'DB21J' || cfg === 'DB21M' || cfg === 'DB19') {
            return 'Nano 4GB';
        }
        return s || '-';
    }

    function formatBatteryCurrent(raw) {
        var n = Number(raw);
        if (!isFinite(n)) return '';
        var amps = Math.abs(n) > 20 ? n / 1000 : n;
        var sign = amps > 0 ? '+' : '';
        return sign + amps.toFixed(2) + ' A';
    }

    function formatBatteryVoltage(raw) {
        var n = Number(raw);
        if (!isFinite(n)) return '-';
        return n.toFixed(2) + ' V';
    }

    function formatBatteryTimeToEmpty(seconds, charging) {
        if (charging) return '-';
        var n = Number(seconds);
        if (!isFinite(n) || n <= 0 || n > 7 * 24 * 3600) return '-';
        if (typeof humanTime === 'function') {
            return humanTime(n, true, 'm');
        }
        var mins = Math.round(n / 60);
        if (mins < 60) return mins + 'm';
        return Math.floor(mins / 60) + 'h ' + (mins % 60) + 'm';
    }

    function isBatteryCharging(battery) {
        if (!battery || typeof battery !== 'object') return false;
        if (battery.charging === true || battery.charging === 'true' || battery.charging === 1) {
            return true;
        }
        var vin = Number(battery.input_voltage);
        var cur = Number(battery.current);
        return isFinite(vin) && vin > 2.5 && isFinite(cur) && cur > 0;
    }

    function isBatteryPresent(battery) {
        if (!battery || typeof battery !== 'object') return false;
        if (battery.present === false || battery.present === 'false' || battery.present === 0) {
            return false;
        }
        if (battery.percentage === 'ND') return false;
        if (battery.present === true || battery.present === 'true' || battery.present === 1) {
            return true;
        }
        return battery.percentage !== undefined && battery.percentage !== null;
    }

    function applyBatteryDiagnostics(battery) {
        var present = isBatteryPresent(battery);
        var charging = present && isBatteryCharging(battery);
        var panel = $('#robot_battery_diag');
        var presentChip = $('#batt_diag_present');
        var chargeChip = $('#batt_diag_charging');
        panel.toggleClass('is-missing', !present);
        presentChip
            .toggleClass('is-ok', present)
            .toggleClass('is-bad', !present)
            .html(present
                ? '<i class="fa fa-check-circle" aria-hidden="true"></i> Present'
                : '<i class="fa fa-times-circle" aria-hidden="true"></i> Not detected'
            );
        chargeChip
            .toggleClass('is-ok', charging)
            .toggleClass('is-warn', present && !charging)
            .html(present
                ? (charging
                    ? '<i class="fa fa-plug" aria-hidden="true"></i> Charging'
                    : '<i class="fa fa-battery-three-quarters" aria-hidden="true"></i> Discharging')
                : '-'
            );
        if (!present || !battery) {
            $('#batt_diag_cell, #batt_diag_input, #batt_diag_current, #batt_diag_temp, #batt_diag_usb1, #batt_diag_usb2, #batt_diag_cycles, #batt_diag_tte').text('-');
            return;
        }
        $('#batt_diag_cell').text(formatBatteryVoltage(battery.cell_voltage));
        $('#batt_diag_input').text(formatBatteryVoltage(battery.input_voltage));
        $('#batt_diag_current').text(formatBatteryCurrent(battery.current) || '-');
        var temp = Number(battery.temperature);
        $('#batt_diag_temp').text(isFinite(temp) ? temp.toFixed(1) + ' °C' : '-');
        $('#batt_diag_usb1').text(formatBatteryVoltage(battery.usb_out_1_voltage));
        $('#batt_diag_usb2').text(formatBatteryVoltage(battery.usb_out_2_voltage));
        var cycles = Number(battery.cycle_count);
        $('#batt_diag_cycles').text(isFinite(cycles) ? String(Math.round(cycles)) : '-');
        $('#batt_diag_tte').text(formatBatteryTimeToEmpty(battery.time_to_empty, charging));
    }

    function applyNetworkSnapshot(net) {
        net = net || {};
        var connected = !!net.connected;
        var kind = net.kind || net.type || net.iface || '';
        var name = net.ssid || net.name || net.network || '';
        var ip = net.ip || net.address || net.ipv4 || '';
        if (ip === '127.0.0.1' || ip.indexOf('127.') === 0) {
            ip = '';
        }
        var status = $('#net_status');
        status.toggleClass('is-ok', connected).toggleClass('is-bad', !connected);
        status.html(
            connected
                ? '<i class="fa fa-check-circle" aria-hidden="true"></i> Connected'
                : '<i class="fa fa-times-circle" aria-hidden="true"></i> Offline'
        );
        var kindLabel = '-';
        if (/wifi|wlan/i.test(String(kind))) kindLabel = 'Wi-Fi';
        else if (/eth|ethernet|wired/i.test(String(kind))) kindLabel = 'Ethernet';
        else if (kind) kindLabel = String(kind);
        $('#net_kind').text(kindLabel);
        if (name) {
            $('#net_name').text(name);
            $('#net_ssid_item').removeAttr('hidden');
        } else {
            $('#net_name').text('-');
            $('#net_ssid_item').attr('hidden', 'hidden');
        }
        $('#net_ip').text(ip || '-');
    }

    function update_overview() {
        let url = get_api_url("health");
        callExternalAPI(url, 'GET', 'text', false, false, function(raw){
            let data;
            try {
                data = (typeof raw === 'string') ? JSON.parse(raw) : raw;
            } catch (e) {
                console.warn('Overview: invalid health payload', e);
                return;
            }
            if (!data || typeof data !== 'object') return;
            applyNetworkSnapshot($.extend({}, NETWORK_BOOT, data.network || data.net || {}, {
                connected: true
            }));

            try {
                let temp = Number(data.temperature);
                if (!isFinite(temp)) {
                    $('#_robot_temp_value').text('-');
                    $('#_robot_temp_status').text('').removeClass('is-cool is-ok is-warn is-bad');
                    $('#_robot_temp_marker').css('left', '0%');
                } else {
                    let tstat = _temp_status(temp);
                    $('#_robot_temp_value').text(temp.toFixed(0) + ' °C');
                    $('#_robot_temp_status').text(tstat.label).removeClass('is-cool is-ok is-warn is-bad').addClass(tstat.cls);
                    $('#_robot_temp_marker').css('left', Math.max(0, Math.min(100, temp)).toFixed(1) + '%');
                }

                let cpu = Number(data.cpu && data.cpu.percentage);
                let ram = Number(data.memory && data.memory.percentage);
                let disk = Number(data.disk && data.disk.percentage);
                if (isFinite(cpu)) {
                    $('#_robot_pcpu_value').text(cpu.toFixed(1) + '%');
                    _set_meter('#_robot_pcpu_meter', cpu, false);
                }
                if (isFinite(ram)) {
                    $('#_robot_ram_value').text(ram.toFixed(1) + '%');
                    _set_meter('#_robot_ram_meter', ram, false);
                }
                if (isFinite(disk)) {
                    $('#_robot_disk_value').text(disk.toFixed(1) + '%');
                    _set_meter('#_robot_disk_meter', disk, false);
                }

                let batt_raw = data.battery && data.battery.percentage;
                applyBatteryDiagnostics(data.battery);
                if (batt_raw !== undefined && batt_raw !== null && batt_raw !== 'ND') {
                    let batt = Number(batt_raw);
                    if (isFinite(batt)) {
                        $('#_robot_batt_value').text(batt.toFixed(1) + '%');
                        _set_meter('#_robot_batt_meter', batt, true);
                    }
                } else if (batt_raw === 'ND') {
                    $('#_robot_batt_value').text('ND');
                    _set_meter('#_robot_batt_meter', 0, true);
                }

                if (data.hardware) {
                    if (data.hardware.board) {
                        $('.robot-info-container #hardware_board').text(data.hardware.board);
                    }
                    if (data.hardware.model) {
                        let cfg = $('.robot-info-container #robot_configuration').text();
                        $('.robot-info-container #hardware_model').text(
                            formatHardwareModel(
                                data.hardware.model,
                                cfg,
                                data.hardware.memory
                            )
                        );
                    }
                }
                if (data.network || data.net) {
                    applyNetworkSnapshot($.extend({}, NETWORK_BOOT, data.network || data.net, {
                        connected: true
                    }));
                }
                if (data.software && data.software.date && data.software.version) {
                    let firmware = '{month}/{day}/{year}'.format(data.software.date);
                    firmware = '{0} ({1})'.format(firmware, String(data.software.version).substr(0, 7));
                    $('.robot-info-container #firmware_info').text(firmware);
                }

                _update_power_thermal(data.throttling);
            } catch (e) {
                console.warn('Overview: failed to render health data', e);
            }
        }, true, true);
    }

    $(document).ready(function () {
        let url = get_api_url("files", "data/config/robot_type");
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_type = 'unknown';
            try { robot_type = data.split('\n')[0].trim(); } catch (e) {}
            $('.robot-info-container #robot_type').html(robot_type.capitalize());
        }, true, true);

        url = get_api_url("files", "data/config/robot_configuration");
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_configuration = 'unknown';
            try { robot_configuration = data.split('\n')[0].trim(); } catch (e) {}
            let template = '<?php echo $image_template ?>';
            $('.robot-thumbnail-container img').attr('src', template.format(robot_configuration));
            $('.robot-info-container #robot_configuration').html(robot_configuration.capitalize());
            let modelEl = $('.robot-info-container #hardware_model');
            let currentModel = modelEl.find('img').length ? '' : modelEl.text();
            modelEl.text(formatHardwareModel(currentModel, robot_configuration));
        }, true, true);

        update_overview();
        applyNetworkSnapshot(NETWORK_BOOT);
        setInterval(update_overview, <?php echo 1000 / $update_hz ?>);
    });

</script>
