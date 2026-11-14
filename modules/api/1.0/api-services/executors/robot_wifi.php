<?php
# Read-only Wi-Fi status for the Overview Connection strip.


use \system\packages\duckietown_duckiebot\Duckiebot;


function execute(&$service, &$actionName, &$arguments) {
    switch ($actionName) {
        case 'status':
            return response200OK(Duckiebot::getNetworkSnapshot());

        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
    }
}//execute
