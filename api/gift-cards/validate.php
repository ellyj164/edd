<?php
/**
 * Validate Gift Card API
 * Check if gift card code is valid and return balance
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    $code = $_GET['code'] ?? '';
    
    if (empty($code)) {
        throw new Exception('Gift card code is required');
    }
    
    $db = db();
    
    // Find gift card
    $stmt = $db->prepare("
        SELECT * FROM giftcards 
        WHERE code = ? 
        AND redeemed_by IS NULL
    ");
    $stmt->execute([$code]);
    $giftcard = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$giftcard) {
        throw new Exception('Invalid or already redeemed gift card code');
    }
    
    echo json_encode([
        'valid' => true,
        'giftcard' => [
            'code' => $giftcard['code'],
            'amount' => $giftcard['amount'],
            'currency' => $giftcard['currency']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'valid' => false,
        'error' => $e->getMessage()
    ]);
}
