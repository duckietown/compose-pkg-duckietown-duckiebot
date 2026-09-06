<?php
/**
 * File Manager as a Robot tab.
 * Embeds the elfinder page without nested dashboard chrome (?embed=1).
 * Guests see an in-tab sign-in gate instead of an iframe that redirects the dashboard.
 */
use system\classes\Core;

require_once dirname(__DIR__, 2) . '/embed_gate.php';

if (robot_embed_page_status('file-manager') !== 'ok') {
    robot_render_embed_gate([
        'tool' => 'File Manager',
        'icon' => 'folder-open',
        'page_id' => 'file-manager',
        'tab_id' => 'file_manager',
        'signed_out' => 'Sign in to browse and edit files on this robot.',
        'no_access' => 'File Manager is limited to administrator and supervisor accounts.',
    ]);
    return;
}

$embed_url = Core::getURL('file-manager', null, null, null, ['embed' => '1']);
?>

<p class="robot-hint">Browse and edit files on this robot.</p>
<div class="robot-embed">
    <iframe
        src="<?php echo htmlspecialchars($embed_url) ?>"
        title="File Manager"
        allow="same-origin"
    ></iframe>
</div>
