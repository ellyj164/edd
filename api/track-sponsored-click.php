<?php
/**
 * Track Sponsored Product Clicks
 * Simple endpoint to track when users click on sponsored products
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid product ID']);
        exit;
    }
    
    $productId = (int)$data['product_id'];
    $db = db();
    
    // Update clicks count for the sponsored product
    $stmt = $db->prepare("
        UPDATE sponsored_products 
        SET clicks = clicks + 1 
        WHERE product_id = ? 
        AND status = 'active'
        AND start_date <= NOW()
        AND (end_date IS NULL OR end_date >= NOW())
    ");
    $stmt->execute([$productId]);
    
    // Log the click for analytics (optional)
    $stmt = $db->prepare("
        INSERT INTO sponsored_product_analytics 
        (product_id, event_type, user_id, session_id, ip_address, created_at) 
        VALUES (?, 'click', ?, ?, ?, NOW())
    ");
    
    $userId = Session::isLoggedIn() ? Session::getUserId() : null;
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // This will fail silently if the analytics table doesn't exist yet
    try {
        $stmt->execute([$productId, $userId, $sessionId, $ipAddress]);
    } catch (Exception $e) {
        // Analytics table might not exist, that's okay
        error_log("Sponsored click analytics log failed: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Track sponsored click error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
