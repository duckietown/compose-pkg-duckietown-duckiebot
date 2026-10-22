<?php

use \system\classes\BlockRenderer;
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;


/**
 * Robot speed (linear or angular), estimated from the wheel encoders read from the robot's
 * DTPS switchboard and the robot's calibration (no ROS needed).
 *
 * The encoders count ticks without direction: the direction of each wheel is taken from the sign
 * of the last wheel command executed by the wheels driver.
 */
class Duckiebot_DTPS_Speed extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "tachometer"
    ];

    static protected $ARGUMENTS = [
        "field" => [
            "name" => "Speed",
            "type" => "enum",
            "enum_values" => ["v", "omega"],
            "mandatory" => True,
            "default" => "v"
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
            "default" => 0.4
        ],
        "unit" => [
            "name" => "Unit",
            "type" => "text",
            "mandatory" => True,
            "default" => "m/s"
        ],
        "allow_negative" => [
            "name" => "Allow negative values",
            "type" => "boolean",
            "mandatory" => True,
            "default" => false
        ]
    ];

    protected static function render($id, &$args) {
        $robot_name = Duckiebot::getDuckiebotName();
        ?>
        <canvas class="resizable" style="width:100%; padding:6px; padding-bottom:30px"></canvas>
        <table style="width:100%; height:10px; position:relative; top:-30px">
            <tr>
                <td style="width:35%" class="text-center">0.0</td>
                <td style="width:30%" class="text-center">
                    <span style="position:relative; top:-20px"><?php echo $args['unit'] ?></span>
                </td>
                <td style="width:35%" class="text-center"><?php echo sprintf("%.1f", $args['max_value']) ?></td>
            </tr>
        </table>
        <script src="<?php echo Core::getJSscriptURL('cbor.min.js', 'duckietown_duckiebot'); ?>"></script>
        <script src="<?php echo Core::getJSscriptURL('dtps_client.js', 'duckietown_duckiebot'); ?>"></script>
        <script src="<?php echo Core::getJSscriptURL('robot_calibration.js', 'duckietown_duckiebot'); ?>"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                let robot_name = '<?php echo $robot_name ?>';
                let field = '<?php echo $args['field'] ?>';
                let max_value = <?php echo floatval($args['max_value']) ?>;
                let allow_negative = <?php echo $args['allow_negative'] ? 'true' : 'false' ?>;
                let chart_config = {
                    type: 'pie',
                    data: {
                        datasets: [{
                            data: [0.5, 0.0, 0.0, 0.5],
                            backgroundColor: [
                                window.chartColors.white,
                                window.chartColors.green,
                                window.chartColors.green,
                                window.chartColors.white
                            ]
                        }]
                    },
                    options: {
                        cutoutPercentage: 50,
                        rotation: -Math.PI,
                        circumference: Math.PI,
                        tooltips: {enabled: false},
                        maintainAspectRatio: false
                    }
                };
                let ctx = $("#<?php echo $id ?> .block_renderer_container canvas")[0].getContext('2d');
                let chart = new Chart(ctx, chart_config);
                window.mission_control_page_blocks_data = window.mission_control_page_blocks_data || {};
                window.mission_control_page_blocks_data['<?php echo $id ?>'] = {chart: chart, config: chart_config};

                // wheel speeds (m/s) from the encoders
                let calibration = RobotCalibration.DEFAULTS;
                RobotCalibration.load(robot_name).then((c) => calibration = c);
                let dtps = DTPS.client(robot_name);
                // Encoder messages reach the browser in bursts (several within the same millisecond), so
                // the speed is measured over a time window, using the encoder's own timestamps.
                const WINDOW_SECS = 0.5;
                let wheels = {
                    left: {samples: [], arrival: 0, direction: 1},
                    right: {samples: [], arrival: 0, direction: 1},
                };
                ['left', 'right'].forEach(function (side) {
                    dtps.subscribe('sensor/wheel_encoder/' + side + '/ticks', function (message) {
                        let wheel = wheels[side];
                        let arrival = performance.now() / 1000.0;
                        let stamp = (message.header && message.header.timestamp > 0) ? message.header.timestamp : arrival;
                        let samples = wheel.samples;
                        if (samples.length > 0 && stamp < samples[samples.length - 1][0]) {
                            // the clock went backwards (e.g., the driver restarted): start over
                            samples.length = 0;
                        }
                        samples.push([stamp, message.data]);
                        while (samples.length > 2 && samples[1][0] <= stamp - WINDOW_SECS) samples.shift();
                        wheel.arrival = arrival;
                    });
                });
                // signed wheel speed (m/s); the encoders only count ticks, the direction comes from the PWM
                function wheel_speed(wheel) {
                    let samples = wheel.samples;
                    if (samples.length < 2) return 0;
                    let first = samples[0], last = samples[samples.length - 1];
                    let elapsed = last[0] - first[0];
                    if (elapsed < 0.05) return 0;
                    let angle = 2 * Math.PI * Math.abs(last[1] - first[1]) / calibration.resolution;
                    return wheel.direction * calibration.radius * angle / elapsed;
                }
                dtps.subscribe('actuator/wheels/base/pwm_executed', function (message) {
                    if (message.left !== 0) wheels.left.direction = Math.sign(message.left);
                    if (message.right !== 0) wheels.right.direction = Math.sign(message.right);
                });

                setInterval(function () {
                    let now = performance.now() / 1000.0;
                    let speed = {};
                    for (let side of ['left', 'right']) {
                        // no encoder messages for a while: treat the wheel as still
                        speed[side] = (now - wheels[side].arrival > 0.5) ? 0 : wheel_speed(wheels[side]);
                    }
                    let value = (field === 'omega') ?
                        (speed.right - speed.left) / calibration.baseline :
                        (speed.left + speed.right) / 2.0;
                    let norm = Math.min(Math.abs(value), max_value) / max_value;
                    let data = chart_config.data.datasets[0].data;
                    if (allow_negative) {
                        if (value < 0) {
                            data[0] = 0.5; data[1] = 0.0; data[2] = norm / 2.0; data[3] = 0.5 - norm / 2.0;
                        } else {
                            data[0] = 0.5 - norm / 2.0; data[1] = norm / 2.0; data[2] = 0.0; data[3] = 0.5;
                        }
                    } else {
                        data[0] = 0.0; data[1] = norm; data[2] = 0.0; data[3] = 1.0 - norm;
                    }
                    chart.update();
                }, 1000.0 / <?php echo floatval($args['fps']) ?>);
            });
        </script>
        <?php
    }//render

}//Duckiebot_DTPS_Speed
?>
