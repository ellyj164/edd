<?php
/**
 * Session Management Class
 * E-Commerce Platform
 */

class Session {
    
    /**
     * Start session if not already started
     */
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        self::start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user ID
     */
    public static function getUserId() {
        self::start();
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public static function getUser() {
        self::start();
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Set user session data
     */
    public static function setUser($userId, $userData = null) {
        self::start();
        $_SESSION['user_id'] = $userId;
        if ($userData) {
            $_SESSION['user'] = $userData;
        }
    }
    
    /**
     * Require user to be logged in - redirect if not
     * This is the missing method that was causing the error
     */
    public static function requireLogin($redirectUrl = '/login.php') {
        self::start();
        
        if (!self::isLoggedIn()) {
            // Store the current page for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            // Redirect to login page
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        return true;
    }
    
    /**
     * Require admin access - redirect if not admin
     */
    public static function requireAdmin($redirectUrl = '/403.php') {
        self::start();
        
        // First check if logged in
        self::requireLogin();
        
        // Check if user is admin
        $user = self::getUser();
        if (!$user || !isset($user['role']) || $user['role'] !== 'admin') {
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        return true;
    }
    
    /**
     * Require seller/vendor access - redirect if not seller
     */
    public static function requireSeller($redirectUrl = '/seller-register.php') {
        self::start();
        
        // First check if logged in
        self::requireLogin();
        
        // Check if user is a seller/vendor
        $user = self::getUser();
        if (!$user || (!in_array($user['role'], ['seller', 'vendor', 'admin']))) {
            header('Location: ' . $redirectUrl);
            exit;
        }
        
        return true;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        self::start();
        
        if (!self::isLoggedIn()) {
            return false;
        }
        
        $user = self::getUser();
        return $user && isset($user['role']) && $user['role'] === $role;
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        self::start();
        $_SESSION['flash'][$type] = $message;
    }
    
    /**
     * Get and clear flash message
     */
    public static function getFlash($type = null) {
        self::start();
        
        if ($type) {
            $message = $_SESSION['flash'][$type] ?? null;
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }
    
    /**
     * Check if flash message exists
     */
    public static function hasFlash($type = null) {
        self::start();
        
        if ($type) {
            return isset($_SESSION['flash'][$type]);
        }
        
        return !empty($_SESSION['flash']);
    }
    
    /**
     * Set session value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session key exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session key
     */
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Clear all session data
     */
    public static function clear() {
        self::start();
        session_unset();
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy() {
        self::start();
        session_destroy();
        
        // Also clear the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::start();
        
        // Clear user-specific session data but keep flash messages for logout confirmation
        unset($_SESSION['user_id']);
        unset($_SESSION['user']);
        unset($_SESSION['redirect_after_login']);
        
        // Set logout success message
        self::setFlash('success', 'You have been successfully logged out.');
    }
    
    /**
     * Regenerate session ID for security
     */
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }
    
    /**
     * Get CSRF token
     */
    public static function getCsrfToken() {
        self::start();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        self::start();
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Update user activity timestamp
     */
    public static function updateActivity() {
        self::start();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if session is expired
     */
    public static function isExpired($timeout = 3600) { // Default 1 hour timeout
        self::start();
        
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }
        
        return (time() - $_SESSION['last_activity']) > $timeout;
    }
}