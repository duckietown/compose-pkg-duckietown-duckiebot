<?php
/**
 * Robot page shell (tabs + shared chrome).
 *
 * UI behaviour flags live in ui_features.php / configuration schema robot_ui/*.
 * Legacy tab implementations are kept under pages/robot/legacy/ - do not delete
 * them when iterating on the redesigned UI; flip the flags instead.
 */
use \system\classes\Core;
use \system\classes\Configuration;
use \system\packages\duckietown_duckiebot\Duckiebot;

require_once __DIR__ . '/ui_features.php';

$dbot_hostname = Duckiebot::getDuckiebotHostname();
$dbot_name = Duckiebot::getDuckiebotName();
$user_logged_in = Core::isUserLoggedIn();
$display_name = $dbot_name ?: $dbot_hostname;
$navbar_title = trim((string) Core::getSetting('navbar_title'));
$repeat_page_title = (strcasecmp($display_name, $navbar_title) !== 0);

// Primary robot-operation tabs first; secondary / deeper tools after.
// (Former label "Info" was renamed to "Overview" for operator clarity.)
$tabs = [
    'info' => [
        'name' => 'Overview',
        'icon' => 'info-circle'
    ],
    'mission_control' => [
        'name' => 'Mission Control',
        'icon' => 'th-large'
    ],
    'components' => [
        'name' => 'Components',
        'icon' => 'puzzle-piece'
    ],
    'health' => [
        'name' => 'Health',
        'icon' => 'heartbeat'
    ],
    'architecture' => [
        'name' => 'Architecture',
        'icon' => 'sitemap'
    ],
    'calibrations' => [
        'name' => 'Calibrations',
        'icon' => 'crosshairs'
    ],
    'file_manager' => [
        'name' => 'File Manager',
        'icon' => 'folder'
    ],
    'portainer' => [
        'name' => 'Portainer',
        'icon' => 'cubes'
    ],
    'settings' => [
        'name' => 'Robot Settings',
        'icon' => 'sliders'
    ]
];

$DEFAULT_TAB = 'info';
$ACTIVE_TAB = Configuration::$ACTION ?? $DEFAULT_TAB;

if (!array_key_exists($ACTIVE_TAB, $tabs)){
    Core::redirectTo(Core::getURL('robot', $DEFAULT_TAB));
}

/**
 * Resolve which PHP file renders the active tab.
 * Modern (redesigned) pages are default; legacy snapshots remain selectable.
 */
$tab_include = sprintf('%s/tabs/%s/index.php', __DIR__, $ACTIVE_TAB);
if ($ACTIVE_TAB === 'info' && !RobotUIFeatures::modern_overview()) {
    $tab_include = __DIR__ . '/legacy/info_chartjs_overview.php';
} elseif ($ACTIVE_TAB === 'architecture' && !RobotUIFeatures::modern_architecture()) {
    $tab_include = __DIR__ . '/legacy/architecture_fullbleed.php';
}

$show_power = RobotUIFeatures::should_render_power_controls();
?>

<style type="text/css">
/*
 * Shared robot dashboard design tokens + chrome.
 * Type scale (px only, no pt):
 *   xs 10 | sm 11 | md 12 | lg 13 | xl 15 | title 18 | value 22
 */
