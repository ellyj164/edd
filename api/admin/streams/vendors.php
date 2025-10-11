<?php
/**
 * Admin API - Get Vendors List
 * Fetch all vendors for dropdowns and filters
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
    
    $stmt = $pdo->query("
        SELECT v.id, v.business_name, v.email, v.phone, 
               COUNT(DISTINCT ls.id) as total_streams,
               COUNT(DISTINCT CASE WHEN ls.status = 'live' THEN ls.id END) as live_streams
        FROM vendors v
        LEFT JOIN live_streams ls ON v.id = ls.vendor_id
        GROUP BY v.id
        ORDER BY v.business_name
    ");
    
    $vendors = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'vendors' => $vendors,
        'count' => count($vendors)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
