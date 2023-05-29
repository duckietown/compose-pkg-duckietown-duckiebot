<?php
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$update_hz = 1.0;
?>

<style type="text/css">
    #_robot_software_div {
        margin: auto;
        text-align: center;
    }
    
    #_placeholder_img{
        padding-top: 100px;
        text-align: center;
    }
    
    ._robot_software_module_container {
        background-color: #eaeaea;
        border-radius: 4px;
        margin: 30px 0;
        height: 100px;
        width: 1000px;
    }
    
    ._robot_software_module_container > i.fa-spinner {
        color: darkgrey;
        margin-top: 30px;
    }
    
    ._robot_software_module_container nav{
        height: 100px;
        width: 1000px;
        display: none;
    }
    
    ._robot_software_module_container nav .container-fluid{
        padding: 0;
    }
    
    ._robot_software_module_container nav .collapse{
        padding: 0;
        width: 100%;
    }
    
    ._robot_software_module_container nav table{
        width: 100%;
    }
    
    ._robot_software_module ._robot_software_module_icon{
        min-width: 100px;
        max-width: 100px;
        border-right: 1px solid lightgrey;
    }
    
    ._robot_software_module ._robot_software_module_icon i.fa{
        font-size: 18pt;
    }
    
    ._robot_software_module ._robot_software_module_info{
        min-width: 500px;
        max-width: 500px;
        padding: 0 15px;
    }
    
    ._robot_software_module ._robot_software_module_info h4{
        margin: 12px 0 6px 0;
    }
    
    ._robot_software_module ._robot_software_module_info h6{
        margin: 0 0 8px 0;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    ._robot_software_module ._robot_software_module_version{
        min-width: 200px;
        max-width: 200px;
        text-align: right;
    }
    
    ._robot_software_module ._robot_software_module_version h5{
        margin-bottom: 0;
    }
    
    ._robot_software_module ._robot_software_module_actions{
        min-width: 200px;
        max-width: 200px;
    }
    
    ._robot_software_module ._robot_software_module_pbar_container{
        padding: 10px 15px 0 15px;
    }
    
    ._robot_software_module ._robot_software_module_pbar_container .progress{
        margin-bottom: 6px;
        visibility: hidden;
    }
    
    ._robot_software_module ._robot_software_module_status_desc {
        /*font-family: monospace;*/
        margin-top: -20px;
        font-size: 9pt;
    }
</style>


<div id="_placeholder_img">
    <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt=""/>
</div>

<div id="_robot_software_div"></div>


<script type="text/javascript">

    window.ROBOT_SOFTWARE_MODULES_INFO = {};
    window.ROBOT_SOFTWARE_MODULES_UPDATER = null;
    
    let _module_pholder_nav = `
    <div class="_robot_software_module_container" id="_robot_software_module_{name}">
        <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
    </div>
    `;
    
    let _module_nav = `
    <nav class="navbar navbar-default" role="navigation">
        <div class="container-fluid">
            <div class="collapse navbar-collapse navbar-left">
                <table class="_robot_software_module">
                    <tr>
                        <td rowspan="2" class="text-center _robot_software_module_icon">
                            <i class="fa fa-{icon}" aria-hidden="true"></i>
                        </td>
                        <td class="_robot_software_module_info">
                            <h4 class="text-left">{name}</h4>
                            <h6 class="text-left">{description}</h6>
                        </td>
                        <td class="_robot_software_module_version">
                            <h5><strong>Current:</strong> {version_local}</h5>
                            <h5><strong>Available:</strong> {version_remote}</h5>
                        </td>
                        <td class="_robot_software_module_actions">
                        
                            <div class="btn-group _robot_software_module_update_action">
                                <button type="button" class="btn btn-{btn_style}" {btn_html} onclick="update_module('{name}')">
                                    <i class="fa fa-{btn_icon}" aria-hidden="true"></i>&nbsp; {btn_label}
                                </button>
                                <button type="button" class="btn btn-{btn_style} dropdown-toggle" {btn_html} data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="height: 34px">
                                    <span class="caret"></span>
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="#" onclick="update_module('{name}', true)">
                                            Force Update
                                        </a>
                                    </li>
                                </ul>
                            </div>

                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="col-md-12 text-center _robot_software_module_pbar_container">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0"></div>
                            </div>
                            <h5 class="text-left _robot_software_module_status_desc">
                                <strong>Status:</strong>
                                {status_desc}
                            </h5>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </nav>
    `;
    
    function _update(data) {
        for (const [name, status] of Object.entries(data.data)) {
            let div = $('#_robot_software_module_{name}'.format({name: name}));
            let pbar = div.find('div.progress-bar');
            if (status.hasOwnProperty('progress') && status.progress > 0 && status.progress <= 100) {
                div.find('div.progress').css('visibility', 'inherit');
                div.find('h5._robot_software_module_status_desc').css('display', 'none');
                // update progress bar
                pbar.css('width', '{0}%'.format(status.progress));
                pbar.html(status.status_txt);
            }
            // finished?
            if (status.status === 'UPDATED') {
                pbar.css('width', '100%'.format(status.progress));
                pbar.addClass('progress-bar-success');
                pbar.html('Updated!');
                // TODO: need to update the button here
            } else if (status.status === 'ERROR') {
                pbar.css('width', '100%'.format(status.progress));
                pbar.addClass('progess-bar-danger');
                // TODO: need to update the button here
            }
        }
    }
    
    function update_module(name, force=false) {
        // compile command url
        let url = get_api_url('code', 'module/update', [name + '?force={0}'.format(force? '1' : '0')]);
        callExternalAPI(url, 'GET', 'json', false, false, function(res){
            if (res.status === 'need-force') {
                let aux_msg = "Use the button <b>Force Update</b> from the dropdown to force the update.";
                openAlert('warning', '{0}<br/><br/>{1}'.format(res.message, aux_msg));
            } else if (res.status === 'error') {
                openAlert('danger', res.message);
            } else if (res.status === 'ok') {
                // start updating (if not done yet)
                _keep_updating();
            } else {
                openAlert('warning', '{0}: {1}'.format(res.status, res.message));
            }
        }, true, true);
    }
    
    function _keep_updating() {
        if (window.ROBOT_SOFTWARE_MODULES_UPDATER === null) {
            let url = get_api_url('code', 'modules/status');
            window.ROBOT_SOFTWARE_MODULES_UPDATER = setInterval(function(){
                callExternalAPI(url, 'GET', 'json', false, false, _update, true, true);
            }, 1000 * (1 / <?php echo $update_hz ?>));
        }
    }
    
    function render_modules(data) {
        for (const [name, status] of Object.entries(data.data)) {
            let div = $('#_robot_software_module_{name}'.format({name: name}));
            let info = window.ROBOT_SOFTWARE_MODULES_INFO[name];
            let icon = info.module.hasOwnProperty('icon') ? info.module.icon : 'cube';
            let description = info.module.hasOwnProperty('description') ? info.module.description : '(no description)';
            // update button style and label
            let btn_label = '';
            let btn_style = '';
            let btn_icon = '';
            let btn_html = '';
            let status_desc = '';
            switch (status.status) {
                case 'UPDATED':
                    btn_label = 'Up to date';
                    btn_style = 'success';
                    btn_icon = 'check';
                    btn_html = 'disabled="disabled"';
                    status_desc = 'Your local version is the latest available.';
                    break;
                case 'AHEAD':
                    btn_label = 'Update';
                    btn_style = 'warning';
                    btn_icon = 'cloud-download';
                    status_desc = 'Your local version is <b>ahead</b> of the official version.' +
                        'This is OK if you are a developer.';
                    break;
                case 'BEHIND':
                    btn_label = 'Update';
                    btn_style = 'primary';
                    btn_icon = 'cloud-download';
                    status_desc = 'Your local version is <b>old</b>. ' +
                        'Update to the newest version now!';
                    break;
                case 'UNKNOWN':
                    btn_label = 'Not ready';
                    btn_style = 'default';
                    btn_icon = 'exclamation';
                    btn_html = 'disabled="disabled"';
                    status_desc = 'We don\'t have information about this module right now. ' +
                        'Try again later.';
                    break;
                case 'ERROR':
                    btn_label = 'Error';
                    btn_style = 'danger';
                    btn_icon = 'exclamation-triangle';
                    btn_html = 'disabled="disabled"';
                    status_desc = 'ERROR: {0}'.format(status.status_txt);
                    break;
                case 'UPDATING':
                    btn_label = 'Updating';
                    btn_style = 'default';
                    btn_icon = 'circle-o-notch fa-spin fa-fw';
                    btn_html = 'disabled="disabled"';
                    status_desc = 'Updating...';
                    break;
            }
            // create module's nav
            div.html(
                _module_nav.format({
                    name: name,
                    icon: icon,
                    description: description,
                    version_local: (status.version.local.head === 'ND')? 'devel' : status.version.local.head,
                    version_remote: status.version.remote.head,
                    btn_label: btn_label,
                    btn_style: btn_style,
                    btn_icon: btn_icon,
                    btn_html: btn_html,
                    status_desc: status_desc
                })
            );
            div.find('nav').css('display', 'inherit');
        }
        // once this is done, start updating (if not done yet)
        _keep_updating();
    }
    
    function render_modules_placeholders() {
        let div = $('#_robot_software_div');
        for (const [name, _] of Object.entries(window.ROBOT_SOFTWARE_MODULES_INFO)) {
            div.append(
                _module_pholder_nav.format({name: name})
            );
        }
    }
    
    function _on_modules_list_success (data) {
        $('#_placeholder_img').css('display', 'none');
        window.ROBOT_SOFTWARE_MODULES_INFO = data.data;
        render_modules_placeholders();
        // get modules status
        let url = get_api_url('code', 'modules/status');
        callExternalAPI(
            url, 'GET', 'json', false, false,
            render_modules, true, false, _on_code_api_error
        );
    }
    
    function _on_code_api_error (data) {
        $('#_placeholder_img').css('display', 'none');
        $('#_robot_software_div').html(
            '<h4>Cannot fetch list of modules. Contact your system administrator.</h4>'
        );
    }
    
    $(document).ready(function(){
        let url = get_api_url('code', 'modules/info');
        callExternalAPI(
            url, 'GET', 'json', false, false,
            _on_modules_list_success, true, false, _on_code_api_error
        );
    });
    
</script>
