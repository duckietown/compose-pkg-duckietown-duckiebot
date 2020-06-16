<?php
use \system\classes\Core;
use \system\classes\Configuration;

$tabs = [
    'info' => [
        'name' => 'Info',
        'icon' => 'info-circle'
    ],
    'control' => [
        'name' => 'Control',
        'icon' => 'gamepad'
    ],
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
    'files' => [
        'name' => 'Files',
        'icon' => 'folder-open'
    ]
];

$DEFAULT_TAB = 'info';
$ACTIVE_TAB = Configuration::$ACTION ?? $DEFAULT_TAB;

if (!array_key_exists($ACTIVE_TAB, $tabs)){
    Core::redirectTo(sprintf('robot/%s', $DEFAULT_TAB));
}
?>

<style type="text/css">
#_robot_tab_btns li > a{
    color: #555;
}
</style>

<table style="width:970px; margin: auto">
    <tr style="border-bottom:1px solid #ddd">
      <td style="width:100%">
        <h2>Robot</h2>
      </td>
    </tr>

    <tr>
      <td style="height:30px"></td>
    </tr>

    <tr>
      <td style="width:100%">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="_robot_tab_btns" role="tablist">
            <?php
            foreach ($tabs as $tab_id => $tab) {
                ?>
                <li role="presentation" class="<?php echo ($tab_id == $ACTIVE_TAB)? 'active' : '' ?>">
                    <a href="#" data-tab="<?php echo $tab_id ?>" role="button" onclick="robot_load_tab('<?php echo $tab_id ?>')">
                        <i class="fa fa-<?php echo $tab['icon'] ?>" aria-hidden="true"></i> <?php echo $tab['name'] ?>
                    </a>
                </li>
                <?php
            }
            ?>
        </ul>
      </td>
    </tr>
</table>

<!-- Tab panes -->
<div class="tab-content" id="_logs_tab_container" style="padding: 20px 0">
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
