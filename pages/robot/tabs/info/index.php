<?php
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_name = Duckiebot::getDuckiebotName();
$dbot_hostname = Duckiebot::getDuckiebotHostname();
$update_hz = 0.5;

$image_template = Core::getImageURL('robots/thumbnails/{0}_all.jpg', 'duckietown');
?>

<style type="text/css">
    .square-canvas {
        width: 100% !important;
        max-width: 220px;
        height: auto !important;
    }
    
    .square-canvas-title {
        margin-bottom: 4px;
    }

    .robot-thumbnail-container {
        height: 50%;
        width: 50%;
        position: relative;
        background: white;
    }

    .robot-thumbnail-container:after {
        content: "";
        display: block;
        padding-bottom: 100%;
    }

    .robot-thumbnail-container img {
        width: auto;
        height: auto;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: auto;
    }

    .robot-info-container dt{
        width: 100px;
    }

    .robot-info-container dd{
        margin-left: 120px;
    }

    .robot-info-container dd img{
        height: 20px
    }
    
    .robot-info-separator hr{
        margin-top: 0;
    }
    
    #_robot_battery_details {
        float: right;
        font-size: 12pt;
    }
</style>


<div class="row">
    <div class="col-md-12 robot-info-container">
        <dl class="dl-horizontal col-md-4">
            <dt>Name</dt>
            <dd>
                <?php echo $dbot_name ?>
            </dd>
            <dt>Type</dt>
            <dd id="robot_type">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
            </dd>
        </dl>
        <dl class="dl-horizontal col-md-4">
            <dt>Configuration</dt>
            <dd id="robot_configuration">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
            </dd>
            <dt>Firmware</dt>
            <dd id="firmware_info">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
            </dd>
        </dl>
        <dl class="dl-horizontal col-md-4">
            <dt>Board</dt>
            <dd id="hardware_board">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
            </dd>
            <dt>Model</dt>
            <dd id="hardware_model">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
            </dd>
        </dl>
    </div>
    
    <div class="col-md-12 text-center robot-info-separator">
        <hr>
    </div>
    
    <div class="col-md-6 robot-thumbnail-container text-center">
        <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
    </div>

    <div class="col-md-3">
        <h4 class="square-canvas-title">
            <i class="fa fa-thermometer-three-quarters" aria-hidden="true"></i>&nbsp;
            Temperature
        </h4>
        <canvas id="_robot_temp_canvas" class="square-canvas"></canvas>
    </div>
    <div class="col-md-3">
        <h4 class="square-canvas-title">
            <i class="fa fa-hdd-o" aria-hidden="true"></i>&nbsp;
            Disk
        </h4>
        <canvas id="_robot_disk_canvas" class="square-canvas"></canvas>
    </div>

    <div class="col-md-6">&nbsp;</div>

    <div class="col-md-3">
        <h4 class="square-canvas-title">
            <i class="fa fa-server" aria-hidden="true"></i>&nbsp;
            CPU
        </h4>
        <canvas id="_robot_pcpu_canvas" class="square-canvas"></canvas>
    </div>
    <div class="col-md-3">
        <h4 class="square-canvas-title">
            <i class="fa fa-microchip" aria-hidden="true"></i>&nbsp;
            RAM
        </h4>
        <canvas id="_robot_ram_canvas" class="square-canvas"></canvas>
    </div>

    <div class="col-md-6">&nbsp;</div>

    <div class="col-md-3">
        <h4 class="square-canvas-title">
            <i class="fa fa-clock-o" aria-hidden="true"></i>&nbsp;
            Frequency
        </h4>
        <canvas id="_robot_fcpu_canvas" class="square-canvas"></canvas>
    </div>
    <div class="col-md-3">
        <h4 class="square-canvas-title">
            <i class="fa fa-battery-three-quarters" aria-hidden="true"></i>&nbsp;
            Battery
            <span id="_robot_battery_details"></span>
        </h4>
        <canvas id="_robot_battery_canvas" class="square-canvas"></canvas>
    </div>
</div>

<div class="col-md-12 text-center">
    <hr>
</div>

