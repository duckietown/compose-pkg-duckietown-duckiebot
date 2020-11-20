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
        'permissions' => [
            'type' => 'object',
            'details' => 'Privacy and Backup',
            '_data' => [
                'allow_push_logs_data' => [
                    'type' => 'boolean',
                    'default' => false,
                    'details' => 'Allow the robot to auto-upload sensor data logs to the Duckietown database of logs.',
                    '__form__' => [
                        'title' => 'Share sensor data logs with Duckietown'
                    ]
                ],
                'allow_push_stats_data' => [
                    'type' => 'boolean',
                    'default' => false,
                    'details' => 'Automatically send usage statistics and crash reports to Duckietown.',
                    '__form__' => [
                        'title' => 'Share anonymous data with Duckietown'
                    ]
                ],
                'allow_push_config_data' => [
                    'type' => 'boolean',
                    'default' => false,
                    'details' => 'Automatically backup robot\'s configuration to the Duckietown Cloud Storage Service (DCSS).',
                    '__form__' => [
                        'title' => 'Backup robot configuration on the cloud'
                    ]
                ]
            ]
        ],
        'robot' => [
            'type' => 'object',
            'details' => 'Robot settings',
            '_data' => [
                'type' => [
                    'type' => 'enum',
                    'values' => [
                        'duckiebot',
                        'duckiedrone',
                        'watchtower',
                        'greenstation',
                        'workstation',
                        'traffic_light',
                        'duckietown'
                    ],
                    '__form__' => [
                        'labels' => [
                            'Duckiebot',
                            'Duckiedrone',
                            'Watchtower',
                            'Green Station',
                            'Workstation',
                            'Traffic Light',
                            'Duckietown'
                        ]
                    ],
                    'default' => 'duckiebot',
                    'details' => 'Robot type'
                ],
                'configuration' => [
                    'type' => 'enum',
                    'values' => [
                        'DB18',
                        'DB19',
                        'DB20',
                        'DB-beta',
                        'DD18',
                        'WT18',
                        'WT19A',
                        'WT19B',
                        'GS17',
                        'TL18',
                        'TL19',
                        'DT20'
                    ],
                    'default' => 'DB18',
                    'details' => 'Robot\'s configuration'
                ],
                'tag_id' => [
                    'type' => 'numeric',
                    'default' => -1,
                    'details' => 'Unique ID of the Tag attached to the robot'
                ],
                'hardware' => [
                    'type' => 'enum',
                    'values' => [
                        'raspberry_pi/3B+',
                        'raspberry_pi/4B2G',
                        'jetson_nano/4GB'
                    ],
                    '__form__' => [
                        'labels' => [
                            'Raspberry Pi / Model 3B+',
                            'Raspberry Pi / Model 4B / 2GB',
                            'Jetson Nano / DevKit 4GB'
                        ]
                    ],
                    'default' => false,
                    'details' => 'Robot\'s hardware'
                ]
            ]
        ]
    ]
];
?>

<div style="margin: auto; width: 80%">
    <?php
    // create form
    $form = new SmartForm($form_schema, []);
    // render form
    $form->render();
    ?>
    
    <button type="button" class="btn btn-primary" id="robot-settings-save-button" style="float:right; margin-bottom: 40px">
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
