<?php
/**
 * Admin API - List Streams with Filtering
 * Fetch and filter live streams for admin dashboard
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
    $liveStream = new LiveStream();
    $pdo = db();
    
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $vendorId = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
    $date = $_GET['date'] ?? '';
    
    // Build query
    $sql = "
        SELECT ls.*, v.business_name as vendor_name, v.id as vendor_id,
               COUNT(DISTINCT sv.id) as current_viewers
        FROM live_streams ls
        JOIN vendors v ON ls.vendor_id = v.id
        LEFT JOIN stream_viewers sv ON ls.id = sv.stream_id AND sv.is_active = 1
        WHERE 1=1
    ";
    
    $params = [];
    
    // Apply filters
    if (!empty($search)) {
        $sql .= " AND (ls.title LIKE ? OR v.business_name LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($status)) {
        $sql .= " AND ls.status = ?";
        $params[] = $status;
    }
    
    if ($vendorId) {
        $sql .= " AND ls.vendor_id = ?";
        $params[] = $vendorId;
    }
    
    if (!empty($date)) {
        $sql .= " AND DATE(ls.scheduled_at) = ?";
        $params[] = $date;
    }
    
    $sql .= " GROUP BY ls.id ORDER BY 
        CASE ls.status 
            WHEN 'live' THEN 1 
            WHEN 'scheduled' THEN 2 
            WHEN 'ended' THEN 3 
            WHEN 'cancelled' THEN 4 
        END,
        ls.scheduled_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $streams = $stmt->fetchAll();
    
    // Get revenue for each stream
    foreach ($streams as &$stream) {
        $revenueStmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as revenue
            FROM stream_orders
            WHERE stream_id = ?
        ");
        $revenueStmt->execute([$stream['id']]);
        $revenueResult = $revenueStmt->fetch();
        $stream['revenue'] = $revenueResult['revenue'];
    }
    
    echo json_encode([
        'success' => true,
        'streams' => $streams,
        'count' => count($streams)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
