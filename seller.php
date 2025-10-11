<?php
/**
 * Seller Profile Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Get seller ID from URL
$sellerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($sellerId <= 0) {
    header('Location: /products.php');
    exit;
}

// Initialize models
$vendorModel = new Vendor();
$productModel = new Product();

// Get vendor/seller information
$vendor = $vendorModel->find($sellerId);

if (!$vendor) {
    header('HTTP/1.1 404 Not Found');
    echo "Seller not found";
    exit;
}

// Get seller's products
$sellerProducts = [];
try {
    $stmt = Database::getInstance()->getConnection()->prepare("
        SELECT p.* 
        FROM products p 
        WHERE p.vendor_id = ? AND p.status = 'active'
        ORDER BY p.created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$sellerId]);
    $sellerProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching seller products: " . $e->getMessage());
}

// Page title
$pageTitle = htmlspecialchars($vendor['business_name'] ?? 'Seller Profile') . ' - Feza Marketplace';

// Include header
if (file_exists(__DIR__ . '/templates/header.php')) {
    include __DIR__ . '/templates/header.php';
} elseif (file_exists(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
} else {
    echo "<!DOCTYPE html><html><head><title>{$pageTitle}</title></head><body>";
}
?>

<style>
.seller-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 20px 24px;
}

.seller-header {
    background: #fff;
    padding: 24px;
    border-radius: 8px;
    border: 1px solid #e5e5e5;
    margin-bottom: 24px;
}

.seller-name {
    font-size: 28px;
    font-weight: 600;
    margin: 0 0 12px 0;
    color: #191919;
}

.seller-info {
    color: #707070;
    font-size: 14px;
    margin-bottom: 8px;
}

.seller-description {
    margin-top: 16px;
    line-height: 1.6;
    color: #191919;
}

.products-section {
    margin-top: 32px;
}

.section-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
    color: #191919;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}

.product-card {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 12px;
    text-decoration: none;
    color: #191919;
    transition: box-shadow 0.2s;
}

.product-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.product-card img {
    width: 100%;
    height: 180px;
    object-fit: contain;
    margin-bottom: 12px;
}

.product-name {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-price {
    font-size: 18px;
    font-weight: 600;
    color: #e53238;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #707070;
}
</style>

<div class="seller-container">
    <div class="seller-header">
        <h1 class="seller-name"><?= htmlspecialchars($vendor['business_name'] ?? 'Unknown Seller'); ?></h1>
        
        <?php if (!empty($vendor['status'])): ?>
            <div class="seller-info">
                Status: <strong><?= htmlspecialchars(ucfirst($vendor['status'])); ?></strong>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($vendor['created_at'])): ?>
            <div class="seller-info">
                Member since: <?= date('F Y', strtotime($vendor['created_at'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($vendor['description'])): ?>
            <div class="seller-description">
                <?= nl2br(htmlspecialchars($vendor['description'])); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="products-section">
        <h2 class="section-title">Products from this seller</h2>
        
        <?php if (!empty($sellerProducts)): ?>
            <div class="products-grid">
                <?php foreach ($sellerProducts as $product): ?>
                    <a href="/product.php?id=<?= $product['id']; ?>" class="product-card">
                        <img src="<?= getProductImageUrl($product['image_url'] ?? ''); ?>" 
                             alt="<?= htmlspecialchars($product['name']); ?>">
                        <div class="product-name"><?= htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">$<?= number_format($product['price'], 2); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>This seller has no products available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
if (file_exists(__DIR__ . '/templates/footer.php')) {
    include __DIR__ . '/templates/footer.php';
} elseif (file_exists(__DIR__ . '/includes/footer.php')) {
    include __DIR__ . '/includes/footer.php';
} else {
    echo "</body></html>";
}
?>
