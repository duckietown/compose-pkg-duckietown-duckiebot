<?php
# Wi-Fi scan / connect API for the Overview Connection strip.


use \system\packages\duckietown_duckiebot\Duckiebot;


function execute(&$service, &$actionName, &$arguments) {
    switch ($actionName) {
        case 'status':
            return response200OK(Duckiebot::getNetworkSnapshot());

        case 'scan':
            $res = Duckiebot::scanWifiNetworks();
            if (!$res['success']) {
                $meta = isset($res['meta']) && is_array($res['meta']) ? $res['meta'] : [];
                if (isset($meta['available']) && $meta['available'] === false) {
                    return response200OK([
                        'available' => false,
                        'networks' => [],
                        'error' => $res['data'],
                    ]);
                }
                return response400BadRequest($res['data']);
            }
            return response200OK($res['data']);

        case 'connect':
            $ssid = isset($arguments['ssid']) ? (string) $arguments['ssid'] : '';
            $psk = array_key_exists('psk', $arguments) ? (string) $arguments['psk'] : null;
            $res = Duckiebot::connectWifi($ssid, $psk);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            return response200OK($res['data']);

        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
    }
}//execute
