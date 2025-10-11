<?php
/**
 * Address Management API - Delete Address
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
    
    // Get address details and verify ownership
    $addressStmt = $db->prepare("SELECT id, is_default FROM addresses WHERE id = ? AND user_id = ?");
    $addressStmt->execute([$addressId, $userId]);
    $address = $addressStmt->fetch();
    
    if (!$address) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Address not found']);
        exit;
    }
    
    // Check if this is the only address
    $countStmt = $db->prepare("SELECT COUNT(*) FROM addresses WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $addressCount = $countStmt->fetchColumn();
    
    if ($addressCount <= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot delete your only address']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Delete the address
    $deleteStmt = $db->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $deleteStmt->execute([$addressId, $userId]);
    
    // If this was the default address, make another one default
    if ($address['is_default']) {
        $newDefaultStmt = $db->prepare("
            UPDATE addresses 
            SET is_default = 1 
            WHERE user_id = ? 
            ORDER BY created_at ASC 
            LIMIT 1
        ");
        $newDefaultStmt->execute([$userId]);
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    Logger::error("Delete address error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>