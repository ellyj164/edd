<?php
/**
 * Cancel Order API
 * Handle order cancellation (only if pending)
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }
    
    $db = db();
    $userId = Session::getUserId();
    $orderId = (int)($input['order_id'] ?? 0);
    
    if ($orderId <= 0) {
        throw new Exception('Invalid order ID');
    }
    
    // Verify order belongs to user and is pending
    $checkStmt = $db->prepare("SELECT id, status FROM orders WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$orderId, $userId]);
    $order = $checkStmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found or does not belong to you');
    }
    
    if ($order['status'] !== 'pending') {
        throw new Exception('Only pending orders can be cancelled');
    }
    
    // Update order status
    $updateStmt = $db->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$orderId]);
    
    logSecurityEvent($userId, 'order_cancelled', 'order', $orderId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Order cancelled successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
