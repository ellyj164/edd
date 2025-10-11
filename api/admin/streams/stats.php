<?php
/**
 * Admin API - Dashboard Statistics
 * Get real-time statistics for admin streaming dashboard
 */

require_once __DIR__ . '/../../../includes/init.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    $pdo = db();
    
    // Total streams count
    $totalStreamsStmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams");
    $totalStreams = $totalStreamsStmt->fetch()['count'];
    
    // Live streams count
    $liveStreamsStmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams WHERE status = 'live'");
    $liveStreams = $liveStreamsStmt->fetch()['count'];
    
    // Scheduled streams count
    $scheduledStreamsStmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams WHERE status = 'scheduled'");
    $scheduledStreams = $scheduledStreamsStmt->fetch()['count'];
    
    // Completed streams count
    $completedStreamsStmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams WHERE status = 'ended'");
    $completedStreams = $completedStreamsStmt->fetch()['count'];
    
    // Cancelled streams count
    $cancelledStreamsStmt = $pdo->query("SELECT COUNT(*) as count FROM live_streams WHERE status = 'cancelled'");
    $cancelledStreams = $cancelledStreamsStmt->fetch()['count'];
    
    // Current total viewers (all active viewers across all live streams)
    $viewersStmt = $pdo->query("
        SELECT COUNT(DISTINCT sv.id) as count 
        FROM stream_viewers sv
        JOIN live_streams ls ON sv.stream_id = ls.id
        WHERE sv.is_active = 1 AND ls.status = 'live'
    ");
    $totalViewers = $viewersStmt->fetch()['count'];
    
    // Total revenue from all streams
    $revenueStmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM stream_orders");
    $totalRevenue = $revenueStmt->fetch()['total'];
    
    // Average revenue per stream
    $avgRevenue = $totalStreams > 0 ? $totalRevenue / $totalStreams : 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_streams' => (int)$totalStreams,
            'live_streams' => (int)$liveStreams,
            'scheduled_streams' => (int)$scheduledStreams,
            'completed_streams' => (int)$completedStreams,
            'cancelled_streams' => (int)$cancelledStreams,
            'total_viewers' => (int)$totalViewers,
            'total_revenue' => (float)$totalRevenue,
            'avg_revenue' => (float)$avgRevenue
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
