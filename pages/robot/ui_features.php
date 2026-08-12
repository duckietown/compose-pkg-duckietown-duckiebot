<?php
/**
 * Robot dashboard UI feature flags
 * --------------------------------
 * Central switches for the Duckiebot robot-page redesign work.
 *
 * WHY THIS FILE EXISTS
 *   Several UI pieces were redesigned (Overview meters, Architecture layout,
 *   Power controls, simplified Settings). Rather than permanently deleting the
 *   previous implementations, we:
 *     1. keep legacy implementations under pages/robot/legacy/, and
 *     2. gate behaviour with the helpers below.
 *
 * HOW TO TOGGLE
 *   Preferred (no code edit): Dashboard package setting under
 *   duckietown_duckiebot → robot_ui → <flag_name>
 *
 *   Override in PHP (for local experiments) before including this file:
 *     define('ROBOT_UI_SHOW_POWER_CONTROLS', true);
 *
 * DEFAULTS lean toward the simplified robot-operator UI.
 *
 * @see pages/robot/legacy/README.md
 */

use system\classes\Core;

if (!class_exists('RobotUIFeatures', false)) {

class RobotUIFeatures
{
    const PACKAGE = 'duckietown_duckiebot';
    const SETTINGS_GROUP = 'robot_ui';

    /**
     * Resolve a robot_ui flag.
     * Order: PHP define ROBOT_UI_<NAME> → package setting → $default.
     *
     * @param string $name Upper-snake name without ROBOT_UI_ prefix, e.g. SHOW_POWER_CONTROLS
     * @param string $setting_key Setting key under robot_ui/, e.g. show_power_controls
     * @param mixed  $default
     * @return mixed
     */
    private static function resolve($name, $setting_key, $default)
    {
        $define = 'ROBOT_UI_' . $name;
        if (defined($define)) {
            return constant($define);
        }
        // Core::getSetting($key, $package, $default) - nested keys use "/".
        return Core::getSetting(
            self::SETTINGS_GROUP . '/' . $setting_key,
            self::PACKAGE,
            $default
        );
    }

    private static function flag($name, $setting_key, $default)
    {
        return (bool) self::resolve($name, $setting_key, $default);
    }

    /**
     * Show the orange Power (Shutdown / Reboot) dropdown in the robot header.
     *
     * Default: false - hidden for the simplified operator UI (easy to trigger
     * accidentally; power actions remain available via other robot tooling).
     *
     * Setting: robot_ui/show_power_controls
     * Define:  ROBOT_UI_SHOW_POWER_CONTROLS
     */
    public static function show_power_controls()
    {
        return self::flag('SHOW_POWER_CONTROLS', 'show_power_controls', false);
    }

    /**
     * When Power controls are enabled, require an authenticated dashboard user.
     *
     * Default: true
     * Setting: robot_ui/power_requires_login
     * Define:  ROBOT_UI_POWER_REQUIRES_LOGIN
     */
    public static function power_requires_login()
    {
        return self::flag('POWER_REQUIRES_LOGIN', 'power_requires_login', true);
    }

    /**
     * Use the redesigned Overview (thermometer + horizontal meters).
     * When false, loads pages/robot/legacy/info_chartjs_overview.php
     * (Chart.js donut / semi-dial gauges).
     *
     * Default: true
     * Setting: robot_ui/modern_overview
     * Define:  ROBOT_UI_MODERN_OVERVIEW
     */
    public static function modern_overview()
    {
        return self::flag('MODERN_OVERVIEW', 'modern_overview', true);
    }

    /**
     * Use the redesigned Architecture tab (1040px grid + rosbridge fallback).
     * When false, loads pages/robot/legacy/architecture_fullbleed.php
     * (full-bleed table layout, ROS HTTP API only).
     *
     * Default: true
     * Setting: robot_ui/modern_architecture
     * Define:  ROBOT_UI_MODERN_ARCHITECTURE
     */
    public static function modern_architecture()
    {
        return self::flag('MODERN_ARCHITECTURE', 'modern_architecture', true);
    }

    /**
     * When modern Architecture is on and /ros HTTP API (port 8084) is down,
     * build the graph from rosbridge /rosapi services.
     *
     * Default: true
     * Setting: robot_ui/architecture_rosbridge_fallback
     * Define:  ROBOT_UI_ARCHITECTURE_ROSBRIDGE_FALLBACK
     */
    public static function architecture_rosbridge_fallback()
    {
        return self::flag(
            'ARCHITECTURE_ROSBRIDGE_FALLBACK',
            'architecture_rosbridge_fallback',
            true
        );
    }

    /**
     * Hide advanced Robot Settings groups (system/robot/autolab) unless
     * core developer_mode is enabled. This mirrors the simplified settings UX.
     *
     * Default: true (always apply the simplification)
     * Setting: robot_ui/simplify_robot_settings
     * Define:  ROBOT_UI_SIMPLIFY_ROBOT_SETTINGS
     */
    public static function simplify_robot_settings()
    {
        return self::flag('SIMPLIFY_ROBOT_SETTINGS', 'simplify_robot_settings', true);
    }

    /**
     * Whether Power controls should render for the current request.
     */
    public static function should_render_power_controls()
    {
        if (!self::show_power_controls()) {
            return false;
        }
        if (self::power_requires_login() && !Core::isUserLoggedIn()) {
            return false;
        }
        return true;
    }
}

} // class_exists guard