.robot-page {
    --r-radius-sm: 6px;
    --r-radius-md: 10px;
    --r-radius-pill: 999px;
    --r-border: #e6e8eb;
    --r-surface: #f8f9fb;
    --r-text: #111827;
    --r-muted: #6b7280;
    --r-gap: 10px;
    --r-gap-lg: 12px;
    --r-max: 1040px;
    /* Status text colors darkened for AA on soft chip backgrounds */
    --r-ok: #047857;
    --r-ok-bg: #ecfdf3;
    --r-ok-border: #bbf7d0;
    --r-warn: #b45309;
    --r-warn-bg: #fffbeb;
    --r-warn-border: #fde68a;
    --r-bad: #b91c1c;
    --r-bad-bg: #fef2f2;
    --r-bad-border: #fecaca;
    --r-fill: #2c5686;
    --r-track: #eef0f3;
    --r-control-border: #8b929e;
    --r-ease: 160ms ease;
    /* Typography */
    --r-fs-xs: 10px;
    --r-fs-sm: 11px;
    --r-fs-md: 12px;
    --r-fs-lg: 13px;
    --r-fs-xl: 15px;
    --r-fs-title: 18px;
    --r-fs-value: 22px;
    --r-fs-icon: 24px;
    --r-fs-icon-lg: 40px;
    --r-fw-normal: 400;
    --r-fw-medium: 500;
    --r-fw-semibold: 600;
    --r-fw-bold: 700;
    --r-lh-tight: 1.15;
    --r-lh: 1.4;
    --r-tracking-label: 0.04em;
    --r-tracking-tight: -0.02em;
    color: var(--r-text);
    font-size: var(--r-fs-md);
    font-weight: var(--r-fw-normal);
    line-height: var(--r-lh);
}
.robot-page h2,
.robot-page h3,
.robot-page h4,
.robot-page h5,
.robot-page h6 {
    color: var(--r-text);
    line-height: var(--r-lh-tight);
}
.robot-page h2 { font-size: var(--r-fs-title); font-weight: var(--r-fw-semibold); }
.robot-page h3 { font-size: var(--r-fs-xl); font-weight: var(--r-fw-semibold); margin: 0 0 8px; }
.robot-page h4 { font-size: var(--r-fs-md); font-weight: var(--r-fw-semibold); margin: 0 0 8px; }
.robot-page h5,
.robot-page h6 { font-size: var(--r-fs-sm); font-weight: var(--r-fw-semibold); margin: 0 0 6px; }
.robot-label {
    font-size: var(--r-fs-sm);
    font-weight: var(--r-fw-semibold);
    color: var(--r-muted);
    text-transform: uppercase;
    letter-spacing: var(--r-tracking-label);
}
.robot-muted { color: var(--r-muted); font-size: var(--r-fs-sm); font-weight: var(--r-fw-medium); }
.robot-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: var(--r-fs-sm);
}

.robot-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--r-gap-lg);
    max-width: var(--r-max);
    width: 100%;
    margin: 0 auto 8px auto;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--r-border);
}
.robot-page-header h2 {
    margin: 0;
    font-size: var(--r-fs-title);
    font-weight: var(--r-fw-semibold);
    letter-spacing: var(--r-tracking-tight);
    line-height: var(--r-lh-tight);
}
.robot-page-header .robot-page-subtitle {
    display: block;
    margin-top: 4px;
    font-size: var(--r-fs-sm);
    font-weight: var(--r-fw-normal);
    color: var(--r-muted);
}
#_robot_tab_btns {
    max-width: var(--r-max);
    width: 100%;
    margin: 0 auto;
    border-bottom: 1px solid var(--r-border);
}
#_robot_tab_btns > li > a {
    color: #555;
    border-radius: var(--r-radius-sm) var(--r-radius-sm) 0 0;
    padding: 7px 10px;
    font-size: var(--r-fs-md);
    font-weight: var(--r-fw-normal);
    transition: color var(--r-ease), background-color var(--r-ease), border-color var(--r-ease);
}
#_robot_tab_btns > li > a:hover {
    color: var(--r-text);
    background: var(--r-surface);
}
#_robot_tab_btns > li.active > a,
#_robot_tab_btns > li.active > a:hover,
#_robot_tab_btns > li.active > a:focus {
    color: var(--r-text);
    font-weight: var(--r-fw-semibold);
    background: #fff;
    border-color: var(--r-border) var(--r-border) transparent;
}
#_robot_tab_btns > li > a:focus-visible {
    outline: 2px solid rgba(44, 86, 134, 0.35);
    outline-offset: 1px;
}
#_robot_tab_btns > li > a > i.fa {
    display: inline-block;
    width: 1.15em;
    margin-right: 2px;
    text-align: center;
    opacity: 0.55;
    font-size: 12px;
    line-height: 1;
}
#_robot_tab_btns > li.active > a > i.fa {
    opacity: 0.85;
}
#_logs_tab_container {
    max-width: var(--r-max);
    width: 100%;
    margin: 0 auto;
    padding-top: var(--r-gap-lg) !important;
}

