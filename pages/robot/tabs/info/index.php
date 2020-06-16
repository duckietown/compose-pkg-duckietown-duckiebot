<?php
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

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

    .robot-thumbnail-container {
        height: 50%;
        width: 50%;
        position: relative;
        background: white;
        border: 1px solid lightgrey;
    }

    .robot-thumbnail-container:after {
        content: "";
        display: block;
        padding-bottom: 100%;
    }

    .robot-thumbnail-container img {
        /*max-height: 70%;*/
        /*max-width: 70%;*/
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
        width: 80px;
    }

    .robot-info-container dd{
        margin-left: 100px;
    }
</style>


<div class="row">
    <div class="col-md-6 robot-thumbnail-container text-center">
        <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
    </div>
    <div class="col-md-6 robot-info-container">
        <h4>General</h4>
        <dl class="dl-horizontal col-md-6">
            <dt>Name</dt>
            <dd><?php echo $dbot_hostname ?></dd>
            <dt>Board</dt>
            <dd>Raspberry</dd>
            <dt>Model</dt>
            <dd id="hardware">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height: 20px">
            </dd>
        </dl>
        <dl class="dl-horizontal col-md-6">
            <dt>Type</dt>
            <dd id="robot_type">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height: 20px">
            </dd>
            <dt>Model</dt>
            <dd id="robot_configuration">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height: 20px">
            </dd>
            <dt>Memory</dt>
            <dd id="ram">
                <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="" style="height: 20px">
            </dd>
        </dl>
    </div>

    <div class="col-md-6">
        &nbsp;
    </div>

    <div class="col-md-3">
        <h4>Temperature</h4>
        <canvas id="_robot_temp_canvas" class="square-canvas"></canvas>
    </div>
    <div class="col-md-3">
        <h4>Disk</h4>
        <canvas id="_robot_disk_canvas" class="square-canvas"></canvas>
    </div>

    <div class="col-md-6">
        &nbsp;
    </div>

    <div class="col-md-3">
        <h4>CPU</h4>
        <canvas id="_robot_cpu_canvas" class="square-canvas"></canvas>
    </div>
    <div class="col-md-3">
        <h4>RAM</h4>
        <canvas id="_robot_ram_canvas" class="square-canvas"></canvas>
    </div>
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
    <div class="col-md-2 text-center" style="border-right: 1px solid lightgrey">
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

<!--<div class="row">-->
<!--    <div class="col-md-2">.col-md-2</div>-->
<!--    <div class="col-md-2">.col-md-2</div>-->
<!--    <div class="col-md-2">.col-md-2</div>-->
<!--    <div class="col-md-2">.col-md-2</div>-->
<!--    <div class="col-md-2">.col-md-2</div>-->
<!--    <div class="col-md-2">.col-md-2</div>-->
<!--</div>-->







<script type="text/javascript">
    
    let api_url = "http://<?php echo $dbot_hostname ?>/{api}/{resource}";

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

    function update_charts(temperature_chart, disk_chart, cpu_chart, ram_chart){
        let url = api_url.format({api:"health", resource:""});
        callExternalAPI(url, 'GET', 'text', false, false, function(data){
            data = JSON.parse(data);
            // update temperature
            let temperature = parseFloat(data.temp.split('\'')[0]);
            temperature_chart.config.data.datasets[0].data[0] = 100.0 - temperature;
            temperature_chart.config.data.datasets[0].data[1] = temperature;
            temperature_chart.config.options.elements.center.text = temperature.toFixed(0) +
                ' \'C';
            // update temperature
            disk_chart.config.data.datasets[0].data[0] = 100.0 - data.disk.pdisk;
            disk_chart.config.data.datasets[0].data[1] = data.disk.pdisk;
            disk_chart.config.options.elements.center.text = data.disk.pdisk.toFixed(1) + '%';
            // update temperature
            cpu_chart.config.data.datasets[0].data[0] = 100.0 - data.pcpu;
            cpu_chart.config.data.datasets[0].data[1] = data.pcpu;
            cpu_chart.config.options.elements.center.text = data.pcpu.toFixed(1) + '%';
            // update temperature
            ram_chart.config.data.datasets[0].data[0] = 100.0 - data.mem.pmem;
            ram_chart.config.data.datasets[0].data[1] = data.mem.pmem;
            ram_chart.config.options.elements.center.text = data.mem.pmem.toFixed(1) + '%';
            // refresh chart
            temperature_chart.update();
            disk_chart.update();
            cpu_chart.update();
            ram_chart.update();
            // update hardware info
            $('.robot-info-container #hardware').html('Pi ' + data.hardware.Model);
            $('.robot-info-container #ram').html(data.hardware.Memory);
            // update health bits
            for (let [key, value] of Object.entries(data.throttled_humans)) {
                $('.robot-health-bits-container #'+key).removeClass('label-default ' +
                    'label-warning label-success');
                $('.robot-health-bits-container #'+key).addClass(value? 'label-warning' : 'label-success');
            }
        }, true, true);
    }

    $(document).ready(function () {
        // get robot type
        let url = api_url.format({api:"files", resource:"config/robot_type"});
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_type = 'unknown';
            try {
                robot_type = data.split('\n')[0].trim();
            } catch (e) {}
            $('.robot-info-container #robot_type').html(robot_type.capitalize());
        }, true, true);
        // get robot configuration
        url = api_url.format({api:"files", resource:"config/robot_configuration"});
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
        let cpu_chart = _robot_info_create_plot(
            "#_robot_cpu_canvas",
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
        // keep updating the plot
        update_charts(temperature_chart, disk_chart, cpu_chart, ram_chart);
        setInterval(
            update_charts,
            <?php echo 1000 / $update_hz ?>,
            temperature_chart, disk_chart, cpu_chart, ram_chart
        );
    });

</script>


