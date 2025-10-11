<?php
/**
 * Admin Wallet Credit API
 * Allows admins to credit user wallets
 */

require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/wallet_service.php';
require_once __DIR__ . '/../../../includes/email_template.php';
require_once __DIR__ . '/../../../includes/currency_service.php';
require_once __DIR__ . '/../../../includes/rbac.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require admin authentication
requireAdminAuth();
checkPermission('wallets.manage');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $targetUserId = (int)($data['user_id'] ?? 0);
    $amount = floatval($data['amount'] ?? 0);
    $description = trim($data['description'] ?? 'Admin credit');
    
    if ($targetUserId <= 0) {
        throw new Exception('Invalid user ID');
    }
    
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }
    
    // Verify user exists
    $user = new User();
    $userData = $user->find($targetUserId);
    if (!$userData) {
        throw new Exception('User not found');
    }
    
    // Credit wallet
    $walletService = new WalletService();
    $newBalance = $walletService->credit(
        $targetUserId,
        $amount,
        'admin_credit',
        $description,
        ['admin_id' => Session::getUserId()]
    );
    
    // Send notification email
    $userName = $userData['username'] ?? $userData['first_name'] ?? 'there';
    send_template_email(
        $userData['email'],
        'Wallet Credited',
        'wallet_notification_template',
        [
            'userName' => $userName,
            'type' => 'credit',
            'amount' => $amount,
            'currency' => 'USD',
            'description' => $description,
            'reference' => 'admin_credit',
            'newBalance' => $newBalance
        ]
    );
    
    // Log audit event
    logAuditEvent('wallet', $targetUserId, 'credit', [
        'amount' => $amount,
        'description' => $description,
        'new_balance' => $newBalance
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Wallet credited successfully',
        'new_balance' => $newBalance
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
