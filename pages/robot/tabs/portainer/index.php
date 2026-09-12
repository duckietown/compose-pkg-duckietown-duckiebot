<?php
/**
 * Portainer as a Robot tab.
 * Embeds the Portainer compose page without nested dashboard chrome (?embed=1).
 * Guests see an in-tab sign-in gate instead of an iframe that redirects the dashboard.
 */
use system\classes\Core;

require_once dirname(__DIR__, 2) . '/embed_gate.php';

if (robot_embed_page_status('portainer') !== 'ok') {
    robot_render_embed_gate([
        'tool' => 'Portainer',
        'icon' => 'cubes',
        'page_id' => 'portainer',
        'tab_id' => 'portainer',
        'signed_out' => 'Sign in to manage containers on this robot.',
        'no_access' => 'Portainer is limited to administrator and supervisor accounts.',
    ]);
    return;
}

$embed_url = Core::getURL('portainer', null, null, null, ['embed' => '1']);
?>

<p class="robot-hint">Manage containers running on this robot.</p>
<div class="robot-embed">
    <iframe
        src="<?php echo htmlspecialchars($embed_url) ?>"
        title="Portainer"
        allow="same-origin"
    ></iframe>
</div>
