<?php
/**
 * Admin Panel Entry Point
 * Direct access to admin dashboard without authentication barriers
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set up admin session automatically for direct access
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_email'] = 'admin@example.com';
$_SESSION['username'] = 'Administrator';

// Redirect to main admin dashboard
header('Location: /admin/');
exit;
?>