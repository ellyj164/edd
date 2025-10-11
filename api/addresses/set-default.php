<?php
/**
 * Address Management API - Set Default Address
 * E-Commerce Platform
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize database connection
$db = db();

// Set content type
header('Content-Type: application/json');

// Require user login
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['address_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Address ID is required']);
        exit;
    }
    
    $addressId = (int)$input['address_id'];
    $userId = Session::getUserId();
    
    // Verify address belongs to user
    $checkStmt = $db->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$addressId, $userId]);
    
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Address not found']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Remove default from all user addresses
    $removeDefaultStmt = $db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
    $removeDefaultStmt->execute([$userId]);
    
    // Set new default address
    $setDefaultStmt = $db->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
    $setDefaultStmt->execute([$addressId, $userId]);
    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Default address updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    Logger::error("Set default address error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>