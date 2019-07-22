<?php
use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;
?>

<!-- Include ROS -->
<script src="<?php echo Core::getJSscriptURL('rosdb.js', 'ros') ?>"></script>

<?php
$vehicle_name = Duckiebot::getDuckiebotName();

// connect to ROSbridge
ROS::connect();
?>

<div style="width:100%; margin:auto">

  <table style="width:100%; margin-bottom:42px">
    <tr>
      <td colspan="4" style="border-bottom:1px solid #ddd">
        <h2>
          Duckiebot
        </h2>
      </td>
    </tr>
    <tr>
      <td class="text-left" style="width:50%; padding-top:10px">
        <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
        <strong><?php echo $vehicle_name ?></strong>
      </td>
      <td class="text-right" style="width:50%; padding-top:10px">
        <span id="vehicle_bridge_status">
          <i class="fa fa-spinner fa-pulse"></i> Connecting...
        </span>
      </td>
    </tr>
  </table>

  <div id="sensors_status" style="width:100%"></div>
</div>

<script type="text/javascript">

  var sensor_state_to_sensor_name = {
    "state_camera": "Camera",
    "state_encoder_and_motor": "Wheel Encoder",
    "state_front_bumper": "Front Bumper",
    "state_tof_fl": "ToF Front-Left",
    "state_tof_fm": "ToF Front-Middle",
    "state_tof_fr": "ToF Front-Right",
    "state_tof_sr": "ToF Right side",
    "state_tof_sl": "ToF Left side",
    "state_tof_bl": "ToF Back-Left",
    "state_tof_bm": "ToF Back-Middle",
    "state_tof_br": "ToF Back-Right",
    "state_lf_inner_left": "Lane Following In-Left",
    "state_lf_inner_right": "Lane Following In-Right",
    "state_lf_outer_right": "Lane Following Out-Right",
    "state_lf_outer_left": "Lane Following Out-Left",
    "state_imu": "IMU"
  };

  $(document).on('<?php echo ROS::$ROSBRIDGE_CONNECTED ?>', function(evt){
    console.log('Connected to websocket server.');
    $('#vehicle_bridge_status').html(
      '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green"></span> Bridge: <strong>Connected</strong>'
    );
    // retrieve sensors from robot
    var sensors_status = new ROSLIB.Service({
      ros : window.ros,
      name : '/sensor_scanning',
      messageType : 'duckietown_msgs/SensorsStatus'
    });
    // create request object
    var request = new ROSLIB.ServiceRequest({
      'state' : true
    });
    // call service
    sensors_status.callService(request, function(result) {
      for (const [key, n] of Object.entries(sensor_state_to_sensor_name)) {
        let value = result[key];
        let b = (value)? 'ok' : 'remove';
        let c = (value)? 'green' : 'red';
        let v = (value)? 'Connected' : 'Not Connected';
        // let n = sensor_state_to_sensor_name[key];
        let entry = '<span class="glyphicon glyphicon-{0}-sign" aria-hidden="true" style="color:{1}"></span> {2}: <strong>{3}</strong></span>'.format(b, c, n, v);
        $('#sensors_status').html($('#sensors_status').html() + '<br/>' + entry);
      }
    });

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
</script>
