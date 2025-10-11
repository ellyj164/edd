<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php';

$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) redirect('/sell.php');

$error = $success = '';
$db = db();

$stmt = $db->prepare("SELECT * FROM store_appearance WHERE vendor_id = ?");
$stmt->execute([$vendorInfo['id']]);
$appearance = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $theme_color = sanitizeInput($_POST['theme_color']);
        $theme_name = sanitizeInput($_POST['theme_name']);
        
        if ($appearance) {
            $stmt = $db->prepare("UPDATE store_appearance SET theme_color = ?, theme_name = ?, updated_at = NOW() WHERE vendor_id = ?");
            $stmt->execute([$theme_color, $theme_name, $vendorInfo['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO store_appearance (vendor_id, theme_color, theme_name) VALUES (?, ?, ?)");
            $stmt->execute([$vendorInfo['id'], $theme_color, $theme_name]);
        }
        
        // Refresh data
        $stmt = $db->prepare("SELECT * FROM store_appearance WHERE vendor_id = ?");
        $stmt->execute([$vendorInfo['id']]);
        $appearance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $success = 'Store appearance updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update appearance.';
    }
}

includeHeader('Store Appearance');
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav"><a href="/seller/settings.php">← Back</a></nav>
        <h1>Store Appearance</h1>
        <p>Customize your store's look and feel</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <div class="settings-card">
        <form method="post">
            <?= csrfTokenInput() ?>
            
            <div class="form-group">
                <label>Store Theme</label>
                <select name="theme_name" class="form-control">
                    <option value="default" <?= ($appearance['theme_name'] ?? 'default') === 'default' ? 'selected' : '' ?>>Default</option>
                    <option value="modern" <?= ($appearance['theme_name'] ?? '') === 'modern' ? 'selected' : '' ?>>Modern</option>
                    <option value="classic" <?= ($appearance['theme_name'] ?? '') === 'classic' ? 'selected' : '' ?>>Classic</option>
                    <option value="minimal" <?= ($appearance['theme_name'] ?? '') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                </select>
            </div>

            <div class="form-group">
                <label>Theme Color</label>
                <input type="color" name="theme_color" class="form-control" value="<?= htmlspecialchars($appearance['theme_color'] ?? '#3b82f6') ?>">
                <small class="form-text">Choose your brand's primary color</small>
            </div>

            <div class="form-group">
                <label>Store Logo</label>
                <div class="upload-info">
                    <p>Upload your store logo (recommended size: 200x200px)</p>
                    <p class="text-muted"><em>Logo upload functionality coming soon</em></p>
                </div>
            </div>

            <div class="form-group">
                <label>Store Banner</label>
                <div class="upload-info">
                    <p>Upload your store banner (recommended size: 1200x300px)</p>
                    <p class="text-muted"><em>Banner upload functionality coming soon</em></p>
                </div>
            </div>

            <div class="preview-section">
                <h3>Preview</h3>
                <div class="store-preview" id="storePreview">
                    <div class="preview-header" style="background-color: <?= htmlspecialchars($appearance['theme_color'] ?? '#3b82f6') ?>">
                        <h4><?= htmlspecialchars($vendorInfo['business_name']) ?></h4>
                    </div>
                    <div class="preview-content">
                        <p>Your store will use this theme and color</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Appearance Settings</button>
        </form>
    </div>
</div>

<style>
.vendor-header{margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e5e7eb}
.vendor-nav a{color:#6b7280;text-decoration:none;font-weight:500}
.vendor-header h1{margin:10px 0 5px 0}.vendor-header p{color:#6b7280;margin:0}
.settings-card{background:white;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);padding:30px}
.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:5px;color:#374151;font-weight:500}
.form-control{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px}
.form-text{display:block;margin-top:5px;color:#6b7280;font-size:13px}
.upload-info{padding:15px;background:#f9fafb;border:1px dashed #d1d5db;border-radius:6px;text-align:center}
.upload-info p{margin:5px 0}
.text-muted{color:#9ca3af}
.preview-section{margin-top:30px;padding-top:30px;border-top:1px solid #e5e7eb}
.preview-section h3{margin-bottom:15px}
.store-preview{border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;max-width:400px}
.preview-header{padding:20px;color:white;text-align:center}
.preview-header h4{margin:0;color:white}
.preview-content{padding:20px;background:white}
.alert{padding:15px;border-radius:6px;margin-bottom:20px}
.alert-success{background:#dcfce7;color:#166534;border:1px solid #86efac}
.alert-danger{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
</style>

<script>
document.querySelector('input[name="theme_color"]').addEventListener('input', function(e) {
    document.querySelector('.preview-header').style.backgroundColor = e.target.value;
});
</script>

<?php includeFooter(); ?>
