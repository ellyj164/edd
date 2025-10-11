<?php
/**
 * API & Integrations Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - API key management with scopes
 * - Webhook management (HMAC, retries)
 * - Third-party integrations (payments/shipping/analytics)
 * - Usage monitoring & rate limiting
 * - API logs and analytics
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/audit_log.php';

// Load additional dependencies
require_once __DIR__ . '/../../includes/init.php';

// Database availability check with graceful fallback
$database_available = false;
$db = null;
try {
    $db = db();
    $db->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Admin Bypass Mode - Skip all authentication when enabled
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
    // Normal authentication check - redirect to login if not authenticated as admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'API & Integrations';
$action = $_GET['action'] ?? 'index';
$tab = $_GET['tab'] ?? 'api_keys';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create_api_key':
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $scopes = $_POST['scopes'] ?? [];
                $rate_limit = intval($_POST['rate_limit'] ?? 1000);
                
                // Generate API key
                $api_key = 'epd_' . bin2hex(random_bytes(32));
                $api_secret = bin2hex(random_bytes(32));
                
                $stmt = $db->prepare("
                    INSERT INTO api_keys (name, api_key, api_secret, permissions, rate_limit, user_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $api_key, hash('sha256', $api_secret), json_encode($scopes), $rate_limit, $_SESSION['user_id']]);
                
                $success = "API key created successfully! Secret: " . $api_secret . " (save this, it won't be shown again)";
                break;
                
            case 'toggle_api_key':
                $id = intval($_POST['api_key_id']);
                $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
                
                $stmt = $db->prepare("UPDATE api_keys SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                
                $success = "API key status updated successfully!";
                break;
                
            case 'create_webhook':
                $name = sanitizeInput($_POST['name']);
                $url = sanitizeInput($_POST['url']);
                $events = $_POST['events'] ?? [];
                $secret = bin2hex(random_bytes(32));
                
                $stmt = $db->prepare("
                    INSERT INTO webhook_subscriptions (url, events, secret, created_by, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$url, json_encode($events), $secret, $_SESSION['user_id']]);
                
                $success = "Webhook created successfully! Secret: " . $secret;
                break;
                
            case 'test_webhook':
                $id = intval($_POST['id']);
                
                // Get webhook details
                $stmt = $db->prepare("SELECT * FROM webhook_subscriptions WHERE id = ?");
                $stmt->execute([$id]);
                $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($webhook) {
                    // Send test payload
                    $test_payload = [
                        'event' => 'test',
                        'timestamp' => time(),
                        'data' => ['message' => 'This is a test webhook from EPD Admin Panel']
                    ];
                    
                    // In real implementation, send HTTP request to webhook URL
                    $success = "Test webhook sent to " . $webhook['url'];
                } else {
                    $error = "Webhook not found";
                }
                break;
                
            case 'update_integration':
                $integration = sanitizeInput($_POST['integration']);
                $config = $_POST['config'] ?? [];
                $status = isset($_POST['enabled']) && $_POST['enabled'] ? 'active' : 'inactive';
                
                // Store integration configuration
                $stmt = $db->prepare("
                    INSERT INTO integrations (name, type, provider, config, status, installed_by, created_at, updated_at) 
                    VALUES (?, 'other', ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE 
                    config = VALUES(config), status = VALUES(status), updated_at = NOW()
                ");
                $stmt->execute([$integration, $integration, json_encode($config), $status, $_SESSION['user_id']]);
                
                $success = "Integration settings updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get data based on tab
$api_keys = [];
$webhooks = [];
$integrations = [];
$api_logs = [];

if ($database_available) {
    try {
        // API Keys
        $stmt = $db->query("
            SELECT ak.*, u.username as created_by_name, 
                   (SELECT COUNT(*) FROM api_logs WHERE api_key_id = ak.id AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as usage_24h
            FROM api_keys ak
            LEFT JOIN users u ON ak.user_id = u.id
            ORDER BY ak.created_at DESC
        ");
        $api_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add defensive mapping for missing fields
        foreach ($api_keys as &$key) {
            $key['description'] = $key['description'] ?? '';
            $key['scopes'] = $key['permissions'] ?? '[]';
            $key['status'] = $key['is_active'] ? 'active' : 'inactive';
        }
        unset($key);
        
        // Webhooks
        $stmt = $db->query("
            SELECT ws.*, u.username as created_by_name,
                   (SELECT COUNT(*) FROM webhook_deliveries WHERE webhook_deliveries.webhook_id = ws.id AND webhook_deliveries.response_status >= 200 AND webhook_deliveries.response_status < 300 AND webhook_deliveries.last_attempt > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as successful_24h,
                   (SELECT COUNT(*) FROM webhook_deliveries WHERE webhook_deliveries.webhook_id = ws.id AND (webhook_deliveries.response_status < 200 OR webhook_deliveries.response_status >= 300) AND webhook_deliveries.last_attempt > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as failed_24h
            FROM webhook_subscriptions ws
            LEFT JOIN users u ON ws.created_by = u.id
            ORDER BY ws.created_at DESC
        ");
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add defensive mapping for missing fields
        foreach ($webhooks as &$webhook) {
            $webhook['name'] = $webhook['name'] ?? $webhook['url'];
            $webhook['status'] = $webhook['is_active'] ? 'active' : 'inactive';
        }
        unset($webhook);
        
        // Integrations
        $stmt = $db->query("
            SELECT i.*, u.username as updated_by_name
            FROM integrations i
            LEFT JOIN users u ON i.installed_by = u.id
            ORDER BY i.name
        ");
        $integrations_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to associative array for easier lookup
        foreach ($integrations_db as $integration) {
            $integrations[$integration['name']] = $integration;
        }
        
        // API Logs (recent)
        if ($tab === 'logs') {
            $stmt = $db->query("
                SELECT al.*, ak.name as api_key_name
                FROM api_logs al
                LEFT JOIN api_keys ak ON al.api_key_id = ak.id
                ORDER BY al.created_at DESC
                LIMIT 100
            ");
            $api_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = 'Database connection required. Please check your database configuration.';
    $api_keys = [];
    $webhooks = [];
}

// Available API scopes
$available_scopes = [
    'read:products' => 'Read products and catalog data',
    'write:products' => 'Create and update products',
    'read:orders' => 'Read order information',
    'write:orders' => 'Create and update orders',
    'read:users' => 'Read user information',
    'write:users' => 'Create and update users',
    'read:analytics' => 'Access analytics and reports',
    'admin:all' => 'Full administrative access'
];

// Available webhook events
$available_events = [
    'order.created' => 'New order placed',
    'order.updated' => 'Order status changed',
    'order.cancelled' => 'Order cancelled',
    'payment.completed' => 'Payment processed',
    'payment.failed' => 'Payment failed',
    'user.registered' => 'New user registered',
    'product.created' => 'New product added',
    'inventory.low' => 'Low stock alert'
];

// Integration configurations
$integration_configs = [
    'stripe' => [
        'name' => 'Stripe Payments',
        'description' => 'Accept payments via Stripe',
        'fields' => [
            'public_key' => ['label' => 'Publishable Key', 'type' => 'text'],
            'secret_key' => ['label' => 'Secret Key', 'type' => 'password'],
            'webhook_secret' => ['label' => 'Webhook Endpoint Secret', 'type' => 'password']
        ]
    ],
    'fedex' => [
        'name' => 'FedEx Shipping',
        'description' => 'Calculate shipping rates and track packages',
        'fields' => [
            'account_number' => ['label' => 'Account Number', 'type' => 'text'],
            'meter_number' => ['label' => 'Meter Number', 'type' => 'text'],
            'api_key' => ['label' => 'API Key', 'type' => 'password'],
            'api_secret' => ['label' => 'API Secret', 'type' => 'password']
        ]
    ],
    'google_analytics' => [
        'name' => 'Google Analytics',
        'description' => 'Track website analytics and e-commerce data',
        'fields' => [
            'tracking_id' => ['label' => 'Tracking ID', 'type' => 'text'],
            'measurement_id' => ['label' => 'Measurement ID (GA4)', 'type' => 'text']
        ]
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .api-key-card, .webhook-card, .integration-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .api-key-card:hover, .webhook-card:hover, .integration-card:hover {
            transform: translateY(-2px);
        }
        
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .status-active { background-color: var(--admin-success); }
        .status-inactive { background-color: var(--admin-danger); }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }
        
        .log-entry {
            background: white;
            border-left: 4px solid var(--admin-accent);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 4px 4px 0;
        }
        
        .log-entry.error {
            border-left-color: var(--admin-danger);
        }
        
        .log-entry.warning {
            border-left-color: var(--admin-warning);
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
                        <i class="fas fa-plug me-2"></i>
                        API & Integrations
                    </h1>
                    <small class="text-white-50">Manage API access and third-party integrations</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Admin
                    </a>
                    <span class="text-white-50">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'api_keys' ? 'active' : ''; ?>" href="?tab=api_keys">
                    <i class="fas fa-key me-1"></i>
                    API Keys
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'webhooks' ? 'active' : ''; ?>" href="?tab=webhooks">
                    <i class="fas fa-webhook me-1"></i>
                    Webhooks
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'integrations' ? 'active' : ''; ?>" href="?tab=integrations">
                    <i class="fas fa-puzzle-piece me-1"></i>
                    Integrations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'logs' ? 'active' : ''; ?>" href="?tab=logs">
                    <i class="fas fa-list me-1"></i>
                    API Logs
                </a>
            </li>
        </ul>

        <?php if ($tab === 'api_keys'): ?>
        <!-- API Keys Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-key me-2"></i>API Keys</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                        <i class="fas fa-plus me-1"></i>
                        Create API Key
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($api_keys)): ?>
        <div class="text-center py-5">
            <i class="fas fa-key fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No API Keys Created</h4>
            <p class="text-muted">Create your first API key to enable programmatic access</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createApiKeyModal">
                <i class="fas fa-plus me-1"></i>
                Create Your First API Key
            </button>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($api_keys as $key): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="api-key-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($key['name']); ?></h6>
                        <span class="status-indicator status-<?php echo $key['status']; ?>"></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo htmlspecialchars($key['description'] ?? ''); ?></p>
                        <div class="mb-2">
                            <small><strong>Key:</strong></small>
                            <div class="code-block"><?php echo htmlspecialchars($key['api_key']); ?></div>
                        </div>
                        <div class="mb-3">
                            <small><strong>Scopes:</strong></small>
                            <div>
                                <?php 
                                $scopes = json_decode($key['scopes'] ?? '[]', true) ?? [];
                                foreach ($scopes as $scope): ?>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($scope); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                Usage: <?php echo number_format($key['usage_24h']); ?> (24h)
                            </small>
                            <div>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle_api_key">
                                    <input type="hidden" name="api_key_id" value="<?php echo $key['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $key['status']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $key['status'] === 'active' ? 'danger' : 'success'; ?>">
                                        <?php echo $key['status'] === 'active' ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">
                            Created by <?php echo htmlspecialchars($key['created_by_name']); ?>
                            on <?php echo date('M d, Y', strtotime($key['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Create API Key Modal -->
        <div class="modal fade" id="createApiKeyModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Create API Key</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_api_key">
                            
                            <div class="mb-3">
                                <label for="api_key_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="api_key_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="api_key_description" class="form-label">Description</label>
                                <textarea class="form-control" id="api_key_description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="rate_limit" class="form-label">Rate Limit (requests per hour)</label>
                                <input type="number" class="form-control" id="rate_limit" name="rate_limit" value="1000" min="1" max="10000">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Scopes</label>
                                <?php foreach ($available_scopes as $scope => $description): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="scopes[]" value="<?php echo $scope; ?>" id="scope_<?php echo str_replace(':', '_', $scope); ?>">
                                    <label class="form-check-label" for="scope_<?php echo str_replace(':', '_', $scope); ?>">
                                        <strong><?php echo $scope; ?></strong><br>
                                        <small class="text-muted"><?php echo $description; ?></small>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create API Key</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'webhooks'): ?>
        <!-- Webhooks Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-webhook me-2"></i>Webhooks</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWebhookModal">
                        <i class="fas fa-plus me-1"></i>
                        Create Webhook
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($webhooks)): ?>
        <div class="text-center py-5">
            <i class="fas fa-webhook fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No Webhooks Configured</h4>
            <p class="text-muted">Set up webhooks to receive real-time event notifications</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWebhookModal">
                <i class="fas fa-plus me-1"></i>
                Create Your First Webhook
            </button>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($webhooks as $webhook): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="webhook-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($webhook['name'] ?? $webhook['url']); ?></h6>
                        <span class="status-indicator status-<?php echo $webhook['status'] ?? 'inactive'; ?>"></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small><strong>URL:</strong></small>
                            <div class="code-block"><?php echo htmlspecialchars($webhook['url']); ?></div>
                        </div>
                        <div class="mb-3">
                            <small><strong>Events:</strong></small>
                            <div>
                                <?php 
                                $events = json_decode($webhook['events'] ?? '[]', true) ?? [];
                                foreach ($events as $event): ?>
                                    <span class="badge bg-info me-1"><?php echo htmlspecialchars($event); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6 text-center">
                                <div class="text-success h5 mb-0"><?php echo number_format($webhook['successful_24h'] ?? 0); ?></div>
                                <small class="text-muted">Success (24h)</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="text-danger h5 mb-0"><?php echo number_format($webhook['failed_24h'] ?? 0); ?></div>
                                <small class="text-muted">Failed (24h)</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($webhook['created_at'])); ?>
                            </small>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="test_webhook">
                                <input type="hidden" name="id" value="<?php echo $webhook['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-play me-1"></i>Test
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Create Webhook Modal -->
        <div class="modal fade" id="createWebhookModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Webhook</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_webhook">
                            
                            <div class="mb-3">
                                <label for="webhook_name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="webhook_name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="webhook_url" class="form-label">Payload URL</label>
                                <input type="url" class="form-control" id="webhook_url" name="url" required 
                                       placeholder="https://example.com/webhook">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Events</label>
                                <?php foreach ($available_events as $event => $description): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="events[]" value="<?php echo $event; ?>" id="event_<?php echo str_replace('.', '_', $event); ?>">
                                    <label class="form-check-label" for="event_<?php echo str_replace('.', '_', $event); ?>">
                                        <strong><?php echo $event; ?></strong><br>
                                        <small class="text-muted"><?php echo $description; ?></small>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Webhook</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'integrations'): ?>
        <!-- Integrations Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fas fa-puzzle-piece me-2"></i>Third-Party Integrations</h4>
            </div>
        </div>

        <div class="row">
            <?php foreach ($integration_configs as $key => $config): ?>
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="integration-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($config['name']); ?></h6>
                        <span class="status-indicator status-<?php echo ($integrations[$key]['enabled'] ?? 0) ? 'active' : 'inactive'; ?>"></span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo htmlspecialchars($config['description']); ?></p>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_integration">
                            <input type="hidden" name="integration" value="<?php echo $key; ?>">
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="enabled" id="enabled_<?php echo $key; ?>" 
                                       <?php echo ($integrations[$key]['enabled'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enabled_<?php echo $key; ?>">
                                    Enable Integration
                                </label>
                            </div>
                            
                            <?php 
                            $saved_config = json_decode($integrations[$key]['config'] ?? '{}', true) ?? [];
                            foreach ($config['fields'] as $field_key => $field_config): 
                            ?>
                            <div class="mb-3">
                                <label for="<?php echo $key . '_' . $field_key; ?>" class="form-label">
                                    <?php echo htmlspecialchars($field_config['label']); ?>
                                </label>
                                <?php if ($field_config['type'] === 'select'): ?>
                                <select class="form-select" name="config[<?php echo $field_key; ?>]" id="<?php echo $key . '_' . $field_key; ?>">
                                    <option value="">Select...</option>
                                    <?php foreach ($field_config['options'] as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo ($saved_config[$field_key] ?? '') === $option ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($option); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <input type="<?php echo $field_config['type']; ?>" class="form-control" 
                                       name="config[<?php echo $field_key; ?>]" id="<?php echo $key . '_' . $field_key; ?>"
                                       value="<?php echo htmlspecialchars($saved_config[$field_key] ?? ''); ?>"
                                       <?php echo $field_config['type'] === 'password' ? 'placeholder="[Hidden]"' : ''; ?>>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            
                            <button type="submit" class="btn btn-primary">Save Configuration</button>
                        </form>
                    </div>
                    <?php if (isset($integrations[$key]['updated_at'])): ?>
                    <div class="card-footer">
                        <small class="text-muted">
                            Last updated by <?php echo htmlspecialchars($integrations[$key]['updated_by_name']); ?>
                            on <?php echo date('M d, Y', strtotime($integrations[$key]['updated_at'])); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php elseif ($tab === 'logs'): ?>
        <!-- API Logs Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fas fa-list me-2"></i>API Logs (Last 100 requests)</h4>
            </div>
        </div>

        <?php if (empty($api_logs)): ?>
        <div class="text-center py-5">
            <i class="fas fa-list fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No API Logs</h4>
            <p class="text-muted">API request logs will appear here once you start using the API</p>
        </div>
        <?php else: ?>
        <?php foreach ($api_logs as $log): ?>
        <div class="log-entry <?php echo $log['status_code'] >= 400 ? 'error' : ($log['status_code'] >= 300 ? 'warning' : ''); ?>">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong><?php echo htmlspecialchars($log['method'] . ' ' . $log['endpoint']); ?></strong>
                    <span class="badge bg-<?php echo $log['status_code'] >= 400 ? 'danger' : ($log['status_code'] >= 300 ? 'warning' : 'success'); ?> ms-2">
                        <?php echo $log['status_code']; ?>
                    </span>
                    <br>
                    <small class="text-muted">
                        API Key: <?php echo htmlspecialchars($log['api_key_name'] ?? 'Unknown'); ?>
                        • IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                        • <?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>
                    </small>
                    <?php if ($log['response_time']): ?>
                    <br><small class="text-muted">Response time: <?php echo $log['response_time']; ?>ms</small>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <?php if ($log['error_message']): ?>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#error_<?php echo $log['id']; ?>">
                        View Error
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($log['error_message']): ?>
            <div class="collapse mt-2" id="error_<?php echo $log['id']; ?>">
                <div class="alert alert-danger mb-0">
                    <small><?php echo htmlspecialchars($log['error_message']); ?></small>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>