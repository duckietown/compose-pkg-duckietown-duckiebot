<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


require_once $GLOBALS['__SYSTEM__DIR__'] . 'templates/forms/SmartForm.php';
require_once $GLOBALS['__SYSTEM__DIR__'] . '/classes/RESTfulAPI.php';


use system\classes\Configuration;
use system\classes\RESTfulAPI;

$api_service = 'robot_settings';
$api_action = 'set';

// load API
RESTfulAPI::init();
$api_cfg = RESTfulAPI::getConfiguration();

// create schema for robot's settings from the API configuration
$action_cfg = $api_cfg[Configuration::$WEBAPI_VERSION]['services'][$api_service]['actions'][$api_action];
$action_params = array_merge($action_cfg['parameters']['mandatory'], $action_cfg['parameters']['optional']);
$form_schema = [
    'type' => 'form',
    'details' => 'Robot settings',
    '_data' => $action_params
];
?>

<div style="margin: auto; width: 80%">
    <?php
    // create form
    // TODO: load values and pass them here
    $form = new SmartForm($form_schema, []);
    // render form
    $form->render();
    ?>
    
    <button type="button" class="btn btn-primary" id="robot-settings-save-button" style="float:right; margin-bottom: 40px">
        <span class="glyphicon glyphicon-floppy-open" aria-hidden="true"></span>&nbsp; Save and Apply
    </button>
    
</div>

<script type="text/javascript">
    $('#robot-settings-save-button').on('click', function(){
        let form = ComposeForm.get("<?php echo $form->formID ?>");
        // call API
        smartAPI('robot_settings', 'set', {
            method: 'POST',
            arguments: {},
            data: form.serialize(),
            block: true,
            confirm: true,
            reload: true
        });
    });
</script>
