<?php
/**
 * Modern Overview tab (default).
 *
 * Redesigned temperature thermometer + horizontal CPU/RAM/Disk/Battery meters.
 * Legacy Chart.js version is preserved at:
 *   pages/robot/legacy/info_chartjs_overview.php
 * Toggle via RobotUIFeatures::modern_overview() / robot_ui/modern_overview.
 *
 * @see ../../ui_features.php
 */
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$update_hz = 0.5;

$image_template = Core::getImageURL('robots/thumbnails/{0}_all.jpg', 'duckietown');
?>

<style type="text/css">
    .robot-overview {
        max-width: var(--r-max, 1040px);
        margin: 0 auto;
    }

    .robot-overview-identity {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 18px;
        align-items: center;
        padding: 8px 12px;
        margin-bottom: var(--r-gap-lg, 12px);
        background: var(--r-surface, #f8f9fb);
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        font-size: var(--r-fs-md, 12px);
        color: var(--r-muted, #6b7280);
    }
    .robot-overview-identity .meta-item strong {
        color: var(--r-text, #111827);
        font-weight: var(--r-fw-semibold, 600);
        margin-left: 4px;
    }

    .robot-overview-layout {
        display: grid;
        grid-template-columns: minmax(220px, 280px) 1fr;
        gap: var(--r-gap-lg, 12px);
        align-items: start;
    }
    @media (max-width: 820px) {
        .robot-overview-layout {
            grid-template-columns: 1fr;
        }
    }

    .robot-overview-thumb {
        position: relative;
        aspect-ratio: 1 / 1;
        background: #fafbfc;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        overflow: hidden;
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
        gap: var(--r-gap, 10px);
    }
    @media (max-width: 560px) {
        .robot-metrics {
            grid-template-columns: 1fr;
        }
    }

    .robot-metric {
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        padding: 12px 14px;
        min-height: 88px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 8px;
        transition: border-color var(--r-ease, 160ms ease), box-shadow var(--r-ease, 160ms ease), transform var(--r-ease, 160ms ease);
    }
    .robot-metric:hover {
        border-color: #d1d5db;
        box-shadow: 0 1px 2px rgba(17, 24, 39, 0.06);
    }
    .robot-metric.is-wide {
        grid-column: 1 / -1;
    }
    .robot-metric-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
    }
    .robot-metric-label {
        margin: 0;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
    }
    .robot-metric-label i {
        margin-right: 4px;
        opacity: 0.8;
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
        font-size: var(--r-fs-sm, 11px);
        color: var(--r-muted, #6b7280);
        font-weight: var(--r-fw-medium, 500);
    }

    .robot-meter {
        height: 8px;
        background: var(--r-track, #eef0f3);
        border-radius: var(--r-radius-pill, 999px);
        overflow: hidden;
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
        position: relative;
        margin-top: 4px;
    }
    .robot-temp-track {
        position: relative;
        display: flex;
        height: 14px;
        border-radius: var(--r-radius-pill, 999px);
        overflow: hidden;
        background: var(--r-track, #eef0f3);
    }
    .robot-temp-zone {
        height: 100%;
    }
    .robot-temp-zone.z-cool { width: 50%; background: #60a5fa; }
    .robot-temp-zone.z-ok   { width: 20%; background: #34d399; }
    .robot-temp-zone.z-warm { width: 15%; background: #fbbf24; }
    .robot-temp-zone.z-hot  { width: 15%; background: #f87171; }
    .robot-temp-marker {
        position: absolute;
        top: -5px;
        width: 3px;
        height: 24px;
        margin-left: -1.5px;
        background: #111827;
        border-radius: 2px;
        box-shadow: 0 0 0 2px #fff, 0 1px 2px rgba(0,0,0,0.25);
        transition: left 0.35s ease;
        left: 0%;
        z-index: 2;
    }
    .robot-temp-scale {
        position: relative;
        height: 14px;
        margin-top: 6px;
        font-size: var(--r-fs-xs, 10px);
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
    .robot-metric.is-cool .robot-metric-value { color: #2563eb; }
    .robot-metric.is-ok .robot-metric-value { color: var(--r-ok, #047857); }
    .robot-metric.is-warn .robot-metric-value { color: var(--r-warn, #b45309); }
    .robot-metric.is-bad .robot-metric-value { color: var(--r-bad, #b91c1c); }

    .robot-health-bits-subtle {
        margin-top: var(--r-gap-lg, 12px);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        padding: 10px 0 0;
        border-top: 1px solid var(--r-border, #e6e8eb);
        min-height: 28px;
    }
    .robot-health-bits-subtle .bits-label {
        font-size: var(--r-fs-xs, 10px);
        color: var(--r-muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
        margin-right: 4px;
    }
    .robot-health-bits-subtle .pt-chip {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: var(--r-fs-sm, 11px);
        font-weight: var(--r-fw-semibold, 600);
        padding: 4px 10px;
        border-radius: var(--r-radius-pill, 999px);
        border: 1px solid transparent;
        background: #f3f4f6;
        color: var(--r-muted, #6b7280);
        line-height: 1.2;
        user-select: none;
    }
    .robot-health-bits-subtle .pt-chip.is-ok {
        background: var(--r-ok-bg, #ecfdf3);
        color: #15803d;
        border-color: var(--r-ok-border, #bbf7d0);
    }
    .robot-health-bits-subtle .pt-chip.is-warn {
        background: var(--r-warn-bg, #fffbeb);
        color: #b45309;
        border-color: var(--r-warn-border, #fde68a);
    }
    .robot-health-bits-subtle .pt-chip.is-bad {
        background: var(--r-bad-bg, #fef2f2);
        color: #b91c1c;
        border-color: var(--r-bad-border, #fecaca);
    }
    .robot-health-bits-subtle .pt-chip.is-hidden {
        display: none;
    }
    .robot-health-bits-subtle .pt-chip .fa-info-circle {
        opacity: 0.55;
        font-size: var(--r-fs-xs, 10px);
    }
</style>


<div class="robot-overview">
    <div class="robot-overview-identity robot-info-container">
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
            <div class="robot-metric is-wide robot-tip robot-interactive" id="metric_temp" tabindex="0" role="button" aria-expanded="false">
                <div class="robot-tip-bubble">
                    <strong>Board temperature</strong>
                    Cool &lt;50°C · Normal 50–70°C · Warm 70–85°C · Hot ≥85°C. Sustained heat can trigger throttling.
                </div>
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">
                        <i class="fa fa-thermometer-half" aria-hidden="true"></i> Temperature
                    </h4>
                    <div>
                        <span class="robot-metric-value" id="_robot_temp_value">—</span>
                        <span class="robot-metric-sub" id="_robot_temp_status"></span>
                    </div>
                </div>
                <div class="robot-temp-gauge">
                    <div class="robot-temp-track">
                        <span class="robot-temp-zone z-cool"></span>
                        <span class="robot-temp-zone z-ok"></span>
                        <span class="robot-temp-zone z-warm"></span>
                        <span class="robot-temp-zone z-hot"></span>
                        <span class="robot-temp-marker" id="_robot_temp_marker"></span>
                    </div>
                    <div class="robot-temp-scale">
                        <span style="left:0%">0°C</span>
                        <span style="left:50%">50</span>
                        <span style="left:70%">70</span>
                        <span style="left:85%">85</span>
                        <span style="left:100%">100°C</span>
                    </div>
                </div>
            </div>

            <div class="robot-metric robot-tip robot-interactive" id="metric_cpu" tabindex="0" role="button" aria-expanded="false">
                <div class="robot-tip-bubble">
                    <strong>CPU usage</strong>
                    Average processor load. High sustained usage can raise temperature and reduce headroom for autonomy.
                </div>
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">
                        <i class="fa fa-server" aria-hidden="true"></i> CPU
                    </h4>
                    <span class="robot-metric-value" id="_robot_pcpu_value">—</span>
                </div>
                <div class="robot-meter" id="_robot_pcpu_meter"><span></span></div>
            </div>

            <div class="robot-metric robot-tip robot-interactive" id="metric_ram" tabindex="0" role="button" aria-expanded="false">
                <div class="robot-tip-bubble">
                    <strong>Memory usage</strong>
                    RAM currently in use. Very high usage can slow processes or cause swapping.
                </div>
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">
                        <i class="fa fa-microchip" aria-hidden="true"></i> RAM
                    </h4>
                    <span class="robot-metric-value" id="_robot_ram_value">—</span>
                </div>
                <div class="robot-meter" id="_robot_ram_meter"><span></span></div>
            </div>

            <div class="robot-metric robot-tip robot-interactive" id="metric_disk" tabindex="0" role="button" aria-expanded="false">
                <div class="robot-tip-bubble">
                    <strong>Disk usage</strong>
                    Storage filled on the robot. Keep free space for logs, maps, and container images.
                </div>
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">
                        <i class="fa fa-hdd-o" aria-hidden="true"></i> Disk
                    </h4>
                    <span class="robot-metric-value" id="_robot_disk_value">—</span>
                </div>
                <div class="robot-meter" id="_robot_disk_meter"><span></span></div>
            </div>

            <div class="robot-metric robot-tip robot-interactive" id="metric_batt" tabindex="0" role="button" aria-expanded="false">
                <div class="robot-tip-bubble">
                    <strong>Battery</strong>
                    Charge remaining when a battery sensor is available. ND means not detected on this configuration.
                </div>
                <div class="robot-metric-head">
                    <h4 class="robot-metric-label">
                        <i class="fa fa-battery-three-quarters" aria-hidden="true"></i> Battery
                    </h4>
                    <div style="text-align:right">
                        <span class="robot-metric-value" id="_robot_batt_value">—</span>
                        <div class="robot-metric-sub" id="_robot_battery_details"></div>
                    </div>
                </div>
                <div class="robot-meter" id="_robot_batt_meter"><span></span></div>
            </div>
        </div>
    </div>

    <div class="robot-health-bits-container robot-health-bits-subtle" id="robot_power_thermal" aria-live="polite">
        <span class="bits-label">Power / thermal</span>
        <span class="pt-chip is-ok robot-tip robot-interactive" id="pt_summary_ok" tabindex="0" role="button" aria-expanded="false">
            <i class="fa fa-check-circle" aria-hidden="true"></i> Power &amp; thermal OK
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>All clear</strong>
                No under-voltage, CPU frequency capping, or thermal throttling since the last check. Click any chip for details when issues appear.
            </span>
        </span>
        <span class="pt-chip is-bad is-hidden robot-tip robot-interactive" id="under-voltage-now" tabindex="0" role="button" aria-expanded="false" data-pt-key="under-voltage-now">
            Under-voltage
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>Under-voltage (now)</strong>
                Supply voltage is too low right now. Check the battery charge, PSU, and power cable / HAT connection.
            </span>
        </span>
        <span class="pt-chip is-bad is-hidden robot-tip robot-interactive" id="freq-capped-now" tabindex="0" role="button" aria-expanded="false" data-pt-key="freq-capped-now">
            CPU capped
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>CPU frequency capped (now)</strong>
                The CPU is limited below its max clock — often from heat or power limits. Reduce load or improve cooling / power.
            </span>
        </span>
        <span class="pt-chip is-bad is-hidden robot-tip robot-interactive" id="throttling-now" tabindex="0" role="button" aria-expanded="false" data-pt-key="throttling-now">
            Throttling
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>Thermal throttling (now)</strong>
                The board is actively slowing itself to cool down. Improve airflow or lower CPU/GPU load.
            </span>
        </span>
        <span class="pt-chip is-warn is-hidden robot-tip robot-interactive" id="under-voltage-occurred" tabindex="0" role="button" aria-expanded="false" data-pt-key="under-voltage-occurred">
            Under-voltage (earlier)
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>Under-voltage (since boot)</strong>
                Voltage dipped earlier in this boot. Not active now, but check power delivery if it keeps happening.
            </span>
        </span>
        <span class="pt-chip is-warn is-hidden robot-tip robot-interactive" id="freq-capped-occurred" tabindex="0" role="button" aria-expanded="false" data-pt-key="freq-capped-occurred">
            CPU capped (earlier)
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>CPU capped (since boot)</strong>
                Frequency was limited earlier this boot. Cleared now; watch temperature and power if it returns.
            </span>
        </span>
        <span class="pt-chip is-warn is-hidden robot-tip robot-interactive" id="throttling-occurred" tabindex="0" role="button" aria-expanded="false" data-pt-key="throttling-occurred">
            Throttling (earlier)
            <i class="fa fa-info-circle" aria-hidden="true"></i>
            <span class="robot-tip-bubble">
                <strong>Throttling (since boot)</strong>
                Thermal throttling happened earlier this boot. Not active now; improve cooling if it recurs.
            </span>
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
        if (temp < 50) return { label: 'Cool', cls: 'is-cool', color: '#2563eb' };
        if (temp < 70) return { label: 'Normal', cls: 'is-ok', color: '#047857' };
        if (temp < 85) return { label: 'Warm', cls: 'is-warn', color: '#b45309' };
        return { label: 'Hot', cls: 'is-bad', color: '#b91c1c' };
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

            try {
                let temp = Number(data.temperature);
                if (!isFinite(temp)) {
                    $('#_robot_temp_value').text('—');
                    $('#_robot_temp_status').text('');
                } else {
                    let temp_pct = Math.max(0, Math.min(100, temp));
                    let tstat = _temp_status(temp);
                    $('#_robot_temp_value').text(temp.toFixed(0) + ' °C');
                    $('#_robot_temp_status').text(tstat.label).css('color', tstat.color);
                    $('#_robot_temp_marker').css('left', temp_pct.toFixed(1) + '%');
                    $('#metric_temp').removeClass('is-cool is-ok is-warn is-bad').addClass(tstat.cls);
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

                let battery_details = $('#_robot_battery_details');
                let batt_raw = data.battery && data.battery.percentage;
                if (batt_raw !== undefined && batt_raw !== null && batt_raw !== 'ND') {
                    let batt = Number(batt_raw);
                    if (isFinite(batt)) {
                        $('#_robot_batt_value').text(batt.toFixed(1) + '%');
                        _set_meter('#_robot_batt_meter', batt, true);
                        if (Number(data.battery.input_voltage) > 2.5 && Number(data.battery.current) > 0) {
                            battery_details.html('<i class="fa fa-plug" aria-hidden="true"></i> Charging');
                        } else if (data.battery.time_to_empty != null) {
                            battery_details.text(humanTime(data.battery.time_to_empty, true, 'm') + ' left');
                        } else {
                            battery_details.text('');
                        }
                    }
                } else if (batt_raw === 'ND') {
                    $('#_robot_batt_value').text('ND');
                    _set_meter('#_robot_batt_meter', 0, true);
                    battery_details.text('');
                }

                if (data.hardware) {
                    if (data.hardware.board) {
                        $('.robot-info-container #hardware_board').text(data.hardware.board);
                    }
                    if (data.hardware.model) {
                        $('.robot-info-container #hardware_model').text(data.hardware.model);
                    }
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
        }, true, true);

        update_overview();
        setInterval(update_overview, <?php echo 1000 / $update_hz ?>);
    });

</script>
