<?php
/**
 * Generate Download Token API
 * Creates a secure download token for digital products
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/digital_download_service.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    $userId = Session::getUserId();
    $productId = (int)($_GET['product_id'] ?? 0);
    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
    
    if ($productId <= 0) {
        throw new Exception('Invalid product ID');
    }
    
    $downloadService = new DigitalDownloadService();
    
    // Generate token
    $token = $downloadService->generateDownloadToken($userId, $productId, $orderId);
    
    // Build download URL
    $downloadUrl = env('APP_URL', 'https://fezamarket.com') . '/download.php?token=' . $token;
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'download_url' => $downloadUrl,
        'expires_in' => 3600 // seconds
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
