<?php
/**
 * Admin API - Export Stream Data
 * Export stream data to CSV format
 */

require_once __DIR__ . '/../../../includes/init.php';

// Check admin authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    $pdo = db();
    
    // Get streams with full details
    $stmt = $pdo->query("
        SELECT 
            ls.id,
            ls.title,
            v.business_name as vendor_name,
            ls.status,
            ls.viewer_count,
            ls.max_viewers,
            ls.scheduled_at,
            ls.started_at,
            ls.ended_at,
            COALESCE(SUM(so.amount), 0) as revenue,
            COUNT(DISTINCT so.id) as orders_count,
            COUNT(DISTINCT CASE WHEN si.interaction_type = 'like' THEN si.id END) as likes,
            COUNT(DISTINCT CASE WHEN si.interaction_type = 'comment' THEN si.id END) as comments
        FROM live_streams ls
        JOIN vendors v ON ls.vendor_id = v.id
        LEFT JOIN stream_orders so ON ls.id = so.stream_id
        LEFT JOIN stream_interactions si ON ls.id = si.stream_id
        GROUP BY ls.id
        ORDER BY ls.scheduled_at DESC
    ");
    
    $streams = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=streams_export_' . date('Y-m-d_His') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
    fputcsv($output, [
        'Stream ID',
        'Title',
        'Vendor',
        'Status',
        'Current Viewers',
        'Max Viewers',
        'Revenue',
        'Orders',
        'Likes',
        'Comments',
        'Scheduled At',
        'Started At',
        'Ended At'
    ]);
    
    // Write data rows
    foreach ($streams as $stream) {
        fputcsv($output, [
            $stream['id'],
            $stream['title'],
            $stream['vendor_name'],
            $stream['status'],
            $stream['viewer_count'],
            $stream['max_viewers'],
            number_format($stream['revenue'], 2),
            $stream['orders_count'],
            $stream['likes'],
            $stream['comments'],
            $stream['scheduled_at'],
            $stream['started_at'] ?? 'N/A',
            $stream['ended_at'] ?? 'N/A'
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
