<?php
/**
 * Developer Portal - API Key Management
 * E-Commerce Platform
 * 
 * Features:
 * - Generate API keys for sandbox and live environments
 * - Manage existing API keys
 * - View API documentation
 * - Monitor API usage
 */

require_once __DIR__ . '/includes/init.php';

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$userId = Session::get('user_id');
$db = db();
$page_title = 'Developer Portal';

// Handle API key actions
$message = '';
$messageType = '';
$newApiSecret = ''; // Store temporarily to show to user once

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== Session::get('csrf_token')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        switch ($_POST['action']) {
            case 'create_api_key':
                $name = sanitizeInput($_POST['name'] ?? '');
                $environment = in_array($_POST['environment'] ?? '', ['sandbox', 'live']) ? $_POST['environment'] : 'sandbox';
                
                if (empty($name)) {
                    $message = 'API key name is required';
                    $messageType = 'error';
                } else {
                    // Check if user has subscription for live environment
                    if ($environment === 'live') {
                        // Check if user is admin or has active live subscription
                        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
                        $stmt->execute([$userId]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        $isAdmin = ($user && $user['role'] === 'admin');
                        
                        if (!$isAdmin) {
                            $stmt = $db->prepare("
                                SELECT id FROM api_subscriptions 
                                WHERE user_id = ? 
                                AND subscription_type = 'live' 
                                AND status = 'active'
                                LIMIT 1
                            ");
                            $stmt->execute([$userId]);
                            $hasSubscription = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if (!$hasSubscription) {
                                $message = 'Live API access requires an active subscription. Please subscribe for $150/month.';
                                $messageType = 'error';
                                break;
                            }
                        }
                    }
                    
                    // Generate API key and secret
                    $apiKey = 'feza_' . $environment . '_' . bin2hex(random_bytes(24));
                    $apiSecret = bin2hex(random_bytes(32));
                    
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO api_keys (user_id, name, environment, api_key, api_secret, is_active, created_at) 
                            VALUES (?, ?, ?, ?, ?, 1, NOW())
                        ");
                        $stmt->execute([$userId, $name, $environment, $apiKey, hash('sha256', $apiSecret)]);
                        
                        $message = 'API key created successfully!';
                        $messageType = 'success';
                        $newApiSecret = $apiSecret; // Store to display once
                    } catch (Exception $e) {
                        $message = 'Error creating API key: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'toggle_status':
                $keyId = intval($_POST['key_id'] ?? 0);
                
                try {
                    // Verify ownership
                    $stmt = $db->prepare("SELECT id, is_active FROM api_keys WHERE id = ? AND user_id = ?");
                    $stmt->execute([$keyId, $userId]);
                    $key = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($key) {
                        $newStatus = $key['is_active'] ? 0 : 1;
                        $stmt = $db->prepare("UPDATE api_keys SET is_active = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $keyId]);
                        
                        $message = 'API key status updated successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'API key not found';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error updating API key: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'delete_key':
                $keyId = intval($_POST['key_id'] ?? 0);
                
                try {
                    $stmt = $db->prepare("DELETE FROM api_keys WHERE id = ? AND user_id = ?");
                    $stmt->execute([$keyId, $userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'API key deleted successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'API key not found';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error deleting API key: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'create_webhook':
                $url = filter_var($_POST['url'] ?? '', FILTER_SANITIZE_URL);
                $events = $_POST['events'] ?? [];
                
                if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                    $message = 'Valid webhook URL is required';
                    $messageType = 'error';
                } elseif (empty($events)) {
                    $message = 'Select at least one event';
                    $messageType = 'error';
                } else {
                    $secret = bin2hex(random_bytes(32));
                    
                    try {
                        $stmt = $db->prepare("
                            INSERT INTO webhook_subscriptions (url, events, secret, created_by, created_at) 
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$url, json_encode($events), $secret, $userId]);
                        
                        $message = 'Webhook created successfully!';
                        $messageType = 'success';
                    } catch (Exception $e) {
                        $message = 'Error creating webhook: ' . $e->getMessage();
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'delete_webhook':
                $webhookId = intval($_POST['id'] ?? 0);
                
                try {
                    $stmt = $db->prepare("DELETE FROM webhook_subscriptions WHERE id = ? AND created_by = ?");
                    $stmt->execute([$webhookId, $userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        $message = 'Webhook deleted successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Webhook not found';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error deleting webhook: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
                
            case 'toggle_webhook':
                $webhookId = intval($_POST['id'] ?? 0);
                
                try {
                    $stmt = $db->prepare("SELECT id, is_active FROM webhook_subscriptions WHERE id = ? AND created_by = ?");
                    $stmt->execute([$webhookId, $userId]);
                    $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($webhook) {
                        $newStatus = $webhook['is_active'] ? 0 : 1;
                        $stmt = $db->prepare("UPDATE webhook_subscriptions SET is_active = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $webhookId]);
                        
                        $message = 'Webhook status updated!';
                        $messageType = 'success';
                    } else {
                        $message = 'Webhook not found';
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = 'Error updating webhook: ' . $e->getMessage();
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Generate CSRF token
if (!Session::get('csrf_token')) {
    Session::set('csrf_token', bin2hex(random_bytes(32)));
}
$csrfToken = Session::get('csrf_token');

// Get user's API keys
try {
    $stmt = $db->prepare("
        SELECT id, name, environment, api_key, is_active, rate_limit, last_used_at, created_at 
        FROM api_keys 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $apiKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $apiKeys = [];
    error_log("Error fetching API keys: " . $e->getMessage());
}

// Check user's subscription status
try {
    // Check if user is admin (gets free live access)
    $isAdmin = false;
    $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $isAdmin = ($user && $user['role'] === 'admin');
    
    // Get active subscriptions
    $stmt = $db->prepare("
        SELECT * FROM api_subscriptions 
        WHERE user_id = ? 
        AND status = 'active'
        ORDER BY subscription_type DESC
    ");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for live subscription
    $hasLiveSubscription = $isAdmin;
    foreach ($subscriptions as $sub) {
        if ($sub['subscription_type'] === 'live') {
            $hasLiveSubscription = true;
            break;
        }
    }
} catch (Exception $e) {
    $subscriptions = [];
    $hasLiveSubscription = false;
    $isAdmin = false;
    error_log("Error fetching subscriptions: " . $e->getMessage());
}

// Get user's webhooks
try {
    $stmt = $db->prepare("
        SELECT ws.*, 
               (SELECT COUNT(*) FROM webhook_deliveries WHERE webhook_deliveries.webhook_subscription_id = ws.id AND webhook_deliveries.success = 1 AND webhook_deliveries.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as successful_deliveries,
               (SELECT COUNT(*) FROM webhook_deliveries WHERE webhook_deliveries.webhook_subscription_id = ws.id AND webhook_deliveries.success = 0 AND webhook_deliveries.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as failed_deliveries
        FROM webhook_subscriptions ws
        WHERE ws.created_by = ? 
        ORDER BY ws.created_at DESC
    ");
    $stmt->execute([$userId]);
    $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $webhooks = [];
    error_log("Error fetching webhooks: " . $e->getMessage());
}

// Get API logs for user's keys
try {
    $stmt = $db->prepare("
        SELECT al.*, ak.name as api_key_name
        FROM api_logs al
        LEFT JOIN api_keys ak ON al.api_key_id = ak.id
        WHERE ak.user_id = ?
        ORDER BY al.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$userId]);
    $apiLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $apiLogs = [];
    error_log("Error fetching API logs: " . $e->getMessage());
}

// Calculate usage statistics
try {
    // Total requests
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_requests,
               AVG(response_time) as avg_response_time,
               SUM(CASE WHEN response_status < 400 THEN 1 ELSE 0 END) as successful_requests
        FROM api_logs al
        LEFT JOIN api_keys ak ON al.api_key_id = ak.id
        WHERE ak.user_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $totalRequests = intval($stats['total_requests'] ?? 0);
    $avgResponseTime = floatval($stats['avg_response_time'] ?? 0);
    $successfulRequests = intval($stats['successful_requests'] ?? 0);
    $successRate = $totalRequests > 0 ? ($successfulRequests / $totalRequests * 100) : 0;
} catch (Exception $e) {
    $totalRequests = 0;
    $avgResponseTime = 0;
    $successRate = 0;
    error_log("Error calculating statistics: " . $e->getMessage());
}

// Available webhook events
$availableEvents = [
    'order.created' => 'New order placed',
    'order.updated' => 'Order status changed',
    'order.cancelled' => 'Order cancelled',
    'order.completed' => 'Order completed',
    'payment.completed' => 'Payment processed',
    'payment.failed' => 'Payment failed',
    'payment.refunded' => 'Payment refunded',
    'user.registered' => 'New user registered',
    'user.updated' => 'User profile updated',
    'product.created' => 'New product added',
    'product.updated' => 'Product updated',
    'product.deleted' => 'Product deleted',
    'inventory.low' => 'Low stock alert'
];

includeHeader($page_title);
?>

<div class="container dev-portal-container">
    <div class="dev-portal-header">
        <h1>Developer Portal</h1>
        <p>Manage your API keys and access documentation</p>
    </div>
    
    <!-- Subscription Status Banner -->
    <?php if (!$hasLiveSubscription): ?>
    <div class="subscription-banner">
        <div class="subscription-info">
            <h3>🚀 Upgrade to Live API Access</h3>
            <p>Currently on <strong>Sandbox (Free)</strong> - for testing only</p>
            <p>Unlock production access with Live API subscription: <strong>$150/month</strong></p>
            <ul class="subscription-features">
                <li>✓ Full production API access</li>
                <li>✓ Higher rate limits</li>
                <li>✓ Priority support</li>
                <li>✓ Real transactions and data</li>
            </ul>
            <a href="/api/subscribe?plan=live" class="btn btn-upgrade">Upgrade Now</a>
        </div>
    </div>
    <?php elseif ($isAdmin): ?>
    <div class="subscription-banner subscription-admin">
        <div class="subscription-info">
            <span class="badge-admin">👑 Admin Account</span>
            <p><strong>You have free unlimited access to all API environments</strong></p>
        </div>
    </div>
    <?php else: ?>
    <div class="subscription-banner subscription-active">
        <div class="subscription-info">
            <span class="badge-active">✓ Active Subscription</span>
            <p><strong>Live API Access</strong> - $150/month</p>
            <a href="/account/billing" class="link-manage">Manage Subscription</a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
            <?php if ($newApiSecret): ?>
                <div class="api-secret-display">
                    <strong>⚠️ Important: Save your API Secret</strong><br>
                    <code><?php echo htmlspecialchars($newApiSecret); ?></code><br>
                    <small>This secret will only be shown once. Store it securely.</small>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="dev-portal-nav">
        <button class="tab-btn active" data-tab="keys">API Keys</button>
        <button class="tab-btn" data-tab="webhooks">Webhooks</button>
        <button class="tab-btn" data-tab="logs">API Logs</button>
        <button class="tab-btn" data-tab="docs">Documentation</button>
        <button class="tab-btn" data-tab="usage">Usage</button>
    </div>
    
    <!-- API Keys Tab -->
    <div class="tab-content active" id="keys-tab">
        <div class="section-header">
            <h2>Your API Keys</h2>
            <button class="btn btn-primary" onclick="showCreateKeyModal()">
                <i class="fas fa-plus"></i> Create API Key
            </button>
        </div>
        
        <?php if (empty($apiKeys)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔑</div>
                <h3>No API Keys Yet</h3>
                <p>Create your first API key to start using the FezaMarket API</p>
                <button class="btn btn-primary" onclick="showCreateKeyModal()">Create API Key</button>
            </div>
        <?php else: ?>
            <div class="api-keys-grid">
                <?php foreach ($apiKeys as $key): ?>
                    <div class="api-key-card">
                        <div class="key-header">
                            <div class="key-info">
                                <h3><?php echo htmlspecialchars($key['name']); ?></h3>
                                <span class="env-badge env-<?php echo $key['environment']; ?>">
                                    <?php echo ucfirst($key['environment']); ?>
                                </span>
                            </div>
                            <div class="key-status">
                                <span class="status-indicator <?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="key-details">
                            <div class="detail-row">
                                <span class="label">API Key:</span>
                                <code class="api-key-display"><?php echo htmlspecialchars($key['api_key']); ?></code>
                                <button class="btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($key['api_key']); ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="label">Rate Limit:</span>
                                <span><?php echo number_format($key['rate_limit']); ?> requests/hour</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Last Used:</span>
                                <span><?php echo $key['last_used_at'] ? date('M j, Y g:i A', strtotime($key['last_used_at'])) : 'Never'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Created:</span>
                                <span><?php echo date('M j, Y', strtotime($key['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="key-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <?php echo $key['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this API key?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete_key">
                                <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Webhooks Tab -->
    <div class="tab-content" id="webhooks-tab">
        <div class="section-header">
            <h2>Webhook Subscriptions</h2>
            <button class="btn btn-primary" onclick="showCreateWebhookModal()">
                <i class="fas fa-plus"></i> Create Webhook
            </button>
        </div>
        
        <?php if (empty($webhooks)): ?>
            <div class="empty-state">
                <div class="empty-icon">🪝</div>
                <h3>No Webhooks Yet</h3>
                <p>Create a webhook to receive real-time event notifications</p>
                <button class="btn btn-primary" onclick="showCreateWebhookModal()">Create Webhook</button>
            </div>
        <?php else: ?>
            <div class="webhooks-grid">
                <?php foreach ($webhooks as $webhook): ?>
                    <?php $events = json_decode($webhook['events'], true) ?? []; ?>
                    <div class="webhook-card">
                        <div class="webhook-header">
                            <div class="webhook-info">
                                <h3>Webhook Endpoint</h3>
                                <span class="status-indicator <?php echo $webhook['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $webhook['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="webhook-details">
                            <div class="detail-row">
                                <span class="label">URL:</span>
                                <code class="webhook-url"><?php echo htmlspecialchars($webhook['url']); ?></code>
                            </div>
                            <div class="detail-row">
                                <span class="label">Events:</span>
                                <div class="event-badges">
                                    <?php foreach ($events as $event): ?>
                                        <span class="event-badge"><?php echo htmlspecialchars($event); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="detail-row">
                                <span class="label">Secret:</span>
                                <code class="webhook-secret"><?php echo htmlspecialchars(substr($webhook['secret'], 0, 16) . '...'); ?></code>
                                <button class="btn-copy" onclick="copyToClipboard('<?php echo htmlspecialchars($webhook['secret']); ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div class="detail-row">
                                <span class="label">Success (7d):</span>
                                <span class="success-count"><?php echo number_format($webhook['successful_deliveries']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Failed (7d):</span>
                                <span class="failed-count"><?php echo number_format($webhook['failed_deliveries']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Last Triggered:</span>
                                <span><?php echo $webhook['last_triggered_at'] ? date('M j, Y g:i A', strtotime($webhook['last_triggered_at'])) : 'Never'; ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Created:</span>
                                <span><?php echo date('M j, Y', strtotime($webhook['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="webhook-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="toggle_webhook">
                                <input type="hidden" name="id" value="<?php echo $webhook['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline">
                                    <?php echo $webhook['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this webhook?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="delete_webhook">
                                <input type="hidden" name="id" value="<?php echo $webhook['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- API Logs Tab -->
    <div class="tab-content" id="logs-tab">
        <div class="section-header">
            <h2>API Request Logs</h2>
            <span class="log-count">Showing last 100 requests</span>
        </div>
        
        <?php if (empty($apiLogs)): ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <h3>No API Logs Yet</h3>
                <p>API request logs will appear here once you start making API calls</p>
            </div>
        <?php else: ?>
            <div class="logs-table-container">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>API Key</th>
                            <th>Method</th>
                            <th>Endpoint</th>
                            <th>Status</th>
                            <th>Time (ms)</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiLogs as $log): ?>
                            <tr class="log-row <?php echo $log['response_status'] >= 400 ? 'log-error' : 'log-success'; ?>">
                                <td class="log-time"><?php echo date('M j, g:i A', strtotime($log['created_at'])); ?></td>
                                <td class="log-key"><?php echo htmlspecialchars($log['api_key_name'] ?? 'Unknown'); ?></td>
                                <td class="log-method">
                                    <span class="method-badge method-<?php echo strtolower($log['method']); ?>">
                                        <?php echo htmlspecialchars($log['method']); ?>
                                    </span>
                                </td>
                                <td class="log-endpoint"><code><?php echo htmlspecialchars($log['endpoint']); ?></code></td>
                                <td class="log-status">
                                    <span class="status-badge status-<?php echo $log['response_status'] >= 400 ? 'error' : 'success'; ?>">
                                        <?php echo htmlspecialchars($log['response_status']); ?>
                                    </span>
                                </td>
                                <td class="log-response-time"><?php echo number_format($log['response_time']); ?>ms</td>
                                <td class="log-ip"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Documentation Tab -->
    <div class="tab-content" id="docs-tab">
        <div class="docs-container">
            <h2>API Documentation</h2>
            
            <section class="doc-section">
                <h3>Getting Started</h3>
                <p>Welcome to the FezaMarket API! This guide will help you get started with integrating our platform into your applications.</p>
                
                <h4>Authentication</h4>
                <p>All API requests require authentication using your API key. Include your key in the request headers:</p>
                <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
                
                <h4>Environments</h4>
                <ul>
                    <li><strong>Sandbox:</strong> Use for testing and development. Data is isolated and safe to experiment with.</li>
                    <li><strong>Live:</strong> Production environment with real data and transactions.</li>
                </ul>
            </section>
            
            <section class="doc-section">
                <h3>Base URLs</h3>
                <div class="code-block">
                    <p><strong>Sandbox:</strong> <code>https://api-sandbox.fezamarket.com</code></p>
                    <p><strong>Live:</strong> <code>https://api.fezamarket.com</code></p>
                </div>
            </section>
            
            <section class="doc-section">
                <h3>Products API</h3>
                
                <h4>List Products</h4>
                <div class="endpoint">
                    <span class="method">GET</span>
                    <code>/v1/products</code>
                </div>
                <p>Retrieve a list of products with optional filtering and pagination.</p>
                
                <h5>Query Parameters:</h5>
                <table class="params-table">
                    <tr>
                        <th>Parameter</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                    <tr>
                        <td><code>page</code></td>
                        <td>integer</td>
                        <td>Page number (default: 1)</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td>integer</td>
                        <td>Items per page (default: 20, max: 100)</td>
                    </tr>
                    <tr>
                        <td><code>category</code></td>
                        <td>string</td>
                        <td>Filter by category slug</td>
                    </tr>
                    <tr>
                        <td><code>search</code></td>
                        <td>string</td>
                        <td>Search products by name or description</td>
                    </tr>
                </table>
                
                <h5>Example Request:</h5>
                <pre><code>curl -X GET "https://api.fezamarket.com/v1/products?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>
                
                <h5>Example Response:</h5>
                <pre><code>{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "price": 29.99,
      "currency": "USD",
      "image_url": "https://example.com/image.jpg",
      "category": "electronics"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100
  }
}</code></pre>
            </section>
            
            <section class="doc-section">
                <h3>Rate Limiting</h3>
                <p>API requests are rate-limited based on your account tier:</p>
                <ul>
                    <li><strong>Free Tier:</strong> 100 requests per hour</li>
                    <li><strong>Pro Tier:</strong> 1,000 requests per hour</li>
                    <li><strong>Enterprise:</strong> Custom limits</li>
                </ul>
                
                <p>Rate limit headers are included in all responses:</p>
                <pre><code>X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200</code></pre>
            </section>
            
            <section class="doc-section">
                <h3>Error Handling</h3>
                <p>The API uses standard HTTP status codes:</p>
                <ul>
                    <li><code>200</code> - Success</li>
                    <li><code>400</code> - Bad Request</li>
                    <li><code>401</code> - Unauthorized</li>
                    <li><code>403</code> - Forbidden</li>
                    <li><code>404</code> - Not Found</li>
                    <li><code>429</code> - Rate Limit Exceeded</li>
                    <li><code>500</code> - Internal Server Error</li>
                </ul>
                
                <h5>Error Response Format:</h5>
                <pre><code>{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "Missing required parameter: name"
  }
}</code></pre>
            </section>
        </div>
    </div>
    
    <!-- Usage Tab -->
    <div class="tab-content" id="usage-tab">
        <div class="usage-container">
            <h2>API Usage Statistics</h2>
            <p>Monitor your API usage and performance metrics.</p>
            
            <div class="usage-stats">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>Total Requests</h3>
                        <p class="stat-value"><?php echo number_format($totalRequests); ?></p>
                        <small>All time</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-content">
                        <h3>Avg Response Time</h3>
                        <p class="stat-value"><?php echo number_format($avgResponseTime, 0); ?>ms</p>
                        <small>Average across all requests</small>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>Success Rate</h3>
                        <p class="stat-value"><?php echo number_format($successRate, 1); ?>%</p>
                        <small><?php echo number_format($successfulRequests); ?> successful requests</small>
                    </div>
                </div>
            </div>
            
            <?php if ($totalRequests === 0): ?>
                <p class="coming-soon-note">Make your first API request to see detailed analytics.</p>
            <?php else: ?>
                <div class="usage-details">
                    <h3>Recent Activity</h3>
                    <p>See the "API Logs" tab for detailed request history.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div id="createKeyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create API Key</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create_api_key">
            
            <div class="form-group">
                <label for="key-name">API Key Name *</label>
                <input type="text" id="key-name" name="name" class="form-control" 
                       placeholder="e.g., My App - Production" required>
                <small>Choose a descriptive name to identify this key</small>
            </div>
            
            <div class="form-group">
                <label for="environment">Environment *</label>
                <select id="environment" name="environment" class="form-control" required>
                    <option value="sandbox">Sandbox (Testing) - Free</option>
                    <option value="live" <?php echo !$hasLiveSubscription ? 'disabled' : ''; ?>>
                        Live (Production) <?php echo !$hasLiveSubscription ? '- Requires $150/month subscription' : ''; ?>
                    </option>
                </select>
                <small>Sandbox keys are for testing only. <?php echo !$hasLiveSubscription ? 'Upgrade to access Live keys.' : 'Use Live keys for production.'; ?></small>
                <?php if (!$hasLiveSubscription): ?>
                <div class="upgrade-notice">
                    <strong>Need Live Access?</strong> <a href="/api/subscribe?plan=live">Subscribe for $150/month</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create API Key</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Webhook Modal -->
<div id="createWebhookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create Webhook</h2>
            <button class="modal-close" onclick="closeWebhookModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="action" value="create_webhook">
            
            <div class="form-group">
                <label for="webhook-url">Webhook URL *</label>
                <input type="url" id="webhook-url" name="url" class="form-control" 
                       placeholder="https://your-domain.com/webhook" required>
                <small>The URL where webhook events will be sent</small>
            </div>
            
            <div class="form-group">
                <label>Subscribe to Events *</label>
                <div class="event-checkboxes">
                    <?php foreach ($availableEvents as $eventKey => $eventLabel): ?>
                        <div class="checkbox-item">
                            <input type="checkbox" id="event-<?php echo $eventKey; ?>" 
                                   name="events[]" value="<?php echo $eventKey; ?>">
                            <label for="event-<?php echo $eventKey; ?>">
                                <strong><?php echo htmlspecialchars($eventKey); ?></strong>
                                <span class="event-desc"><?php echo htmlspecialchars($eventLabel); ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <small>Select the events you want to receive notifications for</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeWebhookModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Webhook</button>
            </div>
        </form>
    </div>
</div>

<style>
.dev-portal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.dev-portal-header {
    text-align: center;
    margin-bottom: 40px;
}

.dev-portal-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.dev-portal-header p {
    color: #6b7280;
    font-size: 18px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #10b981;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
}

.api-secret-display {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 2px solid #f59e0b;
}

.api-secret-display code {
    display: block;
    padding: 10px;
    background: #f3f4f6;
    border-radius: 4px;
    margin: 10px 0;
    font-size: 14px;
    word-break: break-all;
}

/* Subscription Banner Styles */
.subscription-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.subscription-banner.subscription-active {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.subscription-banner.subscription-admin {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.subscription-info h3 {
    margin: 0 0 12px 0;
    font-size: 24px;
    font-weight: 600;
}

.subscription-info p {
    margin: 8px 0;
    opacity: 0.95;
}

.subscription-features {
    list-style: none;
    padding: 0;
    margin: 16px 0;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}

.subscription-features li {
    padding: 4px 0;
}

.btn-upgrade {
    display: inline-block;
    background: white;
    color: #667eea;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 16px;
    transition: transform 0.2s;
}

.btn-upgrade:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.badge-admin, .badge-active {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
}

.link-manage {
    color: white;
    text-decoration: underline;
    opacity: 0.9;
    font-size: 14px;
}

.link-manage:hover {
    opacity: 1;
}

.upgrade-notice {
    margin-top: 8px;
    padding: 8px 12px;
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 4px;
    color: #92400e;
    font-size: 14px;
}

.upgrade-notice a {
    color: #b45309;
    font-weight: 600;
}


.dev-portal-nav {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 30px;
}

.tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    color: #6b7280;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.tab-btn:hover {
    color: #0654ba;
}

.tab-btn.active {
    color: #0654ba;
    border-bottom-color: #0654ba;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-header h2 {
    color: #1f2937;
    font-size: 28px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 30px;
}

.api-keys-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.api-key-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.key-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.key-info h3 {
    color: #1f2937;
    font-size: 18px;
    margin-bottom: 8px;
}

.env-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.env-sandbox {
    background: #fef3c7;
    color: #92400e;
}

.env-live {
    background: #d1fae5;
    color: #065f46;
}

.status-indicator {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-indicator.active {
    background: #d1fae5;
    color: #065f46;
}

.status-indicator.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.key-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.detail-row .label {
    font-weight: 600;
    color: #6b7280;
    min-width: 100px;
}

.api-key-display {
    flex: 1;
    padding: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 13px;
    font-family: monospace;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-copy {
    background: #0654ba;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s ease;
}

.btn-copy:hover {
    background: #044a99;
}

.key-actions {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: #0654ba;
    color: white;
}

.btn-primary:hover {
    background: #044a99;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f3f4f6;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 14px;
}

.btn-danger {
    background: #dc2626;
    color: white;
}

.btn-danger:hover {
    background: #b91c1c;
}

/* Documentation Styles */
.docs-container {
    max-width: 900px;
}

.doc-section {
    margin-bottom: 40px;
}

.doc-section h3 {
    color: #1f2937;
    font-size: 24px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}

.doc-section h4 {
    color: #374151;
    font-size: 18px;
    margin: 20px 0 10px;
}

.doc-section h5 {
    color: #4b5563;
    font-size: 16px;
    margin: 15px 0 10px;
}

.code-block {
    background: #f3f4f6;
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
}

pre {
    background: #1f2937;
    color: #e5e7eb;
    padding: 15px;
    border-radius: 6px;
    overflow-x: auto;
    margin: 15px 0;
}

pre code {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
}

code {
    background: #f3f4f6;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.endpoint {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
}

.method {
    background: #10b981;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 12px;
}

.params-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

.params-table th,
.params-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.params-table th {
    background: #f3f4f6;
    font-weight: 600;
    color: #374151;
}

/* Usage Styles */
.usage-container {
    max-width: 900px;
}

.usage-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 30px 0;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.stat-icon {
    font-size: 48px;
}

.stat-content h3 {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 8px;
}

.stat-value {
    color: #1f2937;
    font-size: 28px;
    font-weight: 700;
}

.coming-soon-note {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    margin-top: 40px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    justify-content: center;
    align-items: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    padding: 30px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.modal-header h2 {
    color: #1f2937;
    font-size: 24px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #6b7280;
    cursor: pointer;
}

.modal-close:hover {
    color: #1f2937;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    color: #374151;
    font-weight: 600;
    margin-bottom: 8px;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group small {
    display: block;
    color: #6b7280;
    font-size: 13px;
    margin-top: 5px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
}

.modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 25px;
}

/* Webhooks Styles */
.webhooks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
}

.webhook-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.webhook-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.webhook-info h3 {
    color: #1f2937;
    font-size: 18px;
    margin-bottom: 8px;
}

.webhook-details {
    margin-bottom: 20px;
}

.webhook-url, .webhook-secret {
    flex: 1;
    padding: 8px;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 13px;
    font-family: monospace;
    word-break: break-all;
}

.event-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.event-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #e0e7ff;
    color: #3730a3;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.webhook-actions {
    display: flex;
    gap: 10px;
}

.event-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 12px;
    margin: 15px 0;
}

.checkbox-item {
    display: flex;
    align-items: start;
    gap: 10px;
}

.checkbox-item input[type="checkbox"] {
    margin-top: 4px;
}

.checkbox-item label {
    display: flex;
    flex-direction: column;
    cursor: pointer;
}

.event-desc {
    font-size: 12px;
    color: #6b7280;
    font-weight: normal;
}

/* API Logs Styles */
.logs-table-container {
    overflow-x: auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.logs-table thead {
    background: #f3f4f6;
}

.logs-table th {
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.logs-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
}

.log-row {
    transition: background 0.2s ease;
}

.log-row:hover {
    background: #f9fafb;
}

.log-row.log-error {
    background: #fef2f2;
}

.log-row.log-error:hover {
    background: #fee2e2;
}

.method-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.method-get {
    background: #dbeafe;
    color: #1e40af;
}

.method-post {
    background: #d1fae5;
    color: #065f46;
}

.method-put {
    background: #fef3c7;
    color: #92400e;
}

.method-delete {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.status-success {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.status-error {
    background: #fee2e2;
    color: #991b1b;
}

.log-count {
    color: #6b7280;
    font-size: 14px;
}

.usage-details {
    margin-top: 40px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.usage-details h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.stat-content small {
    color: #6b7280;
    font-size: 12px;
}

.success-count, .failed-count {
    font-weight: 600;
}

.success-count {
    color: #10b981;
}

.failed-count {
    color: #ef4444;
}

@media (max-width: 768px) {
    .api-keys-grid,
    .webhooks-grid {
        grid-template-columns: 1fr;
    }
    
    .dev-portal-nav {
        flex-wrap: wrap;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .logs-table-container {
        overflow-x: scroll;
    }
    
    .event-checkboxes {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Remove active class from all tabs and buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked button and corresponding content
        btn.classList.add('active');
        const tabId = btn.getAttribute('data-tab') + '-tab';
        document.getElementById(tabId).classList.add('active');
    });
});

// Modal functions
function showCreateKeyModal() {
    document.getElementById('createKeyModal').classList.add('show');
}

function closeModal() {
    document.getElementById('createKeyModal').classList.remove('show');
}

function showCreateWebhookModal() {
    document.getElementById('createWebhookModal').classList.add('show');
}

function closeWebhookModal() {
    document.getElementById('createWebhookModal').classList.remove('show');
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
    });
}

// Close modal when clicking outside
document.getElementById('createKeyModal').addEventListener('click', (e) => {
    if (e.target.id === 'createKeyModal') {
        closeModal();
    }
});

document.getElementById('createWebhookModal').addEventListener('click', (e) => {
    if (e.target.id === 'createWebhookModal') {
        closeWebhookModal();
    }
});
</script>

<?php includeFooter(); ?>
