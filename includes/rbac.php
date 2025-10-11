<?php
/**
 * RBAC Include - Required in all admin pages
 * Role-Based Access Control utilities and permission checks
 */

// Load the RoleMiddleware if it exists
if (file_exists(__DIR__ . '/../middleware/RoleMiddleware.php')) {
    require_once __DIR__ . '/../middleware/RoleMiddleware.php';
}

/**
 * Define granular permissions for admin modules
 */
class AdminPermissions {
    // User Management permissions
    const USERS_VIEW = 'users.view';
    const USERS_CREATE = 'users.create';
    const USERS_EDIT = 'users.edit';
    const USERS_DELETE = 'users.delete';
    const USERS_SUSPEND = 'users.suspend';
    const USERS_ACTIVATE = 'users.activate';
    
    // Role Management permissions
    const ROLES_VIEW = 'roles.view';
    const ROLES_EDIT = 'roles.edit';
    const ROLE_PERMISSIONS_MANAGE = 'role_permissions.manage';
    
    // KYC Management permissions
    const KYC_VIEW = 'kyc.view';
    const KYC_APPROVE = 'kyc.approve';
    const KYC_REJECT = 'kyc.reject';
    
    // Order Management permissions
    const ORDERS_VIEW = 'orders.view';
    const ORDERS_EDIT = 'orders.edit';
    const ORDERS_CANCEL = 'orders.cancel';
    const ORDERS_REFUND = 'orders.refund';
    const ORDERS_TRACK = 'orders.track';
    
    // Product Management permissions
    const PRODUCTS_VIEW = 'products.view';
    const PRODUCTS_CREATE = 'products.create';
    const PRODUCTS_EDIT = 'products.edit';
    const PRODUCTS_DELETE = 'products.delete';
    const PRODUCTS_FEATURED = 'products.featured';
    
    // Vendor Management permissions
    const VENDORS_VIEW = 'vendors.view';
    const VENDORS_APPROVE = 'vendors.approve';
    const VENDORS_SUSPEND = 'vendors.suspend';
    const VENDORS_PAYOUTS = 'vendors.payouts';
    
    // Analytics permissions
    const ANALYTICS_VIEW = 'analytics.view';
    const ANALYTICS_EXPORT = 'analytics.export';
    const ANALYTICS_FINANCIAL = 'analytics.financial';
    
    // System Settings permissions
    const SETTINGS_VIEW = 'settings.view';
    const SETTINGS_EDIT = 'settings.edit';
    const SETTINGS_MAINTENANCE = 'settings.maintenance';
    
    // Security & Audit permissions
    const SECURITY_VIEW = 'security.view';
    const SECURITY_LOGS = 'security.logs';
    const SECURITY_USERS = 'security.users';
    
    // Content Management permissions
    const CONTENT_VIEW = 'content.view';
    const CONTENT_EDIT = 'content.edit';
    const CONTENT_PUBLISH = 'content.publish';
    
    // Financial Management permissions
    const FINANCE_VIEW = 'finance.view';
    const FINANCE_TRANSACTIONS = 'finance.transactions';
    const FINANCE_REPORTS = 'finance.reports';
    const FINANCE_PAYOUTS = 'finance.payouts';
}

/**
 * Enhanced role permissions mapping
 */
class RolePermissions {
    public static function getDefaultRolePermissions() {
        return [
            'admin' => [
                // User Management
                AdminPermissions::USERS_VIEW,
                AdminPermissions::USERS_CREATE,
                AdminPermissions::USERS_EDIT,
                AdminPermissions::USERS_SUSPEND,
                AdminPermissions::USERS_ACTIVATE,
                
                // Role Management (limited)
                AdminPermissions::ROLES_VIEW,
                
                // KYC Management
                AdminPermissions::KYC_VIEW,
                AdminPermissions::KYC_APPROVE,
                AdminPermissions::KYC_REJECT,
                
                // Order Management
                AdminPermissions::ORDERS_VIEW,
                AdminPermissions::ORDERS_EDIT,
                AdminPermissions::ORDERS_CANCEL,
                AdminPermissions::ORDERS_REFUND,
                AdminPermissions::ORDERS_TRACK,
                
                // Product Management
                AdminPermissions::PRODUCTS_VIEW,
                AdminPermissions::PRODUCTS_CREATE,
                AdminPermissions::PRODUCTS_EDIT,
                AdminPermissions::PRODUCTS_FEATURED,
                
                // Vendor Management
                AdminPermissions::VENDORS_VIEW,
                AdminPermissions::VENDORS_APPROVE,
                AdminPermissions::VENDORS_SUSPEND,
                
                // Analytics
                AdminPermissions::ANALYTICS_VIEW,
                AdminPermissions::ANALYTICS_EXPORT,
                
                // Settings (limited)
                AdminPermissions::SETTINGS_VIEW,
                AdminPermissions::SETTINGS_EDIT,
                
                // Content Management
                AdminPermissions::CONTENT_VIEW,
                AdminPermissions::CONTENT_EDIT,
                AdminPermissions::CONTENT_PUBLISH,
            ],
            
            'ops' => [
                // Order Management
                AdminPermissions::ORDERS_VIEW,
                AdminPermissions::ORDERS_EDIT,
                AdminPermissions::ORDERS_TRACK,
                
                // Product Management (limited)
                AdminPermissions::PRODUCTS_VIEW,
                AdminPermissions::PRODUCTS_EDIT,
                
                // Vendor Management (limited)
                AdminPermissions::VENDORS_VIEW,
                
                // Analytics (limited)
                AdminPermissions::ANALYTICS_VIEW,
                
                // Content Management (limited)
                AdminPermissions::CONTENT_VIEW,
                AdminPermissions::CONTENT_EDIT,
            ],
            
            'support' => [
                // User Management (view only)
                AdminPermissions::USERS_VIEW,
                
                // Order Management (limited)
                AdminPermissions::ORDERS_VIEW,
                AdminPermissions::ORDERS_TRACK,
                
                // Product Management (view only)
                AdminPermissions::PRODUCTS_VIEW,
                
                // Vendor Management (view only)
                AdminPermissions::VENDORS_VIEW,
                
                // Content Management (view only)
                AdminPermissions::CONTENT_VIEW,
            ]
        ];
    }
}

