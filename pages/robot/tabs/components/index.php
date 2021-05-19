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
    
    ._robot_component ._robot_component_icon i.fa{
        font-size: 18pt;
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
    
    ._robot_component ._robot_component_actions{
        min-width: 200px;
        max-width: 200px;
    }
    
    ._robot_component ._robot_component_connector {
        padding: 8px 15px 0 15px;
    }
    
    ._robot_component ._robot_component_bus {
        font-family: monospace;
        font-size: 9pt;
    }
</style>


<div id="_placeholder_img">
    <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt=""/>
</div>

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
        for (const [id, component] of Object.entries(data)) {
            let name = component.name;
            container_div.append(
                _pholder_nav.format({name: id})
            );
            let div = $('#_robot_component_{name}'.format({name: id}));
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
        }
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
