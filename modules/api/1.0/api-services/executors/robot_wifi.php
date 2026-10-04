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
            // Compose merges POST into $_GET before executors run. Reject a
            // passphrase that arrived on the query string so it never lands
            // in access logs / Referer; accept it only from the POST body.
            $query = [];
            parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
            if (array_key_exists('psk', $query)) {
                return response400BadRequest(
                    'Passphrase must be sent in the POST body, not the URL.'
                );
            }
            $psk = array_key_exists('psk', $_POST) ? (string) $_POST['psk'] : null;
            $res = Duckiebot::connectWifi($ssid, $psk);
            if (!$res['success']) {
                return response400BadRequest($res['data']);
            }
            return response200OK($res['data']);

        default:
            return response404NotFound(sprintf("The command '%s' was not found", $actionName));
    }
}//execute
