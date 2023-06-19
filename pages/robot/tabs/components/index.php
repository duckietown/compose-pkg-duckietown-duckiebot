<?php
use \system\classes\Core;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;
use \system\classes\Database;

// TODO: these might not be needed anymore
$dbot_hostname = Duckiebot::getDuckiebotHostname();

$robot_name = Duckiebot::getDuckiebotName();
$robot_type = Duckiebot::getRobotType();
$ros_hostname = ROS::sanitize_hostname($dbot_hostname);

$connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
$error_evt = ROS::get_event(ROS::$ROSBRIDGE_ERROR, $ros_hostname);
$closed_evt = ROS::get_event(ROS::$ROSBRIDGE_CLOSED, $ros_hostname);

$HW_TEST_DB_NAME = Duckiebot::$HARDWARE_TEST_RESULTS_DATABASE_NAME;

ROS::connect($ros_hostname);
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

    .cursor-pointer {
        cursor: pointer;
    }

    .float-right{
        position: fixed;
        right: 40px;
    }

    .top-view-modal {
        z-index:1060;
    }
</style>

<table style="width: 970px; margin: auto; margin-bottom: 12px">
    <tr>
        <td class="text-left" style="width:33%">
            <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
            <strong><?php echo $robot_name ?></strong>
        </td>
        <td class="text-center"
            style="width:33%; border-left: 1px solid lightgrey; border-right: 1px solid lightgrey">
            Components
        </td>
        <td class="text-center" style="width:33%; text-align: right">
        <span id="vehicle_bridge_status">
          <i class="fa fa-spinner fa-pulse"></i> Connecting...
        </span>
        </td>
    </tr>
</table>

<?php include_once __DIR__ . "/modals/imu-game.php" ?>

<script src="<?php echo Core::getJSscriptURL('hardware_test_utils.js', 'duckietown_duckiebot'); ?>"></script>

<div id="_placeholder_img">
    <img src="<?php echo Core::getImageURL('loading_blue.gif') ?>" alt=""/>
</div>

<div id="_robot_components_overall_div"></div>

<div id="_robot_components_div"></div>