.robot-status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--r-gap-lg);
    flex-wrap: wrap;
    max-width: var(--r-max);
    width: 100%;
    margin: 0 auto var(--r-gap) auto;
    padding: 8px 12px;
    background: var(--r-surface);
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-md);
    font-size: var(--r-fs-md);
    color: var(--r-muted);
    box-sizing: border-box;
}
.robot-status-bar .robot-status-bar-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
}
.robot-status-bar .robot-status-bar-item strong {
    color: var(--r-text);
    font-weight: var(--r-fw-semibold);
}
.robot-status-bar .robot-status-bar-tools {
    margin-left: auto;
    flex-wrap: wrap;
    gap: 6px;
}
.robot-status-bar .robot-bridge-pill,
.robot-bridge-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: var(--r-radius-pill);
    background: #fff;
    border: 1px solid var(--r-border);
    font-size: var(--r-fs-md);
    font-weight: var(--r-fw-semibold);
    color: var(--r-muted);
    transition: border-color var(--r-ease), background-color var(--r-ease), transform var(--r-ease);
}
.robot-bridge-pill.is-ok {
    border-color: var(--r-ok-border);
    background: var(--r-ok-bg);
    color: var(--r-ok);
}
.robot-bridge-pill.is-bad {
    border-color: var(--r-bad-border);
    background: var(--r-bad-bg);
    color: var(--r-bad);
}
.robot-bridge-pill.is-wait {
    border-color: var(--r-border);
    color: var(--r-muted);
}

/* Shared interactive affordances */
.robot-page .robot-interactive {
    cursor: pointer;
    transition: border-color var(--r-ease), box-shadow var(--r-ease), transform var(--r-ease), background-color var(--r-ease);
}
.robot-page .robot-interactive:hover {
    border-color: #d1d5db;
    box-shadow: 0 1px 2px rgba(17, 24, 39, 0.06);
}
.robot-page .robot-interactive:active {
    transform: scale(0.985);
}
.robot-page .robot-interactive:focus-visible {
    outline: 2px solid rgba(44, 86, 134, 0.4);
    outline-offset: 2px;
}

/*
 * Shared button system
 * --------------------
 * Preferred classes: .robot-btn + variant/size.
 * Bootstrap .btn* inside .robot-page are mapped to the same look so
 * legacy / JS-generated markup stays consistent without deleting it.
 */
