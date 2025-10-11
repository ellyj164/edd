<?php
/**
 * Seller Shipping Settings
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
$db = db();

// Fetch existing shipping settings
$stmt = $db->prepare("SELECT * FROM seller_shipping_settings WHERE vendor_id = ? AND is_active = 1");
$stmt->execute([$vendorInfo['id']]);
$shippingSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete
if (isset($_GET['delete']) && $_GET['delete']) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $db->prepare("UPDATE seller_shipping_settings SET is_active = 0 WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$deleteId, $vendorInfo['id']]);
    redirect('/seller/shipping-settings.php?success=deleted');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        try {
            $sql = "INSERT INTO seller_shipping_settings 
                    (vendor_id, carrier_name, shipping_zone, base_rate, per_item_rate, 
                     free_shipping_threshold, estimated_delivery_days)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $vendorInfo['id'],
                sanitizeInput($_POST['carrier_name']),
                sanitizeInput($_POST['shipping_zone']),
                floatval($_POST['base_rate']),
                floatval($_POST['per_item_rate']),
                $_POST['free_shipping_threshold'] ? floatval($_POST['free_shipping_threshold']) : null,
                $_POST['estimated_delivery_days'] ? intval($_POST['estimated_delivery_days']) : null
            ]);
            redirect('/seller/shipping-settings.php?success=saved');
        } catch (Exception $e) {
            $error = 'Failed to save shipping settings.';
            error_log("Shipping settings error: " . $e->getMessage());
        }
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'] === 'deleted' ? 'Shipping option deleted successfully!' : 'Shipping settings saved successfully!';
}

$page_title = 'Shipping Settings';
includeHeader($page_title);
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav">
            <a href="/seller/settings.php">← Back to Settings</a>
        </nav>
        <h1>Shipping Settings</h1>
        <p>Set up shipping rates and policies for your products</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Existing Shipping Options -->
    <?php if ($shippingSettings): ?>
        <div class="settings-card mb-4">
            <h2>Current Shipping Options</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Carrier</th>
                        <th>Zone</th>
                        <th>Base Rate</th>
                        <th>Per Item</th>
                        <th>Free Shipping</th>
                        <th>Delivery</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shippingSettings as $setting): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($setting['carrier_name']); ?></td>
                            <td><?php echo htmlspecialchars($setting['shipping_zone']); ?></td>
                            <td>$<?php echo number_format($setting['base_rate'], 2); ?></td>
                            <td>$<?php echo number_format($setting['per_item_rate'], 2); ?></td>
                            <td><?php echo $setting['free_shipping_threshold'] ? '$' . number_format($setting['free_shipping_threshold'], 2) : 'N/A'; ?></td>
                            <td><?php echo $setting['estimated_delivery_days'] ? $setting['estimated_delivery_days'] . ' days' : 'N/A'; ?></td>
                            <td>
                                <a href="?delete=<?php echo $setting['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this shipping option?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Add New Shipping Option -->
    <div class="settings-card">
        <h2>Add Shipping Option</h2>
        <form method="post" class="settings-form">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Carrier Name *</label>
                    <input type="text" name="carrier_name" class="form-control" required placeholder="e.g., USPS, FedEx, UPS">
                </div>
                <div class="form-group">
                    <label>Shipping Zone *</label>
                    <select name="shipping_zone" class="form-control" required>
                        <option value="Domestic">Domestic</option>
                        <option value="International">International</option>
                        <option value="Regional">Regional</option>
                        <option value="Local">Local</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Base Rate ($) *</label>
                    <input type="number" name="base_rate" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Per Item Rate ($)</label>
                    <input type="number" name="per_item_rate" class="form-control" step="0.01" min="0" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Free Shipping Threshold ($)</label>
                    <input type="number" name="free_shipping_threshold" class="form-control" step="0.01" min="0" placeholder="Optional">
                    <small class="form-text">Orders above this amount get free shipping</small>
                </div>
                <div class="form-group">
                    <label>Est. Delivery (days)</label>
                    <input type="number" name="estimated_delivery_days" class="form-control" min="1" placeholder="Optional">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Add Shipping Option</button>
        </form>
    </div>
</div>

<style>
.vendor-header { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb; }
.vendor-nav a { color: #6b7280; text-decoration: none; font-weight: 500; }
.vendor-header h1 { margin: 10px 0 5px 0; }
.vendor-header p { color: #6b7280; margin: 0; }
.settings-card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); padding: 30px; }
.settings-card h2 { margin-top: 0; margin-bottom: 20px; color: #1f2937; }
.mb-4 { margin-bottom: 30px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 5px; color: #374151; font-weight: 500; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; }
.form-text { display: block; margin-top: 5px; color: #6b7280; font-size: 13px; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
.table th { background: #f9fafb; font-weight: 600; }
.btn-sm { padding: 5px 10px; font-size: 13px; }
.btn-danger { background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; }
.alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
.alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
.alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
@media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
</style>

<?php includeFooter(); ?>
