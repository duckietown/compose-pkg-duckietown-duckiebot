<?php

use \system\packages\ros\ROS;
use \system\packages\duckietown_duckiebot\Duckiebot;

$robot_name = Duckiebot::getDuckiebotName();
$robot_type = Duckiebot::getRobotType();
$robot_hostname = Duckiebot::getDuckiebotHostname();
$ros_hostname = ROS::sanitize_hostname($robot_hostname);

$connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
$error_evt = ROS::get_event(ROS::$ROSBRIDGE_ERROR, $ros_hostname);
$closed_evt = ROS::get_event(ROS::$ROSBRIDGE_CLOSED, $ros_hostname);

ROS::connect($ros_hostname);
?>

<table style="width: 970px; margin: auto; margin-bottom: 12px">
    <tr>
        <td class="text-left" style="width:33%">
            <i class="fa fa-car" aria-hidden="true"></i> Vehicle:
            <strong><?php echo $robot_name ?></strong>
        </td>
        <td class="text-center"
            style="width:33%; border-left: 1px solid lightgrey; border-right: 1px solid lightgrey">
            Calibrations
        </td>
        <td class="text-center" style="width:33%; text-align: right">
        <span id="vehicle_bridge_status">
          <i class="fa fa-spinner fa-pulse"></i> Connecting...
        </span>
        </td>
    </tr>
</table>

<br/>
<h5>Use the collapsable panels below to check, backup, restore, and test your robot's
    calibrations.</h5>
<br/>

<?php
$calibrations = [
    'camera_intrinsic' => [
        'title' => 'Camera Intrinsic',
        'icon' => 'camera',
    ],
    'camera_extrinsic' => [
        'title' => 'Camera Extrinsic',
        'icon' => 'object-ungroup',
    ]
];
if (in_array($robot_type, ["duckiebot"])) {
    $calibrations['kinematics'] = [
        'title' => 'Kinematics',
        'icon' => 'car',
    ];
}
$open_calibration = "camera_intrinsic";
?>

