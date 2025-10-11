<?php
/**
 * Validate Coupon API
 * Check if coupon code is valid and return discount details
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
        throw new Exception('Coupon code is required');
    }
    
    $db = db();
    
    // Find active coupon
    $stmt = $db->prepare("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND status = 'active'
        AND (valid_from IS NULL OR valid_from <= NOW())
        AND (valid_to IS NULL OR valid_to >= NOW())
    ");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        throw new Exception('Invalid or expired coupon code');
    }
    
    // Check usage limit
    if ($coupon['usage_limit'] > 0) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as usage_count 
            FROM coupon_redemptions 
            WHERE coupon_id = ?
        ");
        $stmt->execute([$coupon['id']]);
        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usage['usage_count'] >= $coupon['usage_limit']) {
            throw new Exception('Coupon usage limit reached');
        }
    }
    
    // Check per-user limit
    if ($coupon['user_usage_limit'] > 0) {
        $userId = Session::getUserId();
        $stmt = $db->prepare("
            SELECT COUNT(*) as user_usage 
            FROM coupon_redemptions 
            WHERE coupon_id = ? AND user_id = ?
        ");
        $stmt->execute([$coupon['id'], $userId]);
        $userUsage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($userUsage['user_usage'] >= $coupon['user_usage_limit']) {
            throw new Exception('You have already used this coupon');
        }
    }
    
    echo json_encode([
        'valid' => true,
        'coupon' => [
            'id' => $coupon['id'],
            'code' => $coupon['code'],
            'type' => $coupon['type'], // percentage or fixed
            'value' => $coupon['value'],
            'description' => $coupon['description'],
            'minimum_amount' => $coupon['minimum_amount'],
            'maximum_discount' => $coupon['maximum_discount']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'valid' => false,
        'error' => $e->getMessage()
    ]);
}
