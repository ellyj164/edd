<?php
/**
 * Wallet Transaction History Page
 * Displays a detailed log of all transactions for a specific user's wallet.
 * @version 1.0.0
 */

// Core application requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/init.php';

$page_title = 'Wallet History';
$error_message = '';
$transactions = [];
$user_info = null;

try {
    requireAdminAuth();
    checkPermission('wallets.view'); // Same permission as the main page
    $pdo = db();

    $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    if (!$user_id) throw new Exception("No user specified.");

    // Get user and wallet info
    $user_stmt = $pdo->prepare("SELECT u.username, u.email, w.id as wallet_id, w.balance FROM users u JOIN wallets w ON u.id = w.user_id WHERE u.id = ?");
    $user_stmt->execute([$user_id]);
    $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user_info) throw new Exception("User or wallet not found.");

    // Get transaction history
    $trans_stmt = $pdo->prepare("SELECT wt.*, a.username as admin_username FROM wallet_transactions wt LEFT JOIN users a ON wt.admin_id = a.id WHERE wt.wallet_id = ? ORDER BY wt.created_at DESC");
    $trans_stmt->execute([$user_info['wallet_id']]);
    $transactions = $trans_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_message = "A critical error occurred: " . $e->getMessage();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Wallet Transaction History</h1>
            <h2 class="h5 text-muted">User: <?php echo htmlspecialchars($user_info['username'] ?? 'N/A'); ?> | Current Balance: $<?php echo number_format($user_info['balance'] ?? 0, 2); ?></h2>
        </div>
        <a href="/admin/wallets/" class="btn btn-outline-primary">&laquo; Back to Wallet Management</a>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance Before</th>
                                <th>Balance After</th>
                                <th>Description</th>
                                <th>Processed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-4">No transactions found for this wallet.</td></tr>
                            <?php else: foreach ($transactions as $tx): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($tx['created_at'])); ?></td>
                                    <td>
                                        <?php $is_credit = $tx['type'] === 'credit'; ?>
                                        <span class="badge <?php echo $is_credit ? 'bg-success' : 'bg-danger'; ?>"><?php echo ucfirst($tx['type']); ?></span>
                                    </td>
                                    <td class="<?php echo $is_credit ? 'text-success' : 'text-danger'; ?> fw-bold">
                                        <?php echo $is_credit ? '+' : '-'; ?>$<?php echo number_format($tx['amount'], 2); ?>
                                    </td>
                                    <td>$<?php echo number_format($tx['balance_before'], 2); ?></td>
                                    <td>$<?php echo number_format($tx['balance_after'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($tx['description']); ?></td>
                                    <td><?php echo htmlspecialchars($tx['admin_username'] ?? 'System'); ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>