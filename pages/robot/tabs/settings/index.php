<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele
#
# Robot Settings tab
# ------------------
# Advanced groups (system / robot / autolab) are hidden for normal operators when
# RobotUIFeatures::simplify_robot_settings() is true (default) AND core
# developer_mode is false. Fields are feature-flagged - not permanently removed.
# @see ../../ui_features.php (robot_ui/simplify_robot_settings)


require_once $GLOBALS['__SYSTEM__DIR__'] . 'templates/forms/SmartForm.php';
require_once $GLOBALS['__SYSTEM__DIR__'] . 'classes/RESTfulAPI.php';
require_once dirname(__DIR__, 2) . '/ui_features.php';


use system\classes\Core;
use system\classes\Configuration;
use system\classes\RESTfulAPI;
use system\packages\duckietown_duckiebot\Duckiebot;

$api_service = 'robot_settings';
$api_action = 'set';

$developer_mode = (bool) Core::getSetting('developer_mode', 'core', false);
$simplify_settings = RobotUIFeatures::simplify_robot_settings();
$lock_anonymous_usage = !RobotUIFeatures::allow_disable_anonymous_usage();

// load API
RESTfulAPI::init();
$api_cfg = RESTfulAPI::getConfiguration();

// create schema for robot's settings from the API configuration
$action_cfg = $api_cfg[Configuration::$WEBAPI_VERSION]['services'][$api_service]['actions'][$api_action];
$action_params = array_merge($action_cfg['parameters']['mandatory'], $action_cfg['parameters']['optional']);

// Normal users: only privacy/backup permissions.
// Advanced fields stay available when simplify_robot_settings=false OR developer_mode=true.
if ($simplify_settings && !$developer_mode) {
    foreach (['system', 'robot', 'autolab'] as $hidden_group) {
        unset($action_params[$hidden_group]);
    }
}

if ($lock_anonymous_usage
    && isset($action_params['permissions']['_data']['allow_push_stats_data'])) {
    $action_params['permissions']['_data']['allow_push_stats_data']['__form__']['disabled'] = true;
    $action_params['permissions']['_data']['allow_push_stats_data']['default'] = true;
}

$form_schema = [
    'type' => 'form',
    'details' => 'Robot settings',
    '_data' => $action_params
];
?>

