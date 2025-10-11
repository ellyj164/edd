<?php
/**
 * Order Tracking API
 * Retrieve order status and tracking information
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Session::isLoggedIn()) {
    errorResponse('Please login to track orders', 401);
}

$userId = Session::getUserId();
$orderId = sanitizeInput($_GET['order_id'] ?? '');

if (empty($orderId)) {
    errorResponse('Order ID is required');
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get order details - ensure it belongs to the user
    $stmt = $db->prepare("
        SELECT * FROM orders 
        WHERE (id = ? OR tracking_number = ?) AND user_id = ?
    ");
    $stmt->execute([$orderId, $orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        errorResponse('Order not found');
    }
    
    // Get tracking updates
    $stmt = $db->prepare("
        SELECT * FROM order_tracking_updates 
        WHERE order_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$order['id']]);
    $trackingUpdates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $order['tracking_updates'] = $trackingUpdates;
    
    successResponse([
        'order' => $order
    ]);
    
} catch (Exception $e) {
    Logger::error('Order tracking error: ' . $e->getMessage());
    errorResponse('An error occurred while retrieving tracking information', 500);
}
