<?php
/**
 * Delete Address API
 * Handle deleting addresses
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
    $addressId = (int)($input['address_id'] ?? 0);
    
    if ($addressId <= 0) {
        throw new Exception('Invalid address ID');
    }
    
    // Verify address belongs to user
    $checkStmt = $db->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
    $checkStmt->execute([$addressId, $userId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Address not found or does not belong to you');
    }
    
    // Delete address
    $deleteStmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $deleteStmt->execute([$addressId, $userId]);
    
    logSecurityEvent($userId, 'address_deleted', 'address', $addressId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Address deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
