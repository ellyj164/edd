<?php
/**
 * API: Set Default Payment Method
 * Sets a payment method as the default for the user
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Require authentication
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

try {
    $userId = Session::getUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    $paymentMethodId = (int)($input['payment_method_id'] ?? 0);
    
    if (!$paymentMethodId) {
        throw new Exception('Payment method ID is required');
    }
    
    $db = db();
    
    // Verify the payment method belongs to the user
    $stmt = $db->prepare("SELECT id FROM user_payment_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$paymentMethodId, $userId]);
    if (!$stmt->fetch()) {
        throw new Exception('Payment method not found');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Remove default from all payment methods
    $stmt = $db->prepare("UPDATE user_payment_methods SET is_default = 0 WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Set the new default
    $stmt = $db->prepare("UPDATE user_payment_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$paymentMethodId, $userId]);
    
    $db->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
