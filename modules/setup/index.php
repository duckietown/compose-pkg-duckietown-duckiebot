<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


require_once $GLOBALS['__SYSTEM__DIR__'] . 'templates/forms/SmartForm.php';
require_once $GLOBALS['__SYSTEM__DIR__'] . 'classes/RESTfulAPI.php';


use \system\classes\Core;
use \system\classes\Database;
use system\classes\Configuration;
use system\classes\RESTfulAPI;
use system\packages\duckietown_duckiebot\Duckiebot;


// constants
$step_no = $_COMPOSE_SETUP_STEP_NO;
$api_service = 'robot_settings';
$api_action = 'set';

if (
    (
        (isset($_GET['step']) && $_GET['step'] == $step_no) ||
        (isset($_GET['force_step']) && $_GET['force_step'] == $step_no)
    ) &&
    (
        isset($_GET['confirm']) && $_GET['confirm'] == '1'
    )
) {
    _compose_first_setup_step_in_progress();
    // confirm step
    $first_setup_db = new Database('core', 'first_setup');
    $first_setup_db->write('step' . $step_no, null);
    
    // redirect to setup page
    Core::redirectTo('setup');
}

// load API
RESTfulAPI::init();
$api_cfg = RESTfulAPI::getConfiguration();

// create schema for robot's settings from the API configuration
$action_cfg = $api_cfg[Configuration::$WEBAPI_VERSION]['services'][$api_service]['actions'][$api_action];
$action_params = array_merge($action_cfg['parameters']['mandatory'], $action_cfg['parameters']['optional']);
// set default to TRUE (special to this page)
foreach (Duckiebot::$PERMISSION_KEYS as $key) {
    $action_params['permissions']['_data'][$key]['default'] = true;
}
// create form schema
$form_schema = [
    'type' => 'form',
    'details' => 'Robot Settings',
    '_data' => []
];
$form_data = [];

// only show hostname option if we can set it
if (Duckiebot::canSetDuckiebotHostname()) {
    $form_schema["_data"]["system"] = [
        "type" => "object",
        "details" => "System preferences",
        "_data" => [
            "hostname" => [
                "type" => "text",
                "default" => null,
                "details" => "The hostname of your robot"
            ]
        ]
    ];
    $form_data["system"]["hostname"] = Duckiebot::getDuckiebotName();
}
// create form
$form_schema["_data"]["permissions"] = [
    "type" => "object",
    "details" => "Data permissions",
    "_data" => $action_params['permissions']['_data']
];

// get settings
$res = Duckiebot::getDuckiebotConfigurations();
$robot_type = $res['success']? $res['data']['type'] : null;
$robot_configuration = $res['success']? $res['data']['configuration'] : null;
// permissions are set to true by default
$permissions = [];
foreach (Duckiebot::$PERMISSION_KEYS as $key) {
    $permissions[$key] = "1";
}
$form_data["permissions"] = $permissions;
?>

<div style="margin: 20px 60px">
    <?php
    if (!is_null($robot_configuration)) {
        ?>
        <h4>Setup your
            <b><?php echo sprintf("%s %s", ucfirst($robot_type), ucfirst($robot_configuration)) ?></b>
            robot.
        </h4>
        <p class="text-center">
            <img src="<?php echo Core::getImageURL(
                    sprintf("robots/%s_trimetric.jpg", $robot_configuration), 'duckietown') ?>"
                 alt="" style="width: auto; height: auto; max-width: 500px; max-height: 400px; ">
        </p>
        <?php
    }
    ?>
    <br/>
    <h3>Robot Settings and Data Permissions</h3>
    <p>
        You can change the name of your robot using the field below.
        <br/>
        <strong>Duckietown</strong> would also like to collect usage statistics and sensor
        data while the robot is in use. Read carefully what types of data you can share with
        Duckietown and grant the permissions you fell more comfortable with.
    </p>
    <br/>
    <?php
    // create form
    $form = new SmartForm($form_schema, $form_data);
    // render form
    $form->render();
    ?>
</div>

<button type="button" class="btn btn-success" id="confirm-step-button" style="float:right">
  <span class="fa fa-arrow-down" aria-hidden="true"></span>&nbsp; Next
</button>

<script type="text/javascript">
    $('#confirm-step-button').on('click', function(){
        // define success function
        let confirm_step_fcn = function(r){
            location.href = 'setup?step=<?php echo $step_no ?>&confirm=1';
        };
        let form = ComposeForm.get("<?php echo $form->formID ?>");
        // call API
        smartAPI('robot_settings', 'set', {
            method: 'POST',
            arguments: {},
            data: form.serialize(),
            block: true,
            confirm: true,
            reload: false,
            on_success: confirm_step_fcn
        });
    });
</script>
