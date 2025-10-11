<?php
/**
 * Gift Card Redemption API
 * Redeem gift card to user wallet
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/wallet_service.php';
require_once __DIR__ . '/../../includes/email_template.php';
require_once __DIR__ . '/../../includes/currency_service.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    $userId = Session::getUserId();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $code = trim($data['code'] ?? '');
    
    if (empty($code)) {
        throw new Exception('Gift card code is required');
    }
    
    $db = db();
    
    // Find unredeemed gift card
    $stmt = $db->prepare("
        SELECT * FROM giftcards 
        WHERE code = ? AND redeemed_by IS NULL
        FOR UPDATE
    ");
    $stmt->execute([$code]);
    $giftCard = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$giftCard) {
        throw new Exception('Invalid or already redeemed gift card');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Mark as redeemed
        $stmt = $db->prepare("
            UPDATE giftcards 
            SET redeemed_by = ?, redeemed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId, $giftCard['id']]);
        
        // Credit wallet
        $walletService = new WalletService();
        $newBalance = $walletService->credit(
            $userId,
            $giftCard['amount'],
            "giftcard_{$giftCard['code']}",
            "Gift card redemption",
            ['gift_card_id' => $giftCard['id']]
        );
        
        $db->commit();
        
        // Send confirmation email
        $user = new User();
        $userData = $user->find($userId);
        $userName = $userData['username'] ?? $userData['first_name'] ?? 'there';
        
        $currencyService = new CurrencyService();
        send_template_email(
            $userData['email'],
            'Gift Card Redeemed Successfully',
            'wallet_notification_template',
            [
                'userName' => $userName,
                'type' => 'credit',
                'amount' => $giftCard['amount'],
                'currency' => $giftCard['currency'],
                'description' => 'Gift card redemption',
                'reference' => $giftCard['code'],
                'newBalance' => $newBalance
            ]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Gift card redeemed successfully',
            'amount' => $giftCard['amount'],
            'currency' => $giftCard['currency'],
            'new_balance' => $newBalance
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
