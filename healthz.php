<?php
/**
 * Health Check Endpoint
 * Returns basic application health status
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/config/config.php';

header('Content-Type: application/json');

// Test database connection using db_ping()
$database_status = db_ping();

if ($database_status) {
    $response = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => defined('APP_VERSION') ? APP_VERSION : '2.0.0',
        'environment' => defined('APP_ENV') ? APP_ENV : 'unknown',
        'checks' => [
            'database' => 'ok'
        ]
    ];
    
    http_response_code(200);
} else {
    $response = [
        'status' => 'unhealthy',
        'timestamp' => date('c'),
        'version' => defined('APP_VERSION') ? APP_VERSION : '2.0.0',
        'environment' => defined('APP_ENV') ? APP_ENV : 'unknown',
        'error' => 'Database connectivity issue',
        'checks' => [
            'database' => 'error'
        ]
    ];
    
    http_response_code(503);
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>