<?php

use \system\classes\BlockRenderer;
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;


/**
 * Wheel commands executed by the wheels driver (PWM duty cycle, -1..1), read from the robot's
 * DTPS switchboard (no ROS needed).
 */
class Duckiebot_DTPS_WheelsPWM extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "exchange"
    ];

    static protected $ARGUMENTS = [
        "topic" => [
            "name" => "DTPS topic",
            "type" => "text",
            "mandatory" => True,
            "default" => "actuator/wheels/base/pwm_executed"
        ],
        "fps" => [
            "name" => "Update frequency (Hz)",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 5
        ],
        "max_value" => [
            "name" => "Maximum value",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 1.0
        ]
    ];

    protected static function render($id, &$args) {
        $robot_name = Duckiebot::getDuckiebotName();
        ?>
        <canvas class="resizable" style="width:100%; height:95%; padding:6px 16px"></canvas>
        <script src="<?php echo Core::getJSscriptURL('cbor.min.js', 'duckietown_duckiebot'); ?>"></script>
        <script src="<?php echo Core::getJSscriptURL('dtps_client.js', 'duckietown_duckiebot'); ?>"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                let time_horizon = 20;
                let color = Chart.helpers.color;
                let chart_config = {
                    type: 'line',
                    data: {
                        labels: range(time_horizon - 1, 0, 1),
                        datasets: [{
                            label: 'Left motor',
                            backgroundColor: color(window.chartColors.red).alpha(0.5).rgbString(),
                            borderColor: window.chartColors.red,
                            fill: true,
                            data: new Array(time_horizon).fill(0)
                        }, {
                            label: 'Right motor',
                            backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
                            borderColor: window.chartColors.blue,
                            fill: true,
                            data: new Array(time_horizon).fill(0)
                        }]
                    },
                    options: {
                        scales: {
                            xAxes: [{scaleLabel: {display: false}}],
                            yAxes: [{
                                scaleLabel: {display: true, labelString: 'PWM duty cycle'},
                                ticks: {
                                    suggestedMin: -<?php echo floatval($args['max_value']) ?>,
                                    suggestedMax: <?php echo floatval($args['max_value']) ?>
                                }
                            }]
                        },
                        tooltips: {enabled: false},
                        maintainAspectRatio: false
                    }
                };
                let ctx = $("#<?php echo $id ?> .block_renderer_container canvas")[0].getContext('2d');
                let chart = new Chart(ctx, chart_config);
                window.mission_control_page_blocks_data = window.mission_control_page_blocks_data || {};
                window.mission_control_page_blocks_data['<?php echo $id ?>'] = {chart: chart, config: chart_config};
                // the latest command, sampled at the block's rate (the driver only publishes on change)
                let latest = {left: 0, right: 0};
                DTPS.client('<?php echo $robot_name ?>').subscribe('<?php echo $args['topic'] ?>', function (message) {
                    latest = {left: message.left, right: message.right};
                });
                setInterval(function () {
                    chart_config.data.datasets[0].data.shift();
                    chart_config.data.datasets[1].data.shift();
                    chart_config.data.datasets[0].data.push(latest.left);
                    chart_config.data.datasets[1].data.push(latest.right);
                    chart.update();
                }, 1000.0 / <?php echo floatval($args['fps']) ?>);
            });
        </script>
        <?php
    }//render

}//Duckiebot_DTPS_WheelsPWM
?>
