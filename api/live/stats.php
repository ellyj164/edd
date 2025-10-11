<?php
/**
 * Stream Statistics API
 * Real-time stream statistics for sellers
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!Session::isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
    
    $userId = Session::getUserId();
    
    // Check if user is a vendor
    $vendor = new Vendor();
    $vendorInfo = $vendor->findByUserId($userId);
    
    if (!$vendorInfo) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Vendor access required'
        ]);
        exit;
    }
    
    if (!isset($_GET['stream_id'])) {
        throw new Exception('Stream ID is required');
    }
    
    $streamId = (int)$_GET['stream_id'];
    
    // Verify the stream belongs to this vendor
    $liveStream = new LiveStream();
    $stream = $liveStream->getStreamById($streamId);
    
    if (!$stream || $stream['vendor_id'] != $vendorInfo['id']) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied'
        ]);
        exit;
    }
    
    // Get comprehensive statistics
    $stats = $liveStream->getStreamStats($streamId);
    
    // Get active viewers
    $streamViewer = new StreamViewer();
    $activeViewers = $streamViewer->getActiveViewers($streamId);
    
    // Get recent comments
    $streamInteraction = new StreamInteraction();
    $recentComments = $streamInteraction->getStreamComments($streamId, 50);
    
    // Get stream orders
    $streamOrder = new StreamOrder();
    $orders = $streamOrder->getStreamOrders($streamId);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'likes' => (int)($stats['likes_count'] ?? 0),
            'dislikes' => (int)($stats['dislikes_count'] ?? 0),
            'comments' => (int)($stats['comments_count'] ?? 0),
            'viewers' => (int)($stats['total_viewers'] ?? 0),
            'current_viewers' => count($activeViewers),
            'orders' => (int)($stats['orders_count'] ?? 0),
            'revenue' => (float)($stats['total_revenue'] ?? 0.00),
            'duration' => $stream['started_at'] ? 
                (strtotime('now') - strtotime($stream['started_at'])) : 0
        ],
        'viewers' => array_map(function($viewer) {
            return [
                'id' => $viewer['id'],
                'username' => $viewer['username'] ?? 'Guest',
                'joined_at' => $viewer['joined_at']
            ];
        }, $activeViewers),
        'comments' => array_map(function($comment) {
            return [
                'id' => $comment['id'],
                'username' => $comment['username'] ?? 'Guest',
                'text' => $comment['comment_text'],
                'created_at' => $comment['created_at']
            ];
        }, $recentComments),
        'orders' => array_map(function($order) {
            return [
                'id' => $order['id'],
                'product_name' => $order['product_name'],
                'username' => $order['username'] ?? 'Guest',
                'amount' => (float)$order['amount'],
                'status' => $order['order_status'],
                'created_at' => $order['created_at']
            ];
        }, $orders)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
