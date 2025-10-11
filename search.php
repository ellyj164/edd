<?php
/**
 * Enhanced Search Page with Mobile-First Design
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$query = sanitizeInput($_GET['q'] ?? '');
$category = sanitizeInput($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$sort = sanitizeInput($_GET['sort'] ?? 'relevance');
$limit = PRODUCTS_PER_PAGE ?? 20;
$offset = ($page - 1) * $limit;

// Redirect to products page if no query
if (empty($query)) {
    redirect('/products.php');
}

$product = new Product();
$products = [];
$totalProducts = 0;
$totalPages = 0;

// Enhanced search with category filtering - with error handling
$categoryId = null;
if (!empty($category)) {
    try {
        // Try to get database connection first
        if (function_exists('db') && db()) {
            $categoryModel = new Category();
            $categoryData = $categoryModel->findBySlug($category);
            if ($categoryData) {
                $categoryId = $categoryData['id'];
            }
        }
    } catch (Exception $e) {
        error_log("Category search failed: " . $e->getMessage());
        // Continue without category filtering
    }
}

try {
    // Try to get database connection first
    if (function_exists('db') && db()) {
        $products = $product->search($query, $categoryId, $limit, $offset);
        
        // Get total count for pagination
        $allResults = $product->search($query, $categoryId, 1000, 0);
        $totalProducts = count($allResults);
        $totalPages = ceil($totalProducts / $limit);
    }
} catch (Exception $e) {
    error_log("Product search failed: " . $e->getMessage());
    // Set empty results for graceful degradation
    $products = [];
    $totalProducts = 0;
    $totalPages = 0;
}

$page_title = "Search Results for \"$query\"";
$meta_description = "Find $query at FezaMarket. Shop from thousands of products with free shipping and great prices.";

includeHeader($page_title);
?>

<!-- Search Results Page -->
<div class="search-results-page">
    <div class="container">
        <!-- Search Header -->
        <div class="search-header">
            <div class="search-info">
                <h1 class="search-title">Search Results</h1>
                <p class="search-meta">
                    Found <?php echo number_format($totalProducts); ?> results for 
                    <strong>"<?php echo htmlspecialchars($query); ?>"</strong>
                    <?php if (!empty($category)): ?>
                        in <strong><?php echo htmlspecialchars(ucwords($category)); ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Mobile Search Filter Bar -->
            <div class="search-filters-mobile">
                <div class="filter-row">
                    <!-- Sort Dropdown -->
                    <select id="sortSelect" class="filter-select" onchange="updateSort(this.value)">
                        <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Best Match</option>
                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Customer Rating</option>
                    </select>
                    
                    <!-- Category Filter -->
                    <select id="categorySelect" class="filter-select" onchange="updateCategory(this.value)">
                        <option value="">All Categories</option>
                        <option value="electronics" <?php echo $category === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                        <option value="fashion" <?php echo $category === 'fashion' ? 'selected' : ''; ?>>Fashion</option>
                        <option value="home" <?php echo $category === 'home' ? 'selected' : ''; ?>>Home & Garden</option>
                        <option value="sports" <?php echo $category === 'sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="motors" <?php echo $category === 'motors' ? 'selected' : ''; ?>>Motors</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Search Results -->
        <?php if (empty($products)): ?>
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No products found</h3>
                <p class="no-results-text">
                    Try different keywords or browse our categories below.
                </p>
                <div class="search-suggestions">
                    <h4>Suggestions:</h4>
                    <ul>
                        <li>Check your spelling</li>
                        <li>Try more general keywords</li>
                        <li>Try different keywords</li>
                        <li>Browse our categories</li>
                    </ul>
                </div>
                <div class="browse-categories">
                    <a href="/category.php?cat=electronics" class="category-suggestion">Electronics</a>
                    <a href="/category.php?cat=fashion" class="category-suggestion">Fashion</a>
                    <a href="/category.php?cat=home" class="category-suggestion">Home & Garden</a>
                    <a href="/products.php" class="btn btn-primary">Browse All Products</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Products Grid -->
            <div class="search-results-grid">
                <?php foreach ($products as $prod): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="/product.php?id=<?php echo $prod['id']; ?>">
                                <img src="<?php echo getSafeProductImageUrl($prod); ?>" 
                                     alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                     loading="lazy">
                            </a>
                            <button class="wishlist-btn" onclick="toggleWishlist(<?php echo $prod['id']; ?>)">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-title">
                                <a href="/product.php?id=<?php echo $prod['id']; ?>">
                                    <?php echo htmlspecialchars($prod['name']); ?>
                                </a>
                            </h3>
                            
                            <?php if (isset($prod['vendor_name']) && $prod['vendor_name']): ?>
                                <p class="product-seller">by <?php echo htmlspecialchars($prod['vendor_name']); ?></p>
                            <?php endif; ?>
                            
                            <div class="product-price">
                                <span class="current-price">$<?php echo number_format($prod['price'], 2); ?></span>
                                <?php if (isset($prod['compare_price']) && $prod['compare_price'] > $prod['price']): ?>
                                    <span class="original-price">$<?php echo number_format($prod['compare_price'], 2); ?></span>
                                    <span class="discount-badge">
                                        <?php echo round((($prod['compare_price'] - $prod['price']) / $prod['compare_price']) * 100); ?>% OFF
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <button type="button" class="btn btn-primary add-to-cart-btn" onclick="addToCart(<?php echo $prod['id']; ?>)">
                                    Add to Cart
                                </button>
                                <a href="/product.php?id=<?php echo $prod['id']; ?>" class="btn btn-outline">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <nav class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="<?php echo buildSearchUrl($query, $category, $sort, $page - 1); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="<?php echo buildSearchUrl($query, $category, $sort, $i); ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo buildSearchUrl($query, $category, $sort, $page + 1); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                    
                    <div class="pagination-info">
                        Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalProducts); ?> 
                        of <?php echo number_format($totalProducts); ?> results
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Mobile-First Search Results Styles */
.search-results-page {
    margin: 1rem 0;
}

