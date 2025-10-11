<?php
/**
 * Admin Footer
 * Closes tags opened in admin_header.php and provides a place for shared scripts.
 */

if (!isset($GLOBALS['__ADMIN_HEADER_SENT']) || !$GLOBALS['__ADMIN_HEADER_SENT']) {
    // If header wasn't sent, avoid emitting stray closing tags.
    return;
}
?>
<!-- Shared admin scripts could go here -->
</body>
</html>