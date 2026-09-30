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

    static protected $DEFAULT_SIZE = ['rows' => 4, 'cols' => 8];

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

        $hz = isset($args['hz']) ? max(20, intval($args['hz'])) : 50;
        $viewer_url = Duckiebot::getKeyboardControllerUrl();
        $uid = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
        ?>
        <div class="robot-kc" id="robot_kc_<?php echo htmlspecialchars($uid) ?>" tabindex="0">
            <div class="robot-kc-toolbar">
                <label class="robot-kc-enable">
                    <input type="checkbox" class="robot-kc-enable-input" />
                    <span>Enable drive</span>
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
                <div class="robot-kc-pad" aria-label="D-pad">
                    <button type="button" class="robot-kc-btn robot-kc-up" data-dir="up" title="Forward (W / up)">▲</button>
                    <button type="button" class="robot-kc-btn robot-kc-left" data-dir="left" title="Left (A / left)">◀</button>
                    <button type="button" class="robot-kc-btn robot-kc-center" disabled aria-hidden="true">
                        <i class="fa fa-gamepad" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="robot-kc-btn robot-kc-right" data-dir="right" title="Right (D / right)">▶</button>
                    <button type="button" class="robot-kc-btn robot-kc-down" data-dir="down" title="Back (S / down)">▼</button>
                </div>

                <div class="robot-kc-side">
                    <label class="robot-kc-speed-label" for="robot_kc_speed_<?php echo htmlspecialchars($uid) ?>">Speed</label>
                    <input id="robot_kc_speed_<?php echo htmlspecialchars($uid) ?>"
                           class="robot-kc-speed"
                           type="range" min="0.2" max="1" step="0.05" value="0.7" />
                    <div class="robot-kc-speed-val">70%</div>
                    <p class="robot-kc-hint">Enable drive, then hold D-pad or WASD. Space stops.</p>
                </div>
            </div>
        </div>

        <style type="text/css">
            #<?php echo htmlspecialchars($id) ?>.block_renderer_canvas { overflow: hidden; }
            #<?php echo htmlspecialchars($id) ?>.block_renderer_canvas > table {
                height: 100%;
                table-layout: fixed;
            }
            #<?php echo htmlspecialchars($id) ?> .block_renderer_container,
            #<?php echo htmlspecialchars($id) ?> .block_renderer_container > td {
                height: 100%;
                vertical-align: top;
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc {
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                gap: 6px;
                height: 100%;
                padding: 4px 10px 6px;
                outline: none;
                user-select: none;
                -webkit-user-select: none;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc:focus {
                box-shadow: inset 0 0 0 2px rgba(38, 164, 234, 0.45);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px 12px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-enable {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                margin: 0;
                font-weight: 600;
                cursor: pointer;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status {
                font-size: 12px;
                padding: 2px 8px;
                border-radius: 999px;
                background: var(--r-surface, #f1f3f5);
                color: var(--r-muted, #6b7280);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status.is-on {
                background: rgba(34, 197, 94, 0.15);
                color: #15803d;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-status.is-wait {
                background: rgba(234, 179, 8, 0.18);
                color: #a16207;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-cmd {
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                font-size: 11px;
                color: var(--r-muted, #6b7280);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-body {
                flex: 1 1 auto;
                min-height: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 20px;
                flex-wrap: wrap;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-pad {
                display: grid;
                grid-template-columns: 52px 52px 52px;
                grid-template-rows: 52px 52px 52px;
                gap: 5px;
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
                border: 2px solid #1f2937;
                border-radius: 8px;
                background: #f8fafc;
                color: #1f2937;
                font-size: 18px;
                line-height: 1;
                cursor: pointer;
                touch-action: none;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-btn:disabled {
                cursor: default;
                opacity: 0.85;
                background: #fff;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-btn.is-active {
                background: #26a4ea;
                border-color: #1d8bc9;
                color: #fff;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-side {
                min-width: 180px;
                max-width: 260px;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-label {
                margin: 0;
                font-weight: 600;
                font-size: 13px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed { width: 100%; }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-speed-val {
                font-variant-numeric: tabular-nums;
                color: var(--r-muted, #6b7280);
                font-size: 12px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc-hint {
                margin: 8px 0 0;
                font-size: 12px;
                line-height: 1.35;
                color: var(--r-muted, #6b7280);
            }
            #<?php echo htmlspecialchars($id) ?> .robot-kc.is-disabled .robot-kc-btn[data-dir] {
                opacity: 0.45;
                pointer-events: none;
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
            var buttons = root.querySelectorAll('.robot-kc-btn[data-dir]');

            var rosHost = <?php echo json_encode($ros_hostname) ?>;
            var joyTopicName = <?php echo json_encode($joy_topic) ?>;
            var periodMs = <?php echo json_encode(intval(1000 / $hz)) ?>;
            var smoothAlpha = 0.28;

            var Keys = { UP: 38, DOWN: 40, LEFT: 37, RIGHT: 39, W: 87, A: 65, S: 83, D: 68, SPACE: 32 };
            var pressed = { up: false, down: false, left: false, right: false };
            var joyPub = null;
            var bridgeReady = false;
            var curFwd = 0;
            var curSteer = 0;
            var timer = null;
            var armed = false;

            function setStatus(text, cls) {
                statusEl.textContent = text;
                statusEl.className = 'robot-kc-status' + (cls ? ' ' + cls : '');
            }

            function setCmdLabel(fwd, steer) {
                cmdEl.textContent = 'axes ' + fwd.toFixed(2) + ' / ' + steer.toFixed(2);
            }

            function driving() {
                return !!(armed && bridgeReady && joyPub);
            }

            function syncButtons() {
                Array.prototype.forEach.call(buttons, function (btn) {
                    var dir = btn.getAttribute('data-dir');
                    btn.classList.toggle('is-active', !!pressed[dir]);
                });
            }

            function getRos() {
                if (!window.ros) return null;
                if (rosHost && window.ros[rosHost]) return window.ros[rosHost];
                if (window.ros['local']) return window.ros['local'];
                if (window.ros.socket) return window.ros;
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
                joyPub.publish(new ROSLIB.Message({
                    axes: [0, fwd, 0, steer, 0, 0, 0, 0],
                    buttons: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                }));
                setCmdLabel(fwd, steer);
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
                if (!armed && curFwd === 0 && curSteer === 0) {
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

            function bindPublisher() {
                var ros = getRos();
                if (!ros || typeof ROSLIB === 'undefined') return false;
                // Single publisher only
                joyPub = new ROSLIB.Topic({
                    ros: ros,
                    name: joyTopicName,
                    messageType: 'sensor_msgs/Joy',
                    queue_size: 1
                });
                bridgeReady = true;
                setStatus(
                    armed ? 'Driving' : 'Bridge ok, enable to drive',
                    armed ? 'is-on' : 'is-wait'
                );
                return true;
            }

            function onKey(e, down) {
                if (!driving()) return;
                var code = e.keyCode;
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

            enable.addEventListener('change', function () {
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
                    setStatus('Driving', 'is-on');
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
                    setStatus(bridgeReady ? 'Off' : 'Waiting for bridge...', bridgeReady ? '' : 'is-wait');
                }
            });

            speed.addEventListener('input', function () {
                var pct = Math.round((parseFloat(speed.value) - 0.2) / (1 - 0.2) * 100);
                speedVal.textContent = Math.max(0, Math.min(100, pct)) + '%';
            });
            speed.dispatchEvent(new Event('input'));

            window.addEventListener('keydown', function (e) { onKey(e, true); }, true);
            window.addEventListener('keyup', function (e) { onKey(e, false); }, true);
            window.addEventListener('blur', function () {
                if (armed) stopAll();
            });

            box.classList.add('is-disabled');
            setStatus('Waiting for bridge...', 'is-wait');

            $(document).on(<?php echo json_encode($connected_evt) ?>, function () {
                bindPublisher();
            });
            setTimeout(function () { bindPublisher(); }, 100);
            setTimeout(function () { bindPublisher(); }, 800);

            if (timer) clearInterval(timer);
            timer = setInterval(tick, periodMs);
        })();
        </script>
        <?php
    }
}
?>