.robot-page .robot-btn,
.robot-page .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin: 0;
    padding: 7px 12px;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-sm);
    background: #fff;
    color: var(--r-text);
    font-size: var(--r-fs-md);
    font-weight: var(--r-fw-semibold);
    line-height: 1.2;
    text-decoration: none;
    text-shadow: none;
    box-shadow: none;
    cursor: pointer;
    user-select: none;
    white-space: nowrap;
    vertical-align: middle;
    transition: background-color var(--r-ease), border-color var(--r-ease), color var(--r-ease), box-shadow var(--r-ease), transform var(--r-ease);
}
.robot-page .robot-btn:hover,
.robot-page .robot-btn:focus,
.robot-page .btn:hover,
.robot-page .btn:focus {
    background: var(--r-surface);
    border-color: #d1d5db;
    color: var(--r-text);
    text-decoration: none;
    outline: none;
}
.robot-page .robot-btn:active,
.robot-page .btn:active,
.robot-page .robot-btn.active,
.robot-page .btn.active {
    transform: scale(0.98);
    box-shadow: none;
}
.robot-page .robot-btn:focus-visible,
.robot-page .btn:focus-visible {
    outline: 2px solid rgba(44, 86, 134, 0.4);
    outline-offset: 2px;
}
.robot-page .robot-btn:disabled,
.robot-page .robot-btn.disabled,
.robot-page .btn:disabled,
.robot-page .btn.disabled,
.robot-page .btn[disabled] {
    opacity: 0.55;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

/* Variants */
.robot-page .robot-btn-primary,
.robot-page .btn-primary {
    background: var(--r-fill);
    border-color: var(--r-fill);
    color: #fff;
}
.robot-page .robot-btn-primary:hover,
.robot-page .robot-btn-primary:focus,
.robot-page .btn-primary:hover,
.robot-page .btn-primary:focus {
    background: #244a74;
    border-color: #244a74;
    color: #fff;
}
.robot-page .robot-btn-ghost,
.robot-page .btn-default,
.robot-page .btn-link {
    background: #fff;
    border-color: var(--r-border);
    color: var(--r-text);
    box-shadow: none;
}
.robot-page .robot-btn-ghost:hover,
.robot-page .btn-default:hover,
.robot-page .btn-link:hover {
    background: var(--r-surface);
    border-color: #d1d5db;
    color: var(--r-text);
    text-decoration: none;
}
.robot-page .btn-link {
    border-color: transparent;
    background: transparent;
    color: var(--r-fill);
}
.robot-page .btn-link:hover,
.robot-page .btn-link:focus {
    background: var(--r-surface);
    border-color: transparent;
    color: #244a74;
}
.robot-page .robot-btn-ok,
.robot-page .btn-success {
    background: var(--r-ok);
    border-color: var(--r-ok);
    color: #fff;
}
.robot-page .robot-btn-ok:hover,
.robot-page .btn-success:hover {
    background: #065f46;
    border-color: #065f46;
    color: #fff;
}
.robot-page .robot-btn-warn,
.robot-page .btn-warning {
    background: var(--r-warn);
    border-color: var(--r-warn);
    color: #fff;
}
.robot-page .robot-btn-warn:hover,
.robot-page .btn-warning:hover {
    background: #92400e;
    border-color: #92400e;
    color: #fff;
}
.robot-page .robot-btn-danger,
.robot-page .btn-danger {
    background: var(--r-bad);
    border-color: var(--r-bad);
    color: #fff;
}
.robot-page .robot-btn-danger:hover,
.robot-page .btn-danger:hover {
    background: #991b1b;
    border-color: #991b1b;
    color: #fff;
}
.robot-page .robot-btn-accent,
.robot-page .btn-info {
    background: #0e7490;
    border-color: #0e7490;
    color: #fff;
}
.robot-page .robot-btn-accent:hover,
.robot-page .btn-info:hover {
    background: #155e75;
    border-color: #155e75;
    color: #fff;
}

/* Sizes */
.robot-page .robot-btn-sm,
.robot-page .btn-sm {
    padding: 5px 10px;
    font-size: var(--r-fs-sm);
}
.robot-page .robot-btn-xs,
.robot-page .btn-xs {
    padding: 3px 8px;
    font-size: var(--r-fs-xs);
}

/*
 * Bootstrap Toggle uses .btn on the shell, On/Off labels, and handle.
 * Keep its absolute layout intact; do not apply robot button chrome.
 */
.robot-page .toggle.btn,
.robot-page .toggle-on.btn,
.robot-page .toggle-off.btn,
.robot-page .toggle-handle.btn {
    display: block;
    align-items: initial;
    justify-content: initial;
    gap: 0;
    margin: 0;
    transform: none;
    box-shadow: none;
    text-shadow: none;
    white-space: nowrap;
    user-select: none;
}
.robot-page .toggle.btn {
    position: relative;
    overflow: hidden;
    padding: 0;
    min-width: 52px;
    min-height: 28px;
    border-radius: var(--r-radius-sm);
    border: 1px solid var(--r-border);
    vertical-align: middle;
}
.robot-page .toggle.btn:hover,
.robot-page .toggle.btn:focus,
.robot-page .toggle.btn:active,
.robot-page .toggle.btn.active {
    transform: none;
    box-shadow: none;
}
.robot-page .toggle-on.btn,
.robot-page .toggle-off.btn {
    position: absolute;
    top: 0;
    bottom: 0;
    margin: 0;
    border: 0;
    border-radius: 0;
    padding-top: 0;
    padding-bottom: 0;
    font-size: var(--r-fs-sm);
    font-weight: var(--r-fw-semibold);
    line-height: 26px;
}
.robot-page .toggle-on.btn {
    left: 0;
    right: 50%;
    padding-right: 20px;
    background: var(--r-fill);
    border-color: var(--r-fill);
    color: #fff;
}
.robot-page .toggle-off.btn {
    left: 50%;
    right: 0;
    padding-left: 20px;
    background: var(--r-track);
    border-color: var(--r-border);
    color: var(--r-muted);
}
.robot-page .toggle-handle.btn {
    position: relative;
    margin: 0 auto;
    padding: 0;
    height: 100%;
    width: 0;
    border-width: 0 1px;
    border-style: solid;
    border-color: var(--r-border);
    border-radius: 0;
    background: #fff;
}
.robot-page .toggle.btn-primary,
.robot-page .toggle.btn-warning {
    background: #fff;
    color: inherit;
}

/*
 * Icon-only menus (Mission Control block ⋯, etc.) must stay ghost.
 * Mapping every .btn to a solid white chip made them look like broken squares
 * on dark / camera block backgrounds.
 */
.robot-page .block_renderer_menu_icon .btn,
.robot-page .block_renderer_header a.btn.dropdown-toggle {
    width: 28px;
    height: 28px;
    padding: 0;
    background: transparent;
    border-color: transparent;
    color: inherit;
    box-shadow: none;
    font-size: 16px;
}
.robot-page .block_renderer_menu_icon .btn:hover,
.robot-page .block_renderer_menu_icon .btn:focus,
.robot-page .block_renderer_header a.btn.dropdown-toggle:hover,
.robot-page .block_renderer_header a.btn.dropdown-toggle:focus {
    background: rgba(17, 24, 39, 0.08);
    border-color: transparent;
    color: inherit;
    box-shadow: none;
    transform: none;
}
.robot-page .block_renderer_menu_icon .btn:active,
.robot-page .block_renderer_header a.btn.dropdown-toggle:active,
.robot-page .block_renderer_header a.btn.dropdown-toggle.active {
    background: rgba(17, 24, 39, 0.12);
    transform: none;
}

/* Segmented control (maps Bootstrap button groups with data-toggle="buttons") */
.robot-page .robot-seg,
.robot-page .btn-group[data-toggle="buttons"] {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 4px;
    vertical-align: middle;
}
.robot-page .robot-seg .robot-seg-item,
.robot-page .btn-group[data-toggle="buttons"] > .btn {
    float: none;
    margin: 0;
    padding: 4px 8px;
    border-radius: var(--r-radius-sm) !important;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    font-size: var(--r-fs-sm);
    font-weight: var(--r-fw-semibold);
    box-shadow: none;
    text-shadow: none;
}
.robot-page .robot-seg .robot-seg-item:hover,
.robot-page .btn-group[data-toggle="buttons"] > .btn:hover {
    border-color: #9ca3af;
    background: #fff;
    color: #111827;
}
.robot-page .robot-seg .robot-seg-item.active,
.robot-page .robot-seg .robot-seg-item:active,
.robot-page .btn-group[data-toggle="buttons"] > .btn.active,
.robot-page .btn-group[data-toggle="buttons"] > .btn:active {
    background: var(--r-fill);
    border-color: var(--r-fill);
    color: #fff;
    box-shadow: none;
    transform: scale(0.98);
}

/* Compact action button groups (software update, power, etc.) */
.robot-page .btn-group:not([data-toggle="buttons"]) > .btn {
    border-radius: 0;
}
.robot-page .btn-group:not([data-toggle="buttons"]) > .btn:first-child {
    border-radius: var(--r-radius-sm) 0 0 var(--r-radius-sm);
}
.robot-page .btn-group:not([data-toggle="buttons"]) > .btn:last-child {
    border-radius: 0 var(--r-radius-sm) var(--r-radius-sm) 0;
}
.robot-page .btn-group:not([data-toggle="buttons"]) > .btn:only-child {
    border-radius: var(--r-radius-sm);
}
.robot-page .btn-group:not([data-toggle="buttons"]) > .btn + .btn {
    margin-left: -1px;
}

.robot-tip {
    position: relative;
}
.robot-tip-bubble {
    position: absolute;
    z-index: 40;
    left: 50%;
    bottom: calc(100% + 8px);
    transform: translateX(-50%) translateY(4px);
    min-width: 180px;
    max-width: 260px;
    padding: 8px 10px;
    border-radius: var(--r-radius-sm);
    background: #1f2937;
    color: #f9fafb;
    font-size: var(--r-fs-sm);
    font-weight: var(--r-fw-medium);
    line-height: 1.35;
    text-align: left;
    text-transform: none;
    letter-spacing: 0;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
    opacity: 0;
    pointer-events: none;
    transition: opacity var(--r-ease), transform var(--r-ease);
    white-space: normal;
}
.robot-tip-bubble::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border: 5px solid transparent;
    border-top-color: #1f2937;
}
.robot-tip.is-open .robot-tip-bubble,
.robot-tip:hover .robot-tip-bubble,
.robot-tip:focus-within .robot-tip-bubble {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
    pointer-events: auto;
}
.robot-tip-bubble strong {
    display: block;
    margin-bottom: 2px;
    font-size: var(--r-fs-sm);
    color: #fff;
}

