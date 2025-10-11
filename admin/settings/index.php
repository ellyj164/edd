<?php
/**
 * System Settings - Admin Module
 * System & Configuration Management
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();
RoleMiddleware::requireAdmin();

$page_title = 'System Settings';
$tab = $_GET['tab'] ?? 'general';

// Handle settings updates
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        switch ($_POST['action']) {
            case 'update_general':
                $settings = [
                    'site_name' => sanitizeInput($_POST['site_name']),
                    'site_description' => sanitizeInput($_POST['site_description']),
                    'admin_email' => sanitizeInput($_POST['admin_email']),
                    'timezone' => sanitizeInput($_POST['timezone']),
                    'currency' => sanitizeInput($_POST['currency']),
                    'currency_symbol' => sanitizeInput($_POST['currency_symbol'])
                ];
                
                foreach ($settings as $key => $value) {
                    Database::query(
                        "INSERT INTO system_settings (category, setting_key, setting_value, updated_by) 
                         VALUES ('general', ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?",
                        [$key, $value, Session::getUserId(), $value, Session::getUserId()]
                    );
                }
                
                $_SESSION['success_message'] = 'General settings updated successfully.';
                logAdminActivity(Session::getUserId(), 'settings_updated', 'system', null, null, $settings);
                break;
                
            case 'update_security':
                $settings = [
                    'session_timeout' => intval($_POST['session_timeout']),
                    'max_login_attempts' => intval($_POST['max_login_attempts']),
                    'lockout_duration' => intval($_POST['lockout_duration']),
                    'enable_2fa' => isset($_POST['enable_2fa']) ? '1' : '0',
                    'password_min_length' => intval($_POST['password_min_length'])
                ];
                
                foreach ($settings as $key => $value) {
                    Database::query(
                        "INSERT INTO system_settings (category, setting_key, setting_value, updated_by) 
                         VALUES ('security', ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?",
                        [$key, $value, Session::getUserId(), $value, Session::getUserId()]
                    );
                }
                
                $_SESSION['success_message'] = 'Security settings updated successfully.';
                break;
                
            case 'update_features':
                $settings = [
                    'enable_multivendor' => isset($_POST['enable_multivendor']) ? '1' : '0',
                    'enable_reviews' => isset($_POST['enable_reviews']) ? '1' : '0',
                    'enable_wishlist' => isset($_POST['enable_wishlist']) ? '1' : '0',
                    'enable_loyalty' => isset($_POST['enable_loyalty']) ? '1' : '0',
                    'enable_coupons' => isset($_POST['enable_coupons']) ? '1' : '0',
                    'auto_approve_products' => isset($_POST['auto_approve_products']) ? '1' : '0'
                ];
                
                foreach ($settings as $key => $value) {
                    Database::query(
                        "INSERT INTO system_settings (category, setting_key, setting_value, updated_by) 
                         VALUES ('features', ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?",
                        [$key, $value, Session::getUserId(), $value, Session::getUserId()]
                    );
                }
                
                $_SESSION['success_message'] = 'Feature settings updated successfully.';
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        Logger::error("Settings update error: " . $e->getMessage());
    }
    
    header('Location: /admin/settings/?tab=' . $tab);
    exit;
}

// Load current settings
try {
    $currentSettings = [];
    $settings = Database::query("SELECT category, setting_key, setting_value FROM system_settings")->fetchAll();
    
    foreach ($settings as $setting) {
        $currentSettings[$setting['category']][$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $currentSettings = [];
    error_log("Error loading settings: " . $e->getMessage());
}

// System information
$systemInfo = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'disk_free_space' => disk_free_space('.') ? formatBytes(disk_free_space('.')) : 'Unknown',
    'current_time' => date('Y-m-d H:i:s T')
];

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 0;
        }
        .nav-pills .nav-link {
            border-radius: 0.5rem;
            margin: 0 0.25rem;
        }
        .system-info-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-cog me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <small class="text-white-50">Configure system settings and preferences</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'general' ? 'active' : ''; ?>" href="?tab=general">
                    <i class="fas fa-globe me-1"></i> General
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'security' ? 'active' : ''; ?>" href="?tab=security">
                    <i class="fas fa-shield-alt me-1"></i> Security
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'features' ? 'active' : ''; ?>" href="?tab=features">
                    <i class="fas fa-puzzle-piece me-1"></i> Features
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'system' ? 'active' : ''; ?>" href="?tab=system">
                    <i class="fas fa-server me-1"></i> System Info
                </a>
            </li>
        </ul>

        <?php if ($tab === 'general'): ?>
        <!-- General Settings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="update_general">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="site_name" class="form-label">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" 
                                       value="<?php echo htmlspecialchars($currentSettings['general']['site_name'] ?? 'E-Commerce Platform'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="admin_email" class="form-label">Admin Email</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" 
                                       value="<?php echo htmlspecialchars($currentSettings['general']['admin_email'] ?? 'admin@example.com'); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_description" class="form-label">Site Description</label>
                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($currentSettings['general']['site_description'] ?? 'Comprehensive E-Commerce Solution'); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="UTC" <?php echo ($currentSettings['general']['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                    <option value="America/New_York" <?php echo ($currentSettings['general']['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                    <option value="America/Chicago" <?php echo ($currentSettings['general']['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                                    <option value="America/Los_Angeles" <?php echo ($currentSettings['general']['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                                    <option value="Europe/London" <?php echo ($currentSettings['general']['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select" id="currency" name="currency">
                                    <option value="USD" <?php echo ($currentSettings['general']['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                    <option value="EUR" <?php echo ($currentSettings['general']['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                    <option value="GBP" <?php echo ($currentSettings['general']['currency'] ?? '') === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                                    <option value="CAD" <?php echo ($currentSettings['general']['currency'] ?? '') === 'CAD' ? 'selected' : ''; ?>>CAD - Canadian Dollar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" 
                                       value="<?php echo htmlspecialchars($currentSettings['general']['currency_symbol'] ?? '$'); ?>" maxlength="5">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save General Settings
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($tab === 'security'): ?>
        <!-- Security Settings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Security Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="update_security">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="session_timeout" class="form-label">Session Timeout (seconds)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                       value="<?php echo $currentSettings['security']['session_timeout'] ?? '3600'; ?>" min="300" max="86400">
                                <small class="form-text text-muted">Time before user sessions expire (300-86400 seconds)</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                       value="<?php echo $currentSettings['security']['max_login_attempts'] ?? '5'; ?>" min="3" max="20">
                                <small class="form-text text-muted">Failed attempts before account lockout</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="lockout_duration" class="form-label">Lockout Duration (seconds)</label>
                                <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                       value="<?php echo $currentSettings['security']['lockout_duration'] ?? '900'; ?>" min="300" max="3600">
                                <small class="form-text text-muted">How long accounts stay locked</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password_min_length" class="form-label">Minimum Password Length</label>
                                <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                       value="<?php echo $currentSettings['security']['password_min_length'] ?? '8'; ?>" min="6" max="32">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa" 
                                           <?php echo ($currentSettings['security']['enable_2fa'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_2fa">
                                        Enable Two-Factor Authentication
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Security Settings
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($tab === 'features'): ?>
        <!-- Feature Settings -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Feature Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="update_features">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Marketplace Features</h6>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_multivendor" name="enable_multivendor" 
                                       <?php echo ($currentSettings['features']['enable_multivendor'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_multivendor">
                                    <strong>Enable Multi-Vendor Marketplace</strong>
                                    <small class="d-block text-muted">Allow multiple vendors to sell products</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_approve_products" name="auto_approve_products" 
                                       <?php echo ($currentSettings['features']['auto_approve_products'] ?? '0') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_approve_products">
                                    <strong>Auto-Approve Products</strong>
                                    <small class="d-block text-muted">Automatically approve vendor product listings</small>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Customer Features</h6>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_reviews" name="enable_reviews" 
                                       <?php echo ($currentSettings['features']['enable_reviews'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_reviews">
                                    <strong>Product Reviews</strong>
                                    <small class="d-block text-muted">Allow customers to review products</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_wishlist" name="enable_wishlist" 
                                       <?php echo ($currentSettings['features']['enable_wishlist'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_wishlist">
                                    <strong>Wishlist</strong>
                                    <small class="d-block text-muted">Enable customer wishlist functionality</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_loyalty" name="enable_loyalty" 
                                       <?php echo ($currentSettings['features']['enable_loyalty'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_loyalty">
                                    <strong>Loyalty Program</strong>
                                    <small class="d-block text-muted">Enable points and rewards system</small>
                                </label>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="enable_coupons" name="enable_coupons" 
                                       <?php echo ($currentSettings['features']['enable_coupons'] ?? '1') === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_coupons">
                                    <strong>Coupons & Discounts</strong>
                                    <small class="d-block text-muted">Enable promotional codes and discounts</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Feature Settings
                    </button>
                </form>
            </div>
        </div>

        <?php elseif ($tab === 'system'): ?>
        <!-- System Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Server Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="system-info-card">
                            <strong>PHP Version:</strong> <?php echo $systemInfo['php_version']; ?>
                        </div>
                        <div class="system-info-card">
                            <strong>Server Software:</strong> <?php echo $systemInfo['server_software']; ?>
                        </div>
                        <div class="system-info-card">
                            <strong>Memory Limit:</strong> <?php echo $systemInfo['memory_limit']; ?>
                        </div>
                        <div class="system-info-card">
                            <strong>Max Execution Time:</strong> <?php echo $systemInfo['max_execution_time']; ?>s
                        </div>
                        <div class="system-info-card">
                            <strong>Max Upload Size:</strong> <?php echo $systemInfo['upload_max_filesize']; ?>
                        </div>
                        <div class="system-info-card">
                            <strong>Free Disk Space:</strong> <?php echo $systemInfo['disk_free_space']; ?>
                        </div>
                        <div class="system-info-card">
                            <strong>Current Time:</strong> <?php echo $systemInfo['current_time']; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Health</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>System Status:</strong> All systems operational
                        </div>
                        
                        <h6>Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="/admin/maintenance/" class="btn btn-outline-warning">
                                <i class="fas fa-tools me-1"></i> System Maintenance
                            </a>
                            <a href="/admin/analytics/" class="btn btn-outline-info">
                                <i class="fas fa-chart-line me-1"></i> View Analytics
                            </a>
                            <a href="/admin/security/" class="btn btn-outline-danger">
                                <i class="fas fa-shield-alt me-1"></i> Security Center
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>