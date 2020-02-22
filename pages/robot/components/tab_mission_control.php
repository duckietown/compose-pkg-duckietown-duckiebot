<?php
use \system\classes\Core;
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
$sizes = [ // allowed block sizes
  [1,2],
  [1,3],
  [2,2],
  [2,4],
  [2,5],
  [2,8],
  [3,8],
  [4,4],
  [4,8],
  [4,5],
  [5,5],
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
$mission_control_grid = [];
if ($load_mission) {
  // read mission details
  $res = $db->read($mission_name);
  if( !$res['success'] ){
    Core::throwError($res['data']);
  }
  $mission_control_grid = $res['data'];
  // if we were able to load the mission, store it as 'last opened'
  $_SESSION['_VEHICLE_LAST_MISSION'] = $mission_name;
}

$is_multi_robot_mission = false;
$robots = [];
for ($i = 0; $i < count($mission_control_grid['blocks']); $i++) {
  $args = $mission_control_grid['blocks'][$i]['args'];
  $ros_hostname = null;
  if (array_key_exists('ros_hostname', $args)) {
    $ros_hostname = $args['ros_hostname'];
  }
  $ros_hostname = ROS::sanitize_hostname($ros_hostname);
  array_push($robots, $ros_hostname);
}
$is_multi_robot_mission = count(array_unique($robots)) > 1;
?>

<table style="width: 970px; margin: auto; margin-bottom: 12px">
    <tr>
      <?php
      $_vehicle = ($is_multi_robot_mission)? 'Multi-robots' : $vehicle_name;
      $_bridge_status = ($is_multi_robot_mission)?
        '<i class="fa fa-square"></i> Multi-bridge' : '<i class="fa fa-spinner fa-pulse"></i> Connecting...';
      ?>
      <td class="text-left" style="width:25%; border-right: 1px solid lightgrey">
        <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
        <strong><?php echo $_vehicle ?></strong>
      </td>
      <td class="text-center" style="width:30%; border-right: 1px solid
      lightgrey">
        <i class="fa fa-object-ungroup" aria-hidden="true"></i> Mission:
        <strong><?php echo is_null($mission_name)? '(none)' : $mission_name ?></strong>
      </td>
      <td class="text-center" style="width:30%; border-right: 1px solid
      lightgrey">
        <span id="vehicle_bridge_status">
          <?php echo $_bridge_status ?>
        </span>
      </td>
      <td class="text-right" style="width:15%">
        <?php
        new MissionControlConfiguration(
          $grid_id,
          $mission_db_package,
          $mission_db,
          $mission_name
        );
        ?>
      </td>
    </tr>
</table>

<?php
if ($load_mission) {
    // replace `~` with the vehicle name in the arg fields
    for ($i = 0; $i < count($mission_control_grid['blocks']); $i++) {
        foreach ($mission_control_grid['blocks'][$i]['args'] as $key => $value) {
            if (substr($value, 0, 1) === "~") {
                // replace `~` with `vehicle_name`
                $value = str_replace('~', '/' . $vehicle_name, $value);
                $mission_control_grid['blocks'][$i]['args'][$key] = $value;
            }
        }
    }

    // load mission options
    $opts = MissionControlConfiguration::get_options($mission_db_package, $mission_db, $mission_name);

    // create mission control grid
    $mission_control = new MissionControl(
        $grid_id,
        $sizes,
        $mission_control_grid['blocks']
    );
    ?>

    <div style="border-top: 1px solid lightgrey; border-bottom: 1px solid lightgrey; padding: 10px 0">
        <?php
        // render mission control grid
        $mission_control->create($opts);
        ?>
    </div>
<?php
}
?>


<script type="text/javascript">
  $(document).on('<?php echo ROS::get_event(ROS::$ROSBRIDGE_CONNECTED) ?>', function(evt){
    console.log('Connected to websocket server.');
    $('#vehicle_bridge_status').html(
      '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green"></span> Bridge: <strong>Connected</strong>'
    );
  });

  $(document).on('<?php echo ROS::get_event(ROS::$ROSBRIDGE_ERROR) ?>', function(evt, error){
    console.log('Error connecting to websocket server: ', error);
    $('#vehicle_bridge_status').html(
      '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red"></span> Bridge: <strong>Error</strong>'
    );
  });

  $(document).on('<?php echo ROS::get_event(ROS::$ROSBRIDGE_CLOSED) ?>', function(evt){
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

  $(window).on('MISSION_CONTROL_OPTIONS_SAVE', function(evt, mission_name, mission_options_json){
    var base_url = "<?php echo Core::getAPIurl('data', 'set', ['database' => $mission_db.'_opts']) ?>";
    var url = "{0}&key={1}&value={2}".format(base_url, mission_name, mission_options_json);
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
</script>