.robot-section {
    max-width: var(--r-max);
    margin: 0 auto;
    width: 100%;
}
.robot-card {
    background: #fff;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-md);
    padding: 12px 14px;
    box-sizing: border-box;
}
.robot-card-title {
    margin: 0 0 8px 0;
    font-size: var(--r-fs-sm);
    font-weight: var(--r-fw-semibold);
    color: var(--r-muted);
    text-transform: uppercase;
    letter-spacing: var(--r-tracking-label);
}
.robot-hint {
    margin: 0 0 var(--r-gap-lg) 0;
    font-size: var(--r-fs-lg);
    color: var(--r-muted);
    line-height: var(--r-lh);
}
.robot-empty-state {
    margin: 24px auto;
    max-width: var(--r-max);
    text-align: center;
    font-size: var(--r-fs-xl);
    font-weight: var(--r-fw-semibold);
    color: var(--r-muted);
    line-height: var(--r-lh);
}

.robot-auth-gate {
    max-width: 480px;
    margin: 40px auto 24px;
    padding: 28px 28px 24px;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-md);
    background: #fff;
    text-align: center;
}
.robot-auth-gate-icon {
    font-size: 28px;
    line-height: 1;
    color: var(--r-fill);
    margin-bottom: 10px;
}
.robot-auth-gate h3 {
    margin: 0 0 8px;
    font-size: var(--r-fs-title);
    font-weight: var(--r-fw-semibold);
    letter-spacing: var(--r-tracking-tight);
    color: var(--r-text);
}
.robot-auth-gate p {
    margin: 0 0 18px;
    font-size: var(--r-fs-lg);
    color: var(--r-muted);
    line-height: var(--r-lh);
}
.robot-auth-gate .robot-btn .fa {
    margin-right: 6px;
}

