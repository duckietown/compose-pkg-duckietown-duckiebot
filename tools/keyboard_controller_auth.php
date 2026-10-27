<?php
/**
 * nginx auth_request target for /keyboard-controller/.
 * Returns 200 when a dashboard session is logged in, otherwise 401.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$logged_in = !empty($_SESSION['USER_LOGGED']);
http_response_code($logged_in ? 200 : 401);
header('Content-Length: 0');
header('Cache-Control: no-store');
