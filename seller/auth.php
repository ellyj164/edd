<?php
/**
 * Seller Authentication Guard
 * Include this at the top of all seller pages
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load core functions if not already loaded
if (!class_exists('Session')) {
    require_once __DIR__ . '/../includes/init.php';
}

// Check if user is logged in
if (!Session::isLoggedIn()) {
    Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/seller-center.php');
    header('Location: /login.php?error=login_required');
    exit;
}

// Check if user has seller/vendor role or is an admin
$userRole = Session::getUserRole();
if (!in_array($userRole, ['seller', 'vendor', 'admin'])) {
    // Check if user has a vendor account (alternative check)
    try {
        $vendor = new Vendor();
        $vendorInfo = $vendor->findByUserId(Session::getUserId());
        if ($vendorInfo) {
            // User has vendor account, allow access
            return true;
        }
    } catch (Exception $e) {
        error_log("Vendor check failed: " . $e->getMessage());
    }
    
    // Log unauthorized access attempt
    if (function_exists('logSecurityEvent')) {
        logSecurityEvent(Session::getUserId(), 'unauthorized_access', 'seller_area', null, [
            'user_role' => $userRole,
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
    }
    
    // Set intended URL and redirect to seller registration
    Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/seller-center.php');
    header('Location: /seller-register.php');
    exit;
}

return true;