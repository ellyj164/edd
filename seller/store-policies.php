<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php';

$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) redirect('/sell.php');

$error = $success = '';
$db = db();

$stmt = $db->prepare("SELECT * FROM store_policies WHERE vendor_id = ?");
$stmt->execute([$vendorInfo['id']]);
$policies = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $data = [
            'return_policy' => sanitizeInput($_POST['return_policy'] ?? ''),
            'refund_policy' => sanitizeInput($_POST['refund_policy'] ?? ''),
            'exchange_policy' => sanitizeInput($_POST['exchange_policy'] ?? ''),
            'shipping_policy' => sanitizeInput($_POST['shipping_policy'] ?? ''),
            'privacy_policy' => sanitizeInput($_POST['privacy_policy'] ?? '')
        ];
        
        if ($policies) {
            $stmt = $db->prepare("UPDATE store_policies SET return_policy = ?, refund_policy = ?, exchange_policy = ?, shipping_policy = ?, privacy_policy = ?, updated_at = NOW() WHERE vendor_id = ?");
            $stmt->execute(array_merge(array_values($data), [$vendorInfo['id']]));
        } else {
            $stmt = $db->prepare("INSERT INTO store_policies (vendor_id, return_policy, refund_policy, exchange_policy, shipping_policy, privacy_policy) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_merge([$vendorInfo['id']], array_values($data)));
        }
        
        $stmt = $db->prepare("SELECT * FROM store_policies WHERE vendor_id = ?");
        $stmt->execute([$vendorInfo['id']]);
        $policies = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = 'Store policies updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update policies.';
    }
}

includeHeader('Store Policies');
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav"><a href="/seller/settings.php">← Back</a></nav>
        <h1>Store Policies</h1>
        <p>Define your store's policies for customers</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="settings-card">
        <form method="post">
            <?= csrfTokenInput() ?>
            
            <div class="form-group">
                <label>Return Policy</label>
                <textarea name="return_policy" class="form-control" rows="4"><?= htmlspecialchars($policies['return_policy'] ?? '') ?></textarea>
                <small class="form-text">Explain your return policy and timeframe</small>
            </div>

            <div class="form-group">
                <label>Refund Policy</label>
                <textarea name="refund_policy" class="form-control" rows="4"><?= htmlspecialchars($policies['refund_policy'] ?? '') ?></textarea>
                <small class="form-text">Explain how and when refunds are processed</small>
            </div>

            <div class="form-group">
                <label>Exchange Policy</label>
                <textarea name="exchange_policy" class="form-control" rows="4"><?= htmlspecialchars($policies['exchange_policy'] ?? '') ?></textarea>
                <small class="form-text">Explain your product exchange policy</small>
            </div>

            <div class="form-group">
                <label>Shipping Policy</label>
                <textarea name="shipping_policy" class="form-control" rows="4"><?= htmlspecialchars($policies['shipping_policy'] ?? '') ?></textarea>
                <small class="form-text">Explain shipping methods, times, and costs</small>
            </div>

            <div class="form-group">
                <label>Privacy Policy</label>
                <textarea name="privacy_policy" class="form-control" rows="4"><?= htmlspecialchars($policies['privacy_policy'] ?? '') ?></textarea>
                <small class="form-text">Explain how you handle customer data</small>
            </div>

            <button type="submit" class="btn btn-primary">Save Store Policies</button>
        </form>
    </div>
</div>

<style>
.vendor-header{margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e5e7eb}
.vendor-nav a{color:#6b7280;text-decoration:none;font-weight:500}
.vendor-header h1{margin:10px 0 5px 0}.vendor-header p{color:#6b7280;margin:0}
.settings-card{background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);padding:30px;max-width:900px}
.form-group{margin-bottom:25px}.form-group label{display:block;margin-bottom:5px;color:#374151;font-weight:500}
.form-control{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-family:inherit}
.form-text{display:block;margin-top:5px;color:#6b7280;font-size:13px}
.alert{padding:15px;border-radius:6px;margin-bottom:20px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
</style>

<?php includeFooter(); ?>
