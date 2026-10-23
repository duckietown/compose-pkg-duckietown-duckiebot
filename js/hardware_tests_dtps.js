/**
 * Hardware tests over DTPS.
 *
 * The robot's drivers run the hardware tests themselves and expose them on the DTPS switchboard
 * (`<queue>/in` to start a test, `<queue>/out` for the result). The sensors are tested by showing
 * their live data. This module replaces the ROS services (`<node>/test/description|run`) that the
 * ROS 1 interface used to expose for the dashboard, so the Components tab works regardless of
 * which ROS stack (if any) is running.
 *
 * Tests are looked up by the `test_service_name` reported by the health API for each component.
 * The descriptions are the ones the ROS 1 wrappers (dt-ros-interface) used to serve.
 *
 * Requires: dtps_client.js, hardware_test_utils.js (json_to_html), duckiebot_imu_game.js (IMU).
 */
(function () {
    "use strict";
    if (window.DT_HW_TESTS !== undefined) return;  // already loaded

    const DOCS_BASE_URL = "https://docs.duckietown.com/ente/opmanual-duckiebot/operations/dashboard/user_hardware_testing_tools.html#";
    // seconds added to a test's own duration before giving up on its result
    const TIMEOUT_BUFFER = 3;
    // time given to the switchboard to replay the current value of a result queue
    const SETTLE_MS = 400;

    function ul(items) {
        return "<ul>" + items.map((item) => "<li>" + item + "</li>").join("") + "</ul>";
    }

    function logsCommand(container) {
        return ul([
            "On your laptop, run the following command to save the logs.",
            "Replace the <code>[path/to/save]</code> to the directory path where you would like to save the logs.",
            `<code>docker -H [ROBOT_NAME].local logs ${container} > [path/to/save/]logs-${container}.txt</code>`,
        ]);
    }

    // --- test definitions -----------------------------------------------------------------------

    function wheelTest(side) {
        const duration = 3, speed = 0.5;
        return {
            kind: "run",
            test_id: `Wheel (${side})`,
            queue: `actuator/wheels/base/test/${side}`,
            timeout: duration,
            data: {info_str: side, speed: speed, duration: duration},
            container: "driver-wheels",
            preparation: ul([
                "Place your Duckiebot upside down on a flat surface with its wheels in the air.",
                "When you run the test, the wheels will start to spin.",
            ]),
            expectation: ul([
                `The ${side} motor should start spinning.`,
                `In about ${duration} seconds, it should stop moving.`,
            ]),
        };
    }

    function ledTest(side, ledIds) {
        const fadeIn = 1, duration = 3, fadeOut = 1;
        return {
            kind: "run",
            test_id: `LEDs (${side})`,
            queue: `actuator/lights/base/test/${side}`,
            timeout: fadeIn + duration + fadeOut,
            data: {led_ids: ledIds, fade_in_duration: fadeIn, duration: duration, fade_out_duration: fadeOut},
            container: "driver-lights",
            preparation: ul([
                `Place your Duckiebot on a flat surface in a position that allows you to see the ${side} LEDs.`,
            ]),
            expectation: ul([
                "The Duckiebot LEDs should start shining.",
                "The LEDs should show a smooth transition of these colors: RED -> YELLOW -> GREEN -> BLUE -> PURPLE -> RED.",
                `In about ${fadeIn + duration + fadeOut} seconds, they should be off.`,
            ]),
        };
    }

    function encoderTest(side) {
        return {
            kind: "stream",
            test_id: `Wheel Encoder (${side})`,
            stream: {path: `sensor/wheel_encoder/${side}/ticks`, render: "ticks"},
            container: "driver-wheel-encoders",
            preparation: ul([
                "Put your Duckiebot upside down, where you can reach and turn the wheels by hand.",
            ]),
            expectation: ul([
                "Once your start the test, a <strong>Tick value</strong> field will appear below.",
                `When you turn the ${side} wheel, if the value changes according to your movement rate, the test is passed.`,
                "(The change of directions would not be reflected.)",
            ]),
        };
    }

    const DISPLAY_TEXT = " Testing... ", DISPLAY_DURATION = 5;
    const BUTTON_BLINK_SECS = 3;

    const TESTS = {
        "wheels_driver_node/tests/left": wheelTest("left"),
        "wheels_driver_node/tests/right": wheelTest("right"),
        // LED ids: 0 front-left, 1 middle, 2 front-right, 3 back-left, 4 back-right
        "led_driver_node/tests/front": ledTest("front", [0, 2]),
        "led_driver_node/tests/back": ledTest("back", [3, 4]),
        "left_wheel_encoder_driver_node/test": encoderTest("left"),
        "right_wheel_encoder_driver_node/test": encoderTest("right"),
        "display_driver_node/test": {
            kind: "run",
            test_id: "Display",
            queue: "actuator/display/interaction_plate/test",
            timeout: DISPLAY_DURATION,
            data: {text: DISPLAY_TEXT, duration: DISPLAY_DURATION},
            container: "driver-display",
            preparation: ul(["Place your Duckiebot on a flat surface with the top screen visible."]),
            expectation: ul([
                `Once the test is started, the top display should show: <strong>${DISPLAY_TEXT}</strong>.`,
                `In about <strong>${DISPLAY_DURATION}</strong> seconds, the test page should disappear, and the homepage should be shown again.`,
            ]),
        },
        "button_driver_node/test": {
            kind: "run",
            test_id: "Top button",
            queue: "sensor/power_button/interaction_plate/test",
            // the test waits for the user to press the button
            timeout: 60,
            data: {led_blink_secs: BUTTON_BLINK_SECS, led_blink_hz: 1},
            container: "driver-power-button",
            preparation: ul(["Place your Duckiebot on a flat surface and locate the power button on the top plate."]),
            expectation: ul([
                `You should see the LED in the power button blink for about ${BUTTON_BLINK_SECS} seconds.`,
                "When the LED <strong>stops</strong> blinking, press and release the button to complete the test.",
                "Try performing this test several times to verify that pressing the power button terminates the test promptly.",
            ]),
        },
        "camera_node/test": {
            kind: "stream",
            test_id: "Camera",
            stream: {path: "sensor/camera/front_center/jpeg", render: "image"},
            container: "driver-camera",
            preparation: ul([
                "Place the Duckiebot on a flat surface within your reach, and make sure that the lens cap has been removed from the camera.",
            ]),
            expectation: ul([
                "You should see a live camera stream from your Duckiebot displayed below.",
                "Move your Duckiebot in a few directions and confirm that the camera stream is updating with the camera motion.",
            ]),
        },
        "front_center_tof_driver_node/test": {
            kind: "stream",
            test_id: "Time-of-Flight (front_center)",
            stream: {path: "sensor/time_of_flight/front_center/range", render: "range"},
            container: "driver-tof",
            preparation: ul([
                "Place your Duckiebot on a flat surface with at least one meter of empty space in front of the Time-of-Flight sensor on the front bumper.",
            ]),
            expectation: ul([
                "Once your start the test, a <strong>Range:</strong> field will appear below.",
                "When you move your hand closer and farther to the <strong>front center</strong> ToF, the range reading should change accordingly, i.e. moving closer leads to a smaller value, and farther with a greater value.",
                "The data below should show (in red color) <em>Out of range</em>.",
            ]),
        },
        "imu_node/test": {
            kind: "stream",
            test_id: "IMU",
            stream: {path: "sensor/imu/base/all", render: "imu"},
            container: "driver-imu",
            extra: [{key: "Success Criterion", type: "string", value: "Did the plane move according to your Duckiebot's movements?"}],
            preparation: ul(["Place your Duckiebot on a flat surface within reach."]),
            expectation: ul([
                "Once the test starts, a modal will pop up with a game.",
                "You will see a flat plane reflecting your Duckiebot's orientation - your objective is to move the ball while keeping it on the plane.",
                "To play, control the plane by <strong>picking up and tilting</strong> your Duckiebot.",
                "The test is successful if the plane moves naturally with the rotation of your Duckiebot.",
            ]),
        },
        "ros_http_api_node/tests/battery": {
            kind: "health",
            test_id: "Duckiebattery",
            check: "battery",
            container: "device-health",
            preparation: ul(["Place your Duckiebot on a flat surface with the charging cable plugged in."]),
            expectation: ul([
                "The Duckiebattery firmware version should be at least <code>2.0.2</code>.",
                "The PCB version should be at least <code>16</code>.",
                "Run the test first with the charging cable plugged in.",
                "Then unplug the charging cable, wait 5 seconds and rerun the test.",
                "If the versions and charging state are correct for both tests, you can then mark the test as Success.",
            ]),
            logs: ul([
                "On your laptop, run the following command to save the logs.",
                "Replace the <code>[path/to/save]</code> to the directory path where you would like to save the logs.",
                "<code>docker -H [ROBOT_NAME].local logs device-health > [path/to/save/]logs-db-device-health.txt</code>",
                "Also on your laptop, run the following commands and save the logs in the terminal to text files.",
                "<code>dts duckiebot battery info [ROBOT_NAME]</code>",
                "<code>dts duckiebot battery check_firmware [ROBOT_NAME]</code>",
            ]),
        },
        "ros_http_api_node/tests/wifi": {
            kind: "health",
            test_id: "USB WiFi Dongle",
            check: "wifi",
            container: "device-health",
            preparation: ul(["Make sure the USB Wifi dongle is plugged in to your Duckiebot and is blinking."]),
            expectation: ul(["The IP address of the WiFi network interface should be displayed below."]),
            logs: ul([
                "On your laptop, run the following command to save the logs.",
                "Replace the <code>[path/to/save]</code> to the directory path where you would like to save the logs.",
                "<code>ssh duckie@[ROBOT_NAME].local ifconfig > [path/to/save/]logs-network-ifconfig.txt</code>",
                "(You might need to provide the password to your Duckiebot when prompted.)",
            ]),
        },
    };

    // --- description --------------------------------------------------------------------------

    function describe(spec) {
        const panelId = "getting-help-panel-" + spec.test_id.replace(/[^a-zA-Z0-9-_:]/g, "_");
        const help = [
            "Demo videos and FAQs",
            ul([
                `<a href='${DOCS_BASE_URL}demos-of-the-hardware-tests'><strong>How-to</strong> series videos</a>`,
                `<a href='${DOCS_BASE_URL}faqs-reporting-problems-getting-help'>FAQs and getting help</a>`,
            ]) + "<p style='font-size: 8pt'>(In case of broken links, please report on the Duckietown Slack.)</p>",
            "Logs Gathering (in case of errors)",
            spec.logs || logsCommand(spec.container),
        ].join("");
        return {
            type: "object",
            parameters: [
                {key: "Preparation", type: "html", value: spec.preparation},
                {key: "Expected Outcomes", type: "html", value: spec.expectation},
                {key: "How to run", type: "html", value: ul(["Click on the <strong>Run the test</strong> button below."])},
                {
                    key: "Getting help with issues",
                    type: "html",
                    value: `<ul><li><button class="btn" data-toggle="collapse" data-target="#${panelId}">Click to toggle the information</button></li></ul>` +
                        `<div class="panel panel-default collapse" id="${panelId}"><div class="panel-body">${help}</div></div>`,
                },
            ],
        };
    }

    // --- running ------------------------------------------------------------------------------

    // run a test implemented by a driver: publish a request on `<queue>/in`, wait for `<queue>/out`
    function runDriverTest(spec, dtps) {
        return new Promise((resolve) => {
            let baseline = -1;
            let armed = false, done = false, timer = null;
            const finish = (result) => {
                if (done) return;
                done = true;
                clearTimeout(timer);
                subscription.close();
                resolve(result);
            };
            const subscription = dtps.subscribe(spec.queue + "/out", (message, meta) => {
                if (!armed) {
                    // the current value, replayed by the switchboard on connection: an old result
                    baseline = Math.max(baseline, meta.index);
                    return;
                }
                if (meta.index <= baseline) return;
                const data = (message && message.data) || {};
                finish({
                    success: data.success === true,
                    response: {type: "object", parameters: data.lst_blocks || []},
                });
            }, {
                onStatus: (status) => {
                    if (status !== "connected" || armed) return;
                    setTimeout(async () => {
                        armed = true;
                        const request = {
                            header: DTPS.header(),
                            data: Object.assign({test_id: spec.test_id, test_timeout: spec.timeout}, spec.data),
                        };
                        try {
                            await dtps.publish(spec.queue + "/in", request);
                        } catch (error) {
                            finish({success: false, error: "Could not start the test: " + error.message});
                            return;
                        }
                        timer = setTimeout(() => finish({
                            success: false,
                            error: `The robot did not report a result within ${spec.timeout + TIMEOUT_BUFFER} seconds.`,
                        }), (spec.timeout + TIMEOUT_BUFFER) * 1000);
                    }, SETTLE_MS);
                },
            });
        });
    }

    async function _getJSON(path) {
        const response = await fetch(window.location.origin + path, {cache: "no-store"});
        if (!response.ok) throw new Error("HTTP " + response.status);
        return await response.json();
    }

    // checks served by the health API
    async function runHealthCheck(spec) {
        try {
            if (spec.check === "battery") {
                const info = await _getJSON("/health/battery/info/");
                const status = await _getJSON("/health/battery/");
                return {
                    success: true,
                    response: {type: "object", parameters: [{
                        key: "Duckiebattery status", type: "html", value: ul([
                            `version: <code>${info.version}</code>`,
                            `boot/pcb_version: <code>${info.boot.pcb_version}</code>`,
                            `battery/charging: <strong>${status.battery.charging}</strong>`,
                        ]),
                    }]},
                };
            }
            if (spec.check === "wifi") {
                const wifi = await _getJSON("/health/network/wifi/");
                return {
                    success: wifi.exists === true,
                    response: {type: "object", parameters: [{
                        key: `Getting the IP of ${wifi.interface}:`, type: "string",
                        value: wifi.exists ? (wifi.ipv4 || "None") : `interface ${wifi.interface} not found`,
                    }]},
                };
            }
        } catch (error) {
            return {success: false, error: "Could not read the robot status: " + error.message};
        }
        return {success: false, error: "Unknown check: " + spec.check};
    }

    /** Runs a test; resolves to {success, response?, error?}. Stream tests resolve immediately. */
    function run(spec, dtps) {
        if (spec.kind === "run") return runDriverTest(spec, dtps);
        if (spec.kind === "health") return runHealthCheck(spec);
        if (spec.kind === "stream") {
            return Promise.resolve({success: true, response: {type: "stream", parameters: spec.extra || []}});
        }
        return Promise.resolve({success: false, error: "Unknown test kind: " + spec.kind});
    }

    // --- live data ----------------------------------------------------------------------------

    /** Shows the live data of a stream test inside `#<update_id>` until the modal is closed. */
    function stream(spec, dtps, robotName, updateId, modalId) {
        const target = () => $("#" + updateId);
        const render = spec.stream.render;
        if (render === "imu") {
            duckiebot_imu_funcs.dtps_setup(dtps, robotName, spec.stream.path);
            $("#modal_IMU").modal("show");
            $("#modal_IMU").one("hidden.bs.modal", () => duckiebot_imu_funcs.cleanup());
            return;
        }
        target().html("<h4>Live Data</h4><p>Waiting for data...</p>");
        // images go into a single <img>, swapped only once each frame is decoded (no flickering)
        let image = null;
        const view = (render === "image") ? DTPS.imageView((url) => {
            if (image === null) {
                // downsized, to make the modal appear fully without scrolling (640 x 480 -> 320 x 240)
                image = $("<img alt='Live stream' width='320' height='240'/>");
                target().html("<h4>Live Data</h4>").append(image);
            }
            image.attr("src", url);
        }) : null;
        const subscription = dtps.subscribe(spec.stream.path, (message) => {
            if (view !== null) {
                view.show(message);
                return;
            }
            let html = "<h4>Live Data</h4>";
            if (render === "ticks") {
                html += "<p><strong>Tick value: </strong>" + message.data + "</p>";
            } else if (render === "range") {
                if (message.data === null || message.data === undefined) {
                    html += "<p style='color: red;'>Out of range</p>";
                } else {
                    html += "<p><strong>Range: </strong>" + (message.data * 100.0).toFixed(1) + " &nbsp;cm</p>";
                }
            }
            target().html(html);
        }, {maxHz: render === "image" ? 10 : 20});
        $("#" + modalId).one("hidden.bs.modal", () => {
            subscription.close();
            if (view !== null) view.close();
        });
    }

    window.DT_HW_TESTS = {
        /** The DTPS test for a component's `test_service_name`, or null. */
        get: (testServiceName) => TESTS.hasOwnProperty(testServiceName) ? TESTS[testServiceName] : null,
        describe: describe,
        run: run,
        stream: stream,
    };
})();
