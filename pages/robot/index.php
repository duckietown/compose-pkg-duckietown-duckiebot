<?php
use \system\classes\Core;
use \system\classes\Configuration;
use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_hostname = Duckiebot::getDuckiebotHostname();

$tabs = [
    'info' => [
        'name' => 'Info',
        'icon' => 'info-circle'
    ],
//    'control' => [
//        'name' => 'Control',
//        'icon' => 'gamepad'
//    ],
    'mission_control' => [
        'name' => 'Mission Control',
        'icon' => 'dashboard'
    ],
    'health' => [
        'name' => 'Health',
        'icon' => 'medkit'
    ],
    'architecture' => [
        'name' => 'Architecture',
        'icon' => 'sitemap'
    ],
//    'software' => [
//        'name' => 'Software',
//        'icon' => 'code-fork'
//    ],
    'settings' => [
        'name' => 'Settings',
        'icon' => 'sliders'
    ]
];

$DEFAULT_TAB = 'info';
$ACTIVE_TAB = Configuration::$ACTION ?? $DEFAULT_TAB;

if (!array_key_exists($ACTIVE_TAB, $tabs)){
    Core::redirectTo(Core::getURL('robot', $DEFAULT_TAB));
}
?>

<style type="text/css">
#_robot_tab_btns li > a{
    color: #555;
}
</style>


<h2 class="page-title-static">
    Robot
    <div id="robot_power_btn_group" class="btn-group" role="group" style="float:right">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false">
                <span class="glyphicon glyphicon-flash" aria-hidden="true"></span>&nbsp;Power &nbsp;
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a href="#" class="robot_power_btn" data-trigger="shutdown">
                        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
                        &nbsp; Shutdown
                    </a>
                </li>
                <li>
                    <a href="#" class="robot_power_btn" data-trigger="reboot">
                        <span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
                        &nbsp; Reboot
                    </a>
                </li>
            </ul>
        </div>
    </div>
</h2>

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="_robot_tab_btns" role="tablist">
    <?php
    foreach ($tabs as $tab_id => $tab) {
        ?>
        <li role="presentation" class="<?php echo ($tab_id == $ACTIVE_TAB)? 'active' : '' ?>">
            <a href="#" data-tab="<?php echo $tab_id ?>" role="button" onclick="robot_load_tab('<?php echo $tab_id ?>')">
                <i class="fa fa-<?php echo $tab['icon'] ?>" aria-hidden="true"></i>&nbsp; <?php echo $tab['name'] ?>
            </a>
        </li>
        <?php
    }
    ?>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="_logs_tab_container" style="padding: 20px 0 0 0">
    <div role="tabpanel" class="tab-pane active">
    <?php
        include sprintf('%s/tabs/%s/index.php', __DIR__, $ACTIVE_TAB);
    ?>
    </div>
</div>


<script type="text/javascript">
    
    function robot_load_tab(tab) {
        redirectTo('robot', tab);
    }
    
    function _call_health_api(resource, action, qs = null, on_success = undefined, dialog = false) {
        let _url = api_url.format({api: "health", resource: "{0}/{1}".format(resource, action)});
        // create query string
        if (qs != null)
            _url += '?' + $.param(qs);
        // call API
        callExternalAPI(_url, 'GET', 'json', dialog, false, on_success);
    }
    
    $(".robot_power_btn").on("click", function(){
        let trigger = $(this).data('trigger');
        _call_health_api("trigger", trigger, null, function (data) {
            if (data.hasOwnProperty('token')) {
                _call_health_api("trigger", trigger, {token: data.token, value: 'dashboard'});
            }
        }, true);
    });
    
</script>
