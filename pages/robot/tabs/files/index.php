<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\packages\duckietown_duckiebot\Duckiebot;

$dbot_hostname = Duckiebot::getDuckiebotHostname();
$files_url = sprintf("http://%s/files/", $dbot_hostname);
?>

<style type="text/css">
    #files_iframe {
        width: 100%;
    }
</style>

<iframe
  id="files_iframe"
  class="vertical_fit"
  src="<?php echo $files_url ?>"
  frameborder="0"
  scrolling="yes"
></iframe>