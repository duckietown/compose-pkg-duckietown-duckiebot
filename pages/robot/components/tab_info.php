<?php
use \system\classes\Core;

//TODO: use the proxy instead
$dbot_hostname = 'watchtower20.local';
$dbot_type = 'watchtower';
$health_api_port = 8085;
$files_api_port = 8082;
$update_hz = 0.5;

$image_template = Core::getImageURL('{0}.jpg', 'duckietown');
?>

<style type="text/css">
    .square-canvas {
        width: 100% !important;
        max-width: 220px;
        height: auto !important;
    }

    .robot-type-image-container {
        height: 50%;
        width: 50%;
        position: relative;
        background: white;
        border: 1px solid lightgrey;
    }

    .robot-type-image-container:after {
        content: "";
        display: block;
        padding-bottom: 100%;
    }

    .robot-type-image-container img {
        max-height: 70%;
        max-width: 70%;
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
    <div class="col-md-6 robot-type-image-container text-center">
        <img
            src="<?php echo Core::getImageURL('loading_blue.gif') ?>"
            alt="">
    </div>
    <div class="col-md-6 robot-info-container">
        <h4>General</h4>
        <dl class="dl-horizontal col-md-6">
            <dt>Name</dt>
            <dd><?php echo $dbot_hostname ?></dd>
            <dt>Board</dt>
            <dd>Raspberry</dd>
            <dt>Model</dt>
            <dd id="hardware">(loading)</dd>
        </dl>
        <dl class="dl-horizontal col-md-6">
            <dt>Type</dt>
            <dd><?php echo ucfirst($dbot_type) ?></dd>
            <dt>Memory</dt>
            <dd id="ram">(loading)</dd>
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
        let url = "http://<?php echo $dbot_hostname ?>:<?php echo $health_api_port ?>";
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
        let url = "http://<?php echo $dbot_hostname ?>:<?php echo $files_api_port
            ?>/config/robot_type";
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_type = 'unknown';
            try {
                robot_type = data.split('\n')[0].trim();
            } catch (e) {}
            let template = '<?php echo $image_template ?>';
            $('.robot-type-image-container img').attr('src', template.format(robot_type));
        }, true, true);
        // create health plots
        let temperature_chart = _robot_info_create_plot(
            "#_robot_temp_canvas",
            ['Cold', 'Hot'],
            [window.chartColors.blue, window.chartColors.red],
            function(t, d) {
                if (t.index === 1)
                    return d.datasets[t.datasetIndex].data[t.index].toFixed(1) + ' ' + '\'C';
                else
                    return ' ';
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


