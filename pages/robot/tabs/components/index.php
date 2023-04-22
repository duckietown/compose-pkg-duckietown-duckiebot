<?php
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;


$dbot_hostname = Duckiebot::getDuckiebotHostname();
?>

<style type="text/css">
    #_robot_components_div {
        margin: auto;
        text-align: center;
    }
    
    #_placeholder_img{
        padding-top: 100px;
        text-align: center;
    }
    
    ._robot_component_container {
        background-color: #eaeaea;
        border-radius: 4px;
        margin: 30px 0;
        height: 100px;
        width: 1000px;
    }
    
    ._robot_component_container > i.fa-spinner {
        color: darkgrey;
        margin-top: 30px;
    }
    
    ._robot_component_container nav{
        height: 100px;
        width: 1000px;
        display: none;
    }
    
    ._robot_component_container nav .container-fluid{
        padding: 0;
    }
    
    ._robot_component_container nav .collapse{
        padding: 0;
        width: 100%;
    }
    
    ._robot_component_container nav table{
        width: 100%;
    }
    
    ._robot_component ._robot_component_icon{
        min-width: 100px;
        max-width: 100px;
        border-right: 1px solid lightgrey;
    }
    
    ._robot_overall_status_icon{
        min-width: 130px;
        max-width: 130px;
        border-right: 1px solid lightgrey;
    }
    
    ._robot_component ._robot_component_icon i.fa{
        font-size: 18pt;
    }
    
    ._robot_overall_status_icon i.fa{
        font-size: 60pt;
    }
    
    ._robot_component ._robot_component_info{
        min-width: 500px;
        max-width: 500px;
        padding: 0 15px;
    }
    
    ._robot_component ._robot_component_info h4{
        margin: 12px 0 6px 0;
    }
    
    ._robot_component ._robot_component_info h6{
        margin: 0 0 8px 0;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }
    
    ._robot_component ._robot_component_stats{
        padding: 4px 30px 0 0;
        min-width: 200px;
        max-width: 200px;
        text-align: right;
        vertical-align: top;
    }
    
    ._robot_component ._robot_component_stats h5{
        margin-bottom: 0;
    }
    
    ._robot_component ._robot_component_connector {
        padding: 8px 15px 0 15px;
    }
    
    ._robot_component ._robot_component_bus,
    ._robot_overall_status_reason {
        font-family: monospace;
        font-size: 9pt;
    }
    
    ._robot_overall_status_info {
        padding-left: 20px;
    }
    
    .navbar-bad{
        background-image: -webkit-linear-gradient(top, #ff8080 0, #ff9d9d 100%);
        background-image: -o-linear-gradient(top,#ff8080 0,#ff9d9d 100%);
        background-image: -webkit-gradient(linear,left top,left bottom,from(#ff8080),to(#ff9d9d));
        background-image: linear-gradient(to bottom,#ff8080 0,#ff9d9d 100%)
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffff8080', endColorstr='#ffff9d9d', GradientType=0);
        filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
    }
    
    .navbar-good{
        background-image: -webkit-linear-gradient(top, #8bc34a 0, #bdea88 100%);
        background-image: -o-linear-gradient(top,#8bc34a 0,#bdea88 100%);
        background-image: -webkit-gradient(linear,left top,left bottom,from(#8bc34a),to(#bdea88));
        background-image: linear-gradient(to bottom,#8bc34a 0,#bdea88 100%)
        filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff8bc34a', endColorstr='#ffbdea88', GradientType=0);
        filter: progid:DXImageTransform.Microsoft.gradient(enabled=false);
    }
</style>


<div id="_placeholder_img">
    <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt=""/>
</div>

<div id="_robot_components_overall_div"></div>

<div id="_robot_components_div"></div>


<script type="text/javascript">
    
    let _api_url = "http://<?php echo $dbot_hostname ?>/{api}/{action}/{resource}";
    window.ROBOT_COMPONENT_TYPE_TO_ICON = {
        "HAT": "microchip",
        "SCREEN": "desktop",
        "CAMERA": "video-camera",
        "IMU": "compass",
        "BUS_MULTIPLEXER": "list-ol",
        "TOF": "eye",
        "MOTOR": "car",
        "BATTERY": "battery",
        "FLIGHT_CONTROLLER": "plane",
        "WIRELESS_ADAPTER": "wifi",
    };
    window.ROBOT_COMPONENT_DEFAULT_ICON = "square";
    
    let _pholder_nav = `
    <div class="_robot_component_container" id="_robot_component_{name}">
        <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
    </div>
    `;
    
    let _nav = `
    <nav class="navbar navbar-default" role="navigation">
        <div class="container-fluid">
            <div class="collapse navbar-collapse navbar-left">
                <table class="_robot_component">
                    <tr>
                        <td rowspan="2" class="text-center _robot_component_icon">
                            <i class="fa fa-{icon}" aria-hidden="true"></i>
                        </td>
                        <td class="_robot_component_info">
                            <h4 class="text-left">{name}</h4>
                            <h6 class="text-left">{description}</h6>
                        </td>
                        <td rowspan="2" class="_robot_component_stats">
                            <h5><strong>Supported:</strong> {supported}</h5>
                            <h5><strong>Detected:</strong> {detected}</h5>
                            {calibrated}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="col-md-12 text-center _robot_component_connector">
                            <h5 class="text-left _robot_component_bus">
                                <strong>Connector:</strong>
                                <span class="text-left _robot_component_bus">
                                {bus}
                                </span>
                            </h5>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </nav>
    `;
    
    let _overall_nav = `
    <nav class="navbar navbar-{style}" role="navigation">
        <div class="">
            <div class="collapse navbar-collapse navbar-left">
                <table class="_robot_overall_status" style="height: 120px">
                    <tr>
                        <td rowspan="2" class="text-center _robot_overall_status_icon">
                            <i class="fa fa-{icon}" aria-hidden="true" style="color: {color}"></i>
                        </td>
                        <td class="_robot_overall_status_info">
                            <h2 class="text-left" style="margin-top: 10px; margin-bottom: 0; color: {color}">{status}</h2>
                            <h5 class="text-left" style="margin-top: 0">Overall Status</h5>
                        </td>
                    </tr>
                    <tr>
                        <td class="col-md-12 _robot_overall_status_info">
                            {explanation}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </nav>
    <hr>`;
    
    let _overall_failure_nav = `
    <h5 class="text-left">
        <strong>Reason:</strong>
        <span class="_robot_overall_status_reason">{missing}</span>
        not found.
    </h5>`;
    
    function api_url(api, action, args) {
        return _api_url.format({api: api, action: action, resource: args.join('/')}).rstrip('/')
    }
    
    function status_icon(value) {
        if (value) {
            return '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green" data-toggle="tooltip" data-placement="right" title="Yes"></span>';
        }
        return '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red" data-toggle="tooltip" data-placement="right" title="No"></span>';
    }
    
    function render_components(data) {
        let container_div = $('#_robot_components_div');
        // sort by "supported"
        let components = Object.values(data).sort((a, b) => (a.supported > b.supported) ? -1 : 1);
        container_div.append('<h4><span class="label label-default">Officially Supported</span></h4>');
        let missing = [];
        for (let i = 0; i < components.length; i++) {
            let component = components[i];
            if (i > 0 && component.supported !== components[i-1].supported) {
                container_div.append("<hr/>");
                container_div.append('<h4><span class="label label-default">Optional</span></h4>');
            }
            let name = component.name;
            container_div.append(
                _pholder_nav.format({name: i})
            );
            let div = $('#_robot_component_{name}'.format({name: i}));
            let icon = window.ROBOT_COMPONENT_TYPE_TO_ICON.hasOwnProperty(component.type) ?
                window.ROBOT_COMPONENT_TYPE_TO_ICON[component.type] : window.ROBOT_COMPONENT_DEFAULT_ICON;
            let description = component.hasOwnProperty('description') ? component.description : '(no description)';
            let bus = "Bus {0} #{1} - Channel #{2} - Address {3}".format(
                component.bus.description, component.bus.number, component.instance, component.address
            );
            let supported = status_icon(component.supported);
            let detected = status_icon(component.detected);
            let calibrated = component.hasOwnProperty('calibration')? (
                component.calibration.needed?
                    "<h5><strong>Calibrated:</strong> {0}</h5>".format(status_icon(component.calibration.completed)) : ''
            ) : '';
            // create component's nav
            div.html(
                _nav.format({
                    name: name,
                    icon: icon,
                    description: description,
                    bus: bus,
                    supported: supported,
                    detected: detected,
                    calibrated: calibrated
                })
            );
            div.find('nav').css('display', 'inherit');
            if (component.supported && !component.detected) {
                missing.push(component.name);
            }
        }
        $('#_robot_components_overall_div').html(_overall_nav.format({
            status: (missing.length > 0)? 'Some components were not detected' : 'Healthy',
            icon: (missing.length > 0)? 'exclamation-circle' : 'check-circle-o',
            style: (missing.length > 0)? 'bad' : 'good',
            color: (missing.length > 0)? 'darkred' : 'darkgreen',
            explanation: (missing.length > 0)? _overall_failure_nav.format({
                    missing: missing.join(", ")
                }) :
                'All the components supported by your robot model are detected.',
        }))
    }
    
    function _component_calib_action(component, action) {
    
    }
    
    function _on_list_success (data) {
        $('#_placeholder_img').css('display', 'none');
        render_components(data.components);
    }
    
    function _on_code_api_error (data) {
        $('#_placeholder_img').css('display', 'none');
        $('#_robot_components_div').html(
            '<h4>Cannot fetch list of components. Contact your system administrator.</h4>'
        );
    }
    
    $(document).ready(function(){
        let url = api_url('health', 'components', []);
        callExternalAPI(
            url, 'GET', 'json', false, false,
            _on_list_success, true, false, _on_code_api_error
        );
    });
    
</script>
