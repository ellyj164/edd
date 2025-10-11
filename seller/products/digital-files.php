<?php
/**
 * Manage Digital Product Files
 * Allows sellers to upload and manage downloadable files for digital products
 */

require_once __DIR__ . '/../../includes/init.php';

Session::requireLogin();

$vendor = new Vendor();
$vendorInfo = $vendor->findByUserId(Session::getUserId());

if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if ($productId <= 0) {
    redirect('/seller/products/');
}

// Verify product belongs to vendor and is digital
$product = new Product();
$productData = $product->find($productId);

if (!$productData || $productData['vendor_id'] != $vendorId) {
    redirect('/seller/products/');
}

if (!$productData['is_digital']) {
    $_SESSION['error'] = 'This is not a digital product';
    redirect('/seller/products/edit.php?id=' . $productId);
}

$errors = [];
$success = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else if (!isset($_FILES['digital_file']) || $_FILES['digital_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please select a file to upload';
    } else {
        $file = $_FILES['digital_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
        } else {
            $version = trim($_POST['version'] ?? '1.0');
            $downloadLimit = !empty($_POST['download_limit']) ? (int)$_POST['download_limit'] : null;
            $expiryDays = !empty($_POST['expiry_days']) ? (int)$_POST['expiry_days'] : null;
            
            // Create upload directory
            $uploadDir = __DIR__ . '/../../uploads/digital_products/' . $vendorId . '/' . $productId;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate secure filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $safeFilename = 'digital_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $filePath = $uploadDir . '/' . $safeFilename;
            $publicPath = '/uploads/digital_products/' . $vendorId . '/' . $productId . '/' . $safeFilename;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Insert into database
                try {
                    $db = db();
                    $stmt = $db->prepare("
                        INSERT INTO digital_products 
                        (product_id, file_name, file_path, file_size, file_type, version, download_limit, expiry_days) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $result = $stmt->execute([
                        $productId,
                        $file['name'],
                        $publicPath,
                        $file['size'],
                        $file['type'],
                        $version,
                        $downloadLimit,
                        $expiryDays
                    ]);
                    
                    if ($result) {
                        $success = 'Digital file uploaded successfully!';
                    } else {
                        $errors[] = 'Failed to save file information';
                        unlink($filePath);
                    }
                } catch (Exception $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                    unlink($filePath);
                }
            } else {
                $errors[] = 'Failed to save file';
            }
        }
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $digitalFileId = (int)($_POST['digital_file_id'] ?? 0);
        
        try {
            $db = db();
            $stmt = $db->prepare("SELECT * FROM digital_products WHERE id = ? AND product_id = ?");
            $stmt->execute([$digitalFileId, $productId]);
            $digitalFile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($digitalFile) {
                // Delete file from filesystem
                $filePath = __DIR__ . '/../../' . ltrim($digitalFile['file_path'], '/');
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                // Delete from database
                $stmt = $db->prepare("DELETE FROM digital_products WHERE id = ?");
                if ($stmt->execute([$digitalFileId])) {
                    $success = 'File deleted successfully';
                } else {
                    $errors[] = 'Failed to delete file';
                }
            } else {
                $errors[] = 'File not found';
            }
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

// Get existing digital files
$digitalFiles = [];
try {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM digital_products WHERE product_id = ? ORDER BY created_at DESC");
    $stmt->execute([$productId]);
    $digitalFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Error loading digital files: ' . $e->getMessage());
}

$page_title = 'Manage Digital Files - ' . $productData['name'];
includeHeader($page_title);
?>

<div class="container" style="padding: 20px; max-width: 1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2">Manage Digital Files</h1>
            <p class="text-muted">Product: <?= htmlspecialchars($productData['name']); ?></p>
        </div>
        <div>
            <a href="/seller/products/edit.php?id=<?= $productId; ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Product
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Upload New File -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-upload me-2"></i>Upload New Digital File
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?= csrfTokenInput(); ?>
                <input type="hidden" name="action" value="upload">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Digital File *</label>
                        <input type="file" name="digital_file" class="form-control" required>
                        <small class="text-muted">Max file size: <?= ini_get('upload_max_filesize'); ?></small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-control" value="1.0" placeholder="1.0">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Download Limit</label>
                        <input type="number" name="download_limit" class="form-control" placeholder="Leave empty for unlimited">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Link Expiry (days after purchase)</label>
                        <input type="number" name="expiry_days" class="form-control" placeholder="Leave empty for no expiry">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload File
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Files -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-file-download me-2"></i>Uploaded Digital Files
        </div>
        <div class="card-body">
            <?php if (empty($digitalFiles)): ?>
                <p class="text-muted text-center py-4">No digital files uploaded yet</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Version</th>
                                <th>Size</th>
                                <th>Download Limit</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($digitalFiles as $file): ?>
                                <tr>
                                    <td><?= htmlspecialchars($file['file_name']); ?></td>
                                    <td><?= htmlspecialchars($file['version']); ?></td>
                                    <td><?= formatFileSize($file['file_size']); ?></td>
                                    <td><?= $file['download_limit'] ? htmlspecialchars($file['download_limit']) : 'Unlimited'; ?></td>
                                    <td><?= $file['expiry_days'] ? htmlspecialchars($file['expiry_days']) . ' days' : 'No expiry'; ?></td>
                                    <td>
                                        <?php if ($file['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($file['created_at'])); ?></td>
                                    <td>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                            <?= csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="digital_file_id" value="<?= $file['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

includeFooter();
?>
