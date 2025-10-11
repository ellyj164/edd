<?php
/**
 * Wallet Transfer API
 * Transfer funds from one user to another
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/wallet_service.php';

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
    
    $recipientEmail = $data['recipient_email'] ?? '';
    $amount = floatval($data['amount'] ?? 0);
    $note = $data['note'] ?? '';
    
    if (empty($recipientEmail)) {
        throw new Exception('Recipient email is required');
    }
    
    if ($amount <= 0) {
        throw new Exception('Invalid amount');
    }
    
    // Find recipient by email
    $user = new User();
    $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
    $stmt->execute([$recipientEmail]);
    $recipient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$recipient) {
        throw new Exception('Recipient not found');
    }
    
    $recipientId = $recipient['id'];
    
    if ($recipientId == $userId) {
        throw new Exception('Cannot transfer to yourself');
    }
    
    // Perform transfer
    $walletService = new WalletService();
    $walletService->transfer($userId, $recipientId, $amount, $note);
    
    // Get updated balance
    $wallet = $walletService->getWallet($userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transfer completed successfully',
        'balance' => $wallet['balance']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
