<?php
use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;
?>

<?php
// TODO: get these from ROS param
$v_gain = 0.41;
$omega_gain = 8.3;
$sensitivity = 0.5;
$omega_calibration = 0.06;
$duckiebot_name = Core::getSetting('duckiebot_name', 'duckietown_duckiebot');

// apply sensitivity
$omega_gain *= $sensitivity;
?>

<script type="text/javascript">

  $( document ).ready(function() {
    // define the output topic
    window.mission_control_cmdVel = new ROSLIB.Topic({
      ros : ros,
      name : '/<?php echo $duckiebot_name ?>/joy_mapper_node/car_cmd',
      messageType : 'duckietown_msgs/Twist2DStamped'
    });

    // define the list of keys that can be used to drive the duckiebot
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

    // capture keyboard events (and update buffer accordingly)
    function key_cb(e){
      if (window.mission_control_Mode != 'manual')
        return;
      // space and arrow keys
      if([32, 37, 38, 39, 40].indexOf(e.keyCode) > -1) {
        e.preventDefault();
        window.mission_control_keyMap[e.keyCode] = e.type == "keydown";
      }
    }
    window.addEventListener("keyup", key_cb, false);
    window.addEventListener("keydown", key_cb, false);

    // define the callback function that turns the key_map into a ROS message
    function publish_command(){
      if (window.mission_control_Mode != 'manual')
        return;
      keys = window.mission_control_Keys;
      key_map = window.mission_control_keyMap;
      // compute linear/angular speeds
      v_gain = <?php echo $v_gain ?>;
      omega_gain = <?php echo $omega_gain ?>;
      v_val = Math.min(key_map[keys.UP_ARROW] + key_map[keys.W], 1) - Math.min(key_map[keys.DOWN_ARROW] + key_map[keys.S], 1);
      omega_val = Math.min(key_map[keys.LEFT_ARROW] + key_map[keys.A], 1) - Math.min(key_map[keys.RIGHT_ARROW] + key_map[keys.D], 1);
      //
      omega_calibration = v_val > 0? <?php echo $omega_calibration ?> : 0;
      //
      var twist = new ROSLIB.Message({
        v : v_val * v_gain,
        omega : (omega_val + omega_calibration) * omega_gain
      });
      window.mission_control_cmdVel.publish(twist);
    }

    // publish command at regular rate
    $(document).on('ROSBRIDGE_CONNECTED', function(e){
      // start publishing commands to the duckiebot
      setInterval(publish_command, <?php echo intval(1000.0 / $output_commands_hz) ?>);
    });

  });
</script>
