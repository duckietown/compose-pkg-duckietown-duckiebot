<?php
/**
 * File Manager as a Robot tab.
 * Embeds the elfinder page without nested dashboard chrome (?embed=1).
 */
use system\classes\Core;

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
