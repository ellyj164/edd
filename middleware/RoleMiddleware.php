<?php
/**
 * Role-Based Access Control (RBAC) Middleware
 * E-Commerce Platform - PHP 8
 */

class RoleMiddleware {
    // Define role hierarchy (higher number = more permissions)
    private static $roleHierarchy = [
        'customer' => 1,
        'vendor' => 2, 
        'support' => 3,
        'finance' => 4,
        'mod' => 5,
        'admin' => 6,
        'super' => 7
    ];
    
    // Define permissions for each role
    private static $permissions = [
        'customer' => [
            'orders.view_own',
            'profile.edit_own', 
            'wishlist.manage_own',
            'reviews.create',
            'cart.manage_own'
        ],
        'vendor' => [
            'products.create',
            'products.edit_own',
            'products.view_own',
            'orders.view_own',
            'store.manage_own',
            'analytics.view_own',
            'customers.view_own'
        ],
        'support' => [
            'tickets.view',
            'tickets.respond',
            'customers.view',
            'orders.view',
            'refunds.process'
        ],
        'finance' => [
            'payouts.manage',
            'transactions.view',
            'reports.financial',
            'taxes.manage'
        ],
        'mod' => [
            'products.moderate',
            'reviews.moderate', 
            'users.suspend',
            'content.moderate'
        ],
        'admin' => [
            'users.manage',
            'products.manage',
            'orders.manage',
            'vendors.manage',
            'system.settings',
            'analytics.view_all'
        ],
        'super' => [
            'system.full_access',
            'database.manage',
            'security.audit'
        ]
    ];
    
    /**
     * Check if user has required role or higher
     */
    public static function hasRole($userRole, $requiredRole) {
        $userLevel = self::$roleHierarchy[$userRole] ?? 0;
        $requiredLevel = self::$roleHierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
    
    /**
     * Check if user has specific permission
     */
    public static function hasPermission($userRole, $permission) {
        // Admin Bypass mode grants all permissions
        if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
            return true;
        }
        
        // Super admin has all permissions
        if ($userRole === 'super') {
            return true;
        }
        
        // Check direct permissions
        $rolePermissions = self::$permissions[$userRole] ?? [];
        if (in_array($permission, $rolePermissions)) {
            return true;
        }
        
        // Check inherited permissions from lower roles
        $userLevel = self::$roleHierarchy[$userRole] ?? 0;
        foreach (self::$roleHierarchy as $role => $level) {
            if ($level < $userLevel && in_array($permission, self::$permissions[$role] ?? [])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Require specific role or redirect
     */
    public static function requireRole($requiredRole, $redirectUrl = '/login.php') {
        // Admin Bypass mode grants all roles
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
        
        Session::start();
        
        if (!Session::isLoggedIn()) {
            Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
            header("Location: $redirectUrl?error=login_required");
            exit;
        }
        
        $userRole = Session::getUserRole();
        
        if (!self::hasRole($userRole, $requiredRole)) {
            // Log unauthorized access attempt
            if (function_exists('logSecurityEvent')) {
                logSecurityEvent(Session::getUserId(), 'unauthorized_access', 'role_check', null, [
                    'required_role' => $requiredRole,
                    'user_role' => $userRole,
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
            }
            
            http_response_code(403);
            header("Location: /403.php");
            exit;
        }
        
        return true;
    }
    
    /**
     * Require specific permission or redirect
     */
    public static function requirePermission($permission, $redirectUrl = '/403.php') {
        // Admin Bypass mode grants all permissions
        if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
            return true;
        }
        
        Session::start();
        
        if (!Session::isLoggedIn()) {
            Session::set('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
            header("Location: /login.php?error=login_required");
            exit;
        }
        
        $userRole = Session::getUserRole();
        
        if (!self::hasPermission($userRole, $permission)) {
            // Log unauthorized access attempt
            if (function_exists('logSecurityEvent')) {
                logSecurityEvent(Session::getUserId(), 'unauthorized_access', 'permission_check', null, [
                    'required_permission' => $permission,
                    'user_role' => $userRole,
                    'url' => $_SERVER['REQUEST_URI'] ?? ''
                ]);
            }
            
            http_response_code(403);
            header("Location: $redirectUrl");
            exit;
        }
        
        return true;
    }
    
    /**
     * Check if user can access admin areas
     */
    public static function requireAdmin() {
        return self::requireRole('admin');
    }
    
    /**
     * Check if user can access vendor areas
     */
    public static function requireVendor() {
        return self::requireRole('vendor');
    }
    
    /**
     * Get user's permissions list
     */
    public static function getUserPermissions($userRole) {
        $permissions = [];
        
        // Super admin gets all permissions
        if ($userRole === 'super') {
            return ['*']; // Wildcard for all permissions
        }
        
        // Get direct permissions
        $permissions = self::$permissions[$userRole] ?? [];
        
        // Add inherited permissions
        $userLevel = self::$roleHierarchy[$userRole] ?? 0;
        foreach (self::$roleHierarchy as $role => $level) {
            if ($level < $userLevel) {
                $permissions = array_merge($permissions, self::$permissions[$role] ?? []);
            }
        }
        
        return array_unique($permissions);
    }
}
?>