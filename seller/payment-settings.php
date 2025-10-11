<?php
/**
 * Seller Payment Settings
 * Configure payment information for receiving payouts
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php';

$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) {
    redirect('/sell.php');
}

$error = '';
$success = '';

// Fetch existing payment info
$db = db();
$stmt = $db->prepare("SELECT * FROM seller_payment_info WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$vendorInfo['id']]);
$paymentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
        
        $data = [
            'vendor_id' => $vendorInfo['id'],
            'payment_method' => $payment_method,
            'bank_name' => sanitizeInput($_POST['bank_name'] ?? ''),
            'account_holder_name' => sanitizeInput($_POST['account_holder_name'] ?? ''),
            'account_number' => sanitizeInput($_POST['account_number'] ?? ''),
            'routing_number' => sanitizeInput($_POST['routing_number'] ?? ''),
            'swift_code' => sanitizeInput($_POST['swift_code'] ?? ''),
            'paypal_email' => sanitizeInput($_POST['paypal_email'] ?? ''),
            'mobile_money_provider' => sanitizeInput($_POST['mobile_money_provider'] ?? ''),
            'mobile_money_number' => sanitizeInput($_POST['mobile_money_number'] ?? ''),
            'additional_info' => sanitizeInput($_POST['additional_info'] ?? ''),
        ];
        
        try {
            if ($paymentInfo) {
                // Update existing
                $sql = "UPDATE seller_payment_info SET 
                        payment_method = ?, bank_name = ?, account_holder_name = ?,
                        account_number = ?, routing_number = ?, swift_code = ?,
                        paypal_email = ?, mobile_money_provider = ?, mobile_money_number = ?,
                        additional_info = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $data['payment_method'], $data['bank_name'], $data['account_holder_name'],
                    $data['account_number'], $data['routing_number'], $data['swift_code'],
                    $data['paypal_email'], $data['mobile_money_provider'], $data['mobile_money_number'],
                    $data['additional_info'], $paymentInfo['id']
                ]);
            } else {
                // Insert new
                $sql = "INSERT INTO seller_payment_info 
                        (vendor_id, payment_method, bank_name, account_holder_name, account_number,
                         routing_number, swift_code, paypal_email, mobile_money_provider, 
                         mobile_money_number, additional_info)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $data['vendor_id'], $data['payment_method'], $data['bank_name'],
                    $data['account_holder_name'], $data['account_number'], $data['routing_number'],
                    $data['swift_code'], $data['paypal_email'], $data['mobile_money_provider'],
                    $data['mobile_money_number'], $data['additional_info']
                ]);
            }
            
            $success = 'Payment information saved successfully!';
            // Refresh data
            $stmt = $db->prepare("SELECT * FROM seller_payment_info WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$vendorInfo['id']]);
            $paymentInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $error = 'Failed to save payment information. Please try again.';
            error_log("Payment settings error: " . $e->getMessage());
        }
    }
}

$page_title = 'Payment Settings - Seller Center';
includeHeader($page_title);
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav">
            <a href="/seller/settings.php">← Back to Settings</a>
        </nav>
        <h1>Payment Settings</h1>
        <p>Configure how you receive payments from your sales</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="settings-card">
        <form method="post" class="settings-form">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="payment_method">Payment Method *</label>
                <select name="payment_method" id="payment_method" class="form-control" required onchange="togglePaymentFields()">
                    <option value="">Select a payment method</option>
                    <option value="bank_transfer" <?php echo ($paymentInfo['payment_method'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                    <option value="paypal" <?php echo ($paymentInfo['payment_method'] ?? '') === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                    <option value="mobile_money" <?php echo ($paymentInfo['payment_method'] ?? '') === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                    <option value="other" <?php echo ($paymentInfo['payment_method'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <!-- Bank Transfer Fields -->
            <div id="bank_fields" class="payment-method-fields" style="display: none;">
                <h3>Bank Transfer Information</h3>
                <div class="form-group">
                    <label for="bank_name">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['bank_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="account_holder_name">Account Holder Name</label>
                    <input type="text" name="account_holder_name" id="account_holder_name" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['account_holder_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="account_number">Account Number</label>
                    <input type="text" name="account_number" id="account_number" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['account_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="routing_number">Routing Number / Sort Code</label>
                    <input type="text" name="routing_number" id="routing_number" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['routing_number'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="swift_code">SWIFT/BIC Code (for international transfers)</label>
                    <input type="text" name="swift_code" id="swift_code" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['swift_code'] ?? ''); ?>">
                </div>
            </div>

            <!-- PayPal Fields -->
            <div id="paypal_fields" class="payment-method-fields" style="display: none;">
                <h3>PayPal Information</h3>
                <div class="form-group">
                    <label for="paypal_email">PayPal Email Address</label>
                    <input type="email" name="paypal_email" id="paypal_email" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['paypal_email'] ?? ''); ?>">
                    <small class="form-text">Payments will be sent to this PayPal email address</small>
                </div>
            </div>

            <!-- Mobile Money Fields -->
            <div id="mobile_money_fields" class="payment-method-fields" style="display: none;">
                <h3>Mobile Money Information</h3>
                <div class="form-group">
                    <label for="mobile_money_provider">Provider</label>
                    <select name="mobile_money_provider" id="mobile_money_provider" class="form-control">
                        <option value="">Select Provider</option>
                        <option value="M-Pesa" <?php echo ($paymentInfo['mobile_money_provider'] ?? '') === 'M-Pesa' ? 'selected' : ''; ?>>M-Pesa</option>
                        <option value="MTN Mobile Money" <?php echo ($paymentInfo['mobile_money_provider'] ?? '') === 'MTN Mobile Money' ? 'selected' : ''; ?>>MTN Mobile Money</option>
                        <option value="Airtel Money" <?php echo ($paymentInfo['mobile_money_provider'] ?? '') === 'Airtel Money' ? 'selected' : ''; ?>>Airtel Money</option>
                        <option value="Other" <?php echo ($paymentInfo['mobile_money_provider'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mobile_money_number">Mobile Money Number</label>
                    <input type="text" name="mobile_money_number" id="mobile_money_number" class="form-control" 
                           value="<?php echo htmlspecialchars($paymentInfo['mobile_money_number'] ?? ''); ?>">
                </div>
            </div>

            <!-- Additional Information -->
            <div class="form-group">
                <label for="additional_info">Additional Information</label>
                <textarea name="additional_info" id="additional_info" class="form-control" rows="3"><?php echo htmlspecialchars($paymentInfo['additional_info'] ?? ''); ?></textarea>
                <small class="form-text">Any special instructions or additional details</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                <a href="/seller/settings.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>

        <?php if ($paymentInfo && !$paymentInfo['is_verified']): ?>
            <div class="alert alert-warning mt-3">
                <strong>⚠️ Payment Information Not Verified</strong><br>
                Your payment information is pending verification. This may take 1-2 business days.
            </div>
        <?php elseif ($paymentInfo && $paymentInfo['is_verified']): ?>
            <div class="alert alert-success mt-3">
                <strong>✓ Payment Information Verified</strong><br>
                Your payment information has been verified and is ready for payouts.
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.vendor-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.vendor-nav a {
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
}

.vendor-nav a:hover {
    color: #374151;
}

.vendor-header h1 {
    margin: 10px 0 5px 0;
}

.vendor-header p {
    color: #6b7280;
    margin: 0;
}

.settings-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 30px;
    max-width: 800px;
}

.settings-form h3 {
    margin-top: 20px;
    margin-bottom: 15px;
    color: #1f2937;
    font-size: 18px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #374151;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
    display: block;
    margin-top: 5px;
    color: #6b7280;
    font-size: 13px;
}

.payment-method-fields {
    margin-top: 20px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
}

.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

.mt-3 {
    margin-top: 20px;
}
</style>

<script>
function togglePaymentFields() {
    const method = document.getElementById('payment_method').value;
    
    // Hide all
    document.getElementById('bank_fields').style.display = 'none';
    document.getElementById('paypal_fields').style.display = 'none';
    document.getElementById('mobile_money_fields').style.display = 'none';
    
    // Show selected
    if (method === 'bank_transfer') {
        document.getElementById('bank_fields').style.display = 'block';
    } else if (method === 'paypal') {
        document.getElementById('paypal_fields').style.display = 'block';
    } else if (method === 'mobile_money') {
        document.getElementById('mobile_money_fields').style.display = 'block';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    togglePaymentFields();
});
</script>

<?php includeFooter(); ?>
