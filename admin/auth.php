<?php
/**
 * Admin Authentication Guard
 * Include this at the top of all admin pages
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load core functions if not already loaded
if (!class_exists('Session')) {
    require_once __DIR__ . '/../includes/init.php';
}

// Admin Bypass Mode - Skip authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    // Set up admin session automatically in bypass mode
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
    return true;
}

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/admin/index.php');
    header('Location: /login.php?error=login_required');
    exit;
}

// Check if user has admin role
$userRole = Session::getUserRole();
if ($userRole !== 'admin') {
    // Log unauthorized access attempt
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent(Session::getUserId(), 'unauthorized_access', 'admin_area', null, [
            'user_role' => $userRole,
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
    }
    
    // Redirect to 403 forbidden
    http_response_code(403);
    header('Location: /403.php');
    exit;
}

return true;