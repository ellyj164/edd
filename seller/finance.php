<?php
/**
 * Seller Finance and Payouts Management
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

// Require vendor login
Session::requireLogin();

$vendor = new Vendor();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Handle payout request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'request_payout':
                $amount = (float)$_POST['amount'];
                $method = $_POST['payout_method'];
                $details = $_POST['payout_details'] ?? [];
                
                // Get wallet info
                $walletQuery = "SELECT * FROM seller_wallets WHERE vendor_id = ?";
                $walletStmt = $db->prepare($walletQuery);
                $walletStmt->execute([$vendorId]);
                $wallet = $walletStmt->fetch();
                
                if (!$wallet || $wallet['balance'] < $amount) {
                    throw new Exception('Insufficient balance for payout request.');
                }
                
                if ($amount < 10) {
                    throw new Exception('Minimum payout amount is $10.00');
                }
                
                // Calculate fees
                $processingFee = $amount * 0.02; // 2% processing fee
                $finalAmount = $amount - $processingFee;
                
                // Generate reference number
                $referenceNumber = 'PO' . date('Ymd') . $vendorId . rand(1000, 9999);
                
                // Create payout request
                $payoutQuery = "
                    INSERT INTO seller_payouts (
                        vendor_id, request_amount, processing_fee, final_amount, 
                        payout_method, payout_details, reference_number, status, requested_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'requested', NOW())
                ";
                $payoutStmt = $db->prepare($payoutQuery);
                $payoutStmt->execute([
                    $vendorId, $amount, $processingFee, $finalAmount,
                    $method, json_encode($details), $referenceNumber
                ]);
                
                // Update wallet pending balance
                $updateWalletQuery = "
                    UPDATE seller_wallets 
                    SET balance = balance - ?, pending_balance = pending_balance + ? 
                    WHERE vendor_id = ?
                ";
                $updateWalletStmt = $db->prepare($updateWalletQuery);
                $updateWalletStmt->execute([$amount, $amount, $vendorId]);
                
                Session::setFlash('success', "Payout request submitted successfully. Reference: {$referenceNumber}");
                break;
                
            case 'update_payment_info':
                $paymentDetails = [
                    'bank_name' => $_POST['bank_name'] ?? '',
                    'account_holder' => $_POST['account_holder'] ?? '',
                    'account_number' => $_POST['account_number'] ?? '',
                    'routing_number' => $_POST['routing_number'] ?? '',
                    'swift_code' => $_POST['swift_code'] ?? '',
                    'paypal_email' => $_POST['paypal_email'] ?? '',
                    'preferred_method' => $_POST['preferred_method'] ?? 'bank_transfer'
                ];
                
                $updateQuery = "
                    UPDATE seller_wallets 
                    SET payment_details = ? 
                    WHERE vendor_id = ?
                ";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([json_encode($paymentDetails), $vendorId]);
                
                Session::setFlash('success', 'Payment information updated successfully.');
                break;
        }
        
    } catch (Exception $e) {
        Session::setFlash('error', 'Error: ' . $e->getMessage());
    }
    
    redirect('/seller/finance.php');
}

// Get wallet information
$walletQuery = "SELECT * FROM seller_wallets WHERE vendor_id = ?";
$walletStmt = $db->prepare($walletQuery);
$walletStmt->execute([$vendorId]);
$wallet = $walletStmt->fetch();

// Create wallet if doesn't exist
if (!$wallet) {
    $createWalletQuery = "INSERT INTO seller_wallets (vendor_id) VALUES (?)";
    $createWalletStmt = $db->prepare($createWalletQuery);
    $createWalletStmt->execute([$vendorId]);
    
    $walletStmt->execute([$vendorId]);
    $wallet = $walletStmt->fetch();
}

// Get recent commissions
$commissionsQuery = "
    SELECT sc.*, o.order_number, p.name as product_name
    FROM seller_commissions sc
    JOIN orders o ON sc.order_id = o.id
    LEFT JOIN products p ON sc.product_id = p.id
    WHERE sc.vendor_id = ?
    ORDER BY sc.created_at DESC
    LIMIT 20
";
$commissionsStmt = $db->prepare($commissionsQuery);
$commissionsStmt->execute([$vendorId]);
$commissions = $commissionsStmt->fetchAll();

// Get payout history
$payoutsQuery = "
    SELECT * FROM seller_payouts 
    WHERE vendor_id = ? 
    ORDER BY requested_at DESC 
    LIMIT 10
";
$payoutsStmt = $db->prepare($payoutsQuery);
$payoutsStmt->execute([$vendorId]);
$payouts = $payoutsStmt->fetchAll();

// Get financial statistics
$statsQuery = "
    SELECT 
        SUM(CASE WHEN status IN ('approved', 'paid') THEN commission_amount ELSE 0 END) as total_earned,
        SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_earnings,
        COUNT(*) as total_transactions,
        AVG(commission_amount) as avg_commission
    FROM seller_commissions 
    WHERE vendor_id = ?
";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$vendorId]);
$stats = $statsStmt->fetch();

// Get monthly earnings for chart
$monthlyQuery = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(commission_amount) as earnings,
        COUNT(*) as transactions
    FROM seller_commissions 
    WHERE vendor_id = ? AND status IN ('approved', 'paid')
    AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$monthlyStmt = $db->prepare($monthlyQuery);
$monthlyStmt->execute([$vendorId]);
$monthlyEarnings = $monthlyStmt->fetchAll();

$page_title = 'Finance & Payouts - Seller Center';
includeHeader($page_title);
?>

<div class="seller-finance-page">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <nav class="breadcrumb">
                    <a href="/seller/dashboard.php">Dashboard</a>
                    <span>/</span>
                    <span>Finance</span>
                </nav>
                <h1>Finance & Payouts</h1>
                <p class="subtitle">Manage your earnings and payout settings</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="downloadStatement()">
                    📄 Download Statement
                </button>
                <button class="btn btn-primary" onclick="showPayoutModal()" <?php echo $wallet['balance'] < 10 ? 'disabled' : ''; ?>>
                    💸 Request Payout
                </button>
            </div>
        </div>
    </div>

    <!-- Wallet Overview -->
    <div class="wallet-section">
        <div class="wallet-cards">
            <!-- Available Balance -->
            <div class="wallet-card primary">
                <div class="wallet-icon">💰</div>
                <div class="wallet-content">
                    <h3>Available Balance</h3>
                    <div class="wallet-amount">$<?php echo number_format($wallet['balance'], 2); ?></div>
                    <div class="wallet-meta">
                        <?php if ($wallet['pending_balance'] > 0): ?>
                            <span class="pending">$<?php echo number_format($wallet['pending_balance'], 2); ?> pending</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Total Earned -->
            <div class="wallet-card">
                <div class="wallet-icon">📈</div>
                <div class="wallet-content">
                    <h3>Total Earned</h3>
                    <div class="wallet-amount">$<?php echo number_format($wallet['total_earned'], 2); ?></div>
                    <div class="wallet-meta">
                        <span><?php echo $stats['total_transactions']; ?> transactions</span>
                    </div>
                </div>
            </div>

            <!-- Total Withdrawn -->
            <div class="wallet-card">
                <div class="wallet-icon">💳</div>
                <div class="wallet-content">
                    <h3>Total Withdrawn</h3>
                    <div class="wallet-amount">$<?php echo number_format($wallet['total_withdrawn'], 2); ?></div>
                    <div class="wallet-meta">
                        <span>Commission Rate: <?php echo $wallet['commission_rate']; ?>%</span>
                    </div>
                </div>
            </div>

            <!-- Pending Earnings -->
            <div class="wallet-card">
                <div class="wallet-icon">⏳</div>
                <div class="wallet-content">
                    <h3>Pending Earnings</h3>
                    <div class="wallet-amount">$<?php echo number_format($stats['pending_earnings'], 2); ?></div>
                    <div class="wallet-meta">
                        <span>From recent sales</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="finance-grid">
        <!-- Earnings Chart -->
        <div class="finance-widget chart-widget">
            <div class="widget-header">
                <h3>Monthly Earnings</h3>
                <div class="chart-controls">
                    <button class="chart-period active" data-period="12">12M</button>
                    <button class="chart-period" data-period="6">6M</button>
                    <button class="chart-period" data-period="3">3M</button>
                </div>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="earningsChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Commissions -->
        <div class="finance-widget commissions-widget">
            <div class="widget-header">
                <h3>Recent Commissions</h3>
                <a href="/seller/finance/commissions.php" class="view-all">View All</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($commissions)): ?>
                    <div class="commissions-list">
                        <?php foreach (array_slice($commissions, 0, 10) as $commission): ?>
                            <div class="commission-item">
                                <div class="commission-info">
                                    <div class="commission-order">
                                        Order #<?php echo $commission['order_number']; ?>
                                    </div>
                                    <div class="commission-product">
                                        <?php echo htmlspecialchars($commission['product_name'] ?: 'Product'); ?>
                                    </div>
                                    <div class="commission-date">
                                        <?php echo formatTimeAgo($commission['created_at']); ?>
                                    </div>
                                </div>
                                <div class="commission-details">
                                    <div class="commission-amount">
                                        $<?php echo number_format($commission['commission_amount'], 2); ?>
                                    </div>
                                    <div class="commission-status">
                                        <span class="status-badge commission-<?php echo strtolower($commission['status']); ?>">
                                            <?php echo ucfirst($commission['status']); ?>
                                        </span>
                                    </div>
                                    <div class="commission-meta">
                                        Sale: $<?php echo number_format($commission['sale_amount'], 2); ?> 
                                        (<?php echo $commission['commission_rate']; ?>%)
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No commissions yet. Start selling to earn commissions!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payout History -->
        <div class="finance-widget payouts-widget">
            <div class="widget-header">
                <h3>Payout History</h3>
                <a href="/seller/finance/payouts.php" class="view-all">View All</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($payouts)): ?>
                    <div class="payouts-list">
                        <?php foreach ($payouts as $payout): ?>
                            <div class="payout-item">
                                <div class="payout-info">
                                    <div class="payout-reference">
                                        <?php echo $payout['reference_number']; ?>
                                    </div>
                                    <div class="payout-method">
                                        <?php echo ucfirst(str_replace('_', ' ', $payout['payout_method'])); ?>
                                    </div>
                                    <div class="payout-date">
                                        <?php echo formatDate($payout['requested_at']); ?>
                                    </div>
                                </div>
                                <div class="payout-details">
                                    <div class="payout-amount">
                                        $<?php echo number_format($payout['final_amount'], 2); ?>
                                    </div>
                                    <div class="payout-status">
                                        <span class="status-badge payout-<?php echo strtolower($payout['status']); ?>">
                                            <?php echo ucfirst($payout['status']); ?>
                                        </span>
                                    </div>
                                    <?php if ($payout['processing_fee'] > 0): ?>
                                        <div class="payout-fee">
                                            Fee: $<?php echo number_format($payout['processing_fee'], 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No payout requests yet.</p>
                        <?php if ($wallet['balance'] >= 10): ?>
                            <button class="btn btn-sm btn-primary" onclick="showPayoutModal()">
                                Request First Payout
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Settings -->
        <div class="finance-widget settings-widget">
            <div class="widget-header">
                <h3>Payment Settings</h3>
                <button class="btn btn-sm btn-outline" onclick="showPaymentSettingsModal()">
                    ⚙️ Update
                </button>
            </div>
            <div class="widget-content">
                <?php 
                $paymentDetails = json_decode($wallet['payment_details'] ?: '{}', true);
                $preferredMethod = $paymentDetails['preferred_method'] ?? 'bank_transfer';
                ?>
                <div class="payment-info">
                    <div class="payment-method">
                        <strong>Preferred Method:</strong> 
                        <?php echo ucfirst(str_replace('_', ' ', $preferredMethod)); ?>
                    </div>
                    
                    <?php if ($preferredMethod === 'bank_transfer' && !empty($paymentDetails['account_number'])): ?>
                        <div class="bank-info">
                            <div><strong>Bank:</strong> <?php echo htmlspecialchars($paymentDetails['bank_name'] ?? 'Not set'); ?></div>
                            <div><strong>Account:</strong> ****<?php echo substr($paymentDetails['account_number'], -4); ?></div>
                        </div>
                    <?php elseif ($preferredMethod === 'paypal' && !empty($paymentDetails['paypal_email'])): ?>
                        <div class="paypal-info">
                            <div><strong>PayPal:</strong> <?php echo htmlspecialchars($paymentDetails['paypal_email']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="no-payment-info">
                            <p>No payment information configured.</p>
                            <button class="btn btn-sm btn-primary" onclick="showPaymentSettingsModal()">
                                Add Payment Info
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="auto-payout">
                    <div class="auto-payout-setting">
                        <label class="toggle-label">
                            <input type="checkbox" <?php echo $wallet['auto_payout_enabled'] ? 'checked' : ''; ?> 
                                   onchange="toggleAutoPayout(this)">
                            <span class="toggle-slider"></span>
                            Auto-Payout
                        </label>
                        <div class="auto-payout-info">
                            When enabled, payouts will be automatically processed when balance reaches 
                            $<?php echo number_format($wallet['auto_payout_threshold'], 2); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payout Request Modal -->
<div class="modal-overlay" id="payoutModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Request Payout</h3>
            <button class="modal-close" onclick="closePayoutModal()">✕</button>
        </div>
        <form id="payoutForm" method="POST" class="modal-content">
            <input type="hidden" name="action" value="request_payout">
            <?php echo csrfTokenInput(); ?>
            
            <div class="payout-summary">
                <div class="available-balance">
                    <span>Available Balance:</span>
                    <span class="balance-amount">$<?php echo number_format($wallet['balance'], 2); ?></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="payoutAmount">Payout Amount</label>
                <div class="amount-input-group">
                    <span class="currency-symbol">$</span>
                    <input type="number" id="payoutAmount" name="amount" 
                           min="10" max="<?php echo $wallet['balance']; ?>" 
                           step="0.01" required>
                </div>
                <div class="form-help">Minimum payout amount is $10.00</div>
            </div>
            
            <div class="form-group">
                <label for="payoutMethod">Payout Method</label>
                <select id="payoutMethod" name="payout_method" required>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="paypal">PayPal</option>
                    <option value="wise">Wise (formerly TransferWise)</option>
                </select>
            </div>
            
            <div class="payout-calculation" id="payoutCalculation" style="display: none;">
                <div class="calc-row">
                    <span>Requested Amount:</span>
                    <span id="requestedAmount">$0.00</span>
                </div>
                <div class="calc-row">
                    <span>Processing Fee (2%):</span>
                    <span id="processingFee">$0.00</span>
                </div>
                <div class="calc-row total">
                    <span>You'll Receive:</span>
                    <span id="finalAmount">$0.00</span>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closePayoutModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Request Payout</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Settings Modal -->
<div class="modal-overlay" id="paymentSettingsModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Payment Settings</h3>
            <button class="modal-close" onclick="closePaymentSettingsModal()">✕</button>
        </div>
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update_payment_info">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="preferredMethod">Preferred Payment Method</label>
                <select id="preferredMethod" name="preferred_method" onchange="togglePaymentFields(this.value)">
                    <option value="bank_transfer" <?php echo $preferredMethod === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="paypal" <?php echo $preferredMethod === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                </select>
            </div>
            
            <div id="bankFields" style="display: <?php echo $preferredMethod === 'bank_transfer' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="bankName">Bank Name</label>
                    <input type="text" id="bankName" name="bank_name" 
                           value="<?php echo htmlspecialchars($paymentDetails['bank_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="accountHolder">Account Holder Name</label>
                    <input type="text" id="accountHolder" name="account_holder" 
                           value="<?php echo htmlspecialchars($paymentDetails['account_holder'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="accountNumber">Account Number</label>
                    <input type="text" id="accountNumber" name="account_number" 
                           value="<?php echo htmlspecialchars($paymentDetails['account_number'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="routingNumber">Routing Number</label>
                    <input type="text" id="routingNumber" name="routing_number" 
                           value="<?php echo htmlspecialchars($paymentDetails['routing_number'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="swiftCode">SWIFT/BIC Code (for international)</label>
                    <input type="text" id="swiftCode" name="swift_code" 
                           value="<?php echo htmlspecialchars($paymentDetails['swift_code'] ?? ''); ?>">
                </div>
            </div>
            
            <div id="paypalFields" style="display: <?php echo $preferredMethod === 'paypal' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="paypalEmail">PayPal Email Address</label>
                    <input type="email" id="paypalEmail" name="paypal_email" 
                           value="<?php echo htmlspecialchars($paymentDetails['paypal_email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closePaymentSettingsModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<style>
.seller-finance-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    color: white;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.breadcrumb {
    font-size: 14px;
    margin-bottom: 8px;
    opacity: 0.8;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.page-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
}

.subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.wallet-section {
    margin-bottom: 30px;
}

.wallet-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.wallet-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 20px;
}

.wallet-card.primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border: none;
}

.wallet-icon {
    font-size: 32px;
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(255,255,255,0.1);
}

.wallet-card:not(.primary) .wallet-icon {
    background: #f8fafc;
}

.wallet-content h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    opacity: 0.8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.wallet-card:not(.primary) .wallet-content h3 {
    color: #6b7280;
}

.wallet-amount {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 4px;
}

.wallet-card:not(.primary) .wallet-amount {
    color: #1f2937;
}

.wallet-meta {
    font-size: 12px;
    opacity: 0.8;
}

.wallet-card:not(.primary) .wallet-meta {
    color: #6b7280;
}

.pending {
    color: #f59e0b;
    font-weight: 600;
}

.finance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.finance-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.widget-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.widget-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.view-all {
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.widget-content {
    padding: 24px;
}

.chart-controls {
    display: flex;
    gap: 8px;
}

.chart-period {
    padding: 4px 12px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.chart-period.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.chart-container {
    height: 200px;
    position: relative;
}

.commissions-list, .payouts-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.commission-item, .payout-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.commission-item:hover, .payout-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.commission-info, .payout-info {
    flex: 1;
}

.commission-order, .payout-reference {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.commission-product, .payout-method {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 4px;
}

.commission-date, .payout-date {
    color: #9ca3af;
    font-size: 12px;
}

.commission-details, .payout-details {
    text-align: right;
}

.commission-amount, .payout-amount {
    font-weight: 700;
    color: #1f2937;
    font-size: 16px;
    margin-bottom: 4px;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.commission-pending, .payout-requested { background: #fef3c7; color: #92400e; }
.commission-approved, .payout-processing { background: #dbeafe; color: #1e40af; }
.commission-paid, .payout-completed { background: #dcfce7; color: #166534; }
.commission-disputed, .payout-failed { background: #fee2e2; color: #dc2626; }

.commission-meta, .payout-fee {
    color: #6b7280;
    font-size: 12px;
}

.payment-info {
    margin-bottom: 24px;
}

.payment-method {
    margin-bottom: 12px;
    font-size: 14px;
    color: #1f2937;
}

.bank-info, .paypal-info {
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
    font-size: 14px;
    color: #6b7280;
}

.bank-info div, .paypal-info div {
    margin-bottom: 4px;
}

.no-payment-info {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}

.auto-payout {
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    font-weight: 500;
    color: #1f2937;
}

.toggle-label input[type="checkbox"] {
    display: none;
}

.toggle-slider {
    width: 44px;
    height: 24px;
    background: #d1d5db;
    border-radius: 12px;
    position: relative;
    transition: background 0.2s ease;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: transform 0.2s ease;
}

.toggle-label input[type="checkbox"]:checked + .toggle-slider {
    background: #10b981;
}

.toggle-label input[type="checkbox"]:checked + .toggle-slider::before {
    transform: translateX(20px);
}

.auto-payout-info {
    font-size: 12px;
    color: #6b7280;
    margin-top: 8px;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #6b7280;
    padding: 4px;
}

.modal-content {
    padding: 24px;
    max-height: 60vh;
    overflow-y: auto;
}

.payout-summary {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.available-balance {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.balance-amount {
    color: #10b981;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.amount-input-group {
    position: relative;
}

.currency-symbol {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-weight: 600;
}

.amount-input-group input {
    padding-left: 32px;
}

.form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.payout-calculation {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.calc-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
    color: #6b7280;
}

.calc-row.total {
    border-top: 1px solid #e5e7eb;
    padding-top: 8px;
    margin-top: 8px;
    font-weight: 600;
    color: #1f2937;
    font-size: 16px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

@media (max-width: 768px) {
    .seller-finance-page {
        padding: 16px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .wallet-cards {
        grid-template-columns: 1fr;
    }
    
    .finance-grid {
        grid-template-columns: 1fr;
    }
    
    .commission-item, .payout-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .commission-details, .payout-details {
        text-align: left;
    }
}
</style>

<script>
// Payout amount calculation
document.getElementById('payoutAmount').addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    const fee = amount * 0.02; // 2% fee
    const final = amount - fee;
    
    document.getElementById('requestedAmount').textContent = '$' + amount.toFixed(2);
    document.getElementById('processingFee').textContent = '$' + fee.toFixed(2);
    document.getElementById('finalAmount').textContent = '$' + final.toFixed(2);
    
    document.getElementById('payoutCalculation').style.display = amount > 0 ? 'block' : 'none';
});

// Modal functions
function showPayoutModal() {
    document.getElementById('payoutModal').style.display = 'flex';
}

function closePayoutModal() {
    document.getElementById('payoutModal').style.display = 'none';
    document.getElementById('payoutForm').reset();
    document.getElementById('payoutCalculation').style.display = 'none';
}

function showPaymentSettingsModal() {
    document.getElementById('paymentSettingsModal').style.display = 'flex';
}

function closePaymentSettingsModal() {
    document.getElementById('paymentSettingsModal').style.display = 'none';
}

// Payment method fields toggle
function togglePaymentFields(method) {
    document.getElementById('bankFields').style.display = method === 'bank_transfer' ? 'block' : 'none';
    document.getElementById('paypalFields').style.display = method === 'paypal' ? 'block' : 'none';
}

// Auto-payout toggle
function toggleAutoPayout(checkbox) {
    const vendorId = <?php echo $vendorId; ?>;
    const enabled = checkbox.checked ? 1 : 0;
    
    fetch('/api/seller/finance/auto-payout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': '<?php echo csrfToken(); ?>'
        },
        body: JSON.stringify({
            enabled: enabled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            checkbox.checked = !checkbox.checked; // Revert on failure
            alert('Error updating auto-payout setting: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        checkbox.checked = !checkbox.checked; // Revert on failure
        alert('Error updating auto-payout setting: ' + error.message);
    });
}

// Download statement
function downloadStatement() {
    window.open('/seller/finance/statement.php', '_blank');
}

// Earnings chart
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('earningsChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyEarnings); ?>;
        
        drawEarningsChart(ctx, monthlyData);
    }
});

function drawEarningsChart(ctx, data) {
    // Simple implementation - replace with Chart.js or similar in production
    ctx.strokeStyle = '#10b981';
    ctx.lineWidth = 3;
    ctx.beginPath();
    
    const width = ctx.canvas.width;
    const height = ctx.canvas.height;
    const padding = 40;
    
    if (data.length > 0) {
        const maxEarnings = Math.max(...data.map(d => parseFloat(d.earnings)));
        
        data.forEach((point, index) => {
            const x = padding + (index / (data.length - 1)) * (width - 2 * padding);
            const y = height - padding - (parseFloat(point.earnings) / maxEarnings) * (height - 2 * padding);
            
            if (index === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        });
        
        ctx.stroke();
        
        // Add data points
        ctx.fillStyle = '#10b981';
        data.forEach((point, index) => {
            const x = padding + (index / (data.length - 1)) * (width - 2 * padding);
            const y = height - padding - (parseFloat(point.earnings) / maxEarnings) * (height - 2 * padding);
            
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
        });
    }
}

// Chart period switching
document.querySelectorAll('.chart-period').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.chart-period').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // In production, reload chart data via AJAX
        console.log('Load data for period:', this.dataset.period);
    });
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});

// Close modals with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});
</script>

<?php includeFooter(); ?>