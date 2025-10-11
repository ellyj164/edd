<?php
/**
 * Buyer Wallet & Payment Methods
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
Session::requireLogin();

$db = db();
$userId = Session::getUserId();

// Get or create buyer record
$buyerQuery = "SELECT * FROM buyers WHERE user_id = ?";
$buyerStmt = $db->prepare($buyerQuery);
$buyerStmt->execute([$userId]);
$buyer = $buyerStmt->fetch();

if (!$buyer) {
    $createBuyerQuery = "INSERT INTO buyers (user_id) VALUES (?)";
    $createBuyerStmt = $db->prepare($createBuyerQuery);
    $createBuyerStmt->execute([$userId]);
    $buyerId = $db->lastInsertId();
    
    $buyerStmt->execute([$userId]);
    $buyer = $buyerStmt->fetch();
} else {
    $buyerId = $buyer['id'];
}

// Get or create wallet
$walletQuery = "SELECT * FROM buyer_wallets WHERE buyer_id = ? AND currency = 'USD'";
$walletStmt = $db->prepare($walletQuery);
$walletStmt->execute([$buyerId]);
$wallet = $walletStmt->fetch();

if (!$wallet) {
    $createWalletQuery = "INSERT INTO buyer_wallets (buyer_id, currency) VALUES (?, 'USD')";
    $createWalletStmt = $db->prepare($createWalletQuery);
    $createWalletStmt->execute([$buyerId]);
    
    $walletStmt->execute([$buyerId]);
    $wallet = $walletStmt->fetch();
}

// Get wallet transactions
$transactionsQuery = "
    SELECT * FROM buyer_wallet_entries 
    WHERE wallet_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
";
$transactionsStmt = $db->prepare($transactionsQuery);
$transactionsStmt->execute([$wallet['id']]);
$transactions = $transactionsStmt->fetchAll();

// Get payment methods (graceful fallback for missing table)
$paymentMethods = [];
try {
    $paymentQuery = "SELECT * FROM buyer_payment_methods WHERE buyer_id = ? ORDER BY is_default DESC, created_at DESC";
    $paymentStmt = $db->prepare($paymentQuery);
    $paymentStmt->execute([$buyerId]);
    $paymentMethods = $paymentStmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist yet, show demo data
    $paymentMethods = [];
}

$page_title = 'Payments & Wallet';
includeHeader($page_title);
?>

<div class="buyer-dashboard">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Payments & Wallet</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fas fa-plus"></i> Add Payment Method
                        </button>
                    </div>
                </div>

                <div class="row">
                    <!-- Wallet Overview -->
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Wallet Balance</h6>
                            </div>
                            <div class="card-body text-center">
                                <div class="wallet-balance">
                                    <div class="balance-amount">
                                        $<?php echo number_format($wallet['balance'], 2); ?>
                                    </div>
                                    <div class="balance-currency text-muted">USD</div>
                                </div>
                                
                                <div class="wallet-actions mt-3">
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFundsModal">
                                        <i class="fas fa-plus"></i> Add Funds
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-exchange-alt"></i> Transfer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">This Month</h6>
                            </div>
                            <div class="card-body">
                                <div class="stat-item mb-3">
                                    <div class="stat-value">$0.00</div>
                                    <div class="stat-label text-muted">Spent</div>
                                </div>
                                <div class="stat-item mb-3">
                                    <div class="stat-value">$0.00</div>
                                    <div class="stat-label text-muted">Cashback Earned</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">0</div>
                                    <div class="stat-label text-muted">Transactions</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods & Transactions -->
                    <div class="col-lg-8">
                        <!-- Payment Methods -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Payment Methods</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($paymentMethods)): ?>
                                    <div class="row">
                                        <?php foreach ($paymentMethods as $method): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="payment-method-card">
                                                    <div class="method-info">
                                                        <div class="method-type">
                                                            <i class="fas fa-credit-card"></i>
                                                            <?php echo ucfirst($method['type']); ?>
                                                        </div>
                                                        <div class="method-details">
                                                            •••• •••• •••• <?php echo $method['last_four']; ?>
                                                        </div>
                                                        <?php if ($method['is_default']): ?>
                                                            <span class="badge badge-primary">Default</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="method-actions">
                                                        <button class="btn btn-sm btn-outline-secondary">Edit</button>
                                                        <button class="btn btn-sm btn-outline-danger">Remove</button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-credit-card fa-3x text-gray-300 mb-3"></i>
                                        <h5>No Payment Methods</h5>
                                        <p class="text-muted">Add a payment method to make purchases easier.</p>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                                            <i class="fas fa-plus"></i> Add Payment Method
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Transaction History -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Transaction History</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($transactions)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Amount</th>
                                                    <th>Balance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($transactions as $transaction): ?>
                                                    <tr>
                                                        <td><?php echo date('M j, Y g:i A', strtotime($transaction['created_at'])); ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo getTransactionBadgeClass($transaction['transaction_type']); ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $transaction['transaction_type'])); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                                        <td class="<?php echo $transaction['amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                            <?php echo ($transaction['amount'] > 0 ? '+' : '') . '$' . number_format($transaction['amount'], 2); ?>
                                                        </td>
                                                        <td>$<?php echo number_format($transaction['balance_after'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-receipt fa-3x text-gray-300 mb-3"></i>
                                        <h5>No Transactions Yet</h5>
                                        <p class="text-muted">Your transaction history will appear here.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Method Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="payment_type" class="form-label">Payment Type</label>
                        <select class="form-select" id="payment_type" name="payment_type">
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                    
                    <div id="card_fields">
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number</label>
                            <input type="text" class="form-control" id="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="exp_date" class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" id="exp_date" placeholder="MM/YY">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" placeholder="123">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cardholder_name" class="form-label">Cardholder Name</label>
                            <input type="text" class="form-control" id="cardholder_name">
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="make_default">
                        <label class="form-check-label" for="make_default">
                            Make this my default payment method
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Add Payment Method</button>
            </div>
        </div>
    </div>
</div>

<style>
.buyer-dashboard {
    background-color: #f8f9fc;
    min-height: 100vh;
}

.main-content {
    padding: 0 1.5rem;
}

.wallet-balance {
    padding: 1rem 0;
}

.balance-amount {
    font-size: 2.5rem;
    font-weight: bold;
    color: #1cc88a;
}

.balance-currency {
    font-size: 0.9rem;
    margin-top: -0.5rem;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #5a5c69;
}

.stat-label {
    font-size: 0.8rem;
}

.payment-method-card {
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    background: white;
}

.method-info {
    margin-bottom: 0.5rem;
}

.method-type {
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.method-details {
    color: #858796;
    font-size: 0.9rem;
}

.method-actions {
    text-align: right;
}
</style>

<?php
function getTransactionBadgeClass($type) {
    switch ($type) {
        case 'credit': return 'success';
        case 'debit': return 'danger';
        case 'refund': return 'info';
        case 'cashback': return 'warning';
        case 'adjustment': return 'secondary';
        default: return 'secondary';
    }
}

includeFooter();
?>