<div class="panel-group" id="calibrations_accordion" role="tablist" aria-multiselectable="false">
    <?php
    foreach ($calibrations as $calibration_key => &$calibration) {
        $expanded = boolval($open_calibration == $calibration_key);
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="h_<?php echo $calibration_key ?>">
                <h4 class="panel-title">
                    <?php
                    if ($expanded) {
                        ?>
                        <a role="button" data-toggle="collapse" data-parent="#calibrations_accordion"
                           href="#c_<?php echo $calibration_key ?>" aria-expanded="true"
                           aria-controls="c_<?php echo $calibration_key ?>">
                            <i class="fa fa-<?php echo $calibration['icon'] ?>"
                               aria-hidden="true"></i>&nbsp;
                            <?php echo $calibration["title"] ?>
                        </a>
                        <?php
                    } else {
                        ?>
                        <a class="collapsed" role="button" data-toggle="collapse"
                           data-parent="#calibrations_accordion" href="#c_<?php echo $calibration_key ?>"
                           aria-expanded="false" aria-controls="c_<?php echo $calibration_key ?>">
                            <i class="fa fa-<?php echo $calibration['icon'] ?>"
                               aria-hidden="true"></i>&nbsp;
                            <?php echo $calibration["title"] ?>
                        </a>
                        <?php
                    }
                    ?>
                    <span id="<?php echo $calibration_key ?>_status" style="float: right">
                        <i class="fa fa-spinner fa-pulse"></i>
                    </span>
                </h4>
            </div>
            <div id="c_<?php echo $calibration_key ?>"
                 class="panel-collapse collapse <?php echo $expanded ? 'in' : '' ?>"
                 data-calib-type="<?php echo $calibration_key ?>"
                 role="tabpanel" aria-labelledby="_<?php echo $calibration_key ?>">
                <div class="panel-body">
                    <h4><span class="label label-default">Local</span></h4>
                    <div>
                        <dl class="dl-horizontal col-md-6" style="margin-bottom: 10px">
                          <dt>Completed</dt>
                          <dd id="<?php echo $calibration_key ?>_exists">
                              <i class="fa fa-spinner fa-pulse"></i>
                          </dd>
                        </dl>
                        <dl class="dl-horizontal col-md-6" style="margin-bottom: 10px">
                          <dt>Calibration date</dt>
                          <dd id="<?php echo $calibration_key ?>_date">
                              <i class="fa fa-spinner fa-pulse"></i>
                          </dd>
                        </dl>
                        <dl class="dl-horizontal col-md-12">
                          <dt>Files</dt>
                          <dd id="<?php echo $calibration_key ?>_files" style="padding-top: 2px;">
                              <i class="fa fa-spinner fa-pulse"></i>
                          </dd>
                        </dl>
                    </div>
                    
                    <br/>
                    <br/>
                    <br/>
                    <h4 style="margin-top: 30px;"><span class="label label-default">Cloud Backups</span></h4>
                    <div id="<?php echo $calibration_key ?>_backups" style="padding: 20px 0 0 100px">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <td class="text-center col-md-1">
                                    #
                                </td>
                                <td class="text-center col-md-1">
                                    Owner
                                </td>
                                <td class="col-md-4">
                                    Origin device
                                </td>
                                <td class="col-md-4">
                                    Date
                                </td>
                                <td class="text-center col-md-2">
                                    Action
                                </td>
                            </tr>
                            </thead>
                            <tbody id="<?php echo $calibration_key ?>_backups_table">
                            <tr>
                                <td colspan="5" class="text-center">
                                    <i id="<?php echo $calibration_key ?>_backups_loader" class="fa fa-spinner fa-pulse" style="margin-top:10px"></i>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php
                    $content_fpath = __DIR__ . '/content/' . $calibration_key . '.php';
                    if (file_exists($content_fpath)) {
                        ?>
                        <h4 style="margin-top: 30px;"><span class="label label-default">Content</span></h4>
                        <?php
                        include_once $content_fpath;
                    }
                    ?>
                    
                    <?php
                    $test_fpath = __DIR__ . '/test/' . $calibration_key . '.php';
                    if (file_exists($test_fpath)) {
                        ?>
                        <h4 style="margin-top: 30px;"><span class="label label-default">Test</span></h4>
                        <?php
                        include_once $test_fpath;
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>


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
    
    let _api_url = "http://<?php echo $robot_hostname ?>/{api}/{action}/{resource}";
    
    let _backup_row = `
        <tr>
            <td class="text-center">
                {id}
            </td>
            <td class="text-center">
                {owner}
            </td>
            <td>
                {origin}
            </td>
            <td>
                {date}
            </td>
            <td class="text-center">
                <button class="btn btn-warning btn-xs" onclick="_restore_backup('{calib_type}', '{origin}')">Restore</button>
            </td>
        </tr>
    `;
    
    function api_url(api, action, args) {
        return _api_url.format({api: api, action: action, resource: args.join('/')}).rstrip('/')
    }
    
    function exists_icon(value) {
        if (value) {
            return '<span class="glyphicon glyphicon-ok-sign" aria-hidden="true" style="color:green" data-toggle="tooltip" data-placement="right" title="Yes"></span>';
        }
        return '<span class="glyphicon glyphicon-remove-sign" aria-hidden="true" style="color:red" data-toggle="tooltip" data-placement="right" title="No"></span>';
    }
    
    function _on_info_success (calib_type, data) {
        $('#{0}_{1}'.format([calib_type, 'exists'])).html(exists_icon(data.exists));
        $('#{0}_{1}'.format([calib_type, 'status'])).html(exists_icon(data.exists));
        $('#{0}_{1}'.format([calib_type, 'date'])).html(data.time || "(none)");
        let files_str = "(none)";
        if (data.files.length > 0) {
            files_str = (data.files.map(f => "<kbd>{0}</kbd>".format(f))).join("<br/>");
        }
        $('#{0}_{1}'.format([calib_type, 'files'])).html(files_str);
    }
    
    function _on_info_error (calib_type, _) {
        $('#{0}_{1}'.format([calib_type, 'status'])).html("(error)");
        $('#{0}_{1}'.format([calib_type, 'exists'])).html("(error)");
        $('#{0}_{1}'.format([calib_type, 'date'])).html("(error)");
        $('#{0}_{1}'.format([calib_type, 'files'])).html("(error)");
    }
    
    function _load_info (calib_type) {
        let url = api_url('files/calibration', 'info', [calib_type]);
        function _on_success_fcn(data) {
            _on_info_success(calib_type, data);
        }
        function _on_error_fcn(data) {
            _on_info_error(calib_type, data);
        }
        callExternalAPI(
            url, 'GET', 'json', false, false, _on_success_fcn, true, true, _on_error_fcn
        );
    }
    
    $('#calibrations_accordion').on('show.bs.collapse', function (e) {
        let calib_type = $(e.target).data('calib-type');
        _load_backups(calib_type);
    });
    
    function _on_backups_list_success (calib_type, data) {
        let html = "<tr><td colspan='5' class='text-center'>(none)</td></tr>";
        if (data.backups.length > 0) {
            data.backups.forEach(function (b, i) {
                html += _backup_row.format({
                    id: i + 1,
                    origin: b.origin,
                    owner: b.owner,
                    date: b.date,
                    calib_type: calib_type
                });
            });
        }
        $('#{0}_{1}'.format([calib_type, 'backups_table'])).html(html);
    }
    
    function _on_backups_list_error (calib_type, _) {
        $('#{0}_{1}'.format([calib_type, 'exists'])).html("(error)");
    }
    
    function _load_backups (calib_type) {
        let url = api_url('files/calibration/backup', 'list', [calib_type]);
        $('#{0}_{1}'.format([calib_type, 'backups_loader'])).css("display", "inline-block");
        function _on_success_fcn(data) {
            $('#{0}_{1}'.format([calib_type, 'backups_table'])).html("");
            _on_backups_list_success(calib_type, data);
        }
        function _on_error_fcn(data) {
            $('#{0}_{1}'.format([calib_type, 'backups_table'])).html("");
            _on_backups_list_error(calib_type, data);
        }
        callExternalAPI(
            url, 'GET', 'json', false, false, _on_success_fcn, true, true, _on_error_fcn
        );
    }
    
    function _restore_backup (calib_type, origin) {
        let url = api_url('files/calibration/backup', 'restore', [calib_type, origin]);
        callExternalAPI(
            url, 'GET', 'json', true, true
        );
    }
    
    $(document).ready(function (){
        <?php
        foreach ($calibrations as $calibration_key => &$_) {
            ?>
            _load_info("<?php echo $calibration_key ?>");
            <?php
        }
        ?>
        _load_backups("<?php echo $open_calibration ?>");
    });
</script>
