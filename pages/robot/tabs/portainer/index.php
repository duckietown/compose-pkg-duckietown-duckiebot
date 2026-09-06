<?php
/**
 * Portainer as a Robot tab.
 * Embeds the Portainer compose page without nested dashboard chrome (?embed=1).
 */
use system\classes\Core;

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
