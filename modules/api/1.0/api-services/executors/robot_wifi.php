<?php
# Read-only Wi-Fi status for the Overview Connection strip.
# Scan and connect are out of this release (unstable permissions).


use \system\packages\duckietown_duckiebot\Duckiebot;


function execute(&$service, &$actionName, &$arguments) {
    switch ($actionName) {
        case 'status':
            return response200OK(Duckiebot::getNetworkSnapshot());

        case 'scan':
        case 'connect':
            return response404NotFound('Wi-Fi network switching is not available in this release.');

        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
    }
}//execute
