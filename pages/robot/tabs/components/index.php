<?php
use \system\classes\Core;
use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;


$dbot_hostname = Duckiebot::getDuckiebotHostname();

$robot_name = Duckiebot::getDuckiebotName();
$robot_type = Duckiebot::getRobotType();
$ros_hostname = ROS::sanitize_hostname($dbot_hostname);

$connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
$error_evt = ROS::get_event(ROS::$ROSBRIDGE_ERROR, $ros_hostname);
$closed_evt = ROS::get_event(ROS::$ROSBRIDGE_CLOSED, $ros_hostname);

ROS::connect($ros_hostname);

// $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
// $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
// $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

function get_last_line($file_path) {
    $line = '';

    $f = fopen($file_path, 'r');
    $cursor = -1;

    // Read the file backwards until a newline is found or the start of the file is reached
    while (fseek($f, $cursor, SEEK_END) === 0) {
        $char = fgetc($f);
        if ($char === "\n") {
            if ($cursor === -1) {
                // Ignore the newline at the end of the file
                $cursor--;
                continue;
            }
            break;
        }
        $line = $char . $line;
        $cursor--;
    }

    fclose($f);

    return $line;
}

function append_line($id_str, $content) {
    $save_dir = '/data/stats/components_verification';

    if (!file_exists($save_dir)) {
        mkdir($save_dir, 0775, true);
    }

    $fname = $save_dir . '/' . $id_str . '.txt';

    $confirm_file = fopen($fname, "a");
    fwrite($confirm_file, $content . "\n");
    fclose($confirm_file);
}

function record_success($id_str) {
    $entry = "Success on " . date('Y-m-d H:i:s');
    append_line($id_str, $entry);
    return $entry;
}

function record_problem($id_str) {
    $entry = "Problem since " . date('Y-m-d H:i:s');
    append_line($id_str, $entry);
    return $entry;
}

// TODO: log file size limitation?
if (isset($_POST['id_str'])) {
    $id_str = $_POST['id_str'];

    $entry = record_success($id_str);

    $special_str = '___' . $id_str . '___';
    echo $special_str . $entry . $special_str;
}

if (isset($_POST['id_str_problem'])) {
    $id_str = $_POST['id_str_problem'];

    $entry = record_problem($id_str);

    $special_str = '___' . $id_str . '___';
    echo $special_str . $entry . $special_str;
}

