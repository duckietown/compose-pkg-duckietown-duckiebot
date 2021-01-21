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
            // open session to have access to login info
            Core::startSession();
            // handle first-setup case: the user is not logged in
            if (!Core::isUserLoggedIn()) {
                if (Core::isComposeConfigured())
                    return response401Unauthorized();
            }
            // robot permissions
            if (array_key_exists('permissions', $arguments)) {
                foreach ($arguments['permissions'] as $key => $value) {
                    $res = Duckiebot::setDuckiebotPermission($key, $value);
                    if (!$res['success']) {
                        return response400BadRequest($res['data']);
                    }
                }
            }
            if (!Core::isUserLoggedIn())
                // the user is not logged in but the platform is not configured, `permissions` only
                return response200OK();
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
                    $res = Duckiebot::setAutolabConfiguration($key, $value);
                    if (!$res['success']) {
                        return response400BadRequest($res['data']);
                    }
                }
            }
            // success
            return response200OK();
            break;
        //
        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
            break;
    }
}//execute

?>
