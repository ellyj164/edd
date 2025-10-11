<?php
/**
 * Authentication Include - Required in all admin pages
 * Standardized authentication checks and session management
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load core functions for Session class
require_once __DIR__ . '/functions.php';

// Load middleware for role checking
require_once __DIR__ . '/../middleware/RoleMiddleware.php';

/**
 * Check if user is authenticated
 */
if (!function_exists('isAuthenticated')) {
    function isAuthenticated() {
        return Session::isLoggedIn();
    }
}

/**
 * Get current user ID
 */
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        return Session::getUserId();
    }
}

/**
 * Get current user role
 */
if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole() {
        return Session::getUserRole();
    }
}

/**
 * Require authentication for admin areas
 */
if (!function_exists('requireAuth')) {
    function requireAuth($redirectUrl = '/login.php') {
        // Check Admin Bypass mode first
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
        
        if (!isAuthenticated()) {
            Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
            header("Location: $redirectUrl?error=login_required");
            exit;
        }
    }
}

/**
 * Require admin role for admin panel access
 */
if (!function_exists('requireAdminAuth')) {
    function requireAdminAuth() {
        // Check Admin Bypass mode first
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
        
        requireAuth();
        RoleMiddleware::requireAdmin();
    }
}

/**
 * Check if current user has specific permission
 */
if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        // Admin Bypass mode grants all permissions
        if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
            return true;
        }
        
        if (!isAuthenticated()) {
            return false;
        }
        return RoleMiddleware::hasPermission(getCurrentUserRole(), $permission);
    }
}

/**
 * Require specific permission
 */
if (!function_exists('requirePermission')) {
    function requirePermission($permission) {
        // Admin Bypass mode grants all permissions
        if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
            return true;
        }
        
        if (!hasPermission($permission)) {
            http_response_code(403);
            header("Location: /403.php");
            exit;
        }
    }
}

/**
 * Get user's full permissions list
 */
if (!function_exists('getUserPermissions')) {
    function getUserPermissions() {
        // Admin Bypass mode grants all permissions
        if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
            return ['*']; // Wildcard for all permissions
        }
        
        if (!isAuthenticated()) {
            return [];
        }
        return RoleMiddleware::getUserPermissions(getCurrentUserRole());
    }
}

/**
 * Global permission check function for all admin modules
 */
if (!function_exists('checkPermission')) {
    function checkPermission($permission) {
        // Admin Bypass mode grants all permissions
        if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
            return true;
        }
        
        return hasPermission($permission);
    }
}

// Auto-require authentication for admin pages if this file is included from admin directory
$script_path = $_SERVER['SCRIPT_FILENAME'] ?? '';
if (strpos($script_path, '/admin/') !== false) {
    // Check if config is loaded, if not load it
    if (!defined('ADMIN_BYPASS')) {
        // Try to load config
        $config_path = __DIR__ . '/../config/config.php';
        if (file_exists($config_path)) {
            require_once $config_path;
        }
    }
    
    // Admin Bypass mode automatically creates admin session
    if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
        // Set up admin session automatically in bypass mode
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $_SESSION['user_id'] = 1;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_email'] = 'admin@example.com';
            $_SESSION['username'] = 'Administrator';
            $_SESSION['admin_bypass'] = true;
        }
    } else {
        // Only require auth if not already admin and not in bypass mode
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            requireAdminAuth();
        }
    }
}