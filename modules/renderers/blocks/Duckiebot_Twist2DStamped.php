<?php

use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;

/**
 * Velocity gauge for duckietown_msgs/Twist2DStamped.
 *
 * Replaces DuckietownMsgs_Twist2DStamped for Mission Control: the stock
 * renderer uses chartColors.white (undefined in Compose) and takes
 * Math.sign(Math.abs(v)) so signed dials never swing negative.
 */
class Duckiebot_Twist2DStamped extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "dashboard"
    ];

    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
        "topic" => [
            "name" => "ROS Topic",
            "type" => "text",
            "mandatory" => True
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
            "mandatory" => True
        ],
        "allow_negative" => [
            "name" => "Allow negative values",
            "type" => "boolean",
            "mandatory" => True,
            "default" => True
        ],
        "unit" => [
            "name" => "Unit",
            "type" => "text",
            "mandatory" => True
        ],
        "field" => [
            "name" => "Message field to show",
            "type" => "text",
            "mandatory" => True
        ]
    ];

    protected static function render($id, &$args) {
        $allow_negative = !empty($args['allow_negative']);
        $max_value = floatval($args['max_value']);
        $min_label = $allow_negative
            ? sprintf("%.1f", -1 * $max_value)
            : "0.0";
        $max_label = sprintf("%.1f", $max_value);
        $ros_hostname = $args['ros_hostname'] ?? null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ROS::connect($ros_hostname);
        $uid = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
        ?>
        <canvas class="resizable" style="width:100%; padding:6px; padding-bottom:30px"></canvas>

        <table style="width:100%; height:10px; position:relative; top:-30px">
            <tr>
                <td style="width:35%" class="text-center">
                    <?php echo htmlspecialchars($min_label) ?>
                </td>
                <td style="width:30%" class="text-center">
          <span style="position:relative; top:-20px">
            <?php echo htmlspecialchars($args['unit']) ?>
          </span>
                </td>
                <td style="width:35%" class="text-center">
                    <?php echo htmlspecialchars($max_label) ?>
                </td>
            </tr>
        </table>

        <script type="text/javascript">
        (function () {
            var blockId = <?php echo json_encode((string) $id) ?>;
            var rosHost = <?php echo json_encode($ros_hostname) ?>;
            var topicName = <?php echo json_encode($args['topic']) ?>;
            var fieldName = <?php echo json_encode($args['field']) ?>;
            var maxSpeed = <?php echo json_encode($max_value) ?>;
            var allowNegative = <?php echo $allow_negative ? 'true' : 'false' ?>;
            var throttleMs = <?php echo json_encode(intval(1000 / max(1, intval($args['fps'])))) ?>;
            var boundKey = 'twist2d_' + <?php echo json_encode($uid) ?>;

            function emptyColor() {
                var styles = window.getComputedStyle(document.documentElement);
                var track = (styles.getPropertyValue('--r-track') || '').trim();
                if (track) return track;
                if (window.chartColors && window.chartColors.white) {
                    return window.chartColors.white;
                }
                return '#e8ebf1';
            }

            function fillColor() {
                var styles = window.getComputedStyle(document.documentElement);
                var fill = (styles.getPropertyValue('--r-fill') || '').trim();
                if (fill) return fill;
                if (window.chartColors && window.chartColors.green) {
                    return window.chartColors.green;
                }
                return 'rgb(75, 192, 192)';
            }

            function getRos() {
                if (!window.ros) return null;
                if (rosHost && window.ros[rosHost]) return window.ros[rosHost];
                if (window.ros['local']) return window.ros['local'];
                if (window.ros.socket) return window.ros;
                return null;
            }

            function init() {
                if (!window.mission_control_page_blocks_data) {
                    window.mission_control_page_blocks_data = {};
                }
                if (window.mission_control_page_blocks_data[boundKey]) {
                    return;
                }
                var ros = getRos();
                if (!ros || typeof ROSLIB === 'undefined' || typeof Chart === 'undefined') {
                    return;
                }
                var canvas = $("#" + blockId + " .block_renderer_container canvas")[0];
                if (!canvas) return;

                var blank = emptyColor();
                var fill = fillColor();
                var chart_config = {
                    type: 'pie',
                    data: {
                        datasets: [{
                            data: allowNegative ? [0.5, 0.0, 0.0, 0.5] : [0.0, 0.0, 0.0, 1.0],
                            backgroundColor: [blank, fill, fill, blank]
                        }]
                    },
                    options: {
                        cutoutPercentage: 50,
                        rotation: -Math.PI,
                        circumference: Math.PI,
                        tooltips: { enabled: false },
                        maintainAspectRatio: false
                    }
                };

                var chart = new Chart(canvas.getContext('2d'), chart_config);
                window.mission_control_page_blocks_data[blockId] = {
                    chart: chart,
                    config: chart_config,
                    allow_negative: allowNegative
                };
                window.mission_control_page_blocks_data[boundKey] = true;

                var subscriber = new ROSLIB.Topic({
                    ros: ros,
                    name: topicName,
                    messageType: 'duckietown_msgs/Twist2DStamped',
                    queue_size: 1,
                    throttle_rate: throttleMs
                });

                subscriber.subscribe(function (message) {
                    var raw = Number(message[fieldName]);
                    if (!isFinite(raw)) raw = 0;
                    var speed_sign = Math.sign(raw);
                    var cur_speed = Math.abs(raw);
                    var speed_norm = Math.min(cur_speed, maxSpeed) / maxSpeed;
                    var config = chart_config;
                    if (allowNegative) {
                        // Chart segments: [left empty, left fill, right fill, right empty]
                        // Labels are -max … +max, so negative → left, positive → right.
                        if (speed_sign < 0) {
                            config.data.datasets[0].data[0] = 0.5 - speed_norm / 2.0;
                            config.data.datasets[0].data[1] = speed_norm / 2.0;
                            config.data.datasets[0].data[2] = 0.0;
                            config.data.datasets[0].data[3] = 0.5;
                        } else {
                            config.data.datasets[0].data[0] = 0.5;
                            config.data.datasets[0].data[1] = 0.0;
                            config.data.datasets[0].data[2] = speed_norm / 2.0;
                            config.data.datasets[0].data[3] = 0.5 - speed_norm / 2.0;
                        }
                    } else {
                        config.data.datasets[0].data[0] = 0.0;
                        config.data.datasets[0].data[1] = speed_norm;
                        config.data.datasets[0].data[2] = 0.0;
                        config.data.datasets[0].data[3] = 1.0 - speed_norm;
                    }
                    chart.update();
                });
            }

            $(document).on(<?php echo json_encode($connected_evt) ?>, init);
            setTimeout(init, 100);
            setTimeout(init, 800);
        })();
        </script>
        <?php
    }
}
?>
