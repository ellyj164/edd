<?php
/**
 * Admin Header + Session Message Helpers
 * Creates a consistent header include for admin pages and provides
 * displaySessionMessages() expected by admin/products/index.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Set a flash message (helper). Type examples: success, error, info, warning.
 */
if (!function_exists('flashMessage')) {
    function flashMessage(string $type, string $message): void {
        $key = $type . '_messages';
        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        $_SESSION[$key][] = $message;
    }
}

/**
 * Backwards compatibility for single-message keys some legacy code may set:
 *  - success_message
 *  - error_message
 *  - info_message
 *  - warning_message
 */

/**
 * Output and clear all flash / session messages.
 */
if (!function_exists('displaySessionMessages')) {
    function displaySessionMessages(): void {
        $map = [
            'success' => ['success_message', 'success_messages'],
            'error'   => ['error_message', 'error_messages'],
            'info'    => ['info_message', 'info_messages'],
            'warning' => ['warning_message', 'warning_messages'],
        ];

        $rendered = false;

        echo '<div class="flash-messages" style="margin:10px 0;">';

        foreach ($map as $type => $keys) {
            $all = [];

            foreach ($keys as $k) {
                if (isset($_SESSION[$k])) {
                    if (is_array($_SESSION[$k])) {
                        $all = array_merge($all, $_SESSION[$k]);
                    } elseif ($_SESSION[$k] !== '') {
                        $all[] = $_SESSION[$k];
                    }
                }
            }

            if (!empty($all)) {
                $rendered = true;
                $class = match ($type) {
                    'success' => 'alert-success',
                    'error'   => 'alert-danger',
                    'warning' => 'alert-warning',
                    'info'    => 'alert-info',
                    default   => 'alert-secondary'
                };

                echo '<div class="alert ' . htmlspecialchars($class) . '" role="alert" style="padding:10px;margin-bottom:8px;border:1px solid #ccc;border-radius:4px;">';
                foreach ($all as $msg) {
                    echo '<div>' . htmlspecialchars($msg) . '</div>';
                }
                echo '</div>';
            }

            // Clear consumed messages
            foreach ($keys as $k) {
                if (isset($_SESSION[$k])) {
                    unset($_SESSION[$k]);
                }
            }
        }

        if (!$rendered) {
            // No messages; nothing to print (keep structure minimal)
        }

        echo '</div>';
    }
}

/**
 * Basic HTML header (only output if not already started by higher-level layout)
 * If your project already wraps pages with a main layout, you can trim this section.
 */
if (empty($GLOBALS['__ADMIN_HEADER_SENT'])) {
    $GLOBALS['__ADMIN_HEADER_SENT'] = true;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Admin Panel</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- If Bootstrap / Font Awesome are used elsewhere, include them here -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body { font-family: Arial, sans-serif; background:#f8f9fb; }
            .admin-container { padding: 20px; }
            .alert { font-size: 14px; }
        </style>
    </head>
    <body>
    <?php
}