<?php
use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;


class Duckiebot_Calibration extends BlockRenderer{

  static protected $ICON = [
    "class" => "glyphicon",
    "name" => "wrench"
  ];

  static protected $ARGUMENTS = [
    "service" => [
      "name" => "ROS Service",
      "type" => "text",
      "mandatory" => True
    ],
    "trim_param" => [
      "name" => "Trim parameter name",
      "type" => "text",
      "mandatory" => True
    ],
    "topic" => [
      "name" => "ROS Topic",
      "type" => "text",
      "mandatory" => True
    ],
    "background_color" => [
      "name" => "Background color",
      "type" => "color",
      "mandatory" => True,
      "default" => "#fff"
    ]
  ];

  protected static function render($id, &$args){
    ?>
    <div id="block_content">

      <table style="width: 100%">
        <tr>
          <td style="width: 80%; padding: 0 30px">

            <div class="col-md-2" style="padding-right:20px">
              <img style="height: 70px; float: right"
                src="<?php echo Core::getImageURL('trim_calibration.png', 'duckietown_duckiebot') ?>">
            </div>
            <div class="col-md-10" id="slide_container">
              <table style="width: 100%">
                <tr>
                  <td colspan="2" style="width: 20%">-1.0</td>
                  <td style="width: 10%"></td>
                  <td style="width: 10%"></td>
                  <td class="text-center" colspan="2" style="width: 20%">0.0</td>
                  <td style="width: 10%"></td>
                  <td style="width: 10%"></td>
                  <td colspan="2" style="width: 20%">1.0</td>
                </tr>
                <tr>
                  <?php
                  for ($i=0; $i < 10; $i++) {
                    ?>
                    <td class="ticks" style="width: 10%">&nbsp;</td>
                    <?php
                  }
                  ?>
                </tr>
                <tr>
                  <td colspan="10">&nbsp;</td>
                </tr>
              </table>
              <input
                type="range"
                min="1"
                max="100"
                value="51"
                class="slider"
                id="trim_range"
                disabled>
            </div>

          </td>
          <td style="width: 20%">
            <a class="btn btn-danger disabled" id="run_test_btn" role="button">
              <i class="fa fa-retweet" aria-hidden="true"></i>
              <br/>
              Run test
            </a>
          </td>
        </tr>
      </table>

    </div>

    <!-- Include ROS -->
    <script src="<?php echo Core::getJSscriptURL('rosdb.js', 'ros') ?>"></script>

    <script type="text/javascript">
      $(document).on("<?php echo ROS::$ROSBRIDGE_CONNECTED ?>", function(evt){
        // create connection point to server
        var trim = new ROSLIB.Service({
          ros : window.ros,
          name : '<?php echo $args['service'] ?>',
          messageType : 'duckietown_msgs/SetValue'
        });
        // configure callback
        $("#<?php echo $id ?> #trim_range").change(function() {
          var value = $(this).val();
          value = -0.75 * ((value - 51) / 100.0);
          // create request object
          var request = new ROSLIB.ServiceRequest({
            value : value
          });
          // send request
          // console.log(value);
          trim.callService(request, function(result) {});
        });
        // advertise commands resource
        ROSDB.advertise(
          'commands',
          '<?php echo $args['topic'] ?>',
          'duckietown_msgs/Twist2DStamped',
          10,
          1
        );
        // get current trim
        var trim_param = new ROSLIB.Param({
          ros : window.ros,
          name : '<?php echo $args['trim_param'] ?>'
        });
        trim_param.get(function(value) {
          // set bar value
          var val = ((-value / 0.75) * 100) + 51;
          $('#<?php echo $id ?> #slide_container #trim_range').val(val);
          // enable bar
          $('#<?php echo $id ?> #slide_container #trim_range').prop('disabled', false);
          $('#<?php echo $id ?> #block_content #run_test_btn').removeClass('disabled');
        });
      });

      $('#<?php echo $id ?> #block_content #run_test_btn').on('click', function(evt){
        window.ROSDB.publish('commands', {v: 0.16, omega: 0.0});
        setTimeout(
          function(){
            window.ROSDB.publish('commands', {v: 0.0, omega: 0.0});
            window.ROSDB.pause('commands');
          },
          4000
        );
      });
    </script>

    <style type="text/css">
      #<?php echo $id ?>{
        background-color: <?php echo $args['background_color'] ?>;
      }

      #<?php echo $id ?> #slide_container {
        font-weight: bold;
      }

      #<?php echo $id ?> #slide_container table tr {
        line-height: 10px;
      }

      #<?php echo $id ?> #slide_container table tr:first-child {
        line-height: 20px;
      }

      #<?php echo $id ?> #slide_container table tr td {
        padding: 0;
      }

      #<?php echo $id ?> #slide_container table tr:first-child td:first-child {
        text-align: left;
      }

      #<?php echo $id ?> #slide_container table tr:first-child td:last-child {
        text-align: right;
      }

      #<?php echo $id ?> #slide_container table tr:nth-child(2) td {
        border-left: 1px solid grey;
        border-right: 1px solid grey;
      }

      #<?php echo $id ?> #slide_container .slider {
        -webkit-appearance: none;
        width: 100%;
        height: 15px;
        border-radius: 5px;
        background: #d3d3d3;
        outline: none;
        -webkit-transition: .2s;
        transition: opacity .2s;
      }

      #<?php echo $id ?> #slide_container .slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: #d14c48;
        cursor: pointer;
      }

      #<?php echo $id ?> #slide_container .slider::-moz-range-thumb {
        width: 25px;
        height: 25px;
        border-radius: 50%;
        background: #d14c48;
        cursor: pointer;
      }

      #<?php echo $id ?> #block_content #run_test_btn {
        font-size: 14pt;
        padding: 20px;

      }

      #<?php echo $id ?> #block_content #run_test_btn .fa {
        font-size: 20pt;
      }
    </style>
    <?php
  }//render

}//DuckiebotCalibration
?>
