<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php';

$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) redirect('/sell.php');

$error = $success = '';
$db = db();

$stmt = $db->prepare("SELECT * FROM seller_tax_settings WHERE vendor_id = ? AND is_active = 1");
$stmt->execute([$vendorInfo['id']]);
$taxSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['delete'])) {
    $stmt = $db->prepare("UPDATE seller_tax_settings SET is_active = 0 WHERE id = ? AND vendor_id = ?");
    $stmt->execute([(int)$_GET['delete'], $vendorInfo['id']]);
    redirect('/seller/tax-settings.php?success=deleted');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $db->prepare("INSERT INTO seller_tax_settings (vendor_id, tax_type, tax_rate, tax_region, tax_id_number, apply_to_shipping, is_inclusive) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $vendorInfo['id'],
            sanitizeInput($_POST['tax_type']),
            floatval($_POST['tax_rate']),
            sanitizeInput($_POST['tax_region']),
            sanitizeInput($_POST['tax_id_number']),
            isset($_POST['apply_to_shipping']) ? 1 : 0,
            isset($_POST['is_inclusive']) ? 1 : 0
        ]);
        redirect('/seller/tax-settings.php?success=saved');
    } catch (Exception $e) {
        $error = 'Failed to save tax settings.';
    }
}

if (isset($_GET['success'])) $success = $_GET['success'] === 'deleted' ? 'Tax setting deleted!' : 'Tax settings saved!';

includeHeader('Tax Settings');
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav"><a href="/seller/settings.php">← Back</a></nav>
        <h1>Tax Settings</h1>
        <p>Configure tax collection for your sales</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if ($taxSettings): ?>
        <div class="settings-card mb-4">
            <h2>Current Tax Settings</h2>
            <table class="table">
                <thead><tr><th>Type</th><th>Rate</th><th>Region</th><th>Tax ID</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($taxSettings as $setting): ?>
                        <tr>
                            <td><?= htmlspecialchars($setting['tax_type']) ?></td>
                            <td><?= number_format($setting['tax_rate'], 2) ?>%</td>
                            <td><?= htmlspecialchars($setting['tax_region'] ?: 'All') ?></td>
                            <td><?= htmlspecialchars($setting['tax_id_number'] ?: 'N/A') ?></td>
                            <td><a href="?delete=<?= $setting['id'] ?>" class="btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <div class="settings-card">
        <h2>Add Tax Setting</h2>
        <form method="post">
            <?= csrfTokenInput() ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Tax Type *</label>
                    <select name="tax_type" class="form-control" required>
                        <option value="VAT">VAT</option>
                        <option value="GST">GST</option>
                        <option value="Sales Tax">Sales Tax</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tax Rate (%) *</label>
                    <input type="number" name="tax_rate" class="form-control" step="0.01" min="0" max="100" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Region/State</label>
                    <input type="text" name="tax_region" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label>Tax ID Number</label>
                    <input type="text" name="tax_id_number" class="form-control" placeholder="Optional">
                </div>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="apply_to_shipping"> Apply tax to shipping charges</label>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="is_inclusive"> Tax is inclusive (included in product price)</label>
            </div>
            <button type="submit" class="btn btn-primary">Add Tax Setting</button>
        </form>
    </div>
</div>

<style>
.vendor-header{margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e5e7eb}
.vendor-nav a{color:#6b7280;text-decoration:none;font-weight:500}
.vendor-header h1{margin:10px 0 5px 0}.vendor-header p{color:#6b7280;margin:0}
.settings-card{background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);padding:30px}
.settings-card h2{margin-top:0;margin-bottom:20px;color:#1f2937}.mb-4{margin-bottom:30px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:5px;color:#374151;font-weight:500}
.form-control{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px}
.table{width:100%;border-collapse:collapse}.table th,.table td{padding:12px;text-align:left;border-bottom:1px solid #e5e7eb}
.table th{background:#f9fafb;font-weight:600}.btn-sm{padding:5px 10px;font-size:13px}
.btn-danger{background:#ef4444;color:white;border:none;border-radius:4px;cursor:pointer}
.alert{padding:15px;border-radius:6px;margin-bottom:20px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
@media (max-width:768px){.form-row{grid-template-columns:1fr}}
</style>

<?php includeFooter(); ?>
