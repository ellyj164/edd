<?php
/**
 * Simple Search Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$query = sanitizeInput($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

if (empty($query)) {
    redirect('/products.php');
}

$product = new Product();
$limit = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $limit;

$products = $product->search($query, $limit, $offset);

// For total count - simplified
$allResults = $product->search($query, 1000, 0);
$totalProducts = count($allResults);

$page_title = "Search Results for \"$query\"";
includeHeader($page_title);
?>

<div class="container">
    <h1>Search Results</h1>
    <p class="text-muted mb-4">
        Found <?php echo $totalProducts; ?> results for "<?php echo htmlspecialchars($query); ?>"
    </p>
    
    <?php if (empty($products)): ?>
        <div class="text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🔍</div>
            <h3>No products found</h3>
            <p class="text-muted">Try different keywords or browse our categories.</p>
            <a href="/products.php" class="btn">Browse All Products</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $prod): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="/product.php?id=<?php echo $prod['id']; ?>">
                            <img src="<?php echo getSafeProductImageUrl($prod); ?>" 
                                 alt="<?php echo htmlspecialchars($prod['name']); ?>">
                        </a>
                    </div>
                    
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="/product.php?id=<?php echo $prod['id']; ?>">
                                <?php echo htmlspecialchars($prod['name']); ?>
                            </a>
                        </h3>
                        
                        <?php if (isset($prod['vendor_name'])): ?>
                            <p class="product-vendor">by <?php echo htmlspecialchars($prod['vendor_name']); ?></p>
                        <?php endif; ?>
                        
                        <p class="product-price"><?php echo formatPrice($prod['price']); ?></p>
                        
                        <div class="product-actions">
                            <button class="btn add-to-cart" data-product-id="<?php echo $prod['id']; ?>">
                                Add to Cart
                            </button>
                            <?php if (Session::isLoggedIn()): ?>
                                <button class="btn btn-outline add-to-wishlist" data-product-id="<?php echo $prod['id']; ?>">
                                    💖
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Simple pagination -->
        <?php if ($totalProducts > $limit): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page - 1; ?>">« Previous</a>
                <?php endif; ?>
                
                <span class="current">Page <?php echo $page; ?> of <?php echo ceil($totalProducts / $limit); ?></span>
                
                <?php if ($page < ceil($totalProducts / $limit)): ?>
                    <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page + 1; ?>">Next »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php includeFooter(); ?>