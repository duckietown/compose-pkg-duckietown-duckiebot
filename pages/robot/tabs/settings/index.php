<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


require_once $GLOBALS['__SYSTEM__DIR__'] . 'templates/forms/SmartForm.php';

// create schema for robot's settings
$form_schema = [
    'type' => 'form',
    'details' => 'Robot settings',
    '_data' => [
        'allow_push_logs_data' => [
            'type' => 'boolean',
            'default' => false,
            'details' => 'Upload robot data logs to the Duckietown database of logs.'
        ],
        'allow_push_stats_data' => [
            'type' => 'boolean',
            'default' => false,
            'details' => 'Automatically send usage statistics and crash reports to Duckietown.'
        ],
        'allow_push_config_data' => [
            'type' => 'boolean',
            'default' => false,
            'details' => 'Automatically backup robot\'s configuration to the Duckietown Cloud Storage Service (DCSS).'
        ],
    ]
];
?>

<div style="margin: auto; width: 80%; padding-top: 40px">
    <?php
    // create form
    $form = new SmartForm($form_schema, []);
    // render form
    $form->render();
    ?>
    
    <button type="button" class="btn btn-primary" id="robot-settings-save-button" style="float:right">
        <span class="glyphicon glyphicon-floppy-open" aria-hidden="true"></span>&nbsp; Save and Apply
    </button>
</div>

<script type="text/javascript">
    $('#robot-settings-save-button').on('click', function(){
        let form = ComposeForm.get("<?php echo $form->formID ?>");
        // call API
        smartAPI('robot_settings', 'set', {
            method: 'POST',
            arguments: {},
            data: form.serialize(),
            block: true,
            confirm: true,
            reload: false
        });
    });
</script>
