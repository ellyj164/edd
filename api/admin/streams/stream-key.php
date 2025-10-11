<?php
/**
 * Admin API - Generate Stream Key
 * Generate unique secure stream keys for vendors
 */

require_once __DIR__ . '/../../../includes/init.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['vendor_id'])) {
        throw new Exception('Vendor ID is required');
    }
    
    $vendorId = (int)$data['vendor_id'];
    $pdo = db();
    
    // Verify vendor exists
    $vendorStmt = $pdo->prepare("SELECT id, business_name FROM vendors WHERE id = ?");
    $vendorStmt->execute([$vendorId]);
    $vendor = $vendorStmt->fetch();
    
    if (!$vendor) {
        throw new Exception('Vendor not found');
    }
    
    // Generate unique stream key
    $streamKey = 'sk_' . bin2hex(random_bytes(20));
    
    // Check if vendor already has a stream key, update or insert
    $checkStmt = $pdo->prepare("
        SELECT id FROM vendor_settings 
        WHERE vendor_id = ? AND setting_key = 'stream_key'
    ");
    $checkStmt->execute([$vendorId]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Update existing stream key
        $stmt = $pdo->prepare("
            UPDATE vendor_settings 
            SET setting_value = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE vendor_id = ? AND setting_key = 'stream_key'
        ");
        $stmt->execute([$streamKey, $vendorId]);
    } else {
        // Insert new stream key
        $stmt = $pdo->prepare("
            INSERT INTO vendor_settings (vendor_id, setting_key, setting_value) 
            VALUES (?, 'stream_key', ?)
        ");
        $stmt->execute([$vendorId, $streamKey]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Stream key generated successfully',
        'stream_key' => $streamKey,
        'vendor_name' => $vendor['business_name']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