<div class="row robot-health-bits-container" style="margin-top: 10px">
    <div class="col-md-6">
        <h4>Status History</h4>
    </div>
    <div class="col-md-6 text-right">
        <h4>Current Status</h4>
    </div>

    <div class="col-md-2 text-center">
        <h4>
            <span class="label label-default" id="under-voltage-occurred">
                Under-Voltage
            </span>
        </h4>
    </div>
    <div class="col-md-2 text-center">
        <h4>
            <span class="label label-default" id="freq-capped-occurred">
                Frequency Capped
            </span>
        </h4>
    </div>
    <div class="col-md-2 text-center" style="border-right: 1px solid grey">
        <h4>
            <span class="label label-default" id="throttling-occurred">
                Throttling
            </span>
        </h4>
    </div>

    <div class="col-md-2 text-center">
        <h4>
            <span class="label label-default" id="under-voltage-now">
                Under-Voltage
            </span>
        </h4>
    </div>
    <div class="col-md-2 text-center">
        <h4>
            <span class="label label-default" id="freq-capped-now">
                Frequency Capped
            </span>
        </h4>
    </div>
    <div class="col-md-2 text-center">
        <h4>
            <span class="label label-default" id="throttling-now">
                Throttling
            </span>
        </h4>
    </div>
</div>


