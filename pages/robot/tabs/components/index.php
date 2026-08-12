<?php
/**
 * Modern Components tab polish (required vs optional, bridge pill, 1040px width).
 * Behavioural changes are CSS/layout only - component start/stop APIs unchanged.
 * Content width aligned with other robot tabs (robot_ui redesign consistency).
 */
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
        max-width: var(--r-max, 1040px);
    }
    
    #_placeholder_img{
        padding-top: 60px;
        text-align: center;
    }
    
    ._robot_component_container {
        background-color: var(--r-surface, #f8f9fb);
        border: 1px solid var(--r-border, #e6e8eb);
        border-radius: var(--r-radius-md, 10px);
        margin: var(--r-gap, 10px) 0;
        height: auto;
        width: 100%;
        max-width: var(--r-max, 1040px);
        overflow: hidden;
        transition: border-color var(--r-ease, 160ms ease), box-shadow var(--r-ease, 160ms ease);
    }
    ._robot_component_container:hover {
        border-color: #d1d5db;
        box-shadow: 0 1px 2px rgba(17, 24, 39, 0.05);
    }
    
    ._robot_component_container > i.fa-spinner {
        color: var(--r-muted, #6b7280);
        margin-top: 30px;
        margin-bottom: 30px;
    }
    
    ._robot_component_container nav{
        height: auto;
        width: 100%;
        margin: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
        min-height: 0;
        display: none;
    }
    
    ._robot_component_container nav .container-fluid{
        padding: 0;
    }
    
    ._robot_component_container nav .collapse{
        padding: 0;
        width: 100%;
        display: block !important;
        height: auto !important;
    }
    
    ._robot_component_container nav table{
        width: 100%;
    }
    
    ._robot_component ._robot_component_icon{
        min-width: 72px;
        max-width: 72px;
        border-right: 1px solid var(--r-border, #e6e8eb);
        padding: 12px 0;
    }
    
    ._robot_overall_status_icon{
        min-width: 100px;
        max-width: 100px;
        border-right: 1px solid var(--r-border, #e6e8eb);
    }
    
    ._robot_component ._robot_component_icon i.fa{
        font-size: var(--r-fs-icon, 24px);
        color: #555;
    }
    
    ._robot_overall_status_icon i.fa{
        font-size: var(--r-fs-icon-lg, 40px);
    }
    
    ._robot_component ._robot_component_info{
        min-width: 280px;
        padding: 8px 12px;
    }
    
    ._robot_component ._robot_component_info h4{
        margin: 8px 0 4px 0;
        font-size: var(--r-fs-xl, 15px);
        font-weight: var(--r-fw-semibold, 600);
    }
    
    ._robot_component ._robot_component_info h6{
        margin: 0 0 6px 0;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        color: var(--r-muted, #6b7280);
        font-size: var(--r-fs-md, 12px);
    }
    
    ._robot_component ._robot_component_stats{
        padding: 10px 16px 0 0;
        min-width: 180px;
        text-align: right;
        vertical-align: middle;
    }
    
    ._robot_component ._robot_component_stats .status-chip-row {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
    }
    
    ._robot_component ._robot_component_stats .status-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: var(--r-radius-pill, 999px);
        border: 1px solid var(--r-border, #e6e8eb);
        background: #fff;
        font-size: var(--r-fs-sm, 11px);
        font-weight: var(--r-fw-medium, 500);
        color: #555;
        transition: border-color var(--r-ease, 160ms ease), transform var(--r-ease, 160ms ease);
    }
    ._robot_component ._robot_component_stats .status-chip:hover {
        border-color: #d1d5db;
    }
    
    ._robot_component ._robot_component_connector {
        padding: 0 12px 10px 12px;
    }
    
    ._robot_component ._robot_component_bus,
    ._robot_overall_status_reason {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: var(--r-fs-sm, 11px);
        color: var(--r-muted, #6b7280);
    }
    
    ._robot_overall_status_info {
        padding-left: 20px;
    }
    
    .navbar-bad{
        background: var(--r-bad-bg, #fef2f2);
        border: 1px solid var(--r-bad-border, #fecaca);
        border-radius: var(--r-radius-md, 10px);
    }
    
    .navbar-good{
        background: var(--r-ok-bg, #ecfdf3);
        border: 1px solid var(--r-ok-border, #bbf7d0);
        border-radius: var(--r-radius-md, 10px);
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

    .robot-components-section-title {
        text-align: left;
        margin: 18px 0 8px;
        font-size: var(--r-fs-md, 12px);
        font-weight: var(--r-fw-semibold, 600);
        color: var(--r-muted, #6b7280);
        text-transform: uppercase;
        letter-spacing: var(--r-tracking-label, 0.04em);
    }

    #_robot_components_optional_wrap {
        margin-top: 8px;
    }

    #_robot_components_optional_toggle {
        display: inline-flex;
        width: 100%;
        max-width: var(--r-max, 1040px);
        margin: 8px auto;
        justify-content: flex-start;
        padding: 8px 12px;
        border-style: dashed;
        color: var(--r-muted, #6b7280);
    }
    #_robot_components_optional_toggle:hover {
        border-style: dashed;
        color: var(--r-text, #111827);
    }

    #_robot_components_overall_div {
        max-width: var(--r-max, 1040px);
        margin: 0 auto var(--r-gap-lg, 12px);
    }
</style>

<div class="robot-status-bar">
    <div class="robot-status-bar-item">
        <i class="fa fa-car" aria-hidden="true"></i>
        <span><strong><?php echo htmlspecialchars($robot_name) ?></strong></span>
    </div>
    <div class="robot-status-bar-item">
        Hardware components
    </div>
    <div class="robot-status-bar-item">
        <span id="vehicle_bridge_status" class="robot-bridge-pill is-wait">
          <i class="fa fa-spinner fa-pulse"></i> Connecting…
        </span>
    </div>
</div>

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
        if (typeof robot_set_bridge_status === 'function') {
            robot_set_bridge_status('ok', '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true"></span> Bridge connected');
        } else {
            $('#vehicle_bridge_status').html(
                '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green"></span> Bridge: <strong>Connected</strong>'
            );
        }
    });

    $(document).on("<?php echo $error_evt ?>", function (evt, error) {
        console.log('Error connecting to websocket server: ', error);
        if (typeof robot_set_bridge_status === 'function') {
            robot_set_bridge_status('bad', '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true"></span> Bridge error');
        } else {
            $('#vehicle_bridge_status').html(
                '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red"></span> Bridge: <strong>Error</strong>'
            );
        }
    });

    $(document).on("<?php echo $closed_evt ?>", function (evt) {
        console.log('Connection to websocket server closed.');
        if (typeof robot_set_bridge_status === 'function') {
            robot_set_bridge_status('bad', '<span class="glyphicon glyphicon-off" aria-hidden="true"></span> Bridge closed');
        } else {
            $('#vehicle_bridge_status').html(
                '<span class="glyphicon glyphicon-off" aria-hidden="true" style="color:red"></span> Bridge: <strong>Closed</strong>'
            );
        }
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
                            <div class="status-chip-row">
                                {supported}
                                {detected}
                                {calibrated}
                            </div>
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

    function status_chip(label, value, strict, passive) {
        let icon;
        let tone = '#555';
        let title = passive || label;
        if (value === true) {
            icon = 'glyphicon-ok-sign';
            tone = '#2e7d32';
            title = 'Yes';
        } else if (strict) {
            icon = 'glyphicon-remove-sign';
            tone = '#c62828';
            title = 'No';
        } else {
            icon = 'glyphicon-minus-sign';
            tone = '#9e9e9e';
        }
        return '<span class="status-chip" title="{3}"><span class="glyphicon {0}" aria-hidden="true" style="color:{1}"></span>{2}</span>'.format(
            icon, tone, label, title
        );
    }

    // Back-compat wrapper
    function status_icon(value, strict, passive) {
        return status_chip('', value, strict, passive);
    }

    function render_components(data) {
        // to be passed to js functions imported
        let ros = window.ros['<?php echo $dbot_hostname ?>'];
        let robot_name = '<?php echo $robot_name?>';

        let container_div = $('#_robot_components_div');
        // sort by "supported"
        let components = Object.values(data).sort((a, b) => (a.supported > b.supported) ? -1 : 1);
        container_div.append('<div class="robot-components-section-title">Required</div>');
        let optional_opened = false;
        let missing = [];
        for (let i = 0; i < components.length; i++) {
            let component = components[i];
            if (i > 0 && component.supported !== components[i-1].supported) {
                // Collapse optional components by default
                container_div.append(
                    '<button type="button" class="robot-btn robot-btn-ghost" id="_robot_components_optional_toggle" data-toggle="collapse" data-target="#_robot_components_optional_wrap" aria-expanded="false">' +
                    '<i class="fa fa-chevron-down" aria-hidden="true"></i> Show optional components' +
                    '</button>' +
                    '<div id="_robot_components_optional_wrap" class="collapse"></div>'
                );
                optional_opened = true;
            }
            let target_div = optional_opened ? $('#_robot_components_optional_wrap') : container_div;
            let name = component.name;
            target_div.append(
                _pholder_nav.format({name: i})
            );
            let div = $('#_robot_component_{name}'.format({name: i}));
            let icon = window.ROBOT_COMPONENT_TYPE_TO_ICON.hasOwnProperty(component.type) ?
                window.ROBOT_COMPONENT_TYPE_TO_ICON[component.type] : window.ROBOT_COMPONENT_DEFAULT_ICON;
            let description = component.hasOwnProperty('description') ? component.description : '(no description)';
            let bus = "Bus {0} #{1} - Channel #{2} - Address {3}".format(
                component.bus.description, component.bus.number, component.instance, component.address
            );
            let supported = status_chip(
                component.supported ? 'Required' : 'Optional',
                component.supported,
                false,
                component.supported ? 'Officially supported' : 'Optional'
            );
            let detected = status_chip(
                'Detected',
                component.detected,
                (component.supported && component.detectable !== false),
                component.detectable? "Not detected" : "Not detectable"
            );
            let calibrated = component.hasOwnProperty('calibration')? (
                component.calibration.needed?
                    status_chip('Calibrated', component.calibration.completed, true, null)
                    : ''
            ) : '';
            let verification_test_button = "";
            if (component.supported && component.test_service_name !== "") {
                let id_str_name = name.replaceAll(' ', '-');
                verification_test_button = `<button type="button" disabled="true" id="modal-btn-${id_str_name}" class="robot-btn robot-btn-accent robot-btn-sm" data-toggle="modal" data-target="#modal-${id_str_name}">Test Hardware</button>`;

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
                                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-sm text-left" id="{btn_id_logs_node}">Download logs of this ROS node</button>
                                    </div>
                                    <div class="col-md-3 bg-light text-left">
                                        <button type="button" class="robot-btn robot-btn-ghost robot-btn-sm text-left" id="{btn_id_logs_docker_container}">Download docker container logs</button>
                                    </div>
                                    <div class="col-md-4"></div>
                                </div>
                                <br><br>
                                <button type="button" class="robot-btn robot-btn-primary text-left" id="{btn_id_run}">Run the test</button>
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
                                            <button type="button" class="robot-btn robot-btn-ok" id="{btn_id_success}">Success</button>
                                            <button type="button" class="robot-btn robot-btn-warn" id="{btn_id_failed}">Problem</button>
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

        // Optional section toggle label
        $('#_robot_components_optional_wrap').on('shown.bs.collapse', function () {
            $('#_robot_components_optional_toggle').html(
                '<i class="fa fa-chevron-up" aria-hidden="true"></i> Hide optional components'
            );
        }).on('hidden.bs.collapse', function () {
            $('#_robot_components_optional_toggle').html(
                '<i class="fa fa-chevron-down" aria-hidden="true"></i> Show optional components'
            );
        });

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