.search-header {
    margin-bottom: 1.5rem;
}

.search-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.search-meta {
    color: #6b7280;
    margin: 0;
    font-size: 0.9rem;
}

.search-filters-mobile {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.filter-row {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.filter-select {
    flex: 1;
    min-width: 140px;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 0.9rem;
}

/* Search Results Grid - Mobile First */
.search-results-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

@media (max-width: 480px) {
    .search-results-grid {
        grid-template-columns: 1fr;
    }
}

@media (min-width: 768px) {
    .search-results-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .search-results-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
    transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.product-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.wishlist-btn {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.wishlist-btn:hover {
    background: white;
    color: #dc2626;
    transform: scale(1.1);
}

.product-info {
    padding: 1rem;
}

.product-title {
    margin: 0 0 0.5rem 0;
    font-size: 0.9rem;
    line-height: 1.3;
    font-weight: 600;
}

.product-title a {
    color: #1f2937;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-title a:hover {
    color: #3b82f6;
}

.product-seller {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0 0 0.75rem 0;
}

.product-price {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.current-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #dc2626;
}

.original-price {
    font-size: 0.9rem;
    color: #6b7280;
    text-decoration: line-through;
}

.discount-badge {
    background: #dc2626;
    color: white;
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-weight: 600;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

/* No Results */
.no-results {
    text-align: center;
    padding: 3rem 1rem;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.no-results h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #374151;
}

.no-results-text {
    color: #6b7280;
    margin-bottom: 2rem;
}

.search-suggestions {
    text-align: left;
    max-width: 300px;
    margin: 2rem auto;
}

.search-suggestions h4 {
    margin-bottom: 1rem;
    color: #374151;
}

.search-suggestions ul {
    list-style: none;
    padding: 0;
}

.search-suggestions li {
    padding: 0.25rem 0;
    color: #6b7280;
}

.browse-categories {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.category-suggestion {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border-radius: 20px;
    text-decoration: none;
    color: #374151;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.category-suggestion:hover {
    background: #e5e7eb;
    color: #1f2937;
}

/* Pagination */
.pagination-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
}

.pagination {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
    justify-content: center;
}

.page-link {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: all 0.2s;
    min-width: 44px;
    text-align: center;
}

.page-link:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.page-link.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.pagination-info {
    font-size: 0.9rem;
    color: #6b7280;
    text-align: center;
}

/* Desktop Improvements */
@media (min-width: 768px) {
    .search-title {
        font-size: 2rem;
    }
    
    .filter-row {
        justify-content: flex-end;
        flex-wrap: nowrap;
    }
    
    .filter-select {
        flex: none;
        width: 200px;
    }
    
    .product-info {
        padding: 1.25rem;
    }
    
    .product-title {
        font-size: 1rem;
    }
    
    .current-price {
        font-size: 1.25rem;
    }
}
</style>

<script>
// Filter and sort functionality
function updateSort(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}

function updateCategory(categoryValue) {
    const url = new URL(window.location);
    if (categoryValue) {
        url.searchParams.set('category', categoryValue);
    } else {
        url.searchParams.delete('category');
    }
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}

// Add to cart functionality
function addToCart(productId) {
    fetch('/api/cart-add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product added to cart!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        showNotification('Error adding to cart', 'error');
    });
}

// Wishlist toggle
function toggleWishlist(productId) {
    fetch('/api/wishlist-toggle.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = event.target.closest('.wishlist-btn');
            const icon = btn.querySelector('i');
            if (data.added) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                showNotification('Added to wishlist!', 'success');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                showNotification('Removed from wishlist', 'info');
            }
        } else {
            if (data.login_required) {
                window.location.href = '/login.php';
            } else {
                showNotification(data.message || 'Error updating wishlist', 'error');
            }
        }
    })
    .catch(error => {
        showNotification('Error updating wishlist', 'error');
    });
}

function showNotification(message, type) {
    // Simple notification - you can enhance this
    alert(message);
}

function updateCartCount() {
    // Update cart count in header if needed
    fetch('/api/cart-count.php')
        .then(response => response.json())
        .then(data => {
            const badges = document.querySelectorAll('.notification-badge, .mobile-nav-badge');
            badges.forEach(badge => {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            });
        })
        .catch(error => {
            console.log('Error updating cart count');
        });
}
</script>

<?php

// Helper function to build search URLs with parameters
function buildSearchUrl($query, $category, $sort, $page) {
    $params = ['q' => $query];
    if (!empty($category)) $params['category'] = $category;
    if (!empty($sort) && $sort !== 'relevance') $params['sort'] = $sort;
    if ($page > 1) $params['page'] = $page;
    return '/search.php?' . http_build_query($params);
}

includeFooter();
?>