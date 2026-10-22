<?php

use \system\classes\BlockRenderer;
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;


/**
 * Live camera image, read from the robot's DTPS switchboard (no ROS needed).
 */
class Duckiebot_DTPS_Camera extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "video-camera"
    ];

    static protected $ARGUMENTS = [
        "topic" => [
            "name" => "DTPS topic",
            "type" => "text",
            "mandatory" => True,
            "default" => "sensor/camera/front_center/jpeg"
        ],
        "fps" => [
            "name" => "Update frequency (Hz)",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 10
        ],
        "style" => [
            "name" => "Image size",
            "type" => "enum",
            "enum_values" => ["contain", "cover", "auto"],
            "mandatory" => True,
            "default" => "cover"
        ],
        "position" => [
            "name" => "Image position",
            "type" => "text",
            "mandatory" => True,
            "default" => "center center"
        ],
        "background-color" => [
            "name" => "Background color",
            "type" => "color",
            "mandatory" => True,
            "default" => "white"
        ]
    ];

    protected static function render($id, &$args) {
        $robot_name = Duckiebot::getDuckiebotName();
        ?>
        <div class="image_placeholder"></div>
        <script src="<?php echo Core::getJSscriptURL('cbor.min.js', 'duckietown_duckiebot'); ?>"></script>
        <script src="<?php echo Core::getJSscriptURL('dtps_client.js', 'duckietown_duckiebot'); ?>"></script>
        <script type="text/javascript">
            $(document).ready(function () {
                let block = $('#<?php echo $id ?>');
                // each frame is decoded before it replaces the previous one (no flickering)
                let view = DTPS.imageView(function (url) {
                    block.css('background-image', 'url(' + url + ')');
                    block.find('.image_placeholder').css('display', 'none');
                });
                DTPS.client('<?php echo $robot_name ?>').subscribe('<?php echo $args['topic'] ?>', function (message) {
                    view.show(message);
                }, {maxHz: <?php echo floatval($args['fps']) ?>});
            });
        </script>
        <style type="text/css">
            #<?php echo $id ?>{
                background-color: <?php echo $args['background-color'] ?? 'white' ?>;
                background-position: <?php echo $args['position'] ?>;
                background-size: <?php echo $args['style'] ?>;
                background-repeat: no-repeat;
            }
            #<?php echo $id ?> .block_renderer_header{
                background-color: rgba(0, 0, 0, 0.1);
                color: white;
            }
            #<?php echo $id ?> .image_placeholder{
                width: 100%;
                height: 100%;
                max-height: 100px;
                background-image: url('<?php echo Core::getImageURL('placeholder.png') ?>');
                background-position: center center;
                background-size: auto 100%;
                background-repeat: no-repeat;
            }
        </style>
        <?php
    }//render

}//Duckiebot_DTPS_Camera
?>
