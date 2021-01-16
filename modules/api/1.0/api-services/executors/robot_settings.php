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
            // robot permissions
            if (array_key_exists('permissions', $arguments)) {
                foreach ($arguments['permissions'] as $key => $value) {
                    $res = Duckiebot::setDuckiebotPermission($key, $value);
                    if (!$res['success']) {
                        return response400BadRequest($res['data']);
                    }
                }
            }
            // robot configuration
            if (array_key_exists('robot', $arguments)) {
                foreach ($arguments['robot'] as $key => $value) {
                    $res = Duckiebot::setDuckiebotConfiguration($key, $value);
                    if (!$res['success']) {
                        return response400BadRequest($res['data']);
                    }
                }
            }
            // autolab configuration
            if (array_key_exists('autolab', $arguments)) {
                foreach ($arguments['autolab'] as $key => $value) {
                    $key = "autolab/$key";
                    $res = Duckiebot::setDuckiebotConfiguration($key, $value);
                    if (!$res['success']) {
                        return response400BadRequest($res['data']);
                    }
                }
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
