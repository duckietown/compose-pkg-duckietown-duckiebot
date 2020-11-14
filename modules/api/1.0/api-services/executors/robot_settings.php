<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\classes\Core;
use \system\packages\duckietown_duckiebot\Duckiebot;


function execute(&$service, &$actionName, &$arguments) {
    $action = $service['actions'][$actionName];
    Core::startSession();
    //
    switch ($actionName) {
        case 'set':
            $res = Duckiebot::setDuckiebotSettings($arguments);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            // success
            return response200OK($res);
            break;
        //
        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
            break;
    }
}//execute

?>
