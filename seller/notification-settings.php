<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php';

$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) redirect('/sell.php');

$error = $success = '';
$db = db();

$stmt = $db->prepare("SELECT * FROM notification_settings WHERE vendor_id = ?");
$stmt->execute([$vendorInfo['id']]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $data = [
            'email_new_order' => isset($_POST['email_new_order']) ? 1 : 0,
            'email_order_shipped' => isset($_POST['email_order_shipped']) ? 1 : 0,
            'email_order_delivered' => isset($_POST['email_order_delivered']) ? 1 : 0,
            'email_customer_message' => isset($_POST['email_customer_message']) ? 1 : 0,
            'email_product_review' => isset($_POST['email_product_review']) ? 1 : 0,
            'email_low_stock' => isset($_POST['email_low_stock']) ? 1 : 0,
            'email_payout_completed' => isset($_POST['email_payout_completed']) ? 1 : 0,
            'email_weekly_summary' => isset($_POST['email_weekly_summary']) ? 1 : 0,
            'email_monthly_report' => isset($_POST['email_monthly_report']) ? 1 : 0,
            'sms_new_order' => isset($_POST['sms_new_order']) ? 1 : 0,
            'sms_urgent_alerts' => isset($_POST['sms_urgent_alerts']) ? 1 : 0
        ];
        
        if ($settings) {
            $sql = "UPDATE notification_settings SET " . implode(', ', array_map(fn($k) => "$k = ?", array_keys($data))) . ", updated_at = NOW() WHERE vendor_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge(array_values($data), [$vendorInfo['id']]));
        } else {
            $stmt = $db->prepare("INSERT INTO notification_settings (vendor_id, " . implode(', ', array_keys($data)) . ") VALUES (?" . str_repeat(', ?', count($data)) . ")");
            $stmt->execute(array_merge([$vendorInfo['id']], array_values($data)));
        }
        
        $stmt = $db->prepare("SELECT * FROM notification_settings WHERE vendor_id = ?");
        $stmt->execute([$vendorInfo['id']]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = 'Notification settings updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update settings.';
    }
}

includeHeader('Notification Settings');
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav"><a href="/seller/settings.php">← Back</a></nav>
        <h1>Notification Settings</h1>
        <p>Manage your notification preferences</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="settings-card">
        <form method="post">
            <?= csrfTokenInput() ?>
            
            <div class="settings-section">
                <h3>Email Notifications</h3>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_new_order" <?= ($settings['email_new_order'] ?? 1) ? 'checked' : '' ?>> New Order Received</label>
                    <p class="description">Get notified when a customer places an order</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_order_shipped" <?= ($settings['email_order_shipped'] ?? 1) ? 'checked' : '' ?>> Order Shipped</label>
                    <p class="description">Confirmation when you mark an order as shipped</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_order_delivered" <?= ($settings['email_order_delivered'] ?? 1) ? 'checked' : '' ?>> Order Delivered</label>
                    <p class="description">Notification when order is delivered</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_customer_message" <?= ($settings['email_customer_message'] ?? 1) ? 'checked' : '' ?>> Customer Messages</label>
                    <p class="description">Get notified when customers send you messages</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_product_review" <?= ($settings['email_product_review'] ?? 1) ? 'checked' : '' ?>> Product Reviews</label>
                    <p class="description">Notification when customers leave reviews</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_low_stock" <?= ($settings['email_low_stock'] ?? 1) ? 'checked' : '' ?>> Low Stock Alerts</label>
                    <p class="description">Alert when product inventory is running low</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_payout_completed" <?= ($settings['email_payout_completed'] ?? 1) ? 'checked' : '' ?>> Payout Completed</label>
                    <p class="description">Confirmation when payouts are processed</p>
                </div>
            </div>

            <div class="settings-section">
                <h3>Summary Reports</h3>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_weekly_summary" <?= ($settings['email_weekly_summary'] ?? 0) ? 'checked' : '' ?>> Weekly Summary</label>
                    <p class="description">Receive weekly sales and performance summary</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="email_monthly_report" <?= ($settings['email_monthly_report'] ?? 0) ? 'checked' : '' ?>> Monthly Report</label>
                    <p class="description">Comprehensive monthly performance report</p>
                </div>
            </div>

            <div class="settings-section">
                <h3>SMS Notifications</h3>
                <div class="notification-item">
                    <label><input type="checkbox" name="sms_new_order" <?= ($settings['sms_new_order'] ?? 0) ? 'checked' : '' ?>> New Order SMS</label>
                    <p class="description">Instant SMS for new orders (charges may apply)</p>
                </div>
                <div class="notification-item">
                    <label><input type="checkbox" name="sms_urgent_alerts" <?= ($settings['sms_urgent_alerts'] ?? 0) ? 'checked' : '' ?>> Urgent Alerts</label>
                    <p class="description">SMS for urgent account or security alerts</p>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Notification Settings</button>
        </form>
    </div>
</div>

<style>
.vendor-header{margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e5e7eb}
.vendor-nav a{color:#6b7280;text-decoration:none;font-weight:500}
.vendor-header h1{margin:10px 0 5px 0}.vendor-header p{color:#6b7280;margin:0}
.settings-card{background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);padding:30px;max-width:800px}
.settings-section{margin-bottom:30px;padding-bottom:30px;border-bottom:1px solid #e5e7eb}
.settings-section:last-of-type{border-bottom:none}
.settings-section h3{margin-top:0;margin-bottom:20px;color:#1f2937}
.notification-item{margin-bottom:20px;padding:15px;background:#f9fafb;border-radius:6px}
.notification-item label{display:block;font-weight:500;color:#374151;cursor:pointer}
.notification-item label input{margin-right:10px}
.notification-item .description{margin:5px 0 0 25px;color:#6b7280;font-size:13px}
.alert{padding:15px;border-radius:6px;margin-bottom:20px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
</style>

<?php includeFooter(); ?>
