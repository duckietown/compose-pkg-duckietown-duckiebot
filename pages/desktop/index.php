<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_hostname = Duckiebot::getDuckiebotHostname();
$update_hz = 1.0;
$vnc_module_name = 'dt-gui-tools';
$vnc_container_name = 'desktop-environment';
$vnc_launcher_name = 'vnc';

$desktop_api_port = 8087;
$code_api_url = sprintf("http://%s/code/{resource}", $dbot_hostname);
$desktop_api_url = sprintf("http://%s:%s", $dbot_hostname, $desktop_api_port);
?>

<style type="text/css">
    #page_container{
      min-width: 100%;
    }
    
    ._ctheme_content {
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        border-top: 1px solid black;
        border-left: 1px solid black;
    }
    
    #desktop_iframe {
        width: 100%;
        height: 100%;
        position: absolute;
        bottom: 0;
        top: 0;
    }
    
    #_desktop_placeholder {
        padding-top: 100px;
        text-align: center;
    }
</style>


<div id="_desktop_placeholder">
    <div id="_desktop_loader">
        <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt=""/>
        <br/>
        Loading...
        <br/>
        Status: <span id="_desktop_status_str">...</span>
    </div>
    
    <div id="_desktop_launcher" style="display: none">
        <br/>
        <br/>
        <button class="btn btn-primary" type="button"
                onclick="_toggle_desktop_container('run');">
            &nbsp;
            Launch Desktop Environment
        </button>
    </div>
    
    <div id="_desktop_control_panel" style="display: none">
        <br/>
        <br/>
        <button class="btn btn-danger" type="button"
                onclick="_toggle_desktop_container('stop');">
            <i class="fa fa-stop" aria-hidden="true"></i>
            &nbsp; Stop
        </button>
        &nbsp;
        <button class="btn btn-warning" type="button"
                onclick="_toggle_desktop_container('restart');">
            <i class="fa fa-refresh" aria-hidden="true"></i>
            &nbsp; Restart
        </button>
    </div>
</div>

<iframe
  id="desktop_iframe"
  frameborder="0"
  scrolling="yes"
  style="visibility: hidden"
></iframe>

<script type="text/javascript">
    let CHECK_CONTAINER_JOB = null;
    let MONITOR_IFRAME_JOB = null;
    let CONTAINER_LAUNCHED = false;
    let CODE_API_URL = "<?php echo $code_api_url ?>";
    let DESKTOP_CONTAINER_STATUS = 'UNKNOWN';
    
    window.addEventListener('message', function(event){
        try {
            let data = JSON.parse(event.data);
            if (!data.hasOwnProperty('from') || !data.hasOwnProperty('state')) return;
            if (data['from'] !== 'novnc') return;
            if (data['state'] === 'connecting') {
                clearInterval(MONITOR_IFRAME_JOB);
                MONITOR_IFRAME_JOB = setInterval(_refresh_iframe, 10000);
            }
            if (data['state'] === 'connected') {
                _on_desktop_environment_loaded();
                clearInterval(MONITOR_IFRAME_JOB);
            }
        } catch (e) {}
    }, false);
    
    function code_api_url(args=[]) {
        return CODE_API_URL.format({resource: args.join('/')}).rstrip('/')
    }
    
    $(document).ready(function(){
        _desktop_check_vnc_container_status();
        CHECK_CONTAINER_JOB = setInterval(_desktop_check_vnc_container_status, 2000);
    });
    
    function _on_desktop_environment_loaded(){
        let desktop_iframe = $('#desktop_iframe');
        desktop_iframe.css('visibility', 'inherit');
        // hide placeholder
        $('#_desktop_placeholder').css('display', 'none');
    }
    
    function _refresh_iframe(){
        $('#desktop_iframe').attr('src', '<?php echo $desktop_api_url ?>');
    }
    
    function _on_desktop_container_status(response){
         let status = response.data.status;
         if (status === 'RUNNING' && DESKTOP_CONTAINER_STATUS === 'RUNNING') {
             // vnc is running, wait for the iframe to load, then release and refresh the iframe
             clearInterval(CHECK_CONTAINER_JOB);
             if (MONITOR_IFRAME_JOB === null) {
                 _refresh_iframe();
                 MONITOR_IFRAME_JOB = setInterval(_refresh_iframe, 5000);
             }
             // show control panel
             $('#_desktop_control_panel').css('display', 'inherit')
         } else if (status !== 'RUNNING' && status !== 'CREATED' && status !== 'PAUSED' &&
                    !CONTAINER_LAUNCHED) {
            $('#_desktop_launcher').css('display', 'inherit');
         }
         DESKTOP_CONTAINER_STATUS = status;
         $('#_desktop_status_str').html(' [<strong>' + DESKTOP_CONTAINER_STATUS + '</strong>] ');
    }
    
    function _on_code_api_error(response){
        openAlert(
            'warning',
            'Error occurred while checking the status of the desktop-environment container.<br/>' +
            'The error reads:<br/><br/><code>{0}</code>'.format(response.message)
        );
        clearInterval(CHECK_CONTAINER_JOB);
    }
    
    function _desktop_check_vnc_container_status(){
        let url = code_api_url(['container', 'status', '<?php echo $vnc_container_name ?>']);
        callExternalAPI(
            url, 'GET', 'json', false, false,
            _on_desktop_container_status, true, false, _on_code_api_error
        );
    }
    
    function _toggle_desktop_container(action) {
        let url = code_api_url(['container', action, '<?php echo $vnc_container_name ?>']);
        // special treatment for `run` action
        if (action === 'run'){
            let args = {
                'name': '<?php echo $vnc_container_name ?>',
                'launcher': '<?php echo $vnc_launcher_name ?>'
            };
            let qs = '?' + $.param(args);
            url = code_api_url(['container', action, '<?php echo $vnc_module_name ?>', qs]);
        }
        // call API
        callExternalAPI(
            url, 'GET', 'json', false, false,
            undefined, true, false, _on_code_api_error
        );
        // hide launcher button
        $('#_desktop_launcher').css('display', 'none');
        CONTAINER_LAUNCHED = true;
    }
</script>