/* Full-height embeds for File Manager / Portainer tabs */
.robot-embed {
    position: relative;
    width: 100%;
    height: calc(100vh - 210px);
    min-height: 480px;
    border: 1px solid var(--r-border);
    border-radius: var(--r-radius-md);
    overflow: hidden;
    background: #fff;
}
.robot-embed iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>

<div class="robot-page">
<?php if ($repeat_page_title || $show_power) { ?>
<div class="robot-page-header">
    <div>
        <?php if ($repeat_page_title) { ?>
        <h2 class="page-title-static" style="border:0; margin:0; padding:0;">
            <?php echo htmlspecialchars($display_name) ?>
            <span class="robot-page-subtitle"><?php echo htmlspecialchars($dbot_hostname) ?></span>
        </h2>
        <?php } ?>
    </div>
    <?php
    /*
     * Power controls (Shutdown / Reboot) - preserved, not deleted.
     * Hidden by default via RobotUIFeatures::show_power_controls() (false).
     * Enable with package setting robot_ui/show_power_controls=true, or
     * define('ROBOT_UI_SHOW_POWER_CONTROLS', true) before this page loads.
     * See ui_features.php.
     */
    if ($show_power) {
    ?>
    <div id="robot_power_btn_group" class="btn-group" role="group">
        <div class="btn-group" role="group">
            <button type="button" class="robot-btn robot-btn-warn robot-btn-sm dropdown-toggle" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                <i class="fa fa-bolt" aria-hidden="true"></i> Power
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li>
                    <a href="#" class="robot_power_btn" data-trigger="shutdown">
                        <i class="fa fa-power-off" aria-hidden="true"></i>
                        &nbsp; Shutdown
                    </a>
                </li>
                <li>
                    <a href="#" class="robot_power_btn" data-trigger="reboot">
                        <i class="fa fa-refresh" aria-hidden="true"></i>
                        &nbsp; Reboot
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <?php
    }
    ?>
</div>
<?php } ?>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="_robot_tab_btns" role="tablist">
    <?php
    foreach ($tabs as $tab_id => $tab) {
        ?>
        <li role="presentation" class="<?php echo ($tab_id == $ACTIVE_TAB)? 'active' : '' ?>">
            <a href="#" data-tab="<?php echo $tab_id ?>" role="button" onclick="robot_load_tab('<?php echo $tab_id ?>')">
                <i class="fa fa-<?php echo $tab['icon'] ?>" aria-hidden="true"></i>&nbsp; <?php echo $tab['name'] ?>
            </a>
        </li>
        <?php
    }
    ?>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="_logs_tab_container" style="padding: 16px 0 0 0">
    <div role="tabpanel" class="tab-pane active">
    <?php
        include $tab_include;
    ?>
    </div>
