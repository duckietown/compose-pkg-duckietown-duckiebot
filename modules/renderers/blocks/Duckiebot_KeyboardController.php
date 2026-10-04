<?php
use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;

/**
 * On-page D-pad / WASD teleop for Mission Control.
 *
 * Publishes ONLY sensor_msgs/Joy on /<veh>/joy.
 * joy_mapper -> car_cmd_switch -> kinematics -> wheels + velocity.
 * One input topic avoids pulse/jitter from competing publishers.
 */
class Duckiebot_KeyboardController extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "gamepad"
    ];

    static protected $DEFAULT_SIZE = ['rows' => 3, 'cols' => 8];

    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
        "hz" => [
            "name" => "Command rate (Hz)",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 50
        ]
    ];

    protected static function render($id, &$args) {
        $vehicle = Duckiebot::getDuckiebotName();
        $ros_hostname = isset($args['ros_hostname']) ? $args['ros_hostname'] : null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ROS::connect($ros_hostname);

        $prefix = !empty($vehicle) ? '/' . $vehicle : '';
        $joy_topic = $prefix . '/joy';
        $imu_topic = $prefix . '/imu_node/raw';
        $tof_topic = $prefix . '/front_center_tof_driver_node/range';
        $wheels_topic = $prefix . '/wheels_driver_node/wheels_cmd_executed';
        $estop_topic = $prefix . '/wheels_driver_node/emergency_stop';
        $trim_param = $prefix . '/kinematics_node/trim';
        $trim_update_srv = $prefix . '/kinematics_node/request_parameters_update';

        $hz = isset($args['hz']) ? max(20, intval($args['hz'])) : 50;
        $viewer_url = Duckiebot::getKeyboardControllerUrl();
        $uid = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
        $can_drive = Core::isUserLoggedIn();
        ?>
        <div class="robot-kc" id="robot_kc_<?php echo htmlspecialchars($uid) ?>" tabindex="0"
             data-can-drive="<?php echo $can_drive ? '1' : '0'; ?>">
            <div class="robot-kc-toolbar">
                <label class="robot-kc-enable<?php echo $can_drive ? '' : ' is-locked'; ?>">
                    <input type="checkbox" class="robot-kc-enable-input"
                        <?php echo $can_drive ? '' : 'disabled aria-disabled="true"'; ?> />
                    <span><?php echo $can_drive ? 'Enable drive' : 'Sign in to drive'; ?></span>
                </label>
                <span class="robot-kc-status is-idle">Off</span>
                <span class="robot-kc-cmd" aria-live="polite">axes 0.00 / 0.00</span>
                <a class="robot-btn robot-btn-ghost robot-btn-xs"
                   href="<?php echo htmlspecialchars($viewer_url) ?>"
                   target="_blank"
                   rel="noopener noreferrer">
                    <i class="fa fa-external-link" aria-hidden="true"></i> Full viewer
                </a>
            </div>

            <div class="robot-kc-body">
                <div class="robot-kc-col robot-kc-col-sensors" aria-label="IMU and left wheel">
                    <div class="robot-kc-sensor-card">
                        <div class="robot-kc-sensor-head">
                            <span class="robot-kc-sensor-label">IMU accel</span>
                            <span class="robot-kc-imu-readout">0.00 g</span>
                        </div>
                        <div class="robot-kc-imu" title="IMU linear acceleration (x/y)">
                            <div class="robot-kc-imu-ring"></div>
                            <div class="robot-kc-imu-dot"></div>
                        </div>
                    </div>
                    <div class="robot-kc-sensor-card robot-kc-sensor-card-wheel">
                        <div class="robot-kc-sensor-head">
                            <span class="robot-kc-sensor-label">Left wheel</span>
                            <span class="robot-kc-wheel-readout robot-kc-wheel-left-readout">0.00 m/s</span>
                        </div>
                        <div class="robot-kc-wheel" title="Left wheel command">
                            <div class="robot-kc-wheel-track"></div>
                            <div class="robot-kc-wheel-pos"></div>
                            <div class="robot-kc-wheel-neg"></div>
                        </div>
                    </div>
                </div>

                <div class="robot-kc-drive">
                    <div class="robot-kc-pad" aria-label="D-pad">
                        <button type="button" class="robot-kc-btn robot-kc-up" data-dir="up" title="Forward (W / up)">
                            <i class="fa fa-arrow-up" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="robot-kc-btn robot-kc-left" data-dir="left" title="Left (A / left)">
                            <i class="fa fa-arrow-left" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="robot-kc-btn robot-kc-center robot-kc-estop"
                                title="<?php echo $can_drive ? 'Emergency stop (E)' : 'Sign in to use e-stop'; ?>"
                                <?php echo $can_drive ? '' : 'disabled aria-disabled="true"'; ?>>
                            <span class="robot-kc-estop-mark">E-STOP</span>
                        </button>
                        <button type="button" class="robot-kc-btn robot-kc-right" data-dir="right" title="Right (D / right)">
                            <i class="fa fa-arrow-right" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="robot-kc-btn robot-kc-down" data-dir="down" title="Back (S / down)">
                            <i class="fa fa-arrow-down" aria-hidden="true"></i>
                        </button>
                    </div>
                    <p class="robot-kc-hint">WASD / arrows · Space stops · E e-stop</p>
                </div>

                <div class="robot-kc-col robot-kc-col-sensors" aria-label="ToF and right wheel">
                    <div class="robot-kc-sensor-card">
                        <div class="robot-kc-sensor-head">
                            <span class="robot-kc-sensor-label">ToF range</span>
                            <span class="robot-kc-tof-readout">— cm</span>
                        </div>
                        <div class="robot-kc-tof" title="Front ToF range">
                            <div class="robot-kc-tof-bg"></div>
                            <div class="robot-kc-tof-fill"></div>
                        </div>
                    </div>
                    <div class="robot-kc-sensor-card robot-kc-sensor-card-wheel">
                        <div class="robot-kc-sensor-head">
                            <span class="robot-kc-sensor-label">Right wheel</span>
                            <span class="robot-kc-wheel-readout robot-kc-wheel-right-readout">0.00 m/s</span>
                        </div>
                        <div class="robot-kc-wheel" title="Right wheel command">
                            <div class="robot-kc-wheel-track"></div>
                            <div class="robot-kc-wheel-pos"></div>
                            <div class="robot-kc-wheel-neg"></div>
                        </div>
                    </div>
                </div>

                <div class="robot-kc-side">
                    <div class="robot-kc-speed-head">
                        <label class="robot-kc-speed-label" for="robot_kc_speed_<?php echo htmlspecialchars($uid) ?>">Speed</label>
                        <div class="robot-kc-speed-val">70%</div>
                    </div>
                    <div class="robot-kc-speed-row">
                        <input id="robot_kc_speed_<?php echo htmlspecialchars($uid) ?>"
                               class="robot-kc-speed"
                               type="range" min="0.2" max="1" step="0.05" value="0.7"
                               orient="vertical"
                               aria-orientation="vertical" />
                    </div>
                    <div class="robot-kc-trim-block">
                        <div class="robot-kc-trim-head">
                            <label class="robot-kc-trim-label" for="robot_kc_trim_<?php echo htmlspecialchars($uid) ?>">Trim</label>
                            <div class="robot-kc-trim-val">0.00</div>
                        </div>
                        <input id="robot_kc_trim_<?php echo htmlspecialchars($uid) ?>"
                               class="robot-kc-trim"
                               type="range" min="-0.2" max="0.2" step="0.01" value="0"
                               title="<?php echo $can_drive ? 'Kinematics trim (C / V)' : 'Sign in to adjust trim'; ?>"
                               aria-label="Kinematics trim"
                               <?php echo $can_drive ? '' : 'disabled aria-disabled="true"'; ?> />
                    </div>
                    <p class="robot-kc-hint robot-kc-hint-side">Enable, then hold D-pad / WASD.</p>
                </div>
            </div>
        </div>

        <style type="text/css">
            #<?php echo htmlspecialchars($id) ?>.block_renderer_canvas {
                overflow: visible;
            }
            #<?php echo htmlspecialchars($id) ?> .block_renderer_header,
            #<?php echo htmlspecialchars($id) ?> .block_renderer_menu_icon,
            #<?php echo htmlspecialchars($id) ?> .block_renderer_menu_icon .btn-group {
                overflow: visible;
            }
            #<?php echo htmlspecialchars($id) ?> .block_renderer_menu_icon .dropdown-menu {
                right: 0;
                left: auto;
                z-index: 1050;
            }
            #<?php echo htmlspecialchars($id) ?>.block_renderer_canvas > table {
                height: 100%;
                table-layout: fixed;
            }
            #<?php echo htmlspecialchars($id) ?> .block_renderer_container {
                height: 100%;
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .block_renderer_container > td {
                height: 100%;
                vertical-align: top;
                overflow: hidden;
                position: relative;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc {
                --kc-accent: var(--r-fill, #2c5686);
                --kc-accent-soft: var(--r-info-bg, rgba(38, 164, 234, 0.16));
                --kc-accent-mid: color-mix(in srgb, var(--r-fill, #2c5686) 34%, transparent);
                --kc-stop: var(--r-bad, #dc2626);
                --kc-stop-deep: var(--r-bad, #b91c1c);
                --kc-stop-bg: var(--r-bad-bg, rgba(220, 38, 38, 0.16));
                box-sizing: border-box;
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
                padding: 4px 10px 8px;
                outline: none;
                user-select: none;
                -webkit-user-select: none;
                overflow: hidden;
                background: transparent;
                color: var(--r-text, #1a1d26);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc:focus {
                box-shadow: inset 0 0 0 2px var(--r-focus-ring, rgba(44, 86, 134, 0.35));
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 6px 10px;
                flex: 0 0 auto;
                padding-bottom: 4px;
                border-bottom: 1px solid var(--r-border, #dde1ea);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-enable {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                margin: 0;
                font-weight: 600;
                font-size: var(--r-fs-lg);
                cursor: pointer;
                color: var(--r-text, #1a1d26);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status {
                font-size: var(--r-fs-sm);
                padding: 2px 8px;
                border-radius: 999px;
                background: var(--kc-accent-soft);
                color: var(--kc-accent);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status.is-on {
                background: var(--r-ok-bg, rgba(34, 197, 94, 0.18));
                color: var(--r-ok, #15803d);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status.is-wait {
                background: var(--r-warn-bg, rgba(242, 197, 17, 0.22));
                color: var(--r-warn, #a16207);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status.is-estop {
                background: var(--kc-stop-bg);
                color: var(--kc-stop-deep);
                font-weight: 700;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-cmd {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: var(--r-fs-sm);
                color: var(--r-muted, #6b7280);
                margin-left: auto;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-body {
                flex: 1 1 auto;
                min-height: 0;
                display: grid;
                grid-template-columns: minmax(96px, 0.95fr) minmax(150px, 1.2fr) minmax(96px, 0.95fr) minmax(84px, 0.6fr);
                gap: 8px 10px;
                align-items: stretch;
                justify-items: stretch;
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-col-sensors {
                display: flex;
                flex-direction: column;
                gap: 8px;
                min-width: 0;
                min-height: 0;
                height: 100%;
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-sensor-card {
                flex: 1 1 0;
                min-height: 0;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
                padding: 6px;
                border: 1px solid var(--r-border, #dde1ea);
                border-radius: 10px;
                background: var(--r-surface, #f4f6fa);
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-sensor-card-wheel {
                flex: 1.05 1 0;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-sensor-head {
                width: 100%;
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 6px;
                flex: 0 0 auto;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-sensor-label {
                margin: 0;
                font-size: var(--r-fs-xs);
                font-weight: 700;
                color: var(--kc-accent);
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-imu-readout,
            #<?php echo htmlspecialchars($id) ?> .robot-kc-tof-readout,
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel-readout {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: var(--r-fs-sm);
                font-weight: 700;
                color: var(--r-text, #1a1d26);
                margin: 0;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-imu,
            #<?php echo htmlspecialchars($id) ?> .robot-kc-tof {
                position: relative;
                flex: 1 1 auto;
                min-height: 0;
                width: auto;
                max-width: 100%;
                aspect-ratio: 1;
                height: auto;
                max-height: 100%;
                align-self: center;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-imu-ring {
                position: absolute;
                inset: 0;
                border-radius: 50%;
                background: var(--kc-accent-mid);
                box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--r-fill, #2c5686) 35%, transparent);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-imu-dot {
                position: absolute;
                width: 18%;
                height: 18%;
                border-radius: 50%;
                background: var(--kc-accent);
                box-shadow: 0 0 0 2px var(--r-card, #fff);
                left: 50%;
                bottom: 50%;
                transform: translate(-50%, 50%);
                transition: left 0.08s linear, bottom 0.08s linear;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-tof-bg,
            #<?php echo htmlspecialchars($id) ?> .robot-kc-tof-fill {
                position: absolute;
                inset: 0;
                border-radius: 50%;
                background: conic-gradient(
                    from 315deg,
                    var(--kc-accent-mid) 90deg,
                    rgba(0, 0, 0, 0) 0deg
                );
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-tof-fill {
                inset: auto;
                width: 100%;
                height: 100%;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                background: conic-gradient(
                    from 315deg,
                    var(--kc-accent) 90deg,
                    rgba(0, 0, 0, 0) 0deg
                );
                transition: width 0.12s linear, height 0.12s linear;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel {
                position: relative;
                flex: 1 1 auto;
                min-height: 0;
                width: min(44px, 40%);
                max-height: 100%;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel-track {
                position: absolute;
                inset: 0;
                border-radius: 6px;
                background: var(--kc-accent-mid);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel-pos,
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel-neg {
                position: absolute;
                left: 0;
                width: 100%;
                height: 0%;
                background: var(--kc-accent);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel-pos {
                bottom: 50%;
                border-radius: 6px 6px 0 0;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-wheel-neg {
                top: 50%;
                border-radius: 0 0 6px 6px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-drive {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 6px;
                min-width: 0;
                min-height: 0;
                height: 100%;
                padding: 8px;
                border: 1px solid var(--r-border, #dde1ea);
                border-radius: 12px;
                background: var(--r-surface, #f4f6fa);
                overflow: visible;
                container-type: size;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-pad {
                display: grid;
                flex: 0 0 auto;
                width: min(100%, calc(100cqh - 28px));
                max-width: 100%;
                aspect-ratio: 1;
                height: auto;
                grid-template-columns: 1fr 1fr 1fr;
                grid-template-rows: 1fr 1fr 1fr;
                gap: 6px;
                grid-template-areas:
                    ". up ."
                    "left center right"
                    ". down .";
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-up { grid-area: up; }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-left { grid-area: left; }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-center { grid-area: center; }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-right { grid-area: right; }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-down { grid-area: down; }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-btn {
                margin: 0;
                width: 100%;
                height: 100%;
                border: 2px solid var(--r-border-strong, #c9ced8);
                border-radius: 12px;
                background: var(--r-card, #fff);
                color: var(--r-text, #1a1d26);
                font-size: clamp(14px, 2.2cqw, 22px);
                line-height: 1;
                cursor: pointer;
                touch-action: none;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-btn.is-active {
                background: var(--kc-accent);
                border-color: var(--kc-accent);
                color: var(--r-on-fill, #fff);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-estop {
                border-color: var(--kc-stop-deep);
                background: var(--r-bad-bg, #fee2e2);
                color: var(--kc-stop-deep);
                font-weight: 800;
                letter-spacing: 0.04em;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-estop-mark {
                display: block;
                font-size: clamp(10px, 1.6cqw, 13px);
                line-height: 1.1;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-estop.is-latched,
            #<?php echo htmlspecialchars($id) ?> .robot-kc.is-estop .robot-kc-estop {
                background: var(--kc-stop);
                border-color: var(--kc-stop-deep);
                color: var(--r-on-fill, #fff);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-side {
                min-width: 0;
                min-height: 0;
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 6px;
                padding: 8px 6px;
                border: 1px solid var(--r-border, #dde1ea);
                border-radius: 10px;
                background: var(--r-surface, #f4f6fa);
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-head {
                width: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 2px;
                flex: 0 0 auto;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-label {
                margin: 0;
                font-weight: 700;
                font-size: var(--r-fs-xs);
                text-transform: uppercase;
                letter-spacing: 0.03em;
                color: var(--kc-accent);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-row {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                flex: 1 1 auto;
                min-height: 0;
                width: 100%;
                padding: 4px 0;
                border-radius: 8px;
                background: var(--r-hover, rgba(15, 61, 92, 0.04));
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed {
                width: 28px;
                height: 100%;
                min-height: 56px;
                margin: 0;
                writing-mode: vertical-lr;
                direction: rtl;
                appearance: slider-vertical;
                -webkit-appearance: slider-vertical;
                accent-color: var(--kc-accent);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-val {
                font-variant-numeric: tabular-nums;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-weight: 800;
                color: var(--r-text, #1a1d26);
                font-size: var(--r-fs-xl);
                line-height: 1.1;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-trim-block {
                width: 100%;
                flex: 0 0 auto;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: 2px;
                padding: 4px 4px 2px;
                border-radius: 8px;
                background: var(--r-hover, rgba(15, 61, 92, 0.04));
                border: 1px dashed var(--r-border, #dde1ea);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-trim-head {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 4px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-trim-label {
                margin: 0;
                font-weight: 600;
                font-size: var(--r-fs-xs);
                text-transform: uppercase;
                letter-spacing: 0.03em;
                color: var(--r-muted, #6b7280);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-trim-val {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: var(--r-fs-xs);
                font-weight: 600;
                color: var(--r-muted, #6b7280);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-trim {
                width: 100%;
                height: 18px;
                margin: 0;
                accent-color: var(--r-control-border, #8b929e);
                opacity: 0.92;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-hint {
                margin: 0;
                font-size: var(--r-fs-sm);
                line-height: 1.3;
                color: var(--r-muted, #6b7280);
                text-align: center;
                flex: 0 0 auto;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-hint-side {
                text-align: center;
                font-size: var(--r-fs-xs);
                max-width: 9em;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc.is-disabled .robot-kc-btn[data-dir] {
                opacity: 0.45;
                pointer-events: none;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc.is-estop .robot-kc-btn[data-dir] {
                opacity: 0.35;
                pointer-events: none;
            }
            @media (max-width: 900px) {
                #<?php echo htmlspecialchars($id) ?> .robot-kc {
                    position: relative;
                    inset: auto;
                    height: 100%;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-body {
                    grid-template-columns: 1fr 1fr;
                    grid-template-rows: auto auto;
                    overflow: auto;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-drive {
                    grid-column: 1 / -1;
                    min-height: 200px;
                    container-type: normal;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-pad {
                    width: min(100%, 220px);
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-side {
                    grid-column: 1 / -1;
                    flex-direction: row;
                    flex-wrap: wrap;
                    align-items: center;
                    min-height: 64px;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-row {
                    flex: 1 1 140px;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-speed {
                    writing-mode: horizontal-tb;
                    direction: ltr;
                    appearance: auto;
                    -webkit-appearance: auto;
                    width: 100%;
                    height: 28px;
                    min-height: 0;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-trim-block {
                    flex: 1 1 160px;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-kc-hint-side {
                    max-width: none;
                    text-align: left;
                }
            }
        </style>

        <script type="text/javascript">
        (function () {
            var root = document.getElementById(<?php echo json_encode((string) $id) ?>);
            if (!root) return;
            // Prevent double-init if the block script is evaluated twice
            if (root.getAttribute('data-kc-bound') === '1') return;
            root.setAttribute('data-kc-bound', '1');

            var box = root.querySelector('.robot-kc');
            var enable = root.querySelector('.robot-kc-enable-input');
            var statusEl = root.querySelector('.robot-kc-status');
            var cmdEl = root.querySelector('.robot-kc-cmd');
            var speed = root.querySelector('.robot-kc-speed');
            var speedVal = root.querySelector('.robot-kc-speed-val');
            var trim = root.querySelector('.robot-kc-trim');
            var trimVal = root.querySelector('.robot-kc-trim-val');
            var estopBtn = root.querySelector('.robot-kc-estop');
            var buttons = root.querySelectorAll('.robot-kc-btn[data-dir]');

            var rosHost = <?php echo json_encode($ros_hostname) ?>;
            var joyTopicName = <?php echo json_encode($joy_topic) ?>;
            var imuTopicName = <?php echo json_encode($imu_topic) ?>;
            var tofTopicName = <?php echo json_encode($tof_topic) ?>;
            var wheelsTopicName = <?php echo json_encode($wheels_topic) ?>;
            var estopTopicName = <?php echo json_encode($estop_topic) ?>;
            var trimParamName = <?php echo json_encode($trim_param) ?>;
            var trimUpdateSrvName = <?php echo json_encode($trim_update_srv) ?>;
            var periodMs = <?php echo json_encode(intval(1000 / $hz)) ?>;
            var canDrive = <?php echo $can_drive ? 'true' : 'false'; ?>;
            var smoothAlpha = 0.28;
            var tofMaxM = 1.2;
            var wheelMaxMs = 0.4;
            var sensorsBound = false;
            var trimBound = false;
            var trimTimer = null;
            var estopLatched = false;

            var imuDot = root.querySelector('.robot-kc-imu-dot');
            var imuReadout = root.querySelector('.robot-kc-imu-readout');
            var tofFill = root.querySelector('.robot-kc-tof-fill');
            var tofReadout = root.querySelector('.robot-kc-tof-readout');
            var leftWheelPos = root.querySelectorAll('.robot-kc-col-sensors')[0].querySelector('.robot-kc-wheel-pos');
            var leftWheelNeg = root.querySelectorAll('.robot-kc-col-sensors')[0].querySelector('.robot-kc-wheel-neg');
            var leftWheelReadout = root.querySelector('.robot-kc-wheel-left-readout');
            var rightWheelPos = root.querySelectorAll('.robot-kc-col-sensors')[1].querySelector('.robot-kc-wheel-pos');
            var rightWheelNeg = root.querySelectorAll('.robot-kc-col-sensors')[1].querySelector('.robot-kc-wheel-neg');
            var rightWheelReadout = root.querySelector('.robot-kc-wheel-right-readout');

            var Keys = {
                UP: 38, DOWN: 40, LEFT: 37, RIGHT: 39,
                W: 87, A: 65, S: 83, D: 68, SPACE: 32,
                E: 69, C: 67, V: 86
            };
            var pressed = { up: false, down: false, left: false, right: false };
            var joyPub = null;
            var estopPub = null;
            var trimParam = null;
            var trimUpdateSrv = null;
            var bridgeReady = false;
            var curFwd = 0;
            var curSteer = 0;
            var timer = null;
            var armed = false;

            function setStatus(text, cls) {
                statusEl.textContent = text;
                statusEl.className = 'robot-kc-status' + (cls ? ' ' + cls : '');
            }

            function refreshStatus() {
                if (estopLatched) {
                    setStatus('E-STOP', 'is-estop');
                    return;
                }
                if (!bridgeReady) {
                    setStatus('Waiting for bridge...', 'is-wait');
                    return;
                }
                if (armed) {
                    setStatus('Driving', 'is-on');
                } else {
                    setStatus('Bridge ok, enable to drive', 'is-wait');
                }
            }

            function setCmdLabel(fwd, steer) {
                cmdEl.textContent = 'axes ' + fwd.toFixed(2) + ' / ' + steer.toFixed(2);
            }

            function driving() {
                return !!(armed && bridgeReady && joyPub && !estopLatched);
            }

            function syncButtons() {
                Array.prototype.forEach.call(buttons, function (btn) {
                    var dir = btn.getAttribute('data-dir');
                    btn.classList.toggle('is-active', !!pressed[dir]);
                });
            }

            function syncEstopUi() {
                box.classList.toggle('is-estop', estopLatched);
                if (estopBtn) estopBtn.classList.toggle('is-latched', estopLatched);
                refreshStatus();
            }

            function getRos() {
                if (!window.ros) return null;
                if (rosHost && window.ros[rosHost]) return window.ros[rosHost];
                // Prefer hostname key used by Mission Control rosbridge connect
                try {
                    var host = window.location && window.location.hostname;
                    if (host && window.ros[host]) return window.ros[host];
                } catch (err) {}
                if (window.ros['local']) return window.ros['local'];
                if (window.ros['akshet.local']) return window.ros['akshet.local'];
                if (window.ros.socket) return window.ros;
                var keys = Object.keys(window.ros);
                for (var i = 0; i < keys.length; i++) {
                    var cand = window.ros[keys[i]];
                    if (cand && cand.socket) return cand;
                }
                return null;
            }

            function targetAxes() {
                if (!driving()) {
                    return { fwd: 0, steer: 0 };
                }
                var scale = parseFloat(speed.value) || 0.7;
                var forward = (pressed.up ? 1 : 0) - (pressed.down ? 1 : 0);
                var steer = (pressed.left ? 1 : 0) - (pressed.right ? 1 : 0);
                return {
                    fwd: forward * scale,
                    steer: steer * scale
                };
            }

            function publishJoy(fwd, steer) {
                // joy_mapper: axes[1] = forward, axes[3] = steer
                // Do NOT pulse buttons[3] here — that would toggle e-stop every tick.
                joyPub.publish(new ROSLIB.Message({
                    axes: [0, fwd, 0, steer, 0, 0, 0, 0],
                    buttons: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                }));
                setCmdLabel(fwd, steer);
            }

            function publishEstop(active) {
                if (!estopPub) return;
                estopPub.publish(new ROSLIB.Message({
                    header: { stamp: { secs: 0, nsecs: 0 }, frame_id: '' },
                    data: !!active
                }));
            }

            function setEstop(active) {
                estopLatched = !!active;
                if (estopLatched) {
                    stopAll();
                    curFwd = 0;
                    curSteer = 0;
                    if (joyPub && bridgeReady) publishJoy(0, 0);
                    if (enable.checked) {
                        enable.checked = false;
                        armed = false;
                        box.classList.add('is-disabled');
                    }
                }
                publishEstop(estopLatched);
                syncEstopUi();
            }

            function toggleEstop() {
                if (!canDrive) {
                    setStatus('Sign in to drive', 'is-wait');
                    return;
                }
                if (!bridgeReady && !bindPublisher()) {
                    setStatus('Waiting for bridge...', 'is-wait');
                    return;
                }
                setEstop(!estopLatched);
            }

            function tick() {
                if (!joyPub || !bridgeReady) return;
                var target = targetAxes();
                var alpha = (target.fwd === 0 && target.steer === 0) ? 0.5 : smoothAlpha;
                curFwd += (target.fwd - curFwd) * alpha;
                curSteer += (target.steer - curSteer) * alpha;
                if (Math.abs(curFwd) < 0.01) curFwd = 0;
                if (Math.abs(curSteer) < 0.01) curSteer = 0;

                // Stream while armed, and briefly while coasting to zero after disable
                if ((!armed || estopLatched) && curFwd === 0 && curSteer === 0) {
                    setCmdLabel(0, 0);
                    return;
                }
                publishJoy(curFwd, curSteer);
            }

            function stopAll() {
                pressed.up = pressed.down = pressed.left = pressed.right = false;
                syncButtons();
                curFwd *= 0.25;
                curSteer *= 0.25;
            }

            function setWheelBar(posEl, negEl, readoutEl, vel) {
                var v = Number(vel) || 0;
                var pct = Math.min(100, Math.abs(v) / wheelMaxMs * 100);
                posEl.style.height = (v > 0 ? pct : 0) + '%';
                negEl.style.height = (v < 0 ? pct : 0) + '%';
                readoutEl.textContent = v.toFixed(2) + ' m/s';
            }

            function updateTrimLabel() {
                var v = parseFloat(trim.value);
                if (!isFinite(v)) v = 0;
                trimVal.textContent = (v >= 0 ? '+' : '') + v.toFixed(2);
            }

            function pushTrim(value) {
                if (!canDrive || !trimParam) return;
                var v = Math.max(-0.2, Math.min(0.2, Number(value) || 0));
                trimParam.set(v);
                if (trimUpdateSrv) {
                    trimUpdateSrv.callService(
                        new ROSLIB.ServiceRequest({ parameter: trimParamName }),
                        function () {},
                        function () {}
                    );
                }
            }

            function scheduleTrimPush() {
                updateTrimLabel();
                if (!canDrive) return;
                if (trimTimer) clearTimeout(trimTimer);
                trimTimer = setTimeout(function () {
                    pushTrim(trim.value);
                }, 120);
            }

            function nudgeTrim(delta) {
                if (!canDrive) {
                    setStatus('Sign in to drive', 'is-wait');
                    return;
                }
                var v = parseFloat(trim.value) || 0;
                v = Math.max(-0.2, Math.min(0.2, Math.round((v + delta) * 100) / 100));
                trim.value = String(v);
                scheduleTrimPush();
            }

            function bindTrim() {
                if (!canDrive) return false;
                var ros = getRos();
                if (!ros || typeof ROSLIB === 'undefined' || trimBound) return false;
                trimBound = true;
                trimParam = new ROSLIB.Param({
                    ros: ros,
                    name: trimParamName
                });
                trimUpdateSrv = new ROSLIB.Service({
                    ros: ros,
                    name: trimUpdateSrvName,
                    serviceType: 'duckietown_msgs/NodeRequestParamsUpdate'
                });
                trimParam.get(function (value) {
                    if (value == null || !isFinite(Number(value))) return;
                    var v = Math.max(-0.2, Math.min(0.2, Number(value)));
                    trim.value = String(v);
                    updateTrimLabel();
                });
                return true;
            }

            function bindSensors() {
                var ros = getRos();
                if (!ros || typeof ROSLIB === 'undefined' || sensorsBound) return false;
                sensorsBound = true;

                var imuSub = new ROSLIB.Topic({
                    ros: ros,
                    name: imuTopicName,
                    messageType: 'sensor_msgs/Imu',
                    queue_size: 1,
                    throttle_rate: 100
                });
                imuSub.subscribe(function (msg) {
                    var ax = Number(msg.linear_acceleration && msg.linear_acceleration.x) || 0;
                    var ay = Number(msg.linear_acceleration && msg.linear_acceleration.y) || 0;
                    var radius = Math.min(Math.sqrt(ax * ax + ay * ay), 9.81);
                    var angle = Math.atan2(ay, ax);
                    var x = radius * Math.cos(angle);
                    var y = radius * Math.sin(angle);
                    var left = 37.5 * x / 9.81 + 50;
                    var bottom = 37.5 * y / 9.81 + 50;
                    imuDot.style.left = left + '%';
                    imuDot.style.bottom = bottom + '%';
                    imuReadout.textContent = (radius / 9.81).toFixed(2) + ' g';
                });

                var tofSub = new ROSLIB.Topic({
                    ros: ros,
                    name: tofTopicName,
                    messageType: 'sensor_msgs/Range',
                    queue_size: 1,
                    throttle_rate: 100
                });
                tofSub.subscribe(function (msg) {
                    var range = Number(msg.range);
                    if (!isFinite(range) || range < 0) {
                        tofReadout.textContent = '— cm';
                        tofFill.style.width = '100%';
                        tofFill.style.height = '100%';
                        return;
                    }
                    var scale = Math.max(0.12, Math.min(1, range / tofMaxM));
                    var pct = (scale * 100).toFixed(1) + '%';
                    tofFill.style.width = pct;
                    tofFill.style.height = pct;
                    tofReadout.textContent = (range * 100).toFixed(0) + ' cm';
                });

                var wheelsSub = new ROSLIB.Topic({
                    ros: ros,
                    name: wheelsTopicName,
                    messageType: 'duckietown_msgs/WheelsCmdStamped',
                    queue_size: 1,
                    throttle_rate: 100
                });
                wheelsSub.subscribe(function (msg) {
                    setWheelBar(leftWheelPos, leftWheelNeg, leftWheelReadout, msg.vel_left);
                    setWheelBar(rightWheelPos, rightWheelNeg, rightWheelReadout, msg.vel_right);
                });

                // Mirror external e-stop state (e.g. full viewer)
                var estopSub = new ROSLIB.Topic({
                    ros: ros,
                    name: estopTopicName,
                    messageType: 'duckietown_msgs/BoolStamped',
                    queue_size: 1,
                    throttle_rate: 200
                });
                estopSub.subscribe(function (msg) {
                    var active = !!(msg && msg.data);
                    if (active === estopLatched) return;
                    estopLatched = active;
                    if (estopLatched) {
                        stopAll();
                        curFwd = 0;
                        curSteer = 0;
                        if (enable.checked) {
                            enable.checked = false;
                            armed = false;
                            box.classList.add('is-disabled');
                        }
                    }
                    syncEstopUi();
                });
                return true;
            }

            function bindPublisher() {
                if (!canDrive) return false;
                var ros = getRos();
                if (!ros || typeof ROSLIB === 'undefined') return false;
                // Single joy publisher only
                joyPub = new ROSLIB.Topic({
                    ros: ros,
                    name: joyTopicName,
                    messageType: 'sensor_msgs/Joy',
                    queue_size: 1
                });
                estopPub = new ROSLIB.Topic({
                    ros: ros,
                    name: estopTopicName,
                    messageType: 'duckietown_msgs/BoolStamped',
                    queue_size: 1
                });
                bridgeReady = true;
                bindSensors();
                bindTrim();
                refreshStatus();
                return true;
            }

            function onKey(e, down) {
                var code = e.keyCode;
                var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                if (tag === 'input' || tag === 'textarea' || tag === 'select') return;

                // Actuation (e-stop, trim, drive) requires sign-in
                if (code === Keys.E) {
                    if (down) {
                        e.preventDefault();
                        toggleEstop();
                    }
                    return;
                }
                if (code === Keys.C && down) {
                    e.preventDefault();
                    nudgeTrim(-0.01);
                    return;
                }
                if (code === Keys.V && down) {
                    e.preventDefault();
                    nudgeTrim(0.01);
                    return;
                }

                if (!driving()) return;
                var handled = true;
                if (code === Keys.SPACE) {
                    if (down) stopAll();
                } else if (code === Keys.UP || code === Keys.W) {
                    pressed.up = down;
                } else if (code === Keys.DOWN || code === Keys.S) {
                    pressed.down = down;
                } else if (code === Keys.LEFT || code === Keys.A) {
                    pressed.left = down;
                } else if (code === Keys.RIGHT || code === Keys.D) {
                    pressed.right = down;
                } else {
                    handled = false;
                }
                if (handled) {
                    e.preventDefault();
                    syncButtons();
                }
            }

            Array.prototype.forEach.call(buttons, function (btn) {
                var dir = btn.getAttribute('data-dir');
                function set(down, ev) {
                    if (!driving()) return;
                    if (ev) {
                        ev.preventDefault();
                        ev.stopPropagation();
                    }
                    pressed[dir] = down;
                    if (down && btn.setPointerCapture && ev && ev.pointerId != null) {
                        try { btn.setPointerCapture(ev.pointerId); } catch (err) {}
                    }
                    syncButtons();
                }
                btn.addEventListener('pointerdown', function (ev) { set(true, ev); });
                btn.addEventListener('pointerup', function (ev) { set(false, ev); });
                btn.addEventListener('pointercancel', function (ev) { set(false, ev); });
                btn.addEventListener('lostpointercapture', function () {
                    if (pressed[dir]) {
                        pressed[dir] = false;
                        syncButtons();
                    }
                });
            });

            if (estopBtn) {
                estopBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    ev.stopPropagation();
                    toggleEstop();
                });
            }

            enable.addEventListener('change', function () {
                if (!canDrive) {
                    enable.checked = false;
                    armed = false;
                    box.classList.add('is-disabled');
                    setStatus('Sign in to drive', 'is-wait');
                    return;
                }
                if (estopLatched) {
                    enable.checked = false;
                    armed = false;
                    box.classList.add('is-disabled');
                    refreshStatus();
                    return;
                }
                armed = !!enable.checked;
                box.classList.toggle('is-disabled', !armed);
                if (armed) {
                    if (!bridgeReady && !bindPublisher()) {
                        enable.checked = false;
                        armed = false;
                        box.classList.add('is-disabled');
                        setStatus('Waiting for bridge...', 'is-wait');
                        return;
                    }
                    refreshStatus();
                    box.focus();
                } else {
                    stopAll();
                    setTimeout(function () {
                        if (joyPub) {
                            curFwd = 0;
                            curSteer = 0;
                            publishJoy(0, 0);
                        }
                    }, 150);
                    refreshStatus();
                }
            });

            speed.addEventListener('input', function () {
                var pct = Math.round((parseFloat(speed.value) - 0.2) / (1 - 0.2) * 100);
                speedVal.textContent = Math.max(0, Math.min(100, pct)) + '%';
            });
            speed.dispatchEvent(new Event('input'));

            trim.addEventListener('input', scheduleTrimPush);
            trim.addEventListener('change', scheduleTrimPush);
            updateTrimLabel();

            window.addEventListener('keydown', function (e) { onKey(e, true); }, true);
            window.addEventListener('keyup', function (e) { onKey(e, false); }, true);
            window.addEventListener('blur', function () {
                if (armed) stopAll();
            });

            box.classList.add('is-disabled');
            if (!canDrive) {
                setStatus('Sign in to drive', 'is-wait');
            } else {
                setStatus('Waiting for bridge...', 'is-wait');
            }

            $(document).on(<?php echo json_encode($connected_evt) ?>, function () {
                if (canDrive) bindPublisher();
            });
            if (canDrive) {
                setTimeout(function () { bindPublisher(); }, 100);
                setTimeout(function () { bindPublisher(); }, 800);
            }

            if (timer) clearInterval(timer);
            timer = setInterval(tick, periodMs);
        })();
        </script>
        <?php
    }
}
?>
