<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Configuration;
use \system\classes\Database;


$MAX_LEVEL = 4;
$step_no = $_COMPOSE_SETUP_STEP_NO;

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
      <span class="_label">The Duckiebot is being initialized!</span>
      <br/>
      <br/>
      <div class="_placeholder" style="text-align: center; padding: 20px 0">
        <img src="<?php echo Core::getImageURL('loading_blue.gif')?>">
        <br/>
        <br/>
        <h4>Waiting for the <b>device-loader</b> container...</h4>
      </div>

      <div class="_finished" style="text-align: center; padding: 20px 0; display: none">
        <div class="row">
          <div class="col-md-4 text-right">
            <!-- <img src="<?php echo Core::getImageURL('duckiebot.png', 'duckietown_duckiebot')?>" width="250px"> -->
            <span class="glyphicon glyphicon-ok" aria-hidden="true" style="font-size: 34px; color:darkgreen"></span>
          </div>
          <div class="col-md-8 text-left">
            <h4>Your Duckiebot is now ready to drive!</h4>
          </div>
        </div>
      </div>


      <div class="row _progress _machine_stats" style="padding-right: 62px; display: none">
        <?php
        $bars = [
          'cpu' => [
            'txt' => 'CPU',
            'icon' => 'tachometer'
          ],
          'temp' => [
            'txt' => 'TEMP',
            'icon' => 'thermometer-half'
          ],
          'disk' => [
            'txt' => 'DISK',
            'icon' => 'database'
          ]
        ];
        foreach ($bars as $key => $spec) {
          ?>
          <div class="col-md-1 text-right" style="padding-right: 0">
            <!-- <input type="text" class="form-control" placeholder="Username" aria-describedby="basic-addon1"> -->
            <?php echo $spec['txt'] ?>&nbsp;
            <i class="fa fa-<?php echo $spec['icon'] ?>" style="font-size: 16px"></i>
          </div>
          <div class="col-md-3">
            <div class="progress" style="height:14px">
              <div
                id="_<?php echo $key ?>_progressbar"
                class="progress-bar"
                role="progressbar"
                aria-valuenow="0"
                aria-valuemin="0"
                aria-valuemax="100"
                style="width:0%">
              </div>
            </div>
          </div>
          <?php
        }
        ?>
      </div>
      <br/>

      <?php
      for ($lvl=0; $lvl < $MAX_LEVEL; $lvl++) {
        $color = ($lvl == 0)? 'success' : 'default';
        ?>
        <div class="loader-progress-row _progress" id="level-<?php echo $lvl ?>-progress-row" style="display:none">
          <div class="row">
            <div class="col-md-10">
              <p class="_action"></p>
            </div>
            <div class="col-md-2">
              <p class="_steps"></p>
            </div>
          </div>
          <div class="row">
            <div class="col-md-10">
              <div class="progress">
                <div
                  class="progress-bar progress-bar-<?php echo $color ?>"
                  role="progressbar"
                  aria-valuenow="0"
                  aria-valuemin="0"
                  aria-valuemax="100"
                  style="width: 0%">
                </div>
              </div>
            </div>
            <div class="col-md-2">
              <p class="_progress_txt"></p>
            </div>
          </div>
        </div>
        <?php
      }
      ?>
    </p>

    <button type="button" class="btn btn-success _finished" id="confirm-step-button" style="float: right; display: none">
      <span class="fa fa-arrow-down" aria-hidden="true"></span>
      &nbsp;
      Next
    </button>
    <button type="button" class="btn btn-default _placeholder _progress" id="skip-step-button" style="float: right; margin-right: 10px">
      <span class="fa fa-fast-forward" aria-hidden="true"></span>
      &nbsp;
      Skip
    </button>
  </form>
</div>