<style type="text/css">
    .robot-settings {
        max-width: var(--r-max, 1040px);
        margin: 0 auto;
        font-size: var(--r-fs-md, 12px);
        line-height: var(--r-lh, 1.4);
        color: var(--r-text, #111827);
    }
    .robot-settings-card {
        background: #fff;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        padding: 4px 4px 8px;
        margin-bottom: var(--r-gap-lg, 12px);
        overflow: hidden;
    }
    .robot-settings-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 10px;
        margin-bottom: 40px;
    }
    .robot-settings .text-muted {
        color: var(--r-muted, #6b7280);
        font-size: var(--r-fs-sm, 11px);
        margin: 0;
    }
    .robot-settings label,
    .robot-settings .control-label {
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-text, #111827);
    }

    /* SmartForm: match shared robot chrome (no grey bars / monospace / Bootstrap toggle look) */
    .robot-settings .compose-smart-form {
        margin: 0;
    }
    .robot-settings .compose-form-group {
        margin: 0;
        padding: 14px 16px 6px;
    }
    .robot-settings .compose-form-group + .compose-form-group {
        border-top: 1px solid var(--r-border, #e6e8eb);
    }
    .robot-settings .compose-form-group > h4 {
        margin: 0 0 2px;
        font-size: var(--r-fs-xl, 15px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-text, #111827);
        letter-spacing: var(--r-tracking-tight, -0.02em);
        line-height: var(--r-lh-tight, 1.15);
    }
    .robot-settings .compose-form-group > h5 {
        margin: 0 0 10px;
        font-size: var(--r-fs-sm, 11px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-muted, #6b7280);
        letter-spacing: var(--r-tracking-label, 0.04em);
        text-transform: uppercase;
        line-height: var(--r-lh, 1.4);
    }
    .robot-settings .compose-form-group-content {
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: 0;
    }
    .robot-settings .compose-form-atom {
        margin: 0;
        padding: 12px 0;
        border-top: 1px solid var(--r-border, #e6e8eb);
        display: grid;
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "label control"
            "details details";
        column-gap: 16px;
        row-gap: 4px;
        align-items: center;
    }
    .robot-settings .compose-form-atom:first-child {
        border-top: 0;
    }
    .robot-settings .compose-form-atom > .input-group {
        grid-area: label;
        display: contents;
        width: auto;
        margin: 0;
    }
    .robot-settings .compose-form-atom .input-group-addon {
        grid-area: label;
        width: auto !important;
        padding: 0;
        border: 0;
        background: transparent;
        border-radius: 0;
        box-shadow: none;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-text, #111827);
        text-align: left;
        line-height: var(--r-lh, 1.4);
    }
    .robot-settings .compose-form-atom .input-group-addon.closure-block {
        display: none;
    }
    .robot-settings .compose-form-atom .robot-settings-radios,
    .robot-settings .compose-form-atom .compose-smart-form-input:not([type="checkbox"]) {
        grid-area: control;
        justify-self: end;
    }
    .robot-settings .compose-form-atom .compose-smart-form-input[type="checkbox"] {
        display: none !important;
    }
    .robot-settings .compose-form-atom .help-block-details {
        grid-area: details;
        display: block;
        margin: 0;
        padding: 0;
        font-size: var(--r-fs-sm, 11px);
        font-weight: var(--r-fw-normal, 400);
        color: var(--r-muted, #6b7280);
        line-height: var(--r-lh, 1.4);
    }
    .robot-settings .compose-form-atom .help-block-details .fa {
        color: var(--r-muted, #6b7280);
        opacity: 0.85;
    }
    .robot-settings .compose-form-atom .help-block-default {
        display: none !important;
    }
    .robot-settings .compose-form-atom .help-block-default span {
        display: none !important;
    }

    /* Boolean fields: On / Off radios (replaces bootstrap-toggle) */
    .robot-settings-radios {
        display: inline-flex;
        align-items: center;
        gap: 14px;
        margin: 0;
    }
    .robot-settings-radio {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-text, #111827);
        cursor: pointer;
        user-select: none;
        line-height: 1.2;
    }
    .robot-settings-radio input[type="radio"] {
        appearance: none;
        -webkit-appearance: none;
        width: 16px;
        height: 16px;
        margin: 0;
        border: 1.5px solid var(--r-control-border, #8b929e);
        border-radius: 50%;
        background: #fff;
        box-shadow: none;
        cursor: pointer;
        flex: 0 0 auto;
        transition: border-color var(--r-ease, 160ms ease), box-shadow var(--r-ease, 160ms ease);
    }
    .robot-settings-radio input[type="radio"]:hover {
        border-color: #767d8a;
    }
    .robot-settings-radio input[type="radio"]:focus {
        outline: none;
    }
    .robot-settings-radio input[type="radio"]:focus-visible {
        outline: 2px solid rgba(44, 86, 134, 0.4);
        outline-offset: 2px;
    }
    .robot-settings-radio input[type="radio"]:checked {
        border-color: var(--r-fill, #2c5686);
        background:
            radial-gradient(circle, var(--r-fill, #2c5686) 0 45%, transparent 48%),
            #fff;
    }
    .robot-settings-radio input[type="radio"]:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .robot-settings-radio:has(input:disabled) {
        cursor: not-allowed;
        color: var(--r-muted, #6b7280);
    }

    /* Non-boolean inputs stay aligned with the same type scale */
    .robot-settings .compose-smart-form-input[type="text"],
    .robot-settings .compose-smart-form-input[type="number"],
    .robot-settings .compose-smart-form-input[type="password"],
    .robot-settings .compose-smart-form-input[type="email"],
    .robot-settings select.compose-smart-form-input {
        height: 32px;
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-sm, 6px);
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-normal, 400);
        color: var(--r-text, #111827);
        box-shadow: none;
    }
</style>

<div class="robot-settings">
    <p class="robot-hint">Update robot permissions and configuration. Changes apply after you save.</p>
    <div class="robot-settings-card">
    <?php
    // get settings
    $data = [
        'permissions' => Duckiebot::getDuckiebotPermissions(),
    ];
    if (!$simplify_settings || $developer_mode) {
        $data['robot'] = Duckiebot::getDuckiebotConfigurations();
        $data['autolab'] = Duckiebot::getAutolabConfigurations();
    }
    foreach ($data as $key => &$res) {
        $data[$key] = $res['success']? $res['data'] : [];
    }
    unset($res);
    if ($lock_anonymous_usage) {
        if (!isset($data['permissions']) || !is_array($data['permissions'])) {
            $data['permissions'] = [];
        }
        $data['permissions']['allow_push_stats_data'] = true;
    }
    // create form
    $form = new SmartForm($form_schema, $data);
    // render form
    $form->render();
    ?>
    </div>

    <div class="robot-settings-actions">
    <?php if (Core::isUserLoggedIn()) { ?>
    <button type="button" class="robot-btn robot-btn-primary" id="robot-settings-save-button">
        <i class="fa fa-check" aria-hidden="true"></i> Save and Apply
    </button>
    <?php } else { ?>
    <p class="text-muted">
        Sign in to change robot settings.
    </p>
    <?php } ?>
    </div>
</div>

<script type="text/javascript">
    (function() {
        var formId = "<?php echo $form->formID ?>";

        function unwrapToggle($input) {
            if ($input.data('bs.toggle') && typeof $input.bootstrapToggle === 'function') {
                try { $input.bootstrapToggle('destroy'); } catch (e) {}
            }
            var $wrap = $input.closest('div.toggle');
            if ($wrap.length) {
                $wrap.replaceWith($input);
            }
            $input.removeAttr('data-toggle').removeAttr('data-onstyle').removeAttr('data-offstyle')
                .removeAttr('data-class').removeAttr('data-size');
            $input.css('display', 'none');
        }

        function convertBooleansToRadios($root) {
            $root.find('input[type="checkbox"].compose-smart-form-input').each(function() {
                var $input = $(this);
                if ($input.data('robot-settings-radios')) {
                    return;
                }
                unwrapToggle($input);

                var id = $input.attr('id') || ('rs-' + Math.random().toString(36).slice(2, 10));
                var groupName = 'robot-settings-bool-' + id;
                var on = !!$input.prop('checked');
                var disabled = !!$input.prop('disabled');
                var disabledAttr = disabled ? ' disabled' : '';

                var $radios = $(
                    '<div class="robot-settings-radios" role="radiogroup">' +
                        '<label class="robot-settings-radio">' +
                            '<input type="radio" name="' + groupName + '" value="1"' +
                                (on ? ' checked' : '') + disabledAttr + '>' +
                            '<span>On</span>' +
                        '</label>' +
                        '<label class="robot-settings-radio">' +
                            '<input type="radio" name="' + groupName + '" value="0"' +
                                (!on ? ' checked' : '') + disabledAttr + '>' +
                            '<span>Off</span>' +
                        '</label>' +
                    '</div>'
                );

                $input.after($radios);
                $input.data('robot-settings-radios', true);

                $radios.on('change', 'input[type="radio"]', function() {
                    $input.prop('checked', $(this).val() === '1');
                });
            });
        }

        function lockAnonymousUsage($root) {
            if (!<?php echo $lock_anonymous_usage ? 'true' : 'false' ?>) {
                return;
            }
            $root.find('.compose-form-atom').each(function() {
                var title = $(this).find('.input-group-addon').first().text().replace(/\s+/g, ' ').trim();
                if (title.toLowerCase().indexOf('anonymous usage') === -1) {
                    return;
                }
                var $input = $(this).find('input[type="checkbox"].compose-smart-form-input');
                $input.prop('checked', true).prop('disabled', true);
                $(this).find('.robot-settings-radios input[type="radio"]')
                    .prop('disabled', true)
                    .filter('[value="1"]').prop('checked', true);
            });
        }

        function enhanceWhenReady(attempt) {
            var $root = $('.robot-settings #' + formId);
            if (!$root.length) {
                return;
            }
            if ($root.find('input[type="checkbox"].compose-smart-form-input').length) {
                convertBooleansToRadios($root);
                lockAnonymousUsage($root);
                return;
            }
            if ((attempt || 0) < 40) {
                setTimeout(function() { enhanceWhenReady((attempt || 0) + 1); }, 50);
            }
        }

        $(document).ready(function() {
            enhanceWhenReady(0);
            // bootstrap-toggle auto-inits on ready; convert again shortly after
            setTimeout(function() { enhanceWhenReady(0); }, 120);
        });

        $('#robot-settings-save-button').on('click', function() {
            var form = ComposeForm.get(formId);
            smartAPI('robot_settings', 'set', {
                method: 'POST',
                arguments: {},
                data: form.serialize(),
                block: true,
                confirm: true,
                reload: true
            });
        });
    })();
</script>
