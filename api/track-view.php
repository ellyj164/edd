<?php
/**
 * Track Product View API
 * Records user product views for AI recommendations
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        errorResponse('Method not allowed', 405);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
    
    if ($productId <= 0) {
        errorResponse('Invalid product ID');
    }
    
    // Get user info
    $userId = Session::isLoggedIn() ? Session::getUserId() : null;
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    $viewDuration = isset($input['duration']) ? (int)$input['duration'] : 0;
    
    // Verify product exists
    $product = new Product();
    if (!$product->find($productId)) {
        errorResponse('Product not found');
    }
    
    // Insert view record
    $db = db();
    $stmt = $db->prepare("
        INSERT INTO user_product_views 
        (user_id, product_id, session_id, ip_address, user_agent, referrer, view_duration) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $userId,
        $productId,
        $sessionId,
        $ipAddress,
        $userAgent,
        $referrer,
        $viewDuration
    ]);
    
    if ($result) {
        successResponse(['tracked' => true], 'View tracked successfully');
    } else {
        errorResponse('Failed to track view');
    }
    
} catch (Exception $e) {
    Logger::error('Track view error: ' . $e->getMessage());
    // Silent fail - don't disrupt user experience
    successResponse(['tracked' => false], 'View tracking skipped');
}
