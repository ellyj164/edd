<?php
/**
 * Enhanced Vendor Product Management
 * E-Commerce Platform - Comprehensive CRUD with Variants and Bulk Upload
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php'; // Seller authentication guard

// Initialize database connection
$db = db();

$vendor = new Vendor();
$product = new Product();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Handle filters and search
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'created_at_desc';

// Build query with filters
$whereConditions = ['vendor_id = ?'];
$params = [$vendorId];

if (!empty($search)) {
    $whereConditions[] = '(name LIKE ? OR sku LIKE ? OR description LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $whereConditions[] = 'status = ?';
    $params[] = $status;
}

if (!empty($category)) {
    $whereConditions[] = 'category_id = ?';
    $params[] = $category;
}

// Handle sorting
$orderClause = match($sort) {
    'name_asc' => 'ORDER BY name ASC',
    'name_desc' => 'ORDER BY name DESC', 
    'price_asc' => 'ORDER BY price ASC',
    'price_desc' => 'ORDER BY price DESC',
    'stock_asc' => 'ORDER BY stock_quantity ASC',
    'stock_desc' => 'ORDER BY stock_quantity DESC',
    'sales_desc' => 'ORDER BY purchase_count DESC',
    'created_at_asc' => 'ORDER BY created_at ASC',
    default => 'ORDER BY created_at DESC'
};

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get products with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$productsQuery = "
    SELECT p.*, c.name as category_name,
           COALESCE(pv.variant_count, 0) as variant_count,
           COALESCE(pi.image_count, 0) as image_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN (
        SELECT product_id, COUNT(*) as variant_count 
        FROM product_variants 
        GROUP BY product_id
    ) pv ON p.id = pv.product_id
    LEFT JOIN (
        SELECT product_id, COUNT(*) as image_count 
        FROM product_images 
        GROUP BY product_id
    ) pi ON p.id = pi.product_id
    $whereClause
    $orderClause
    LIMIT $limit OFFSET $offset
";

$productsStmt = $db->prepare($productsQuery);
$productsStmt->execute($params);
$products = $productsStmt->fetchAll();

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM products $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Get categories for filter dropdown
$categoriesQuery = "SELECT DISTINCT c.id, c.name FROM categories c 
                   JOIN products p ON c.id = p.category_id 
                   WHERE p.vendor_id = ? AND c.status = 'active'
                   ORDER BY c.name";
$categoriesStmt = $db->prepare($categoriesQuery);
$categoriesStmt->execute([$vendorId]);
$categories = $categoriesStmt->fetchAll();

// Get inventory alerts
$alertsQuery = "
    SELECT COUNT(*) as low_stock_count 
    FROM products 
    WHERE vendor_id = ? AND stock_quantity <= min_stock_level AND status = 'active'
";
$alertsStmt = $db->prepare($alertsQuery);
$alertsStmt->execute([$vendorId]);
$alerts = $alertsStmt->fetch();

$page_title = 'Manage Products - Seller Center';
includeHeader($page_title);
?>

<div class="seller-products-page">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <nav class="breadcrumb">
                    <a href="/seller/dashboard.php">Dashboard</a>
                    <span>/</span>
                    <span>Products</span>
                </nav>
                <h1>Manage Products</h1>
                <p class="subtitle">Create and manage your product catalog</p>
            </div>
            <div class="header-actions">
                <a href="/seller/products/bulk-upload.php" class="btn btn-outline">
                    📄 Bulk Upload
                </a>
                <a href="/seller/products/add.php" class="btn btn-primary">
                    + Add Product
                </a>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($alerts['low_stock_count'] > 0): ?>
        <div class="alert alert-warning">
            <div class="alert-icon">⚠️</div>
            <div class="alert-content">
                <strong>Low Stock Alert!</strong>
                You have <?php echo $alerts['low_stock_count']; ?> product(s) with low stock levels.
                <a href="?status=active&sort=stock_asc" class="alert-link">View products</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="search-group">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search products..." class="search-input">
                <button type="submit" class="search-btn">🔍</button>
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>

            <div class="filter-group">
                <select name="category" class="filter-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <select name="sort" class="filter-select">
                    <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z-A</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price Low-High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price High-Low</option>
                    <option value="stock_asc" <?php echo $sort === 'stock_asc' ? 'selected' : ''; ?>>Stock Low-High</option>
                    <option value="sales_desc" <?php echo $sort === 'sales_desc' ? 'selected' : ''; ?>>Best Selling</option>
                </select>
            </div>

            <button type="submit" class="btn btn-outline">Apply Filters</button>
            <?php if (!empty($search) || !empty($status) || !empty($category) || $sort !== 'created_at_desc'): ?>
                <a href="/seller/products.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Products Grid -->
    <div class="products-section">
        <?php if (!empty($products)): ?>
            <div class="products-header">
                <div class="products-count">
                    Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                </div>
                <div class="view-toggles">
                    <button class="view-toggle active" data-view="grid">⊞</button>
                    <button class="view-toggle" data-view="list">☰</button>
                </div>
            </div>

            <div class="products-grid" id="productsContainer">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                        <div class="product-image">
                            <?php 
                            $imageUrl = '/api/products/image.php?id=' . $product['id'] . '&size=medium';
                            if (!empty($product['image_url'])) {
                                $imageUrl = htmlspecialchars($product['image_url']);
                            }
                            ?>
                            <img src="<?php echo $imageUrl; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy">
                            <div class="product-status status-<?php echo strtolower($product['status']); ?>">
                                <?php echo ucfirst($product['status']); ?>
                            </div>
                            <?php if ($product['featured']): ?>
                                <div class="featured-badge">⭐ Featured</div>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <div class="product-header">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="product-actions-menu">
                                    <button class="menu-trigger">⋮</button>
                                    <div class="actions-dropdown">
                                        <a href="/seller/products/edit.php?id=<?php echo $product['id']; ?>">✏️ Edit</a>
                                        <a href="/product.php?id=<?php echo $product['id']; ?>" target="_blank">👁️ Preview</a>
                                        <button onclick="duplicateProduct(<?php echo $product['id']; ?>)">📋 Duplicate</button>
                                        <button onclick="toggleProductStatus(<?php echo $product['id']; ?>, '<?php echo $product['status'] === 'active' ? 'inactive' : 'active'; ?>')">
                                            <?php echo $product['status'] === 'active' ? '⏸️ Deactivate' : '▶️ Activate'; ?>
                                        </button>
                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="danger">🗑️ Delete</button>
                                    </div>
                                </div>
                            </div>

                            <div class="product-meta">
                                <span class="sku">SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></span>
                                <span class="category"><?php echo htmlspecialchars($product['category_name'] ?: 'Uncategorized'); ?></span>
                            </div>

                            <div class="product-pricing">
                                <div class="price-info">
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                        <span class="sale-price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                        <span class="original-price">$<?php echo number_format($product['price'], 2); ?></span>
                                        <span class="discount">
                                            -<?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="current-price">$<?php echo number_format($product['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="product-stats">
                                <div class="stat-group">
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $product['stock_quantity']; ?></span>
                                        <span class="stat-label">Stock</span>
                                        <?php if ($product['stock_quantity'] <= $product['min_stock_level']): ?>
                                            <span class="low-stock-indicator">⚠️</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $product['view_count']; ?></span>
                                        <span class="stat-label">Views</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $product['purchase_count']; ?></span>
                                        <span class="stat-label">Sales</span>
                                    </div>
                                </div>
                            </div>

                            <?php if ($product['review_count'] > 0): ?>
                                <div class="product-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $rating = $product['average_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) echo '⭐';
                                            else echo '☆';
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-text">
                                        <?php echo number_format($rating, 1); ?> (<?php echo $product['review_count']; ?> reviews)
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="product-features">
                                <?php if ($product['variant_count'] > 0): ?>
                                    <span class="feature-tag">📦 <?php echo $product['variant_count']; ?> variants</span>
                                <?php endif; ?>
                                <?php if ($product['image_count'] > 1): ?>
                                    <span class="feature-tag">🖼️ <?php echo $product['image_count']; ?> images</span>
                                <?php endif; ?>
                                <?php if ($product['digital']): ?>
                                    <span class="feature-tag">💾 Digital</span>
                                <?php endif; ?>
                                <?php if ($product['downloadable']): ?>
                                    <span class="feature-tag">⬇️ Downloadable</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="product-actions">
                            <a href="/seller/products/edit.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-outline">Edit</a>
                            <a href="/product.php?id=<?php echo $product['id']; ?>" 
                               target="_blank" class="btn btn-sm btn-ghost">Preview</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $baseUrl = '/seller/products.php?' . http_build_query($queryParams);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl; ?>&page=1" class="page-link">First</a>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $i; ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>" class="page-link">Next →</a>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $totalPages; ?>" class="page-link">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h2>No products found</h2>
                <?php if (!empty($search) || !empty($status) || !empty($category)): ?>
                    <p>No products match your current filters.</p>
                    <a href="/seller/products.php" class="btn btn-outline">Clear Filters</a>
                <?php else: ?>
                    <p>Start building your store by adding your first product.</p>
                    <div class="empty-actions">
                        <a href="/seller/products/add.php" class="btn btn-primary">Add Your First Product</a>
                        <a href="/seller/products/bulk-upload.php" class="btn btn-outline">Upload Products in Bulk</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.seller-products-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.breadcrumb {
    font-size: 14px;
    margin-bottom: 8px;
    opacity: 0.8;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.page-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
}

.subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.alert-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
}

.alert-icon {
    font-size: 20px;
}

.alert-link {
    color: #92400e;
    font-weight: 600;
    text-decoration: underline;
}

.filters-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.filters-form {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.search-group {
    display: flex;
    flex: 1;
    min-width: 300px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px 0 0 8px;
    font-size: 14px;
}

.search-btn {
    padding: 12px 16px;
    background: #3b82f6;
    color: white;
    border: 1px solid #3b82f6;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
}

.filter-group {
    display: flex;
    align-items: center;
}

.filter-select {
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    min-width: 150px;
}

.products-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.products-count {
    color: #6b7280;
    font-size: 14px;
}

.view-toggles {
    display: flex;
    gap: 4px;
}

.view-toggle {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
}

.view-toggle.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.product-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: white;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.product-image {
    position: relative;
    height: 200px;
    background: #f9fafb;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-status {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active { background: #dcfce7; color: #166534; }
.status-inactive { background: #f3f4f6; color: #6b7280; }
.status-draft { background: #fef3c7; color: #92400e; }
.status-archived { background: #fee2e2; color: #dc2626; }

.featured-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #fbbf24;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.product-info {
    padding: 20px;
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 12px;
}

.product-name {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    flex: 1;
    line-height: 1.4;
}

.product-actions-menu {
    position: relative;
}

.menu-trigger {
    padding: 4px 8px;
    background: none;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    color: #6b7280;
}

.menu-trigger:hover {
    background: #f3f4f6;
}

.actions-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 8px 0;
    min-width: 160px;
    z-index: 10;
    display: none;
}

.product-actions-menu:hover .actions-dropdown {
    display: block;
}

.actions-dropdown a,
.actions-dropdown button {
    display: block;
    width: 100%;
    padding: 8px 16px;
    text-align: left;
    color: #374151;
    text-decoration: none;
    background: none;
    border: none;
    font-size: 14px;
    cursor: pointer;
}

.actions-dropdown a:hover,
.actions-dropdown button:hover {
    background: #f3f4f6;
}

.actions-dropdown .danger {
    color: #dc2626;
}

.product-meta {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 12px;
}

.product-pricing {
    margin-bottom: 16px;
}

.price-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.current-price,
.sale-price {
    font-size: 18px;
    font-weight: 700;
    color: #dc2626;
}

.original-price {
    font-size: 14px;
    color: #6b7280;
    text-decoration: line-through;
}

.discount {
    background: #dc2626;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.product-stats {
    margin-bottom: 16px;
}

.stat-group {
    display: flex;
    gap: 20px;
}

.stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 12px;
    position: relative;
}

.stat-value {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.stat-label {
    color: #6b7280;
    margin-top: 2px;
}

.low-stock-indicator {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 10px;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    font-size: 12px;
}

.rating-stars {
    color: #fbbf24;
}

.rating-text {
    color: #6b7280;
}

.product-features {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 16px;
}

.feature-tag {
    display: inline-block;
    padding: 2px 6px;
    background: #e5e7eb;
    color: #374151;
    border-radius: 4px;
    font-size: 11px;
}

.product-actions {
    display: flex;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.page-link {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s ease;
}

.page-link:hover {
    background: #f3f4f6;
}

.page-link.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-state h2 {
    color: #1f2937;
    margin-bottom: 12px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 24px;
}

.empty-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

/* List view styles */
.products-grid.list-view {
    grid-template-columns: 1fr;
}

