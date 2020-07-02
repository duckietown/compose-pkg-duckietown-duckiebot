<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;


$step_no = $_COMPOSE_SETUP_STEP_NO;

$dbot_hostname = 'watchtower01.local';
$dbot_type = 'watchtower';
$health_api_port = 8085;
$files_api_port = 8082;
$update_hz = 0.5;

$image_template = Core::getImageURL('robots/thumbnails/{0}.jpg', 'duckietown');

if(
    (
      (isset($_GET['step']) && $_GET['step'] == $step_no) ||
      (isset($_GET['force_step']) && $_GET['force_step'] == $step_no)
    ) &&
    (
      isset($_GET['confirm']) && $_GET['confirm'] == '1'
    )
  ){
  _compose_first_setup_step_in_progress();
  // confirm step
  $first_setup_db = new Database('core', 'first_setup');
  $first_setup_db->write('step'.$step_no, null);

  // redirect to setup page
  Core::redirectTo('setup');
}
?>

<div style="margin: 20px 0 10px 30px">
  <form id="step-form">
    <p>
      <span class="_label">Configure your Duckietown device.</span>
      <br/>
      <br/>
        
        <style type="text/css">
    .robot-type-image-container {
        height: 50%;
        width: 50%;
        position: relative;
        background: white;
        border: 1px solid lightgrey;
    }

    .robot-type-image-container:after {
        content: "";
        display: block;
        padding-bottom: 100%;
    }

    .robot-type-image-container img {
        /*max-height: 70%;*/
        /*max-width: 70%;*/
        width: auto;
        height: auto;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        margin: auto;
    }

    .robot-info-container dt{
        width: 80px;
    }

    .robot-info-container dd{
        margin-left: 100px;
    }
</style>


<div class="row">
    <div class="col-md-6 robot-type-image-container text-center">
        <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt="">
    </div>
    <div class="col-md-6 robot-info-container" id="robot-configurator-form"></div>
</div>
    
    </p>

    <button type="button" class="btn btn-success _finished" id="confirm-step-button" style="float: right">
      <span class="fa fa-arrow-down" aria-hidden="true"></span>&nbsp; Next
    </button>
  </form>
</div>


<script type="text/javascript">
    
    let ROBOT_CONFIGURATOR_SCHEMA = {
        'type': 'form',
        'details': 'Robot configuration',
        '_data': {
            'robot_type': {
                'type': 'enum',
                'values': [
                    'duckiebot',
                    'duckiedrone',
                    'duckietown',
                    'traffic_light',
                    'watchtower'
                ],
                'details': 'Type of robot to configure',
                '_form': {
                    'labels': [
                        'Duckiebot',
                        'Duckiedrone',
                        'Duckietown',
                        'Traffic Light ASD',
                        'Watchtower'
                    ]
                }
            }
        }
    };

    $(document).ready(function () {
        // create form
        let form = new ComposeForm(null, ROBOT_CONFIGURATOR_SCHEMA);
        form.render('robot-configurator-form');
        
        // get robot type
        let url = "http://<?php echo $dbot_hostname ?>:<?php echo $files_api_port
            ?>/config/robot_type";
        //callExternalAPI(url, 'GET', 'text', false, false, function(data) {
        //    let robot_type = 'unknown';
        //    try {
        //        robot_type = data.split('\n')[0].trim();
        //    } catch (e) {}
        //    let template = '<?php //echo $image_template ?>//';
        //    $('.robot-type-image-container img').attr('src', template.format(robot_type));
        //}, true, true);
        
        $('.robot-type-image-container img').attr('src', '<?php echo $image_template ?>'.format('WT19-B_TL_all'));
    });

</script>
