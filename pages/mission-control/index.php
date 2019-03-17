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
$mission_name = 'aido_default';

// define parameters for the mission control grid
$grid_width = 966; // do not use 970px to accomodate for differences between browsers
$resolution = 8;
$block_gutter = 10;
$block_border_thickness = 1;


// TODO: there must be a better way for doing this
$duckiebot_name = Core::getSetting('navbar_title', 'core', 'n.a.');


// read mission details
$db = new Database( 'aido_duckiebot', 'mission' );
$res = $db->read($mission_name);
if( !$res['success'] ){
  Core::throwError( $res['data'] );
}
$mission_control_grid = $res['data'];

// append name of the duckiebot to each topic
for ($i = 0; $i < count($mission_control_grid['blocks']); $i++) {
  if( array_key_exists('topic', $mission_control_grid['blocks'][$i]['args']) ){
    $mission_control_grid['blocks'][$i]['args']['topic'] = sprintf(
      '/%s/%s',
      $duckiebot_name,
      $mission_control_grid['blocks'][$i]['args']['topic']
    );
  }
}

// define allowed block sizes
$sizes = [
  [1,1],
  [1,2],
  [1,3],
  [2,2],
  [2,4],
  [3,8],
  [4,8],
  [6,8],
  [8,8]
];

// create mission control grid
$mission_control = new MissionControl(
  "duckiebot-mission-control-grid",
  $grid_width,
  $resolution,
  $block_gutter,
  $block_border_thickness,
  $sizes,
  $mission_control_grid['blocks']
);
?>

<div style="width:100%; margin:auto">

  <table style="width:100%; margin-bottom:42px">
    <tr>
      <td colspan="4" style="border-bottom:1px solid #ddd">
        <h2>
          Mission Control

          <span style="float: right; font-size: 12pt">Take over&nbsp;
            <input type="checkbox"
                data-toggle="toggle"
                data-onstyle="primary"
                data-offstyle="warning"
                data-class="fast"
                data-size="small"
                name="duckiebot_driving_mode_toggle"
                id="duckiebot_driving_mode_toggle">
           </span>
        </h2>
      </td>
    </tr>
    <tr>
      <td class="text-left" style="width:20%; padding-top:10px">
        <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
        <strong><?php echo $duckiebot_name ?></strong>
      </td>
      <td class="text-center" style="width:30%; padding-top:10px">
        <i class="fa fa-object-ungroup" aria-hidden="true"></i> Mission:
        <strong>TODO</strong>
      </td>
      <td class="text-center" style="width:30%; padding-top:10px">
        <i class="fa fa-toggle-on" aria-hidden="true"></i> Mode:
        <strong id="duckiebot_driving_mode_status">Autonomous</strong>
      </td>
      <td class="text-right" style="width:20%; padding-top:10px">
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
    window.mission_control_Mode = 'autonomous';
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
  });


  $('#duckiebot_driving_mode_toggle').change(function(){
    if ($(this).prop('checked')){
      // change the page background
      $('body').css('background-image', 'linear-gradient(to top, #F7F7F6, #FFC800, #F7F7F6)');
      $('#duckiebot_driving_mode_status').html('Manual');
      window.mission_control_Mode = 'manual';
    }else{
      $('body').css('background-image', 'none');
      $('#duckiebot_driving_mode_status').html('Autonomous');
      window.mission_control_Mode = 'autonomous';
    }
  });
  </script>

  <?php
  include_once "components/take_over.php";
  ?>
</div>