<script type="text/javascript">
    
    let api_url = "http://<?php echo $dbot_hostname ?>/{api}/{resource}";
    let MAX_CLOCK_FREQ = 2.0;

    function _robot_info_create_plot(canvas_id, labels, colors, tooltip_cb){
        let chart_config = {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [
                    {
                        data: [100.0, 0.0],
                        backgroundColor: [
                            Chart.helpers.color(colors[0]).alpha(0.8).rgbString(),
                            Chart.helpers.color(colors[1]).alpha(0.8).rgbString()
                        ],
                        hoverBackgroundColor: [
                            Chart.helpers.color(colors[0]).rgbString(),
                            Chart.helpers.color(colors[1]).rgbString()
                        ],
                        borderWidth: 1
                    }
                ]
            },
            options: {
                legend: {
                    position: 'left'
                },
                tooltips: {
                    callbacks: {
                        label: tooltip_cb
                    }
                },
                elements: {
                    center: {
                        text: '',
                        fontStyle: 'Helvetica',
                        sidePadding: 15
                    }
                }
            }
        };
        // create context
        let ctx = $(canvas_id)[0].getContext('2d');
        // return chart obj
        return new Chart(ctx, chart_config);
    }

    function update_charts(temperature_chart, disk_chart, pcpu_chart, ram_chart, fcpu_chart, batt_chart){
        let url = api_url.format({api:"health", resource:""});
        callExternalAPI(url, 'GET', 'text', false, false, function(data){
            data = JSON.parse(data);
            // update temperature
            temperature_chart.config.data.datasets[0].data[0] = 100.0 - data.temperature;
            temperature_chart.config.data.datasets[0].data[1] = data.temperature;
            temperature_chart.config.options.elements.center.text = data.temperature.toFixed(0) +
                ' \'C';
            // update disk
            disk_chart.config.data.datasets[0].data[0] = 100.0 - data.disk.percentage;
            disk_chart.config.data.datasets[0].data[1] = data.disk.percentage;
            disk_chart.config.options.elements.center.text = data.disk.percentage.toFixed(1) + '%';
            // update pCPU
            pcpu_chart.config.data.datasets[0].data[0] = 100.0 - data.cpu.percentage;
            pcpu_chart.config.data.datasets[0].data[1] = data.cpu.percentage;
            pcpu_chart.config.options.elements.center.text = data.cpu.percentage.toFixed(1) + '%';
            // update RAM
            ram_chart.config.data.datasets[0].data[0] = 100.0 - data.memory.percentage;
            ram_chart.config.data.datasets[0].data[1] = data.memory.percentage;
            ram_chart.config.options.elements.center.text = data.memory.percentage.toFixed(1) + '%';
            // update fCPU
            MAX_CLOCK_FREQ = data.cpu.frequency.max / 10 ** 9;
            let fcpu = (data.cpu.frequency.current / (10 ** 9)).toFixed(1);
            fcpu_chart.config.data.datasets[0].data[0] = Math.max(MAX_CLOCK_FREQ - fcpu, 0).toFixed(1);
            fcpu_chart.config.data.datasets[0].data[1] = fcpu;
            fcpu_chart.config.options.elements.center.text = fcpu + 'GHz';
            // update battery
            let battery_details = $('#_robot_battery_details');
            if (data.battery.percentage !== 'ND') {
                batt_chart.config.data.datasets[0].data[0] = 100.0 - data.battery.percentage;
                batt_chart.config.data.datasets[0].data[1] = data.battery.percentage;
                batt_chart.config.options.elements.center.text = data.battery.percentage.toFixed(1) + '%';
                if (data.battery.input_voltage > 2.5 && data.battery.current > 0) {
                    // charging
                    battery_details.html('<i class="fa fa-plug" aria-hidden="true" title="Battery charging"></i>');
                } else {
                    // discharging
                    battery_details.html(humanTime(data.battery.time_to_empty, true, 'm') + ' left');
                }
            } else {
                battery_details.html("");
                batt_chart.config.data.datasets[0].data[0] = 100;
                batt_chart.config.data.datasets[0].data[1] = 0;
                batt_chart.config.options.elements.center.text = '  ND  ';
            }
            // refresh chart
            temperature_chart.update();
            disk_chart.update();
            pcpu_chart.update();
            ram_chart.update();
            fcpu_chart.update();
            batt_chart.update();
            // update hardware info
            $('.robot-info-container #hardware_board').html(data.hardware.board);
            $('.robot-info-container #hardware_model').html(data.hardware.model);
            // update firmware info
            let firmware = '{month}/{day}/{year}'.format(data.software.date);
            firmware = '{0} ({1})'.format(firmware, data.software.version.substr(0, 7));
            $('.robot-info-container #firmware_info').html(firmware);
            // update health bits
            for (let [key, value] of Object.entries(data.throttling)) {
                $('.robot-health-bits-container #'+key).removeClass('label-default ' +
                    'label-warning label-success');
                $('.robot-health-bits-container #'+key).addClass(
                    value? (key.endsWith('-occurred')? 'label-warning' : 'label-danger') : 'label-success');
            }
        }, true, true);
    }

    $(document).ready(function () {
        // get robot type
        let url = api_url.format({api:"files", resource:"data/config/robot_type"});
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_type = 'unknown';
            try {
                robot_type = data.split('\n')[0].trim();
            } catch (e) {}
            $('.robot-info-container #robot_type').html(robot_type.capitalize());
        }, true, true);
        // get robot configuration
        url = api_url.format({api:"files", resource:"data/config/robot_configuration"});
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_configuration = 'unknown';
            try {
                robot_configuration = data.split('\n')[0].trim();
            } catch (e) {}
            let template = '<?php echo $image_template ?>';
            $('.robot-thumbnail-container img').attr('src', template.format(robot_configuration));
            $('.robot-info-container #robot_configuration').html(robot_configuration.capitalize());
        }, true, true);
        // create health plots
        let temperature_chart = _robot_info_create_plot(
            "#_robot_temp_canvas",
            ['Cold', 'Hot'],
            [window.chartColors.blue, window.chartColors.red],
            function(t, d) {
                let msg = d.datasets[t.datasetIndex].data[t.index].toFixed(1) + ' \'C';
                if (t.index === 0)
                    msg += ' before meltdown!';
                else
                    msg = 'Temperature: ' + msg;
                return msg;
            }
        );
        let disk_chart = _robot_info_create_plot(
            "#_robot_disk_canvas",
            ['Free', 'Used'],
            [window.chartColors.blue, window.chartColors.red],
            (t, d) => d.labels[t.index] + ': ' +
                d.datasets[t.datasetIndex].data[t.index].toFixed(1)+'%'
        );
        let pcpu_chart = _robot_info_create_plot(
            "#_robot_pcpu_canvas",
            ['Free', 'Used'],
            [window.chartColors.green, window.chartColors.red],
            (t, d) => d.labels[t.index] + ': ' +
                d.datasets[t.datasetIndex].data[t.index].toFixed(1)+'%'
        );
        let ram_chart = _robot_info_create_plot(
            "#_robot_ram_canvas",
            ['Free', 'Used'],
            [window.chartColors.green, window.chartColors.red],
            (t, d) => d.labels[t.index] + ': ' +
                d.datasets[t.datasetIndex].data[t.index].toFixed(1)+'%'
        );
        let fcpu_chart = _robot_info_create_plot(
            "#_robot_fcpu_canvas",
            ['Idle', 'Busy'],
            [window.chartColors.grey, window.chartColors.red],
            (t, d) => d.labels[t.index] + ': ' +
                d.datasets[t.datasetIndex].data[t.index]+'GHz'
        );
        let batt_chart = _robot_info_create_plot(
            "#_robot_battery_canvas",
            ['Empty', 'Full'],
            [window.chartColors.red, window.chartColors.green],
            (t, d) => d.labels[t.index] + ': ' +
                d.datasets[t.datasetIndex].data[t.index].toFixed(1)+'%'
        );
        // keep updating the plot
        update_charts(temperature_chart, disk_chart, pcpu_chart, ram_chart, fcpu_chart, batt_chart);
        setInterval(
            update_charts,
            <?php echo 1000 / $update_hz ?>,
            temperature_chart, disk_chart, pcpu_chart, ram_chart, fcpu_chart, batt_chart
        );
    });

</script>


