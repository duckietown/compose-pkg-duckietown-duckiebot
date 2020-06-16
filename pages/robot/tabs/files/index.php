<?php

use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_hostname = Duckiebot::getDuckiebotHostname();
?>

<iframe src="http://<?php echo $dbot_hostname ?>/files/" style="width: 100%; height: 400px; border: 0"></iframe>