/**
 * Check if current user has permission for admin action
 */
function hasAdminPermission($permission) {
    // Admin Bypass mode grants all permissions
    if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
        return true;
    }
    
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRole = getCurrentUserRole();
    
    // Super admin has all permissions
    if ($userRole === 'super') {
        return true;
    }
    
    return RoleMiddleware::hasPermission($userRole, $permission);
}

/**
 * Require specific admin permission
 */
function requireAdminPermission($permission) {
    // Admin Bypass mode grants all permissions
    if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
        return true;
    }
    
    if (!hasAdminPermission($permission)) {
        logSecurityEvent(getCurrentUserId(), 'permission_denied', 'security', null, [
            'permission' => $permission,
            'user_role' => getCurrentUserRole(),
            'url' => $_SERVER['REQUEST_URI'] ?? ''
        ]);
        
        http_response_code(403);
        header("Location: /403.php");
        exit;
    }
}

/**
 * Get navigation items based on user permissions
 */
function getAdminNavigation() {
    $nav = [];
    
    // Dashboard - always visible for admin users
    $nav[] = [
        'title' => 'Dashboard',
        'url' => '/admin/',
        'icon' => 'fas fa-tachometer-alt',
        'active' => $_SERVER['REQUEST_URI'] === '/admin/'
    ];
    
    // User Management
    if (hasAdminPermission(AdminPermissions::USERS_VIEW)) {
        $nav[] = [
            'title' => 'User Management',
            'url' => '/admin/users/',
            'icon' => 'fas fa-users',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/users/') === 0
        ];
    }
    
    // Role Management
    if (hasAdminPermission(AdminPermissions::ROLES_VIEW)) {
        $nav[] = [
            'title' => 'Roles & Permissions',
            'url' => '/admin/roles/',
            'icon' => 'fas fa-user-shield',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/roles/') === 0
        ];
    }
    
    // Order Management
    if (hasAdminPermission(AdminPermissions::ORDERS_VIEW)) {
        $nav[] = [
            'title' => 'Order Management',
            'url' => '/admin/orders/',
            'icon' => 'fas fa-shopping-cart',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/orders/') === 0
        ];
    }
    
    // Product Management
    if (hasAdminPermission(AdminPermissions::PRODUCTS_VIEW)) {
        $nav[] = [
            'title' => 'Product Catalog',
            'url' => '/admin/products/',
            'icon' => 'fas fa-box',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/products/') === 0
        ];
    }
    
    // Vendor Management
    if (hasAdminPermission(AdminPermissions::VENDORS_VIEW)) {
        $nav[] = [
            'title' => 'Vendor Management',
            'url' => '/admin/vendors/',
            'icon' => 'fas fa-store',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/vendors/') === 0
        ];
    }
    
    // Analytics
    if (hasAdminPermission(AdminPermissions::ANALYTICS_VIEW)) {
        $nav[] = [
            'title' => 'Analytics & Reports',
            'url' => '/admin/analytics/',
            'icon' => 'fas fa-chart-bar',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/analytics/') === 0
        ];
    }
    
    // Settings
    if (hasAdminPermission(AdminPermissions::SETTINGS_VIEW)) {
        $nav[] = [
            'title' => 'System Settings',
            'url' => '/admin/settings/',
            'icon' => 'fas fa-cog',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/settings/') === 0
        ];
    }
    
    // Security (admin only)
    if (hasAdminPermission(AdminPermissions::SECURITY_VIEW)) {
        $nav[] = [
            'title' => 'Security & Audit',
            'url' => '/admin/security/',
            'icon' => 'fas fa-shield-alt',
            'active' => strpos($_SERVER['REQUEST_URI'], '/admin/security/') === 0
        ];
    }
    
    return $nav;
}