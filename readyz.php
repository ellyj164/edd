<?php
/**
 * Readiness Check Endpoint
 * Returns detailed application readiness status including migrations
 */

require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/json');

try {
    $checks = performHealthCheck();
    
    // Check for required database tables
    $db = Database::getInstance()->getConnection();
    $requiredTables = [
        'users', 'login_attempts', 'user_sessions', 'email_tokens', 
        'email_verification_tokens', 'password_reset_tokens', 'user_totp_secrets',
        'email_logs', 'profiles', 'addresses', 'vendors', 'categories', 
        'products', 'orders', 'order_items', 'carts', 'cart_items',
        'reviews', 'wishlists', 'settings'
    ];
    
    $stmt = $db->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    if (empty($missingTables)) {
        $checks['migrations'] = ['status' => 'ok', 'message' => 'All required tables present'];
    } else {
        $checks['migrations'] = ['status' => 'error', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
    }
    
    // Check admin user exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    $checks['admin_user'] = [
        'status' => $adminCount > 0 ? 'ok' : 'warning',
        'message' => $adminCount > 0 ? 'Admin user exists' : 'No admin user found'
    ];
    
    // Determine overall status
    $hasError = false;
    foreach ($checks as $check) {
        if ($check['status'] === 'error') {
            $hasError = true;
            break;
        }
    }
    
    $response = [
        'status' => $hasError ? 'not_ready' : 'ready',
        'timestamp' => date('c'),
        'version' => APP_VERSION,
        'environment' => APP_ENV,
        'checks' => $checks
    ];
    
    http_response_code($hasError ? 503 : 200);
    
} catch (Exception $e) {
    $response = [
        'status' => 'not_ready',
        'timestamp' => date('c'),
        'version' => APP_VERSION,
        'environment' => APP_ENV,
        'error' => DEBUG_MODE ? $e->getMessage() : 'Application not ready',
        'checks' => [
            'application' => ['status' => 'error', 'message' => 'Startup error']
        ]
    ];
    
    http_response_code(503);
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>