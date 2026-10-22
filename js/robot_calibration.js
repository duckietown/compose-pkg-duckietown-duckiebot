/**
 * Robot calibration for the dashboard, read through the files API (no ROS needed).
 *
 *   RobotCalibration.load("<robot_name>").then((c) => RobotCalibration.toPWM(c, v, omega));
 *
 * Sources, robot-specific file first, then `default.yaml`, then built-in defaults:
 *   - /data/config/calibrations/kinematics/: gain, trim, k, limit (and legacy baseline/radius)
 *   - /data/config/calibrations/robot/: wheel_baseline, wheel_radius, encoder_resolution
 *     (the robot-level geometry wins over the legacy values in the kinematics calibration)
 */
(function () {
    "use strict";
    if (window.RobotCalibration !== undefined) return;  // already loaded

    const DEFAULTS = {gain: 1.0, trim: 0.0, k: 27.0, limit: 1.0, baseline: 0.1, radius: 0.0318, resolution: 135};

    // the calibration files are flat `key: value` YAML maps
    function parseFlatYAML(text) {
        const out = {};
        for (const raw of text.split("\n")) {
            const line = raw.replace(/#.*$/, "").trim();
            const match = line.match(/^([A-Za-z0-9_]+)\s*:\s*(.+)$/);
            if (!match) continue;
            const value = Number(match[2]);
            out[match[1]] = isNaN(value) ? match[2].replace(/^['"]|['"]$/g, "") : value;
        }
        return out;
    }

    // whether the robot-specific calibration file exists (null if the files API cannot tell)
    async function _robotFileExists(kind) {
        try {
            const response = await fetch(window.location.origin + `/files/calibration/info/${kind}`, {cache: "no-store"});
            if (!response.ok) return null;
            return (await response.json()).exists === true;
        } catch (error) {
            return null;
        }
    }

    async function _load(kind, robotName) {
        // skip the robot-specific file when it does not exist (avoids a 404 in the console)
        const names = (await _robotFileExists(kind)) === false ? ["default"] : [robotName, "default"];
        for (const name of names) {
            const url = window.location.origin + `/files/data/config/calibrations/${kind}/${name}.yaml`;
            try {
                const response = await fetch(url, {cache: "no-store"});
                if (response.ok) return parseFlatYAML(await response.text());
            } catch (error) {
                console.warn(`calibration: cannot read ${url}`, error);
            }
        }
        return {};
    }

    async function load(robotName) {
        const kinematics = await _load("kinematics", robotName);
        const robot = await _load("robot", robotName);
        const c = Object.assign({}, DEFAULTS);
        for (const key of ["gain", "trim", "k", "limit", "baseline", "radius"]) {
            if (typeof kinematics[key] === "number") c[key] = kinematics[key];
        }
        if (typeof robot.wheel_baseline === "number") c.baseline = robot.wheel_baseline;
        if (typeof robot.wheel_radius === "number") c.radius = robot.wheel_radius;
        if (typeof robot.encoder_resolution === "number") c.resolution = robot.encoder_resolution;
        return c;
    }

    function _clip(value, limit) {
        return Math.max(-limit, Math.min(limit, value));
    }

    /** Wheel PWM for a linear (m/s) and angular (rad/s) velocity; same model as the keyboard controller. */
    function toPWM(c, v, omega) {
        const x = 0.5 * omega * c.baseline;
        const y = 1.0 / (c.radius * c.k);
        return {
            left: _clip((c.gain - c.trim) * (v - x) * y, c.limit),
            right: _clip((c.gain + c.trim) * (v + x) * y, c.limit),
        };
    }

    window.RobotCalibration = {DEFAULTS: DEFAULTS, load: load, toPWM: toPWM, parseFlatYAML: parseFlatYAML};
})();