if (isset($_POST['id_str_read'])) {
    $id_str_read = $_POST['id_str_read'];

    $save_dir = '/data/stats/components_verification';
    if (!file_exists($save_dir)) {
        mkdir($save_dir, 0775, true);
    }

    $special_str = '___' . $id_str_read . '___';
    $fname = $save_dir . '/' . $id_str_read . '.txt';

    if (!file_exists($fname)) {
        echo $special_str . '' . $special_str;
    } else {
        $last_line = get_last_line($fname);
        echo $special_str . $last_line . $special_str;
    }
}


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
    
    function api_url(api, action, args) {
        return _api_url.format({api: api, action: action, resource: args.join('/')}).rstrip('/')
    }
    
    function status_icon(value) {
        if (value) {
            return '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green" data-toggle="tooltip" data-placement="right" title="Yes"></span>';
        }
        return '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red" data-toggle="tooltip" data-placement="right" title="No"></span>';
    }

    function update_style_based_on_records(id_str_name, last_record) {
        let modal_btn_id = 'modal-btn-' + id_str_name;
        let record_id = 'record-' + id_str_name;

        let disp_txt = "None"
        if (last_record !== "") {
            disp_txt = last_record;
            if (last_record.startsWith("Problem")) {
                $('#' + modal_btn_id).removeClass("btn-success").addClass("btn-info");
            } else {
                $('#' + modal_btn_id).removeClass("btn-info").addClass("btn-success");
            }
        }
        $('#' + record_id).html(`Last status: ${disp_txt}`);
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
            let verification_test_button = "";
            if (component.detected) {
                let id_str_name = name.replaceAll(' ', '-');
                verification_test_button = '<button type="button" id="modal-btn-' + id_str_name + '" class="btn btn-info" data-toggle="modal" data-target="' + '#modal-' + id_str_name + '">Verify Hardware</button>';

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
                                <p id="{desc_id}" class="text-left"></p>
                                <br>
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
                let modal_btn_id = 'modal-btn-' + id_str_name;
                let btn_id_run = 'btn-' + id_str_name;
                let btn_id_success = 'btn-succ-' + id_str_name;
                let btn_id_failed = 'btn-fail-' + id_str_name;
                let output_id = 'out-' + id_str_name;
                let prog_id = 'prog-' + id_str_name;
                let desc_id = 'desc-' + id_str_name;
                let record_id = 'record-' + id_str_name;

                container_div.append(test_modal.format({
                    modal_id: 'modal-' + id_str_name,
                    test_name: "Verification: " + name,
                    btn_id_run: btn_id_run,
                    btn_id_success: btn_id_success,
                    btn_id_failed: btn_id_failed,
                    output_id: output_id,
                    prog_id: prog_id,
                    desc_id: desc_id,
                    record_id: record_id,
                }));

                // test ros service description
                let _testDescClient = new ROSLIB.Service({
                    ros : window.ros['<?php echo $dbot_hostname ?>'],
                    name : '/' + '<?php echo $robot_name?>' + '/' + component.test_service_name + '/desc',
                    serviceType : 'std_srvs/Trigger'
                });
                var reqDesc = new ROSLIB.ServiceRequest({});
                _testDescClient.callService(reqDesc, function(result) {
                    console.log('Desc service call on \n'
                    + _testDescClient.name
                    + ':\n');
                    // + result.message);
                    
                    let outHtml = "";
                    // TODO: formatting from string to html
                    let sections = result.message.split(/\r?\n\r?\n/);
                    // sections: 1 - prep; 2 - run; 3 - expectations; 4 - logs gathering
                    let secTitles = ["Preparation", "Expected Outcomes", "How to run", "Logs Gathering (in case of errors)"];

                    console.log(sections);
                    for (let idx = 0; idx < sections.length; idx++) {
                        outHtml += ("<h4>" + secTitles[idx] + "</h4>");

                        let sec = sections.at(idx);
                        let lines = sec.split(/\r?\n/);

                        let tmp = "<ul>";
                        lines.forEach((item) => {
                            let s = item.replaceAll('`', '<code>').replaceAll("'", "</code>")
                            // TODO: handle this properly. The reason it's only handled here, but 
                            // not in the desription, is because the test services are not necessarily run only via the GUI.
                            if (s == "Run the test.") {
                                s = "Click the 'Run the test' button below";
                            }
                            tmp += ("<li>" + s + "</li>");
                        });
                        tmp += "</ul>";

                        outHtml += tmp;
                    }

                    $('#' + desc_id).html(outHtml);
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
                    console.log("Triggered " + id_str_name);

                    // clear output
                    $('#' + output_id).html("");
                    // show progress
                    $('#' + prog_id).show();
                    // hide button
                    $('#' + btn_id_run).hide();

                    // console.log($dbot_hostname)
                    _testRunClient.callService(request, function(result) {
                        $('#' + prog_id).hide();
                        $('#' + btn_id_run).show();
                        // show result buttons
                        $('#' + btn_id_success).show();
                        console.log('Result for service call on \n'
                        + _testRunClient.name
                        + ':\n'
                        + result.message);

                        $('#' + output_id).html(result.message.replaceAll("\n", "<br>"));
                    });
                })

                $.ajax({
                    url: window.location.href,
                    type: "POST",
                    data: {id_str_read: id_str_name},
                    success: function(response) {
                        let special_str = "___" + id_str_name + "___";
                        let entry = response.split(special_str)[1];
                        // console.log("Found record: " + entry);
                        if (entry !== "") {
                            console.log(`[${id_str_name}] Found verification record: ${entry}`);
                        }
                        update_style_based_on_records(id_str_name, entry);
                    }
                });

                // user confirms success
                $('#' + btn_id_success).click(function() {
                    let text = "Do you confirm the test was successful?";
                    if (confirm(text) == true) {
                        console.log("Success");
                        // console.log(window.location.href)
                        $.ajax({
                            url: window.location.href,
                            // url: '<?php echo $base_url; ?>/components/process.php',
                            type: "POST",
                            data: {id_str: id_str_name},
                            success: function(response) {
                                let special_str = "___" + id_str_name + "___";
                                let entry = response.split(special_str)[1];
                                console.log(`[${id_str_name}] Marked success: ${entry}`);
                                update_style_based_on_records(id_str_name, entry);
                            }
                        });
                        $('#' + btn_id_success).hide();
                    }
                });

                // user confirms 
                $('#' + btn_id_failed).click(function() {
                    console.log("Problem encountered");
                    $.ajax({
                        url: window.location.href,
                        type: "POST",
                        data: {id_str_problem: id_str_name},
                        success: function(response) {
                            let special_str = "___" + id_str_name + "___";
                            let entry = response.split(special_str)[1];
                            console.log(`[${id_str_name}] Marked problem: ${entry}`);
                            update_style_based_on_records(id_str_name, entry);
                        }
                    });
                    $('#' + btn_id_success).hide();
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
