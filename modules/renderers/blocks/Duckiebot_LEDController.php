<?php
use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;

/**
 * Mission Control LED colour control.
 *
 * Publishes duckietown_msgs/LEDPattern on /<veh>/led_driver_node/led_pattern.
 * rgb_vals indices: 0=front_left, 1=back_left, 2=middle(unused),
 * 3=back_right, 4=front_right.
 */
class Duckiebot_LEDController extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "lightbulb-o"
    ];

    static protected $DEFAULT_SIZE = ['rows' => 2, 'cols' => 8];

    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ]
    ];

    protected static function render($id, &$args) {
        $vehicle = Duckiebot::getDuckiebotName();
        $ros_hostname = isset($args['ros_hostname']) ? $args['ros_hostname'] : null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ROS::connect($ros_hostname);

        $prefix = !empty($vehicle) ? '/' . $vehicle : '';
        $led_topic = $prefix . '/led_driver_node/led_pattern';
        $uid = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
        $storage_key = 'duckietown.led_pattern.' . (strlen((string) $vehicle) ? $vehicle : 'local');
        ?>
        <div class="robot-led" id="robot_led_<?php echo htmlspecialchars($uid) ?>">
            <div class="robot-led-toolbar">
                <span class="robot-led-status is-wait">Waiting for bridge…</span>
                <span class="robot-led-topic" title="<?php echo htmlspecialchars($led_topic) ?>">
                    <?php echo htmlspecialchars($led_topic) ?>
                </span>
            </div>

            <div class="robot-led-body">
                <div class="robot-led-layout" aria-label="Duckiebot LED layout">
                    <div class="robot-led-cell" data-led="front_left">
                        <label class="robot-led-label" for="robot_led_fl_<?php echo htmlspecialchars($uid) ?>">Front L</label>
                        <input id="robot_led_fl_<?php echo htmlspecialchars($uid) ?>"
                               class="robot-led-color" type="color" value="#ffffff"
                               data-led="front_left" title="Front left LED" />
                        <input class="robot-led-intensity" type="range" min="0" max="1" step="0.05" value="0.2"
                               data-led="front_left" aria-label="Front left intensity" />
                    </div>
                    <div class="robot-led-cell" data-led="front_right">
                        <label class="robot-led-label" for="robot_led_fr_<?php echo htmlspecialchars($uid) ?>">Front R</label>
                        <input id="robot_led_fr_<?php echo htmlspecialchars($uid) ?>"
                               class="robot-led-color" type="color" value="#ffffff"
                               data-led="front_right" title="Front right LED" />
                        <input class="robot-led-intensity" type="range" min="0" max="1" step="0.05" value="0.2"
                               data-led="front_right" aria-label="Front right intensity" />
                    </div>
                    <div class="robot-led-divider" aria-hidden="true"></div>
                    <div class="robot-led-cell" data-led="back_left">
                        <label class="robot-led-label" for="robot_led_bl_<?php echo htmlspecialchars($uid) ?>">Back L</label>
                        <input id="robot_led_bl_<?php echo htmlspecialchars($uid) ?>"
                               class="robot-led-color" type="color" value="#ff0000"
                               data-led="back_left" title="Back left LED" />
                        <input class="robot-led-intensity" type="range" min="0" max="1" step="0.05" value="0.2"
                               data-led="back_left" aria-label="Back left intensity" />
                    </div>
                    <div class="robot-led-cell" data-led="back_right">
                        <label class="robot-led-label" for="robot_led_br_<?php echo htmlspecialchars($uid) ?>">Back R</label>
                        <input id="robot_led_br_<?php echo htmlspecialchars($uid) ?>"
                               class="robot-led-color" type="color" value="#ff0000"
                               data-led="back_right" title="Back right LED" />
                        <input class="robot-led-intensity" type="range" min="0" max="1" step="0.05" value="0.2"
                               data-led="back_right" aria-label="Back right intensity" />
                    </div>
                </div>

                <div class="robot-led-side">
                    <div class="robot-led-presets" role="group" aria-label="LED presets">
                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" data-preset="default">Default</button>
                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" data-preset="white">White</button>
                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" data-preset="red">Red</button>
                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" data-preset="blue">Blue</button>
                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" data-preset="green">Green</button>
                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-xs" data-preset="off">Off</button>
                    </div>
                </div>
            </div>
        </div>

        <style type="text/css">
            #<?php echo htmlspecialchars($id) ?>.block_renderer_canvas {
                overflow: visible;
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
            #<?php echo htmlspecialchars($id) ?> .robot-led {
                box-sizing: border-box;
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: column;
                gap: 6px;
                padding: 4px 10px 8px;
                overflow: hidden;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-toolbar {
                display: flex;
                align-items: center;
                gap: 10px;
                min-height: 22px;
                flex: 0 0 auto;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-status {
                font-size: 11px;
                font-weight: 600;
                color: #6b7280;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-status.is-wait { color: #b45309; }
            #<?php echo htmlspecialchars($id) ?> .robot-led-status.is-on { color: #15803d; }
            #<?php echo htmlspecialchars($id) ?> .robot-led-status.is-bad { color: #b91c1c; }
            #<?php echo htmlspecialchars($id) ?> .robot-led-topic {
                margin-left: auto;
                font-size: 10px;
                color: #9ca3af;
                font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
                max-width: 55%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-body {
                flex: 1 1 auto;
                min-height: 0;
                display: grid;
                grid-template-columns: minmax(0, 1fr) 150px;
                gap: 12px;
                align-items: center;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-layout {
                display: flex;
                align-items: center;
                justify-content: space-around;
                gap: 8px;
                min-height: 0;
                padding: 2px 4px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-divider {
                width: 1px;
                align-self: stretch;
                background: #e5e7eb;
                margin: 4px 2px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-cell {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 4px;
                padding: 2px 4px;
                min-width: 0;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-label {
                margin: 0;
                font-size: 11px;
                font-weight: 600;
                color: #4b5563;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-color {
                width: 42px;
                height: 32px;
                padding: 0;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: #fff;
                cursor: pointer;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-color::-webkit-color-swatch-wrapper {
                padding: 2px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-color::-webkit-color-swatch {
                border: none;
                border-radius: 4px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-intensity {
                width: 72px;
                height: 16px;
                margin: 0;
                accent-color: #26a4ea;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-side {
                display: flex;
                flex-direction: column;
                justify-content: center;
                gap: 8px;
                min-width: 0;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-presets {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            #<?php echo htmlspecialchars($id) ?> .robot-led-presets .robot-btn.is-active {
                border-color: #26a4ea;
                color: #0b6ea8;
                background: rgba(38, 164, 234, 0.12);
            }
            @media (max-width: 720px) {
                #<?php echo htmlspecialchars($id) ?> .robot-led-body {
                    grid-template-columns: 1fr;
                }
                #<?php echo htmlspecialchars($id) ?> .robot-led-side {
                    flex-direction: row;
                    flex-wrap: wrap;
                    align-items: center;
                }
            }
        </style>

        <script type="text/javascript">
        (function () {
            var box = document.getElementById('robot_led_<?php echo htmlspecialchars($uid) ?>');
            if (!box) return;

            var statusEl = box.querySelector('.robot-led-status');
            var colorInputs = box.querySelectorAll('.robot-led-color');
            var intensityInputs = box.querySelectorAll('.robot-led-intensity');
            var presetBtns = box.querySelectorAll('[data-preset]');
            var rosHost = <?php echo json_encode($ros_hostname) ?>;
            var topicName = <?php echo json_encode($led_topic) ?>;
            var connectedEvt = <?php echo json_encode($connected_evt) ?>;
            var storageKey = <?php echo json_encode($storage_key) ?>;
            var ledPub = null;
            var bridgeReady = false;
            var publishTimer = null;
            var syncingUi = false;
            var lastAppliedFingerprint = '';

            var CONTROLLABLE = ['front_left', 'front_right', 'back_left', 'back_right'];
            var LED_ORDER = ['front_left', 'back_left', 'middle', 'back_right', 'front_right'];
            var DEFAULT_STATE = {
                front_left:  { hex: '#ffffff', a: 0.2 },
                front_right: { hex: '#ffffff', a: 0.2 },
                back_left:   { hex: '#ff0000', a: 0.2 },
                back_right:  { hex: '#ff0000', a: 0.2 }
            };
            var PRESETS = {
                default: DEFAULT_STATE,
                white: {
                    front_left:  { hex: '#ffffff', a: 0.2 },
                    front_right: { hex: '#ffffff', a: 0.2 },
                    back_left:   { hex: '#ffffff', a: 0.2 },
                    back_right:  { hex: '#ffffff', a: 0.2 }
                },
                red: {
                    front_left:  { hex: '#ff0000', a: 0.2 },
                    front_right: { hex: '#ff0000', a: 0.2 },
                    back_left:   { hex: '#ff0000', a: 0.2 },
                    back_right:  { hex: '#ff0000', a: 0.2 }
                },
                blue: {
                    front_left:  { hex: '#0066ff', a: 0.25 },
                    front_right: { hex: '#0066ff', a: 0.25 },
                    back_left:   { hex: '#0066ff', a: 0.25 },
                    back_right:  { hex: '#0066ff', a: 0.25 }
                },
                green: {
                    front_left:  { hex: '#00cc44', a: 0.25 },
                    front_right: { hex: '#00cc44', a: 0.25 },
                    back_left:   { hex: '#00cc44', a: 0.25 },
                    back_right:  { hex: '#00cc44', a: 0.25 }
                },
                off: {
                    front_left:  { hex: '#000000', a: 0 },
                    front_right: { hex: '#000000', a: 0 },
                    back_left:   { hex: '#000000', a: 0 },
                    back_right:  { hex: '#000000', a: 0 }
                }
            };

            function setStatus(text, cls) {
                statusEl.textContent = text;
                statusEl.className = 'robot-led-status' + (cls ? ' ' + cls : '');
            }

            function getRos() {
                if (!window.ros) return null;
                if (rosHost && window.ros[rosHost]) return window.ros[rosHost];
                if (window.ros['local']) return window.ros['local'];
                if (window.ros.socket) return window.ros;
                var keys = Object.keys(window.ros);
                for (var i = 0; i < keys.length; i++) {
                    var cand = window.ros[keys[i]];
                    if (cand && cand.socket) return cand;
                }
                return null;
            }

            function clamp01(v) {
                v = parseFloat(v);
                if (!isFinite(v)) return 0.2;
                return Math.max(0, Math.min(1, v));
            }

            function normalizeHex(hex) {
                var h = String(hex || '#000000').trim().toLowerCase();
                if (h.charAt(0) !== '#') h = '#' + h;
                if (/^#[0-9a-f]{3}$/.test(h)) {
                    h = '#' + h[1] + h[1] + h[2] + h[2] + h[3] + h[3];
                }
                if (!/^#[0-9a-f]{6}$/.test(h)) return '#000000';
                return h;
            }

            function hexToRgb01(hex) {
                var h = normalizeHex(hex).slice(1);
                var n = parseInt(h, 16);
                return {
                    r: ((n >> 16) & 255) / 255,
                    g: ((n >> 8) & 255) / 255,
                    b: (n & 255) / 255
                };
            }

            function normalizeState(raw) {
                var out = {};
                CONTROLLABLE.forEach(function (name) {
                    var src = (raw && raw[name]) || DEFAULT_STATE[name];
                    out[name] = {
                        hex: normalizeHex(src.hex),
                        a: clamp01(src.a)
                    };
                });
                return out;
            }

            function fingerprintState(state) {
                return CONTROLLABLE.map(function (name) {
                    return name + ':' + state[name].hex + ':' + state[name].a.toFixed(2);
                }).join('|');
            }

            function loadStoredState() {
                try {
                    var raw = window.localStorage.getItem(storageKey);
                    if (!raw) return normalizeState(DEFAULT_STATE);
                    return normalizeState(JSON.parse(raw));
                } catch (err) {
                    return normalizeState(DEFAULT_STATE);
                }
            }

            function saveState(state) {
                try {
                    window.localStorage.setItem(storageKey, JSON.stringify(normalizeState(state)));
                } catch (err) {}
            }

            function readUiState() {
                var state = {};
                CONTROLLABLE.forEach(function (name) {
                    var colorEl = box.querySelector('.robot-led-color[data-led="' + name + '"]');
                    var intensityEl = box.querySelector('.robot-led-intensity[data-led="' + name + '"]');
                    state[name] = {
                        hex: normalizeHex(colorEl ? colorEl.value : '#000000'),
                        a: clamp01(intensityEl ? intensityEl.value : 0.2)
                    };
                });
                return state;
            }

            function writeUiState(state, opts) {
                opts = opts || {};
                syncingUi = true;
                CONTROLLABLE.forEach(function (name) {
                    var colorEl = box.querySelector('.robot-led-color[data-led="' + name + '"]');
                    var intensityEl = box.querySelector('.robot-led-intensity[data-led="' + name + '"]');
                    if (colorEl) colorEl.value = state[name].hex;
                    if (intensityEl) intensityEl.value = String(state[name].a);
                });
                syncingUi = false;
                highlightMatchingPreset(state);
                if (opts.persist !== false) saveState(state);
            }

            function statesEqual(a, b) {
                return fingerprintState(a) === fingerprintState(b);
            }

            function highlightMatchingPreset(state) {
                var match = null;
                Object.keys(PRESETS).forEach(function (name) {
                    if (statesEqual(state, normalizeState(PRESETS[name]))) match = name;
                });
                Array.prototype.forEach.call(presetBtns, function (btn) {
                    btn.classList.toggle('is-active', btn.getAttribute('data-preset') === match);
                });
            }

            function buildMessageFromState(state) {
                var byName = {
                    front_left: { r: 0, g: 0, b: 0, a: 0 },
                    back_left: { r: 0, g: 0, b: 0, a: 0 },
                    middle: { r: 0, g: 0, b: 0, a: 0 },
                    back_right: { r: 0, g: 0, b: 0, a: 0 },
                    front_right: { r: 0, g: 0, b: 0, a: 0 }
                };
                CONTROLLABLE.forEach(function (name) {
                    var rgb = hexToRgb01(state[name].hex);
                    byName[name] = { r: rgb.r, g: rgb.g, b: rgb.b, a: state[name].a };
                });
                return {
                    rgb_vals: LED_ORDER.map(function (name) {
                        return byName[name];
                    })
                };
            }

            function publishState(state, statusText) {
                if (!ledPub || !bridgeReady || typeof ROSLIB === 'undefined') {
                    setStatus('Waiting for bridge…', 'is-wait');
                    return false;
                }
                var normalized = normalizeState(state);
                ledPub.publish(new ROSLIB.Message(buildMessageFromState(normalized)));
                lastAppliedFingerprint = fingerprintState(normalized);
                saveState(normalized);
                highlightMatchingPreset(normalized);
                setStatus(statusText || 'Synced', 'is-on');
                return true;
            }

            function publishNow(statusText) {
                return publishState(readUiState(), statusText || 'Pattern sent');
            }

            function schedulePublish() {
                if (syncingUi) return;
                if (publishTimer) clearTimeout(publishTimer);
                publishTimer = setTimeout(function () {
                    publishTimer = null;
                    var state = readUiState();
                    saveState(state);
                    highlightMatchingPreset(state);
                    publishNow('Pattern sent');
                }, 40);
            }

            function applyPreset(name) {
                var preset = PRESETS[name];
                if (!preset) return;
                writeUiState(normalizeState(preset), { persist: true });
                schedulePublish();
            }

            function bindPublisher() {
                var ros = getRos();
                if (!ros || typeof ROSLIB === 'undefined') return false;
                if (!ledPub) {
                    ledPub = new ROSLIB.Topic({
                        ros: ros,
                        name: topicName,
                        messageType: 'duckietown_msgs/LEDPattern',
                        queue_size: 1
                    });
                }
                bridgeReady = true;
                // Re-apply stored pattern so robot matches UI after every reload.
                var state = readUiState();
                var fp = fingerprintState(state);
                if (fp === lastAppliedFingerprint) {
                    setStatus('Synced', 'is-on');
                    return true;
                }
                publishState(state, 'Synced on load');
                return true;
            }

            Array.prototype.forEach.call(presetBtns, function (btn) {
                btn.addEventListener('click', function () {
                    applyPreset(btn.getAttribute('data-preset'));
                });
            });

            // Restore last commanded pattern into the UI before wiring colour events.
            writeUiState(loadStoredState(), { persist: false });

            Array.prototype.forEach.call(colorInputs, function (el) {
                el.addEventListener('input', schedulePublish);
                el.addEventListener('change', schedulePublish);
            });
            Array.prototype.forEach.call(intensityInputs, function (el) {
                el.addEventListener('input', schedulePublish);
                el.addEventListener('change', schedulePublish);
            });

            // Keep multiple Mission Control tabs consistent.
            window.addEventListener('storage', function (evt) {
                if (evt.key !== storageKey || !evt.newValue) return;
                try {
                    var state = normalizeState(JSON.parse(evt.newValue));
                    writeUiState(state, { persist: false });
                    if (bridgeReady) publishState(state, 'Synced');
                } catch (err) {}
            });

            setStatus('Waiting for bridge…', 'is-wait');
            $(document).on(connectedEvt, function () {
                bindPublisher();
            });
            setTimeout(function () { bindPublisher(); }, 100);
            setTimeout(function () { bindPublisher(); }, 800);
        })();
        </script>
        <?php
    }
}
?>
