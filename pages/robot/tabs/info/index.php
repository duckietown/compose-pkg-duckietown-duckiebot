<?php
use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_name = Duckiebot::getDuckiebotName();
$dbot_hostname = Duckiebot::getDuckiebotHostname();
$update_hz = 0.5;

$image_fmt = Core::getImageURL('robots/infoboard/{0}.jpg', 'duckietown');
$load_img = Core::getImageURL('loading_blue.gif') ;

$infoboard = [
    'width' => 1000,
    'height' => 700,
    'opacity' => 50
];
$indicator = [
    'border' => 6,
    'color' => 'green'
];

$components = [
    'HAT#0' => [
        'x' => 240,
        'y' => 310,
        'diameter' => 120,
        'text' => 'CAM'
    ]
];

?>

<style type="text/css">
    .robot-infoboard {
        width: <?php echo $infoboard['width'] ?>px;
        height: <?php echo $infoboard['height'] ?>px;
        text-align: center;
        position: relative;
    }
    
    .robot-infoboard-background {
        width: <?php echo $infoboard['width'] ?>px;
        height: <?php echo $infoboard['height'] ?>px;
        opacity: <?php echo $infoboard['opacity'] ?>%;
        position: absolute;
        top: 0;
        left: 0;
        display: none;
    }
    
    .robot-infoboard-component-indicator {
        border: <?php echo $indicator['border'] ?>px solid <?php echo $indicator['color'] ?>;
        border-radius: 100%;
        position: absolute;
    }
</style>


<div class="robot-infoboard">
    <img alt="" class="robot-infoboard-placeholder" src="<?php echo $load_img ?>" />
    <img alt="" class="robot-infoboard-background" src="#" />
    
    <?php
    foreach ($components as $component) {
        ?>
        
        
        <div class="robot-infoboard-component-indicator" style="
            width: <?php echo $component['diameter'] ?>px;
            height: <?php echo $component['diameter'] ?>px;
            top: <?php echo intval($component['y'] - 0.5 * $component['diameter']) ?>px;
            left: <?php echo intval($component['x'] - 0.5 * $component['diameter']) ?>px;
        "><?php echo $component['text'] ?></div>
        <?php
    }
    ?>
    
</div>





<script type="text/javascript">
    
    let api_url = "http://<?php echo $dbot_hostname ?>/{api}/{resource}";

    $(document).ready(function () {
        // get robot type
        let url = api_url.format({api:"files", resource:"data/config/robot_type"});
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_type = 'unknown';
            try {
                robot_type = data.split('\n')[0].trim();
            } catch (e) {}
        }, true, true);
        // get robot configuration
        url = api_url.format({api:"files", resource:"data/config/robot_configuration"});
        callExternalAPI(url, 'GET', 'text', false, false, function(data) {
            let robot_configuration = 'unknown';
            try {
                robot_configuration = data.split('\n')[0].trim();
            } catch (e) {}
            $('.robot-infoboard-placeholder').css('display', 'none');
            let bground_img_url = "<?php echo $image_fmt ?>".format(robot_configuration);
            let bground_img = $('.robot-infoboard-background');
            bground_img.attr('src', bground_img_url);
            bground_img.css('display', 'inline');
        }, true, true);
    });

</script>