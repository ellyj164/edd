<?php
/**
 * Customer Digital Product Downloads
 * Secure download page for purchased digital products
 */

require_once __DIR__ . '/includes/init.php';

Session::requireLogin();
$userId = Session::getUserId();

// Get download token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('HTTP/1.1 404 Not Found');
    echo "Invalid download link";
    exit;
}

try {
    $db = db();
    
    // Get download record
    $stmt = $db->prepare("
        SELECT cd.*, p.name as product_name, dp.file_name, dp.file_path, dp.file_size
        FROM customer_downloads cd
        JOIN products p ON cd.product_id = p.id
        JOIN digital_products dp ON cd.digital_product_id = dp.id
        WHERE cd.download_token = ? AND cd.user_id = ?
    ");
    $stmt->execute([$token, $userId]);
    $download = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$download) {
        header('HTTP/1.1 404 Not Found');
        echo "Download not found or unauthorized";
        exit;
    }
    
    // Check if expired
    if ($download['expires_at'] && strtotime($download['expires_at']) < time()) {
        $page_title = 'Download Expired';
        includeHeader($page_title);
        ?>
        <div class="container my-5">
            <div class="alert alert-danger">
                <h4>Download Link Expired</h4>
                <p>This download link has expired. Please contact the seller for assistance.</p>
                <a href="/account.php?tab=orders" class="btn btn-primary">View Your Orders</a>
            </div>
        </div>
        <?php
        includeFooter();
        exit;
    }
    
    // Check download limit
    if ($download['download_limit'] && $download['download_count'] >= $download['download_limit']) {
        $page_title = 'Download Limit Reached';
        includeHeader($page_title);
        ?>
        <div class="container my-5">
            <div class="alert alert-warning">
                <h4>Download Limit Reached</h4>
                <p>You have reached the maximum number of downloads for this product (<?= $download['download_limit']; ?> downloads).</p>
                <p>If you need additional downloads, please contact the seller.</p>
                <a href="/account.php?tab=orders" class="btn btn-primary">View Your Orders</a>
            </div>
        </div>
        <?php
        includeFooter();
        exit;
    }
    
    // If download action is requested
    if (isset($_GET['action']) && $_GET['action'] === 'download') {
        // Update download count
        $stmt = $db->prepare("
            UPDATE customer_downloads 
            SET download_count = download_count + 1,
                last_downloaded_at = NOW(),
                ip_address = ?,
                user_agent = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $download['id']
        ]);
        
        // Serve file
        $filePath = __DIR__ . $download['file_path'];
        
        if (!file_exists($filePath)) {
            header('HTTP/1.1 404 Not Found');
            echo "File not found on server";
            exit;
        }
        
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($download['file_name']) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
        
        // Output file
        readfile($filePath);
        exit;
    }
    
    // Show download page
    $page_title = 'Download: ' . $download['product_name'];
    includeHeader($page_title);
    
    $remainingDownloads = $download['download_limit'] 
        ? ($download['download_limit'] - $download['download_count']) 
        : 'Unlimited';
    
    $expiryDate = $download['expires_at'] ? date('M d, Y', strtotime($download['expires_at'])) : 'No expiry';
    
    ?>
    
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-download me-2"></i>Digital Product Download</h4>
                    </div>
                    <div class="card-body">
                        <h5><?= htmlspecialchars($download['product_name']); ?></h5>
                        
                        <div class="row my-4">
                            <div class="col-md-6">
                                <p><strong>File Name:</strong><br><?= htmlspecialchars($download['file_name']); ?></p>
                                <p><strong>File Size:</strong><br><?= formatFileSize($download['file_size']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Downloads Used:</strong><br><?= $download['download_count']; ?> / <?= $download['download_limit'] ?: 'Unlimited'; ?></p>
                                <p><strong>Link Expires:</strong><br><?= $expiryDate; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($download['last_downloaded_at']): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Last downloaded: <?= date('M d, Y H:i', strtotime($download['last_downloaded_at'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2">
                            <a href="?token=<?= urlencode($token); ?>&action=download" class="btn btn-primary btn-lg">
                                <i class="fas fa-download me-2"></i>Download Now
                            </a>
                            <a href="/account.php?tab=orders" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Orders
                            </a>
                        </div>
                        
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6>Important Information:</h6>
                            <ul class="small mb-0">
                                <li>Save this file to a secure location after downloading</li>
                                <li>Do not share your download link with others</li>
                                <li>This download is for your personal use only</li>
                                <?php if ($download['download_limit']): ?>
                                    <li>You have <?= $remainingDownloads; ?> download(s) remaining</li>
                                <?php endif; ?>
                                <?php if ($download['expires_at']): ?>
                                    <li>This download link will expire on <?= $expiryDate; ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    
} catch (Exception $e) {
    error_log('Download error: ' . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo "An error occurred";
    exit;
}

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
