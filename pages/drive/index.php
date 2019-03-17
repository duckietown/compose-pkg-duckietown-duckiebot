<?php
use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;
?>

<script src="<?php echo Core::getJSscriptURL('jquery-ui-1.11.1.js', 'duckietown'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('packery.pkgd.min.js', 'duckietown'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('draggabilly.pkgd.min.js', 'duckietown'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('roslibjs.min.js', 'ros'); ?>"></script>

<?php
// define parameters for the mission control grid
$grid_width = 966; // do not use 970px to accomodate for differences between browsers
$resolution = 4;
$block_gutter = 10;
$block_border_thickness = 1;
$output_commands_hz = 20;
$sensitivity = 0.5;

// TODO: get these from ROS param
$v_gain = 0.41;
$omega_gain = 8.3;

// apply sensitivity
$omega_gain *= $sensitivity;

// define block
$blocks = [
  [
    "shape" => [
      "rows" => 1,
      "cols" => 4
    ],
    "renderer" => "SensorMsgs_CompressedImage",
    "title" => "Camera",
    "subtitle" => "\/duckiebot\/camera_node\/image\/compressed",
    "args" => [
      "topic" => "\/duckiebot\/camera_node\/image\/compressed",
      "fps" => 20,
      "style" => "cover",
      "position" => "center center",
      "background-color" => "white"
    ]
  ]
];

// create mission control grid
$mission_control = new MissionControl(
  "duckiebot-mission-control-windshield",
  $grid_width,
  $resolution,
  $block_gutter,
  $block_border_thickness,
  [],
  $blocks
);
?>

<div style="width:100%; margin:auto">

  <table style="width:100%; margin-bottom:22px">
    <tr>
      <td colspan="4" style="border-bottom:1px solid #ddd">
        <h2>
          Drive
        </h2>
      </td>
    </tr>
    <tr>
      <td class="text-left" style="width:50%; padding-top:10px">
        <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
        <strong><?php echo Core::getSetting('navbar_title', 'core', 'n.a.') ?></strong>
      </td>
      <td class="text-right" style="width:50%; padding-top:10px">
        <span id="duckiebot_bridge_status">
          <i class="fa fa-spinner fa-pulse"></i> Connecting...
        </span>
      </td>
    </tr>
  </table>

  <?php
  $mission_control->create();

  // get WebSocket hostname (default to HTTP_HOST if not set)
  $ws_hostname = Core::getSetting('rosbridge_host', 'aido_duckiebot');
  if(strlen($ws_hostname) < 2){
    $ws_hostname = $_SERVER['HTTP_HOST'];
  }
  // compile the Websocket URL
  $ws_url = sprintf(
    "ws://%s:%d",
    $ws_hostname,
    Core::getSetting('rosbridge_port', 'aido_duckiebot')
  );
  ?>

  <script type="text/javascript">

  $( document ).ready(function() {
    window.mission_control_page_blocks_data = {};
    // Connect to ROS
    window.ros = new ROSLIB.Ros({
      url : "<?php echo $ws_url ?>"
    });
    ros.on('connection', function() {
      console.log('Connected to websocket server.');
      $('#duckiebot_bridge_status').html('<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green"></span> Bridge: <strong>Connected</strong>');
      $(document).trigger('ROSBridge_connected');
    });
    ros.on('error', function(error) {
      console.log('Error connecting to websocket server: ', error);
      $('#duckiebot_bridge_status').html('<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red"></span> Bridge: <strong>Error</strong>');
    });
    ros.on('close', function() {
      console.log('Connection to websocket server closed.');
      $('#duckiebot_bridge_status').html('<span class="glyphicon glyphicon-off" aria-hidden="true" style="color:red"></span> Bridge: <strong>Closed</strong>');
    });

    // define the output topic
    // TODO: hard-coded topic
    window.drive_page_cmdVel = new ROSLIB.Topic({
      ros : ros,
      name : '/duckiebot/joy_mapper_node/car_cmd',
      messageType : 'duckietown_msgs/Twist2DStamped'
    });

    // define the list of keys that can be used to drive the duckiebot
    window.drive_page_Keys = {
      UP_ARROW: 38,
      LEFT_ARROW: 37,
      DOWN_ARROW: 40,
      RIGHT_ARROW: 39,
      W: 87,
      A: 65,
      S: 83,
      D: 68,
    };

    // define buffer of pressed keys
    window.drive_page_keyMap = {};
    // set all the keys to False (i.e., not-pressed)
    for (let item in window.drive_page_Keys) {
      if (isNaN(Number(item))) {
        window.drive_page_keyMap[window.drive_page_Keys[item]] = false;
      }
    }
    // capture keyboard events (and update buffer accordingly)
    onkeydown = onkeyup = function(e){
      e = e || event; // to deal with IE
      window.drive_page_keyMap[e.keyCode] = e.type == 'keydown';
    }

    // define the callback function that turns the key_map into a ROS message
    function publish_command(){
      keys = window.drive_page_Keys;
      key_map = window.drive_page_keyMap;
      // compute linear/angular speeds
      v_gain = <? echo $v_gain ?>;
      omega_gain = <? echo $omega_gain ?>;
      v_val = Math.min(key_map[keys.UP_ARROW] + key_map[keys.W], 1) - Math.min(key_map[keys.DOWN_ARROW] + key_map[keys.S], 1);
      omega_val = Math.min(key_map[keys.LEFT_ARROW] + key_map[keys.A], 1) - Math.min(key_map[keys.RIGHT_ARROW] + key_map[keys.D], 1);
      //
      var twist = new ROSLIB.Message({
        v : v_val * v_gain,
        omega : omega_val * omega_gain
      });
      window.drive_page_cmdVel.publish(twist);

      // DEBUG
      console.log('V: {0}, R: {1}'.format(v_val, omega_val));
    }

    // publish command at regular rate
    $(document).on('ROSBridge_connected', function(e){
      // start publishing commands to the duckiebot
      setInterval(publish_command, <?php echo intval(1000.0 / $output_commands_hz) ?>);
    });

  });
  </script>

</div>
