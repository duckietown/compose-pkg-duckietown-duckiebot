<?php
use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;
use \system\packages\duckietown_duckiebot\Duckiebot;
?>

<span style="float: right; font-size: 12pt">Take over&nbsp;
  <input type="checkbox"
      data-toggle="toggle"
      data-onstyle="primary"
      data-offstyle="warning"
      data-class="fast"
      data-size="small"
      name="vehicle_driving_mode_toggle"
      id="vehicle_driving_mode_toggle">
</span>

<?php
$v_gain = 0.5;
$omega_gain = 8.3;
$sensitivity = 0.5;
$output_commands_hz = 10.0;
$vehicle_name = Duckiebot::getDuckiebotName();

// apply sensitivity
$omega_gain *= $sensitivity;
?>

<script src="<?php echo Core::getJSscriptURL('cbor.min.js', 'duckietown_duckiebot'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('dtps_client.js', 'duckietown_duckiebot'); ?>"></script>
<script src="<?php echo Core::getJSscriptURL('robot_calibration.js', 'duckietown_duckiebot'); ?>"></script>

<script type="text/javascript">

  // The robot is driven over DTPS: wheel PWM commands go straight to the wheels driver
  // (`actuator/wheels/base/pwm`), like the keyboard controller does; no ROS stack is needed.
  window.mission_control_dtps = DTPS.client('<?php echo $vehicle_name ?>');
  window.mission_control_calibration = RobotCalibration.DEFAULTS;
  RobotCalibration.load('<?php echo $vehicle_name ?>').then(function (calibration) {
    window.mission_control_calibration = calibration;
  });

  // define the list of keys that can be used to drive the vehicle
  window.mission_control_Keys = {
    SPACE: 32,
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
  window.mission_control_keyMap = {};
  // set all the keys to False (i.e., not-pressed)
  for (let item in window.mission_control_Keys) {
    if (isNaN(Number(item))) {
      window.mission_control_keyMap[window.mission_control_Keys[item]] = false;
    }
  }
  // whether the last command sent was a non-zero one (a stop is sent once when the keys are released)
  window.mission_control_moving = false;

  // capture keyboard events (and update buffer accordingly)
  function key_cb(e){
    if (window.mission_control_Mode != 'manual')
      return;
    // space and arrow keys
    if([32, 37, 38, 39, 40].indexOf(e.keyCode) > -1) {
      e.preventDefault();
      window.mission_control_keyMap[e.keyCode] = e.type == "keydown";
    }
  }//key_cb

  function send_pwm(pwm) {
    window.mission_control_dtps.publish("actuator/wheels/base/pwm", {
      header: DTPS.header(),
      left: pwm.left,
      right: pwm.right
    }).catch(function (error) {
      console.warn('Cannot send the wheel command: ', error);
    });
  }

  // turn the key_map into a wheel command
  function publish_command(){
    if (window.mission_control_Mode != 'manual')
      return;
    let keys = window.mission_control_Keys;
    let key_map = window.mission_control_keyMap;
    // compute linear/angular speeds
    let v_val = Math.min(key_map[keys.UP_ARROW] + key_map[keys.W], 1) - Math.min(key_map[keys.DOWN_ARROW] + key_map[keys.S], 1);
    let omega_val = Math.min(key_map[keys.LEFT_ARROW] + key_map[keys.A], 1) - Math.min(key_map[keys.RIGHT_ARROW] + key_map[keys.D], 1);
    let v = v_val * <?php echo $v_gain ?>;
    let omega = omega_val * <?php echo $omega_gain ?>;
    if (v === 0 && omega === 0) {
      // nothing pressed: send a single stop, then stay quiet (the wheels driver also stops
      // by itself when it receives no command for a while)
      if (window.mission_control_moving) {
        send_pwm({left: 0, right: 0});
        window.mission_control_moving = false;
      }
      return;
    }
    send_pwm(RobotCalibration.toPWM(window.mission_control_calibration, v, omega));
    window.mission_control_moving = true;
  }//publish_command

  $(document).ready(function() {
    window.mission_control_Mode = 'autonomous';
    // attach listeners to key events
    window.addEventListener("keyup", key_cb, false);
    window.addEventListener("keydown", key_cb, false);
    // publish commands to the vehicle at regular rate
    setInterval(publish_command, <?php echo intval(1000.0 / $output_commands_hz) ?>);
  });

  $('#vehicle_driving_mode_toggle').change(function(){
    if ($(this).prop('checked')){
      // change the page background
      $('body').css('background-image', 'linear-gradient(to top, #F7F7F6, #FFC800, #F7F7F6)');
      $('#vehicle_driving_mode_status').html('Manual');
      window.mission_control_Mode = 'manual';
      // taking over disables the autopilot (e.g. stops lane following), as the keyboard controller does
      window.mission_control_dtps.publish("node/control_mapper/autopilot", {
        header: DTPS.header(),
        data: false
      }).catch(function (error) {
        console.warn('Cannot disable the autopilot: ', error);
      });
    }else{
      $('body').css('background-image', 'none');
      $('#vehicle_driving_mode_status').html('Autonomous');
      window.mission_control_Mode = 'autonomous';
      // release the wheels
      for (let key in window.mission_control_keyMap) window.mission_control_keyMap[key] = false;
      send_pwm({left: 0, right: 0});
      window.mission_control_moving = false;
    }
  });

</script>
