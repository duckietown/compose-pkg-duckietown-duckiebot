<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Configuration;

//$pages_available = [
//  'dashboard',
//  'templates',
//  'stacks',
//  'containers',
//  'images',
//  'networks',
//  'volumes',
//  'events',
//  'host',
//  // ---
//  'extensions',
//  'endpoints',
//  'settings'
//];
//$default_page = 'dashboard';
//
//$page = Configuration::$ACTION;
//if (!in_array(Configuration::$ACTION, $pages_available))
//  $page = $default_page;

//// get Portainer hostname (defaults to HTTP_HOST if not set)
//$coder_hostname = Core::getSetting('coder_hostname', 'coder');
//if(strlen($coder_hostname) < 2){
//  $coder_hostname = Core::getBrowserHostname();
//}
//$coder_port = Core::getSetting('coder_port', 'coder');
//$coder_url = sprintf('http://%s:%s/#/%s', $coder_hostname, $coder_port, $page);

$coder_url = 'http://localhost:8089'

?>

<style type="text/css">
    #page_container{
      min-width: 100%;
    }
    
    ._ctheme_content {
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        border-top: 1px solid black;
        border-left: 1px solid black;
    }
    
    #coder_iframe {
        width: 100%;
        height: 100%;
        position: absolute;
        bottom: 0;
        top: 0;
    }
</style>

<iframe
  id="coder_iframe"
  src="<?php echo $coder_url ?>"
  frameborder="0"
  scrolling="yes"
></iframe>
