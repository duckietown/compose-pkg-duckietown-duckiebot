<?php
/**
 * In-tab gate for tools that live behind compose page ACLs (File Manager, Portainer).
 * Guests used to iframe those pages; compose then window.open(..., "_top") to Robot.
 */
use system\classes\Core;

if (!function_exists('robot_embed_page_status')) {
    function robot_embed_page_status($page_id)
    {
        $all = Core::getPagesList('by-id');
        if (!is_array($all) || !isset($all[$page_id]) || empty($all[$page_id]['enabled'])) {
            return 'missing';
        }
        $allowed = Core::getFilteredPagesList('by-id', true, Core::getUserRolesList());
        if (is_array($allowed) && isset($allowed[$page_id])) {
            return 'ok';
        }
        return Core::isUserLoggedIn() ? 'forbidden' : 'signed_out';
    }
}

if (!function_exists('robot_tab_login_url')) {
    function robot_tab_login_url($tab_id)
    {
        $return = 'robot/' . ltrim((string) $tab_id, '/');
        return Core::getURL('login') . '?q=' . rawurlencode(base64_encode($return));
    }
}

if (!function_exists('robot_render_embed_gate')) {
    function robot_render_embed_gate(array $opts)
    {
        $tool = (string) $opts['tool'];
        $icon = isset($opts['icon']) ? (string) $opts['icon'] : 'lock';
        $tab_id = (string) $opts['tab_id'];
        $status = robot_embed_page_status($opts['page_id']);
        $login_enabled = (bool) Core::getSetting('login_enabled', 'core');

        if ($status === 'missing') {
            $title = $tool . ' is not available';
            $body = $tool . ' is not installed on this robot.';
            $action = null;
        } elseif ($status === 'forbidden') {
            $title = 'Not available for this account';
            $body = isset($opts['no_access'])
                ? (string) $opts['no_access']
                : ($tool . ' is limited to administrator and supervisor accounts.');
            $action = null;
        } elseif (!$login_enabled) {
            $title = 'Sign-in is turned off';
            $body = $tool . ' needs a signed-in account, but sign-in is disabled on this dashboard.';
            $action = null;
        } else {
            $title = 'Sign in to use ' . $tool;
            $body = isset($opts['signed_out'])
                ? (string) $opts['signed_out']
                : ('Sign in to open ' . $tool . '.');
            $action = robot_tab_login_url($tab_id);
        }
        ?>
        <div class="robot-auth-gate">
            <div class="robot-auth-gate-icon" aria-hidden="true">
                <i class="fa fa-<?php echo htmlspecialchars($icon) ?>"></i>
            </div>
            <h3><?php echo htmlspecialchars($title) ?></h3>
            <p><?php echo htmlspecialchars($body) ?></p>
            <?php if (!is_null($action)) { ?>
                <a class="robot-btn robot-btn-primary" href="<?php echo htmlspecialchars($action) ?>">
                    <i class="fa fa-sign-in" aria-hidden="true"></i>
                    Sign in
                </a>
            <?php } ?>
        </div>
        <?php
    }
}
