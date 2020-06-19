<?php
use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_hostname = Duckiebot::getDuckiebotHostname();
$update_hz = 1;
?>

<br/>
<h4>Temperature</h4>
<canvas id="_robot_temp_canvas" style="width:100%; height:240px"></canvas>


<br/>
<h4>CPU Frequency</h4>
<canvas id="_robot_fcpu_canvas" style="width:100%; height:240px"></canvas>


<br/>
<h4>CPU Usage</h4>
<canvas id="_robot_pcpu_canvas" style="width:100%; height:240px"></canvas>


<br/>
<h4>RAM Usage</h4>
<canvas id="_robot_pmem_canvas" style="width:100%; height:240px"></canvas>


<br/>
<h4>Swap Usage</h4>
<canvas id="_robot_pswap_canvas" style="width:100%; height:240px"></canvas>


<br/>
<h4>CPU Voltage</h4>
<canvas id="_robot_cpu_voltage_canvas" style="width:100%; height:240px"></canvas>


<br/>
<h4>RAM Voltage</h4>
<canvas id="_robot_ram_voltage_canvas" style="width:100%; height:240px"></canvas>


<script type="text/javascript">
    
    let api_url = "http://<?php echo $dbot_hostname ?>/{api}/{resource}";
    
    let _HISTORY_HORIZON_LEN = 60;
    let _DATA_TEMPERATURE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_CPU_FREQUENCY = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_CPU_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_RAM_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_SWAP_USAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_CPU_VOLTAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);
    let _DATA_RAM_VOLTAGE = new Array(_HISTORY_HORIZON_LEN).fill(0);

    function format_time(secs){
        let parts = [];
        if (secs > 59)
            parts.push('{0}m'.format(Math.floor(secs / 60)));
        if (secs % 60 !== 0 || secs === 0)
            parts.push('{0}s'.format(secs % 60));
        return parts.join(' ');
    }

    function _robot_health_create_plot(canvas_id, data, title, y_label, tick_cb, color, min, max){
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
            'Temperature',
            'Temperature (\'C)',
            (v) => v.toFixed(1)+' \'C',
            window.chartColors.red, 40, 90
        );
        let fcpu_chart = _robot_health_create_plot(
            "#_robot_fcpu_canvas",
            _DATA_CPU_FREQUENCY,
            'CPU Frequency',
            'Clock Frequency (GHz)',
            (v) => v.toFixed(1)+' GHz',
            window.chartColors.green, 0, 2.0
        );
        let pcpu_chart = _robot_health_create_plot(
            "#_robot_pcpu_canvas",
            _DATA_CPU_USAGE,
            'CPU Usage',
            'Usage (%)',
            (v) => v.toFixed(1)+'%',
            window.chartColors.blue, 0.0, 100.0
        );
        let pmem_chart = _robot_health_create_plot(
            "#_robot_pmem_canvas",
            _DATA_RAM_USAGE,
            'RAM Usage',
            'Usage (%)',
            (v) => v.toFixed(1)+'%',
            window.chartColors.blue, 0.0, 100.0
        );
        let pswap_chart = _robot_health_create_plot(
            "#_robot_pswap_canvas",
            _DATA_SWAP_USAGE,
            'Swap Usage',
            'Usage (%)',
            (v) => v.toFixed(1)+'%',
            window.chartColors.blue, 0.0, 100.0
        );
        let cpu_voltage_chart = _robot_health_create_plot(
            "#_robot_cpu_voltage_canvas",
            _DATA_CPU_VOLTAGE,
            'CPU Voltage',
            'Voltage (V)',
            (v) => v.toFixed(1)+' V',
            window.chartColors.yellow, 0.6, 1.4
        );
        let ram_voltage_chart = _robot_health_create_plot(
            "#_robot_ram_voltage_canvas",
            _DATA_RAM_VOLTAGE,
            'RAM Voltage',
            'Voltage (V)',
            (v) => v.toFixed(1)+' V',
            window.chartColors.yellow, 0.6, 1.4
        );
        // keep updating the plot
        setInterval(function(){
            let url = api_url.format({api:"health", resource:""});
            callExternalAPI(url, 'GET', 'text', false, false, function(data){
                data = JSON.parse(data);
                // cut the time horizon to `_HISTORY_HORIZON_LEN` points
                temperature_chart.config.data.datasets[0].data.shift();
                fcpu_chart.config.data.datasets[0].data.shift();
                pcpu_chart.config.data.datasets[0].data.shift();
                pmem_chart.config.data.datasets[0].data.shift();
                pswap_chart.config.data.datasets[0].data.shift();
                cpu_voltage_chart.config.data.datasets[0].data.shift();
                ram_voltage_chart.config.data.datasets[0].data.shift();
                // add new Y
                temperature_chart.config.data.datasets[0].data.push(data.temp.split('\'')[0]);
                fcpu_chart.config.data.datasets[0].data.push(parseFloat(data.frequency) / (10 ** 9));
                pcpu_chart.config.data.datasets[0].data.push(data.pcpu);
                pmem_chart.config.data.datasets[0].data.push(data.mem.pmem);
                pswap_chart.config.data.datasets[0].data.push(data.swap.pswap);
                cpu_voltage_chart.config.data.datasets[0].data.push(data.volts.core.split('V')[0]);
                ram_voltage_chart.config.data.datasets[0].data.push(data.volts.sdram_i.split('V')[0]);
                // refresh chart
                temperature_chart.update();
                fcpu_chart.update();
                pcpu_chart.update();
                pmem_chart.update();
                pswap_chart.update();
                cpu_voltage_chart.update();
                ram_voltage_chart.update();
            }, true, true);
        }, <?php echo 1000 / $update_hz ?>);
    });

</script>
