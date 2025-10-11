<?php
/**
 * Wallet Transfer API
 * Handle peer-to-peer wallet transfers
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/wallet_service.php';

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
    
    // Extract transfer details
    $recipientEmail = trim($input['recipient_email'] ?? '');
    $amount = floatval($input['amount'] ?? 0);
    $note = trim($input['note'] ?? '');
    
    // Validation
    if (empty($recipientEmail)) {
        throw new Exception('Recipient email is required');
    }
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than zero');
    }
    
    if ($amount < 0.01) {
        throw new Exception('Minimum transfer amount is $0.01');
    }
    
    // Find recipient by email
    $recipientStmt = $db->prepare("SELECT id, email FROM users WHERE email = ? AND status = 'active'");
    $recipientStmt->execute([$recipientEmail]);
    $recipient = $recipientStmt->fetch();
    
    if (!$recipient) {
        throw new Exception('Recipient not found or account is not active');
    }
    
    $recipientId = $recipient['id'];
    
    // Cannot transfer to self
    if ($recipientId == $userId) {
        throw new Exception('Cannot transfer funds to yourself');
    }
    
    // Use wallet service for transfer
    $walletService = new WalletService($db);
    $walletService->transfer($userId, $recipientId, $amount, $note);
    
    logSecurityEvent($userId, 'wallet_transfer_sent', 'wallet', $recipientId, [
        'amount' => $amount,
        'recipient_email' => $recipientEmail
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Transfer completed successfully',
        'data' => [
            'amount' => $amount,
            'recipient_email' => $recipientEmail
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
