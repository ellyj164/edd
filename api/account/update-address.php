<?php
/**
 * Update Address API
 * Handle editing existing addresses
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
    
    // Validate and sanitize inputs
    $addressType = $input['address_type'] ?? 'both';
    $fullName = trim($input['full_name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $addressLine1 = trim($input['address_line1'] ?? '');
    $addressLine2 = trim($input['address_line2'] ?? '');
    $city = trim($input['city'] ?? '');
    $state = trim($input['state'] ?? '');
    $postalCode = trim($input['postal_code'] ?? '');
    $country = trim($input['country'] ?? 'US');
    $isDefault = isset($input['is_default']) && $input['is_default'] ? 1 : 0;
    
    if (empty($fullName) || empty($addressLine1) || empty($city) || empty($state) || empty($postalCode)) {
        throw new Exception('Full name, address line 1, city, state, and postal code are required');
    }
    
    $db->beginTransaction();
    
    try {
        if ($isDefault) {
            $removeDefaultStmt = $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $removeDefaultStmt->execute([$userId]);
        }
        
        $updateStmt = $db->prepare("
            UPDATE user_addresses 
            SET address_type = ?, full_name = ?, phone = ?, address_line1 = ?, address_line2 = ?,
                city = ?, state = ?, postal_code = ?, country = ?, is_default = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $updateStmt->execute([
            $addressType, $fullName, $phone, $addressLine1, $addressLine2,
            $city, $state, $postalCode, $country, $isDefault, $addressId, $userId
        ]);
        
        $db->commit();
        
        logSecurityEvent($userId, 'address_updated', 'address', $addressId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Address updated successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
