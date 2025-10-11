<?php
/**
 * Products Listing Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$product = new Product();
$category = new Category();

// Get parameters
$categoryId = (int)($_GET['category'] ?? 0);
$search = sanitizeInput($_GET['q'] ?? '');
$sort = sanitizeInput($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));

// Pagination settings
$limit = PRODUCTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Get products based on filters
$products = [];
$totalProducts = 0;

if ($search) {
    $products = $product->search($search, $limit, $offset);
    // For total count, we'd need a separate count query - simplified here
    $totalProducts = count($product->search($search, 1000, 0)); // Rough count
    $pageTitle = "Search Results for \"$search\"";
} elseif ($categoryId) {
    $products = $product->findByCategory($categoryId, $limit, $offset);
    $totalProducts = $product->count("category_id = $categoryId AND status = 'active'");
    
    $categoryInfo = $category->find($categoryId);
    $pageTitle = $categoryInfo ? "Products in " . $categoryInfo['name'] : "Products";
} else {
    $products = $product->findAll($limit, $offset);
    $totalProducts = $product->count("status = 'active'");
    $pageTitle = "All Products";
}

// Sort products
switch ($sort) {
    case 'price_low':
        usort($products, function($a, $b) { return $a['price'] <=> $b['price']; });
        break;
    case 'price_high':
        usort($products, function($a, $b) { return $b['price'] <=> $a['price']; });
        break;
    case 'name':
        usort($products, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        break;
    case 'newest':
    default:
        // Already sorted by created_at DESC in model
        break;
}

// Get all categories for filter
$categories = $category->getParents();

// Pagination
$pagination = Pagination::create($totalProducts, $limit, $page, '/products.php?' . http_build_query(array_filter([
    'category' => $categoryId ?: null,
    'q' => $search ?: null,
    'sort' => $sort !== 'newest' ? $sort : null
])));

$page_title = $pageTitle;
includeHeader($page_title);
?>

<div class="container">
    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-3">
            <div class="sidebar">
                <h3 class="sidebar-title">Categories</h3>
                <ul class="sidebar-list">
                    <li><a href="/products.php" class="<?php echo !$categoryId ? 'active' : ''; ?>">All Products</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="/products.php?category=<?php echo $cat['id']; ?>" 
                               class="<?php echo $categoryId == $cat['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($search || $categoryId): ?>
                <div class="mt-3">
                    <a href="/products.php" class="btn btn-outline btn-sm">Clear Filters</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-9">
            <!-- Header and Sort -->
            <div class="d-flex justify-between align-center mb-3">
                <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                
                <div class="d-flex align-center">
                    <label for="sort" style="margin-right: 0.5rem;">Sort by:</label>
                    <select id="sort" class="form-control sort-select" style="width: auto;">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                    </select>
                </div>
            </div>
            
            <!-- Products Count -->
            <p class="text-muted mb-3">
                Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
            </p>
            
            <!-- Products Grid -->
            <?php if (empty($products)): ?>
                <div class="text-center" style="padding: 3rem;">
                    <h3>No products found</h3>
                    <p class="text-muted">Try adjusting your search or browse our categories.</p>
                    <a href="/products.php" class="btn">View All Products</a>
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
                                <?php if ($prod['featured']): ?>
                                    <div class="product-badge featured">Featured</div>
                                <?php endif; ?>
                                
                                <?php 
                                // Check if product is sold out
                                $isSoldOut = ($prod['stock_quantity'] <= 0);
                                $totalSold = $prod['purchase_count'] ?? 0;
                                ?>
                                
                                <?php if ($isSoldOut): ?>
                                    <div class="product-badge sold-out">
                                        <i class="fas fa-times-circle"></i> SOLD OUT
                                    </div>
                                <?php elseif ($prod['stock_quantity'] <= ($prod['low_stock_threshold'] ?? 5)): ?>
                                    <div class="product-badge low-stock">
                                        <i class="fas fa-exclamation-triangle"></i> Low Stock
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($totalSold > 0): ?>
                                    <div class="product-sales-info">
                                        <small class="text-muted">
                                            <i class="fas fa-shopping-cart"></i> <?php echo $totalSold; ?> sold
                                        </small>
                                    </div>
                                <?php endif; ?>
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
                                    <?php if ($prod['stock_quantity'] > 0): ?>
                                        <button class="btn add-to-cart" data-product-id="<?php echo $prod['id']; ?>">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                    
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
                
                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_previous']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['previous_page']])); ?>">
                                &laquo; Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php foreach ($pagination['links'] as $link): ?>
                            <?php if ($link['current']): ?>
                                <span class="current"><?php echo $link['page']; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $link['page']])); ?>">
                                    <?php echo $link['page']; ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>">
                                Next &raquo;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php includeFooter(); ?>