<script type="text/javascript">

  <?php
  $loader_hostname = Core::getSetting('device_loader_host', 'duckietown_duckiebot');
  $loader_port = Core::getSetting('device_loader_port', 'duckietown_duckiebot');
  if(strlen($loader_hostname) < 2){
    $loader_hostname = strstr($_SERVER['HTTP_HOST'], ':', true);
  }
  ?>

  let LOADER_HOSTNAME = "<?php echo $loader_hostname ?>";
  let LOADER_PORT = "<?php echo $loader_port ?>";
  let UPDATE_HZ = 1.0;
  var LOADER_INTERVAL = null;

  function _successFcn(result, status, xhr) {
    if (result.status == "error") return;
    if (result.status == "ready" && result.progress['0'].progress >= 100) {
      clearInterval(LOADER_INTERVAL);
      $('#step-form ._label').css('display', 'none');
      $('#step-form ._progress').css('display', 'none');
      $('#step-form ._placeholder').css('display', 'none');
      $('#step-form ._finished').css('display', 'inherit');
      return;
    }
    $('#step-form ._placeholder').css('display', 'none');
    $('#step-form ._machine_stats').css('display', 'inherit');
    Object.keys(result.progress).forEach(function(key) {
      let action = result.progress[key]['action'];
      let steps = result.progress[key]['steps'];
      let progress = result.progress[key]['progress'];
      let total_steps = steps.total;
      let current_step = Math.min(steps.current+1, steps.total);
      let cpu_usage = result.cpu.usage;
      let cpu_temp = result.cpu.temperature;
      let disk_usage = result.disk.usage;
      if (action == null || steps.total <= 0){
        $('#level-{0}-progress-row'.format(key)).css('display', 'none');
        return;
      }
      $('#level-{0}-progress-row'.format(key)).css('display', 'inherit');
      $('#level-{0}-progress-row ._action'.format(key)).html(action);
      if (total_steps < 100) {
        $('#level-{0}-progress-row ._steps'.format(key)).html('Step {0}/{1}'.format(current_step, total_steps));
      }else {
        $('#level-{0}-progress-row ._steps'.format(key)).html('');
      }
      $('#level-{0}-progress-row .progress-bar'.format(key)).css('width', progress+'%');
      $('#level-{0}-progress-row ._progress_txt'.format(key)).html(progress+'%');
      // machine stats
      // -> cpu usage
      $('#_cpu_progressbar').css('width', cpu_usage+'%');
      $('#_cpu_progressbar').removeClass('progress-bar-default progress-bar-success progress-bar-warning progress-bar-danger');
      $('#_cpu_progressbar').addClass('progress-bar-'+_percentage_to_color(cpu_usage, 10, 80));
      // -> cpu temperature
      $('#_temp_progressbar').css('width', cpu_temp+'%');
      $('#_temp_progressbar').removeClass('progress-bar-default progress-bar-success progress-bar-warning progress-bar-danger');
      $('#_temp_progressbar').addClass('progress-bar-'+_percentage_to_color(cpu_temp, 30, 50));
      // -> disk usage
      $('#_disk_progressbar').css('width', disk_usage+'%');
      $('#_disk_progressbar').removeClass('progress-bar-default progress-bar-success progress-bar-warning progress-bar-danger');
      $('#_disk_progressbar').addClass('progress-bar-'+_percentage_to_color(disk_usage, 25, 80));
    })
  }

  function _errorFcn(errorThrown) {
    for (var lvl = 0; lvl < <?php echo $MAX_LEVEL ?>; lvl++) {
      $('#step-form ._progress').css('display', 'none');
    }
    $('#step-form ._placeholder').css('display', 'inherit');
  }

  function _percentage_to_color(percentage, low, high) {
    if (percentage <= low) {
      return 'success';
    }
    if (percentage <= (low + (high-low) * 0.8)) {
      return 'default';
    }
    if (percentage <= high) {
      return 'warning';
    }
    return 'danger';
  }

  function _update() {
    let url = "http://{0}:{1}/".format(LOADER_HOSTNAME, LOADER_PORT);
    let callType = "GET";
    let resultDataType = "json";
    let successDialog = false;
    let reload = false;
    let silentMode = true;
    let suppressErrors = true;
    callExternalAPI(
      url,
      callType,
      resultDataType,
      successDialog,
      reload,
      _successFcn,
      silentMode,
      suppressErrors,
      _errorFcn
    );

  }

  $('#step-form #skip-step-button').on('click', function(){
    location.href = 'setup?step=<?php echo $step_no ?>&confirm=1';
  });

  $('#step-form #confirm-step-button').on('click', function(){
    location.href = 'setup?step=<?php echo $step_no ?>&confirm=1';
  });

  $(document).ready(function(){
    LOADER_INTERVAL = setInterval(_update, 1000 * (1.0 / UPDATE_HZ));
  });

</script>
