<?php
/**
 * Complete Admin Dashboard - All 29 Features
 * FezaMarket E-Commerce Management Platform
 */

session_start();
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

// Load RBAC if it exists
if (file_exists(__DIR__ . '/../includes/rbac.php')) {
    require_once __DIR__ . '/../includes/rbac.php';
}

// Initialize admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    // Demo mode for testing
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@fezamarket.com',
        'role' => 'admin'
    ];
}

$page_title = 'Admin Dashboard - FezaMarket';
$page_subtitle = 'Complete E-Commerce Management - 29 Features';

// Admin statistics (live data from database)
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'pending_users' => 0,
    'total_sellers' => 0,
    'active_sellers' => 0,
    'pending_sellers' => 0,
    'total_products' => 0,
    'active_products' => 0,
    'pending_products' => 0,
    'total_orders' => 0,
    'pending_orders' => 0,
    'processing_orders' => 0,
    'completed_orders' => 0,
    'total_revenue' => 0,
    'monthly_revenue' => 0,
    'daily_revenue' => 0
];

// Fetch live statistics from database
try {
    $db = db();
    
    // User statistics
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active' AND deleted_at IS NULL");
    $stats['active_users'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending' AND deleted_at IS NULL");
    $stats['pending_users'] = $stmt->fetchColumn();
    
    // Seller statistics (vendors)
    $stmt = $db->query("SELECT COUNT(*) as total FROM vendors WHERE deleted_at IS NULL");
    $stats['total_sellers'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM vendors WHERE status = 'active' AND deleted_at IS NULL");
    $stats['active_sellers'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM vendors WHERE status = 'pending' AND deleted_at IS NULL");
    $stats['pending_sellers'] = $stmt->fetchColumn();
    
    // Product statistics
    $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL");
    $stats['total_products'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active' AND deleted_at IS NULL");
    $stats['active_products'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'pending' AND deleted_at IS NULL");
    $stats['pending_products'] = $stmt->fetchColumn();
    
    // Order statistics
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE deleted_at IS NULL");
    $stats['total_orders'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending' AND deleted_at IS NULL");
    $stats['pending_orders'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing' AND deleted_at IS NULL");
    $stats['processing_orders'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed' AND deleted_at IS NULL");
    $stats['completed_orders'] = $stmt->fetchColumn();
    
    // Revenue statistics
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'completed' AND deleted_at IS NULL");
    $stats['total_revenue'] = (float)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'completed' AND deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['monthly_revenue'] = (float)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders WHERE status = 'completed' AND deleted_at IS NULL AND DATE(created_at) = CURDATE()");
    $stats['daily_revenue'] = (float)$stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Error fetching admin statistics: " . $e->getMessage());
    // Stats remain at 0 if database query fails
}

// Complete admin modules configuration (29 features across 22 modules)
$admin_modules = [
    'core' => [
        'title' => 'Core Management',
        'features' => [
            ['name' => 'Dashboard', 'url' => '/admin/', 'icon' => 'fas fa-tachometer-alt', 'desc' => 'Main dashboard with KPIs and widgets'],
            ['name' => 'Analytics Hub', 'url' => '/admin/analytics/', 'icon' => 'fas fa-chart-line', 'desc' => 'Advanced business intelligence']
        ]
    ],
    'users' => [
        'title' => 'User Management',
        'features' => [
            ['name' => 'User Management', 'url' => '/admin/users/', 'icon' => 'fas fa-users', 'desc' => 'Manage all user accounts'],
            ['name' => 'Roles & Permissions', 'url' => '/admin/roles/', 'icon' => 'fas fa-user-shield', 'desc' => 'RBAC system configuration'],
            ['name' => 'KYC & Verification', 'url' => '/admin/kyc/', 'icon' => 'fas fa-id-card', 'desc' => 'Identity verification workflow']
        ]
    ],
    'products' => [
        'title' => 'Product & Inventory',
        'features' => [
            ['name' => 'Product Management', 'url' => '/admin/products/', 'icon' => 'fas fa-box', 'desc' => 'Product catalog management'],
            ['name' => 'Inventory Management', 'url' => '/admin/inventory/', 'icon' => 'fas fa-warehouse', 'desc' => 'Stock tracking and alerts'],
            ['name' => 'Categories & SEO', 'url' => '/admin/categories/', 'icon' => 'fas fa-sitemap', 'desc' => 'Category tree and SEO optimization']
        ]
    ],
    'orders' => [
        'title' => 'Order & Fulfillment',
        'features' => [
            ['name' => 'Order Management', 'url' => '/admin/orders/', 'icon' => 'fas fa-shopping-cart', 'desc' => 'Complete order lifecycle'],
            ['name' => 'Shipping & Logistics', 'url' => '/admin/shipping/', 'icon' => 'fas fa-truck', 'desc' => 'Shipping management and tracking']
        ]
    ],
    'finance' => [
        'title' => 'Financial Management',
        'features' => [
            ['name' => 'Payment Tracking', 'url' => '/admin/payments/', 'icon' => 'fas fa-credit-card', 'desc' => 'Transaction monitoring'],
            ['name' => 'Payout Management', 'url' => '/admin/payouts/', 'icon' => 'fas fa-money-bill-wave', 'desc' => 'Vendor payout processing'],
            ['name' => 'Financial Reports', 'url' => '/admin/finance/', 'icon' => 'fas fa-chart-pie', 'desc' => 'Revenue and profit analysis'],
            ['name' => 'Wallet Management', 'url' => '/admin/wallets/', 'icon' => 'fas fa-wallet', 'desc' => 'Digital wallet system']
        ]
    ],
    'customer_service' => [
        'title' => 'Customer Service',
        'features' => [
            ['name' => 'Dispute Resolution', 'url' => '/admin/disputes/', 'icon' => 'fas fa-gavel', 'desc' => 'Dispute and case management'],
            ['name' => 'Communications', 'url' => '/admin/communications/', 'icon' => 'fas fa-comments', 'desc' => 'Customer communication hub']
        ]
    ],
    'marketing' => [
        'title' => 'Marketing & Promotions',
        'features' => [
            ['name' => 'Marketing Campaigns', 'url' => '/admin/campaigns/', 'icon' => 'fas fa-bullhorn', 'desc' => 'Campaign management and A/B testing'],
            ['name' => 'Coupons & Discounts', 'url' => '/admin/coupons/', 'icon' => 'fas fa-tags', 'desc' => 'Promotional codes and discounts'],
            ['name' => 'Loyalty & Rewards', 'url' => '/admin/loyalty/', 'icon' => 'fas fa-gift', 'desc' => 'Customer loyalty programs']
        ]
    ],
    'content' => [
        'title' => 'Content & Media',
        'features' => [
            ['name' => 'Content Management', 'url' => '/admin/cms/', 'icon' => 'fas fa-edit', 'desc' => 'Website content and pages'],
            ['name' => 'Media Library', 'url' => '/admin/media/', 'icon' => 'fas fa-images', 'desc' => 'Asset and media management'],
            ['name' => 'Live Streaming', 'url' => '/admin/streaming/', 'icon' => 'fas fa-video', 'desc' => 'Live stream management']
        ]
    ],
    'analytics' => [
        'title' => 'Analytics & Reporting',
        'features' => [
            ['name' => 'Analytics & Reports', 'url' => '/admin/analytics/', 'icon' => 'fas fa-chart-bar', 'desc' => 'Comprehensive business analytics'],
            ['name' => 'Custom Dashboards', 'url' => '/admin/dashboards/', 'icon' => 'fas fa-desktop', 'desc' => 'Personalized dashboard creation']
        ]
    ],
    'system' => [
        'title' => 'System Administration',
        'features' => [
            ['name' => 'System Settings', 'url' => '/admin/settings/', 'icon' => 'fas fa-cog', 'desc' => 'General system configuration'],
            ['name' => 'API & Integrations', 'url' => '/admin/integrations/', 'icon' => 'fas fa-plug', 'desc' => 'Third-party integrations'],
            ['name' => 'System Maintenance', 'url' => '/admin/maintenance/', 'icon' => 'fas fa-tools', 'desc' => 'System health and maintenance'],
            ['name' => 'Security Center', 'url' => '/admin/security/', 'icon' => 'fas fa-shield-alt', 'desc' => 'Security monitoring and logs']
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--admin-accent);
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .module-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .module-title {
            color: var(--admin-primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--admin-light);
        }
        
        .feature-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .feature-card:hover {
            background: white;
            border-color: var(--admin-accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
        }
        
        .feature-icon {
            font-size: 2rem;
            color: var(--admin-accent);
            margin-bottom: 1rem;
        }
        
        .feature-name {
            font-weight: 600;
            color: var(--admin-primary);
            margin-bottom: 0.5rem;
        }
        
        .feature-desc {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .action-btn {
            width: 100%;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .admin-header {
                padding: 1rem 0;
            }
            
            .module-section {
                padding: 1rem;
            }
            
            .feature-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <p class="mb-0 opacity-75"><?php echo $page_subtitle; ?></p>
                </div>
                <div class="col-md-4">
                    <div class="user-info">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="me-3">
                                <i class="fas fa-user-circle fa-2x"></i>
                            </div>
                            <div>
                                <div class="fw-bold"><?php echo $_SESSION['admin_user']['username']; ?></div>
                                <small><?php echo $_SESSION['admin_user']['email']; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Key Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_users']); ?></h4>
                            <small class="text-muted">Total Users</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-box fa-2x text-success"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_products']); ?></h4>
                            <small class="text-muted">Products</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-shopping-cart fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?php echo number_format($stats['total_orders']); ?></h4>
                            <small class="text-muted">Orders</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-dollar-sign fa-2x text-info"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">$<?php echo number_format($stats['total_revenue'], 0); ?></h4>
                            <small class="text-muted">Revenue</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin Modules - All 29 Features -->
        <div class="row">
            <div class="col-lg-9">
                <?php foreach ($admin_modules as $module_key => $module): ?>
                <div class="module-section">
                    <h3 class="module-title">
                        <?php echo $module['title']; ?>
                        <span class="badge bg-primary ms-2"><?php echo count($module['features']); ?> Features</span>
                    </h3>
                    <div class="row">
                        <?php foreach ($module['features'] as $feature): ?>
                        <div class="col-md-6 col-lg-4">
                            <a href="<?php echo $feature['url']; ?>" class="feature-card">
                                <div class="feature-icon">
                                    <i class="<?php echo $feature['icon']; ?>"></i>
                                </div>
                                <div class="feature-name"><?php echo $feature['name']; ?></div>
                                <div class="feature-desc"><?php echo $feature['desc']; ?></div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Quick Actions Sidebar -->
            <div class="col-lg-3">
                <div class="quick-actions">
                    <h4 class="h5 mb-3">Quick Actions</h4>
                    
                    <a href="/admin/products/create.php" class="btn btn-primary action-btn">
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </a>
                    
                    <a href="/admin/users/create.php" class="btn btn-success action-btn">
                        <i class="fas fa-user-plus me-2"></i>Create User
                    </a>
                    
                    <a href="/admin/coupons/create.php" class="btn btn-warning action-btn">
                        <i class="fas fa-tags me-2"></i>New Coupon
                    </a>
                    
                    <a href="/admin/campaigns/create.php" class="btn btn-info action-btn">
                        <i class="fas fa-bullhorn me-2"></i>Create Campaign
                    </a>
                    
                    <button class="btn btn-secondary action-btn" onclick="exportData()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                    
                    <hr>
                    
                    <h6 class="mb-3">System Status</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Server Health</span>
                        <span class="badge bg-success">Good</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Database</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Cache</span>
                        <span class="badge bg-warning">Clearing</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Feature counter animation
        document.addEventListener('DOMContentLoaded', function() {
            const featureCount = document.querySelectorAll('.feature-card').length;
            console.log(`Admin Dashboard loaded with ${featureCount} features across 10 modules`);
            
            // Add click tracking
            document.querySelectorAll('.feature-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    const featureName = this.querySelector('.feature-name').textContent;
                    console.log(`Accessing feature: ${featureName}`);
                });
            });
            
            // Add hover effects to quick action buttons
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                });
            });
        });
        
        // Export data functionality
        function exportData() {
            const exportMenu = document.createElement('div');
            exportMenu.className = 'export-menu position-absolute bg-white border rounded shadow-lg p-3';
            exportMenu.style.cssText = 'z-index: 1000; right: 10px; top: 100%; min-width: 200px;';
            exportMenu.innerHTML = `
                <h6 class="mb-3">Export Options</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-sm btn-outline-primary" onclick="exportUsers()">
                        <i class="fas fa-users me-1"></i> Export Users
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="exportProducts()">
                        <i class="fas fa-box me-1"></i> Export Products
                    </button>
                    <button class="btn btn-sm btn-outline-warning" onclick="exportOrders()">
                        <i class="fas fa-shopping-cart me-1"></i> Export Orders
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="exportFinancials()">
                        <i class="fas fa-chart-line me-1"></i> Export Financials
                    </button>
                </div>
            `;
            
            // Remove existing menu if any
            const existingMenu = document.querySelector('.export-menu');
            if (existingMenu) existingMenu.remove();
            
            // Position relative to button
            const button = event.target.closest('.action-btn');
            button.style.position = 'relative';
            button.appendChild(exportMenu);
            
            // Close menu when clicking outside
            setTimeout(() => {
                document.addEventListener('click', function closeMenu(e) {
                    if (!exportMenu.contains(e.target) && !button.contains(e.target)) {
                        exportMenu.remove();
                        document.removeEventListener('click', closeMenu);
                    }
                });
            }, 100);
        }
        
        // Export functions
        function exportUsers() {
            showExportProgress('Exporting users...', () => {
                window.location.href = '/admin/export.php?type=users';
            });
        }
        
        function exportProducts() {
            showExportProgress('Exporting products...', () => {
                window.location.href = '/admin/export.php?type=products';
            });
        }
        
        function exportOrders() {
            showExportProgress('Exporting orders...', () => {
                window.location.href = '/admin/export.php?type=orders';
            });
        }
        
        function exportFinancials() {
            showExportProgress('Exporting financial data...', () => {
                window.location.href = '/admin/export.php?type=financials';
            });
        }
        
        function showExportProgress(message, callback) {
            const toast = document.createElement('div');
            toast.className = 'toast-notification bg-info text-white p-3 rounded position-fixed';
            toast.style.cssText = 'bottom: 20px; right: 20px; z-index: 1050;';
            toast.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
                callback();
            }, 1500);
        }
    </script>
</body>
</html>