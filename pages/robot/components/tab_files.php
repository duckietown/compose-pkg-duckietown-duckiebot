<?php

use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_hostname = Duckiebot::getDuckiebotHostname();

//TODO: use the proxy instead
$dbot_hostname = 'watchtower20.local';
$files_api_port = 8082;
?>

<iframe src="http://<?php echo $dbot_hostname ?>:<?php echo $files_api_port ?>/"
        style="width: 100%; height: 400px; border: 0"></iframe>
