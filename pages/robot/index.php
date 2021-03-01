<?php
use \system\classes\Core;
use \system\classes\Configuration;

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


<h2 class="page-title"></h2>

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
</script>
