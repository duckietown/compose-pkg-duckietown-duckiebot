<?php

use system\classes\Core;
use system\packages\duckietown_duckiebot\Duckiebot;

// TODO: these might not be needed anymore
$dbot_hostname = Core::getSetting(
    'health_api/hostname', 'duckietown_duckiebot', Duckiebot::getDuckiebotHostname()
);
$update_hz = 0.5;
?>

<style type="text/css">
    .robot-health {
        max-width: var(--r-max, 1040px);
        margin: 0 auto;
    }
    .robot-health-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--r-gap, 10px);
    }
    @media (min-width: 900px) {
        .robot-health-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    .robot-health-card {
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        padding: 12px 14px;
    }
    .robot-health-card h4 {
        margin: 0 0 8px 0;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
    }
    .robot-health-card canvas {
        width: 100% !important;
        height: 220px !important;
    }
</style>

<div class="robot-health">
    <p class="robot-hint">Live telemetry history (last ~60 samples). Values update automatically.</p>
    <div class="robot-health-grid">
        <div class="robot-health-card">
            <h4>CPU temperature</h4>
            <canvas id="_robot_temp_canvas"></canvas>
        </div>
        <div class="robot-health-card">
            <h4>CPU Frequency</h4>
            <canvas id="_robot_fcpu_canvas"></canvas>
        </div>
        <div class="robot-health-card">
            <h4>CPU Usage</h4>
            <canvas id="_robot_pcpu_canvas"></canvas>
        </div>
        <div class="robot-health-card">
            <h4>RAM Usage</h4>
            <canvas id="_robot_pmem_canvas"></canvas>
        </div>
        <div class="robot-health-card">
            <h4>Swap Usage</h4>
            <canvas id="_robot_pswap_canvas"></canvas>
        </div>
        <div class="robot-health-card">
            <h4>GPU Usage</h4>
            <canvas id="_robot_pgpu_canvas"></canvas>
        </div>
        <div class="robot-health-card">
            <h4>GPU temperature</h4>
            <canvas id="_robot_tgpu_canvas"></canvas>
        </div>
    </div>
</div>


<script type="text/javascript">

    let _HISTORY_HORIZON_LEN = 60;
    let _DATA_TEMPERATURE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_CPU_FREQUENCY = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_CPU_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_RAM_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_SWAP_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_GPU_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_GPU_TEMP = new Array(_HISTORY_HORIZON_LEN).fill(null);

    function format_time(secs) {
        let parts = [];
        if (secs > 59)
            parts.push('{0}m'.format(Math.floor(secs / 60)));
        if (secs % 60 !== 0 || secs === 0)
            parts.push('{0}s'.format(secs % 60));
        return parts.join(' ');
    }

    function _robot_health_create_plot(canvas_id, data, title, y_label, tick_cb, color, min, max) {
        let chart_config = {
            type: 'line',
            data: {
                labels: range(_HISTORY_HORIZON_LEN - 1, 0, 1),
                datasets: [
                    {
                        label: title,
                        backgroundColor: Chart.helpers.color(color).alpha(0.3).rgbString(),
                        data: data,
                        borderColor: Chart.helpers.color(color).alpha(0.6).rgbString(),
                        pointRadius: 3,
                        pointBackgroundColor: '#fff',
                        borderWidth: 2,
                        fill: true
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [
                        {
                            ticks: {
                                callback: tick_cb,
                                min: min,
                                max: max
                            },
                            gridLines: {
                                display: false
                            },
                            scaleLabel: {
                                display: true,
                                labelString: y_label
                            }
                        }
                    ],
                    xAxes: [
                        {
                            ticks: {
                                callback: format_time
                            }
                        }
                    ]
                }
            }
        };
        // create context
        let ctx = $(canvas_id)[0].getContext('2d');
        // return chart obj
        return new Chart(ctx, chart_config);
    }

    $(document).ready(function () {
        let temperature_chart = _robot_health_create_plot(
            "#_robot_temp_canvas",
            _DATA_TEMPERATURE,
            'CPU temperature',
            'Temperature (°C)',
            (v) => v.toFixed(1) + ' °C',
            window.chartColors.red, 20, 80
        );
        let fcpu_chart = _robot_health_create_plot(
            "#_robot_fcpu_canvas",
            _DATA_CPU_FREQUENCY,
            'CPU Frequency',
            'Clock Frequency (GHz)',
            (v) => v.toFixed(1) + ' GHz',
            window.chartColors.green, 0, 2.0
        );
        let pcpu_chart = _robot_health_create_plot(
            "#_robot_pcpu_canvas",
            _DATA_CPU_USAGE,
            'CPU Usage',
            'Usage (%)',
            (v) => v.toFixed(1) + '%',
            window.chartColors.blue, 0.0, 100.0
        );
        let pmem_chart = _robot_health_create_plot(
            "#_robot_pmem_canvas",
            _DATA_RAM_USAGE,
            'RAM Usage',
            'Usage (%)',
            (v) => v.toFixed(1) + '%',
            window.chartColors.blue, 0.0, 100.0
        );
        let pswap_chart = _robot_health_create_plot(
            "#_robot_pswap_canvas",
            _DATA_SWAP_USAGE,
            'Swap Usage',
            'Usage (%)',
            (v) => v.toFixed(1) + '%',
            window.chartColors.blue, 0.0, 100.0
        );
        let pgpu_chart = _robot_health_create_plot(
            "#_robot_pgpu_canvas",
            _DATA_GPU_USAGE,
            'GPU Usage',
            'Usage (%)',
            (v) => v.toFixed(1) + '%',
            window.chartColors.blue, 0.0, 100.0
        );
        let tgpu_chart = _robot_health_create_plot(
            "#_robot_tgpu_canvas",
            _DATA_GPU_TEMP,
            'GPU temperature',
            'Temperature (°C)',
            (v) => v.toFixed(1) + ' °C',
            window.chartColors.red, 20, 80
        );
        // keep updating the plot
        setInterval(function () {
            let url = get_api_url("health");
            callExternalAPI(url, 'GET', 'text', false, false, function (data) {
                data = JSON.parse(data);
                // cut the time horizon to `_HISTORY_HORIZON_LEN` points
                temperature_chart.config.data.datasets[0].data.shift();
                fcpu_chart.config.data.datasets[0].data.shift();
                pcpu_chart.config.data.datasets[0].data.shift();
                pmem_chart.config.data.datasets[0].data.shift();
                pswap_chart.config.data.datasets[0].data.shift();
                pgpu_chart.config.data.datasets[0].data.shift();
                tgpu_chart.config.data.datasets[0].data.shift();
                // add new Y
                temperature_chart.config.data.datasets[0].data.push(data.temperature);
                fcpu_chart.config.data.datasets[0].data.push(data.cpu.frequency.current / (10 ** 9));
                pcpu_chart.config.data.datasets[0].data.push(data.cpu.percentage);
                pmem_chart.config.data.datasets[0].data.push(data.memory.percentage);
                pswap_chart.config.data.datasets[0].data.push(data.swap.percentage);
                pgpu_chart.config.data.datasets[0].data.push(data.gpu.percentage);
                tgpu_chart.config.data.datasets[0].data.push(data.gpu.temperature);
                // refresh chart
                temperature_chart.update();
                fcpu_chart.update();
                pcpu_chart.update();
                pmem_chart.update();
                pswap_chart.update();
                pgpu_chart.update();
                tgpu_chart.update();
            }, true, true);
        }, <?php echo 1000 / $update_hz ?>);
    });

</script>
