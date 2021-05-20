<?php

use \system\packages\duckietown_duckiebot\Duckiebot;

function get_calib_html($calib_type){
    $res = Duckiebot::getCalibrationContent($calib_type);
    if (!$res["success"]) {
        echo $res['data'];
        return;
    }
    ?>
    <pre style="height: 130px"><?php echo $res['data'];?></pre>
    <?php
}
?>