<script type="text/javascript">

    $(document).on("<?php echo $connected_evt ?>", function (evt) {
        console.log('Connected to websocket server.');
        $('#vehicle_bridge_status').html(
            '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green"></span> Bridge: <strong>Connected</strong>'
        );

    });

    $(document).on("<?php echo $error_evt ?>", function (evt, error) {
        console.log('Error connecting to websocket server: ', error);
        $('#vehicle_bridge_status').html(
            '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red"></span> Bridge: <strong>Error</strong>'
        );
    });

    $(document).on("<?php echo $closed_evt ?>", function (evt) {
        console.log('Connection to websocket server closed.');
        $('#vehicle_bridge_status').html(
            '<span class="glyphicon glyphicon-off" aria-hidden="true" style="color:red"></span> Bridge: <strong>Closed</strong>'
        );
    });
    
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
        "WHEEL_ENCODER": "sun-o",
        "BUTTON": "hand-o-down",
        "LED_GROUP": "adjust",
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
                        <td>
                            {verification_test_button}
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

    function status_icon(value, strict, passive) {
        if (value === true) {
            return '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green" data-toggle="tooltip" data-placement="right" title="Yes"></span>';
        }
        if (strict){
            return '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red" data-toggle="tooltip" data-placement="right" title="No"></span>';
        } else {
            return '<span class="glyphicon glyphicon-minus-sign" aria-hidden="true" style="color:darkgrey" data-toggle="tooltip" data-placement="right" title="{0}"></span>'.format(passive);
        }
    }

    function render_components(data) {
        // to be passed to js functions imported
        let ros = window.ros['<?php echo $dbot_hostname ?>'];
        let robot_name = '<?php echo $robot_name?>';

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
            let supported = status_icon(component.supported, false, "Optional");
            let detected = status_icon(
                component.detected,
                (component.supported && component.detectable !== false),
                component.detectable? "No" : "Not detectable"
            );
            let calibrated = component.hasOwnProperty('calibration')? (
                component.calibration.needed?
                    "<h5><strong>Calibrated:</strong> {0}</h5>".format(
                        status_icon(component.calibration.completed, true, null)
                    ) : ''
            ) : '';
            let verification_test_button = "";
            if (component.supported && component.test_service_name !== "") {
                let id_str_name = name.replaceAll(' ', '-');
                verification_test_button = `<button type="button" disabled="true" id="modal-btn-${id_str_name}" class="btn btn-info" data-toggle="modal" data-target="#modal-${id_str_name}">Test Hardware</button>`;

                let test_modal = `
                    <!-- Modal -->
                    <div class="modal fade" id="{modal_id}" role="dialog">
                        <div class="modal-dialog modal-lg">
                        
                        <!-- Modal content-->
                        <div class="modal-content">
                            <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">{test_name}</h4>
                            </div>
                            <div class="modal-body">
                                <p id="{description_id}" class="text-left"></p>
                                <br/>
                                <div class="row">
                                    <div class="col-md-2">
                                        <p>Also, you could:</p>
                                    </div>
                                    <div class="col-md-3 bg-light text-left">
                                        <button type="button" class="btn btn-sm text-left" id="{btn_id_logs_node}">Download logs of this ROS node</button>
                                    </div>
                                    <div class="col-md-3 bg-light text-left">
                                        <button type="button" class="btn btn-sm text-left" id="{btn_id_logs_docker_container}">Download docker container logs</button>
                                    </div>
                                    <div class="col-md-4"></div>
                                </div>
                                <br><br>
                                <button type="button" class="btn btn-primary text-left" id="{btn_id_run}">Run the test</button>
                                <!-- div class="row">
                                    <div class="col-md-12 bg-light text-right">
                                        <button type="button" class="btn btn-primary text-left" id="{btn_id_run}">Run the test</button>
                                    </div>
                                </div -->
                            </div>
                            <div class="modal-footer">
                                <p class="text-left" id="{output_id}"></p>
                                <div id="{prog_id}" class="progress" style="display:none">
                                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                                        <span class="sr-only">Running</span>
                                    </div>
                                </div>
                                <!-- button type="button" class="btn btn-default" data-dismiss="modal">Close</button -->
                            </div>
                            <div class="modal-footer">
                                <div class="container>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p id="{record_id}" class="text-left"></p>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-success" id="{btn_id_success}">Success</button>
                                            <button type="button" class="btn btn-warning" id="{btn_id_failed}">Problem</button>
                                        </div>
                                    </div>
                                    <!-- button type="button" class="btn btn-default" data-dismiss="modal">Close</button -->
                                </div>
                            </div>
                        </div>
                        
                        </div>
                    </div>
                `;

                // IDs
                let modal_id = 'modal-' + id_str_name;
                let modal_btn_id = 'modal-btn-' + id_str_name;
                let btn_id_run = 'btn-' + id_str_name;
                let btn_id_logs_node = 'btn-logs-node-' + id_str_name;
                let btn_id_logs_docker_container = 'btn-logs-docker-container-' + id_str_name;
                let btn_id_success = 'btn-succ-' + id_str_name;
                let btn_id_failed = 'btn-fail-' + id_str_name;
                let output_id = 'out-' + id_str_name;
                let prog_id = 'prog-' + id_str_name;
                let description_id = 'description-' + id_str_name;
                let record_id = 'record-' + id_str_name;

                container_div.append(test_modal.format({
                    modal_id: modal_id,
                    test_name: "Verification: " + name,
                    btn_id_run: btn_id_run,
                    btn_id_logs_node: btn_id_logs_node,
                    btn_id_logs_docker_container: btn_id_logs_docker_container,
                    btn_id_success: btn_id_success,
                    btn_id_failed: btn_id_failed,
                    output_id: output_id,
                    prog_id: prog_id,
                    description_id: description_id,
                    record_id: record_id,
                }));

                // test ros service description
                let _testDescriptionClient = new ROSLIB.Service({
                    ros : window.ros['<?php echo $dbot_hostname ?>'],
                    name : `/${robot_name}/${component.test_service_name}/description`,
                    serviceType : 'std_srvs/Trigger'
                });
                var reqDescription = new ROSLIB.ServiceRequest({});
                _testDescriptionClient.callService(reqDescription, function(result) {
                    // enable button
                    $('#' + modal_btn_id).prop('disabled', false);
                    
                    try {
                        // --- Method v1: send json and parse blocks
                        const response_obj = JSON.parse(result.message);
                        // console.log(response_obj);
                        $('#' + description_id).html(json_to_html(response_obj));
                        // --- Method v1 End
                    } catch (error) {
                        $('#' + description_id).html("Error parsing the response: " + result.message);
                    }
                });

                // test ros service
                let _testRunClient = new ROSLIB.Service({
                    ros : window.ros['<?php echo $dbot_hostname ?>'],
                    name : '/' + '<?php echo $robot_name?>' + '/' + component.test_service_name + '/run',
                    serviceType : 'std_srvs/Trigger'
                });
                let request = new ROSLIB.ServiceRequest({});

                // before test finish, do not show success/problem buttons
                $('#' + btn_id_success).hide();

                $('#' + btn_id_run).click(function() {
                    console.log(`[${id_str_name}] Hardware test triggered.`);

                    // clear output
                    $('#' + output_id).html("");
                    // show progress
                    $('#' + prog_id).show();
                    // hide button
                    $('#' + btn_id_run).hide();

                    _testRunClient.callService(request, function(result) {
                        $('#' + prog_id).hide();
                        $('#' + btn_id_run).show();
                        // show result buttons
                        $('#' + btn_id_success).show();

                        // indicate the completion of the service call
                        console.log(`[${id_str_name}] Test run service returned from:\n${_testRunClient.name}`);

                        if (!result.success) {
                            // alert("Not successful");
                            $('#' + output_id).html("<h4 style='color: red'>The test run was not successful!</h4>");
                            return;
                        }

                        try {
                            const response_obj = JSON.parse(result.message);

                            if (response_obj.type == "object") {
                                $('#' + output_id).html(json_to_html(response_obj));
                            } else if (response_obj.type == "stream") {
                                $('#' + output_id).html(json_to_html(response_obj));

                                // setup live stream of data
                                try {
                                    let stream_topic = extract_stream_topic_from_json(response_obj);

                                    let update_id = output_id + "-stream"
                                    let update_div = $('<div>').attr('id', update_id);
                                    $('#' + output_id).append(update_div);
                                    stream_data(
                                        ros,
                                        robot_name,
                                        stream_topic.test_topic_name,
                                        stream_topic.test_topic_type,
                                        update_id,
                                        modal_id,
                                    );
                                } catch (error) {
                                    console.log(`[${id_str_name}]Stream type response received, but an error has occurred. Error msg: ${error}`);
                                }
                            }
                        } catch (error) {
                            $('#' + output_id).html("Error parsing the response: " + result.message);
                        }
                    });
                })

                // looking for test records
                smartAPI(
                    "data",
                    "get",
                    {
                        arguments: {
                            database: "<?php echo $HW_TEST_DB_NAME ?>",
                            key: id_str_name
                        },
                        quiet: true,
                        on_success: function (data) {
                            let [datetime, passed] = parse_db_record_response(data);
                            update_style_based_on_records(id_str_name, datetime, passed);
                        },
                    }
                )

                let write_to_db = function(passed) {
                    smartAPI(
                        "data",
                        "set",
                        {
                            arguments: {
                                database: "<?php echo $HW_TEST_DB_NAME ?>",
                                key: id_str_name,
                                value: JSON.stringify({
                                    passed: passed,
                                    datetime: Date.now()
                                })
                            },
                            quiet: true,
                            reload: true
                        }
                    )
                }

                // user confirms success
                $('#' + btn_id_success).click(function() {
                    let text = "Do you confirm the test was successful?";
                    if (confirm(text) == true) {
                        console.log(`[${id_str_name}] Recording "Success" status for this test.`);
                        write_to_db(true);
                        $('#' + btn_id_success).hide();
                        // create events file
                        create_hardware_test_event_file(robot_name, component.key, true);
                    }
                });

                // user confirms problem
                $('#' + btn_id_failed).click(function() {
                    console.log(`[${id_str_name}] Recording "Problem" status for this component.`);
                    write_to_db(false);
                    $('#' + btn_id_success).hide();
                    // create events file
                    create_hardware_test_event_file(robot_name, component.key, false);
                });

                // --- download logs
                // ROS node
                $('#' + btn_id_logs_node).click(function() {
                    let node_name = component.test_service_name.split('/')[0];
                    download_ros_node_logs(robot_name, node_name);
                });
                // docker container
                $('#' + btn_id_logs_docker_container).click(function() {
                    // Show the download modal
                    $('#modal-docker-container-logs').modal('show');
                });
            }
            // create component's nav
            div.html(
                _nav.format({
                    name: name,
                    icon: icon,
                    description: description,
                    bus: bus,
                    supported: supported,
                    detected: detected,
                    calibrated: calibrated,
                    verification_test_button: verification_test_button,
                })
            );
            div.find('nav').css('display', 'inherit');

            if (component.supported && (component.detectable !== false && !component.detected)) {
                missing.push(component.name);
            }
        }
        // create a modal allowing the user to download logs from each running docker container
        create_view_list_docker_containers(robot_name);

        $('#_robot_components_overall_div').html(_overall_nav.format({
            status: (missing.length > 0)? 'Some components were not detected' : 'Healthy',
            icon: (missing.length > 0)? 'exclamation-circle' : 'check-circle-o',
            style: (missing.length > 0)? 'bad' : 'good',
            color: (missing.length > 0)? 'darkred' : 'darkgreen',
            explanation: (missing.length > 0)? _overall_failure_nav.format({
                    missing: missing.join(", ")
                }) :
                'All the components supported and detectable by your robot model are detected.',
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
        let url = get_api_url('health', 'components');
        callExternalAPI(
            url, 'GET', 'json', false, false,
            _on_list_success, true, false, _on_code_api_error
        );
    });
    
</script>