.products-grid.list-view .product-card {
    display: flex;
    height: 150px;
}

.products-grid.list-view .product-image {
    width: 200px;
    height: 150px;
    flex-shrink: 0;
}

.products-grid.list-view .product-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

@media (max-width: 768px) {
    .seller-products-page {
        padding: 16px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-group {
        min-width: auto;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .products-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
}
</style>

<script>
// View toggle functionality
document.querySelectorAll('.view-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
        document.querySelectorAll('.view-toggle').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const view = this.dataset.view;
        const container = document.getElementById('productsContainer');
        
        if (view === 'list') {
            container.classList.add('list-view');
        } else {
            container.classList.remove('list-view');
        }
    });
});

// Product management functions
function toggleProductStatus(productId, status) {
    if (confirm(`Are you sure you want to ${status === 'active' ? 'activate' : 'deactivate'} this product?`)) {
        fetch('/api/seller/products/status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo csrfToken(); ?>'
            },
            body: JSON.stringify({
                product_id: productId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating product status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error updating product status: ' + error.message);
        });
    }
}

function duplicateProduct(productId) {
    if (confirm('Are you sure you want to duplicate this product?')) {
        fetch('/api/seller/products/duplicate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo csrfToken(); ?>'
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/seller/products/edit.php?id=' + data.new_product_id;
            } else {
                alert('Error duplicating product: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error duplicating product: ' + error.message);
        });
    }
}

function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        fetch('/api/seller/products/delete.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo csrfToken(); ?>'
            },
            body: JSON.stringify({
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting product: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error deleting product: ' + error.message);
        });
    }
}

// Auto-submit filters on change
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>

<?php includeFooter(); ?>