</div>
</div><!-- /.robot-page -->


<script type="text/javascript">

    let api_url = "http://<?php echo $dbot_hostname ?>/{api}/{path}";

    function get_api_url(api, action="", resources=[], qs=null) {
        let path = [action, ...resources];
        // compile URL
        let _url = api_url.format({api: api, path: path.join('/')}).rstrip('/');
        // make sure there is a final slash in the URL
        _url += "/";
        // create query string
        if (qs != null)
            _url += '?' + $.param(qs);
        // ---
        return _url;
    }

    function robot_load_tab(tab) {
        redirectTo('robot', tab);
    }

    function robot_set_bridge_status(state, label) {
        let el = $('#vehicle_bridge_status');
        if (!el.length) return;
        el.removeClass('is-ok is-bad is-wait');
        if (state === 'ok') el.addClass('is-ok');
        else if (state === 'bad') el.addClass('is-bad');
        else el.addClass('is-wait');
        el.html(label);
    }

    // Subtle sticky tooltips (hover + click/keyboard) for .robot-tip chips
    $(document).on('click', '.robot-tip', function (e) {
        e.preventDefault();
        let tip = $(this);
        let wasOpen = tip.hasClass('is-open');
        $('.robot-tip.is-open').not(tip).removeClass('is-open').attr('aria-expanded', 'false');
        tip.toggleClass('is-open', !wasOpen).attr('aria-expanded', wasOpen ? 'false' : 'true');
    });
    $(document).on('keydown', '.robot-tip', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        } else if (e.key === 'Escape') {
            $(this).removeClass('is-open').attr('aria-expanded', 'false');
        }
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.robot-tip').length) {
            $('.robot-tip.is-open').removeClass('is-open').attr('aria-expanded', 'false');
        }
    });

    <?php if ($show_power) { ?>
    // Power dropdown handlers - only bound when the controls are rendered.
    function _call_health_api(resource, action, qs = null, on_success = undefined, dialog = false) {
        let _url = get_api_url("health", "{0}/{1}".format(resource, action));
        if (qs != null)
            _url += '?' + $.param(qs);
        callExternalAPI(_url, 'GET', 'json', dialog, false, on_success);
    }

    $(".robot_power_btn").on("click", function () {
        let trigger = $(this).data('trigger');
        openYesNoModal(
            "Are you sure you want to {0} the robot?".format(trigger),
            function () {
                _call_health_api("trigger", trigger, null, function (data) {
                    if (data.hasOwnProperty('token')) {
                        _call_health_api("trigger", trigger, {
                            token: data.token,
                            value: 'dashboard'
                        });
                    }
                }, true);
            },
            false,
            'sm'
        );
    });
    <?php } ?>

</script>
