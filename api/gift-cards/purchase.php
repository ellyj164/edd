<?php
/**
 * Gift Card Purchase API
 * Handles gift card creation and payment initiation
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

try {
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }
    
    // Validate required fields
    $requiredFields = ['card_type', 'amount', 'design', 'recipient_name', 'recipient_email', 'sender_name', 'sender_email'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate amount
    $amount = (float)$data['amount'];
    if ($amount < 5 || $amount > 1000) {
        throw new Exception('Invalid amount. Must be between $5 and $1000.');
    }
    
    // Validate email addresses
    if (!filter_var($data['recipient_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid recipient email address');
    }
    
    if (!filter_var($data['sender_email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid sender email address');
    }
    
    // Generate unique gift card code
    $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 16));
    
    // Get database connection
    $db = db();
    
    // Create gift card record
    $stmt = $db->prepare("
        INSERT INTO gift_cards (
            code, amount, balance, card_type, design, 
            recipient_name, recipient_email, sender_name, sender_email, 
            personal_message, status, created_at, expires_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR))
    ");
    
    $stmt->execute([
        $code,
        $amount,
        $amount, // Initial balance equals amount
        $data['card_type'],
        $data['design'],
        $data['recipient_name'],
        $data['recipient_email'],
        $data['sender_name'],
        $data['sender_email'],
        $data['personal_message'] ?? ''
    ]);
    
    $giftCardId = $db->lastInsertId();
    
    // Create order for gift card purchase
    $userId = Session::isLoggedIn() ? Session::getUserId() : null;
    
    $orderStmt = $db->prepare("
        INSERT INTO orders (
            user_id, order_number, status, subtotal, tax, total_amount, 
            payment_method, shipping_address, created_at
        ) VALUES (?, ?, 'pending', ?, 0, ?, 'pending', '', NOW())
    ");
    
    $orderNumber = 'GC' . date('YmdHis') . rand(1000, 9999);
    $orderStmt->execute([
        $userId,
        $orderNumber,
        $amount,
        $amount
    ]);
    
    $orderId = $db->lastInsertId();
    
    // Link gift card to order
    $linkStmt = $db->prepare("
        UPDATE gift_cards SET order_id = ? WHERE id = ?
    ");
    $linkStmt->execute([$orderId, $giftCardId]);
    
    // Return success with payment URL
    echo json_encode([
        'success' => true,
        'gift_card_id' => $giftCardId,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'amount' => $amount,
        'payment_url' => '/checkout.php?order_id=' . $orderId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
