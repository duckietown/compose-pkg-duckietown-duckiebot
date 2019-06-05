<?php
use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;
?>

<script src="<?php echo Core::getJSscriptURL('jquery-ui-1.11.1.js'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('packery.pkgd.min.js'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('draggabilly.pkgd.min.js'); ?>"></script>

<?php
$mission_db = "duckietown_duckiebot_missions";
$mission_db_package = "data";
$mission_name = (isset($_GET['mission']) && strlen(trim($_GET['mission'])) > 0)? trim($_GET['mission']) : null;
$vehicle_name = Duckiebot::getDuckiebotName();
$grid_id = "vehicle-mission-control-grid";
$missions_regex = "/^(?!__).*/";

// define parameters for the mission control grid
$grid_width = 966; // do not use 970px to accomodate for differences between browsers
$resolution = 8;
$block_gutter = 10;
$block_border_thickness = 1;
$sizes = [ // allowed block sizes
  [1,2],
  [1,3],
  [2,2],
  [2,4],
  [2,8],
  [3,8],
  [4,4],
  [4,8],
  [6,8]
];

// open DB of missions
$db = new Database($mission_db_package, $mission_db, $missions_regex);

// open load modal if no mission was given
if ($db->size() > 0 && is_null($mission_name)) {
  $mission_name = null;
  // no mission given
  // 1. check if there is only one available
  if ($db->size() == 1){
    $missions = $db->list_keys();
    $mission_name = $missions[0];
  }else{
    // 2. try to open the last opened mission
    if (isset($_SESSION['_VEHICLE_LAST_MISSION'])) {
      $mission = $_SESSION['_VEHICLE_LAST_MISSION'];
      if ($db->key_exists($mission)) {
        $mission_name = $mission;
      }
    }
    // 3. open the load modal if there is at least one mission available
    if (is_null($mission_name)) {
      ?>
      <script type="text/javascript">
      $(document).ready(function(){
        $('#mission-control-load-modal').modal('show');
      });
      </script>
      <?php
    }
  }
}

// create a mission control menu to the left
new MissionControlMenu(
  $grid_id,
  'left',
  $mission_db_package,
  $mission_db,
  $mission_name,
  $missions_regex
);
?>

<div style="width:100%; margin:auto">

  <table style="width:100%; margin-bottom:42px">
    <tr>
      <td colspan="4" style="border-bottom:1px solid #ddd">
        <h2>
          Mission Control

          <?php
          include_once "components/take_over.php";
          ?>
        </h2>
      </td>
    </tr>
    <tr>
      <td class="text-left" style="width:20%; padding-top:10px">
        <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
        <strong><?php echo $vehicle_name ?></strong>
      </td>
      <td class="text-center" style="width:30%; padding-top:10px">
        <i class="fa fa-object-ungroup" aria-hidden="true"></i> Mission:
        <strong><?php echo is_null($mission_name)? '(none)' : $mission_name ?></strong>
      </td>
      <td class="text-center" style="width:30%; padding-top:10px">
        <i class="fa fa-toggle-on" aria-hidden="true"></i> Mode:
        <strong id="vehicle_driving_mode_status">Autonomous</strong>
      </td>
      <td class="text-right" style="width:20%; padding-top:10px">
        <span id="vehicle_bridge_status">
          <i class="fa fa-spinner fa-pulse"></i> Connecting...
        </span>
      </td>
    </tr>
  </table>

  <?php
  $load_mission = true;
  // check if the mission exists
  if ($db->size() == 0){
    echo sprintf('<h3 class="text-center">%s</h3></div>', "No missions available!");
    $load_mission = false;
  } elseif (is_null($mission_name) || !$db->key_exists($mission_name)) {
    $message = is_null($mission_name)? "No mission loaded!" : "Mission '$mission_name' not found!";
    echo sprintf('<h3 class="text-center">%s</h3></div>', $message);
    $load_mission = false;
  }

  if ($load_mission) {
    // read mission details
    $res = $db->read($mission_name);
    if( !$res['success'] ){
      Core::throwError( $res['data'] );
    }
    $mission_control_grid = $res['data'];

    // if we were able to load the mission, store it as 'last opened'
    $_SESSION['_VEHICLE_LAST_MISSION'] = $mission_name;

    // replace `~` with the vehicle name in the arg fields
    for ($i = 0; $i < count($mission_control_grid['blocks']); $i++) {
      foreach ($mission_control_grid['blocks'][$i]['args'] as $key => $value) {
        if (substr($value, 0, 1) === "~") {
          // replace `~` with `vehicle_name`
          $value = str_replace('~', '/'.$vehicle_name, $value);
          $mission_control_grid['blocks'][$i]['args'][$key] = $value;
        }
      }
    }

    // create mission control grid
    $mission_control = new MissionControl(
      $grid_id,
      $grid_width,
      $resolution,
      $block_gutter,
      $block_border_thickness,
      $sizes,
      $mission_control_grid['blocks']
    );

    // render mission control grid
    $mission_control->create();

    // connect to ROSbridge
    ROS::connect();
  }
  ?>

</div>


<script type="text/javascript">
  $(document).on('<?php echo ROS::$ROSBRIDGE_CONNECTED ?>', function(evt){
    console.log('Connected to websocket server.');
    $('#vehicle_bridge_status').html(
      '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green"></span> Bridge: <strong>Connected</strong>'
    );
  });

  $(document).on('<?php echo ROS::$ROSBRIDGE_ERROR ?>', function(evt, error){
    console.log('Error connecting to websocket server: ', error);
    $('#vehicle_bridge_status').html(
      '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red"></span> Bridge: <strong>Error</strong>'
    );
  });

  $(document).on('<?php echo ROS::$ROSBRIDGE_CLOSED ?>', function(evt){
    console.log('Connection to websocket server closed.');
    $('#vehicle_bridge_status').html(
      '<span class="glyphicon glyphicon-off" aria-hidden="true" style="color:red"></span> Bridge: <strong>Closed</strong>'
    );
  });

  $(document).ready(function() {
    window.mission_control_page_blocks_data = {};
  });

  $(window).on('MISSION_CONTROL_MENU_SAVE', function(evt, mission_name, mission_json){
    var base_url = "<?php echo Core::getAPIurl('data', 'set', ['database' => $mission_db]) ?>";
    var url = "{0}&key={1}&value={2}".format(base_url, mission_name, mission_json);
    // send data to server
    callAPI(
      url,
      true,           //successDialog
      false,          // reload
      function(){
        // reload mission
        $(window).trigger('MISSION_CONTROL_MENU_LOAD', [mission_name]);
      },              // funct
      false,          // silentMode
      false,          // suppressErrors
      undefined,      // errorFcn
      'POST'          // transportType
    );
  });

  $(window).on('MISSION_CONTROL_MENU_LOAD', function(evt, mission_name){
    var url = "<?php echo Core::getCurrentResourceURL()?>?mission={0}".format(mission_name);
    window.location = url;
  });

  $(window).on('MISSION_CONTROL_MENU_DELETE', function(evt, mission_name){
    var base_url = "<?php echo Core::getAPIurl('data', 'delete', ['database' => $mission_db]) ?>";
    var url = "{0}&key={1}".format(base_url, mission_name);
    // send data to server
    callAPI(
      url,
      true,           //successDialog
      false,          // reload
      function(){     // funct
        // reload page
        $(window).trigger('MISSION_CONTROL_MENU_LOAD', ['']);
      }
    );
  });
</script>
