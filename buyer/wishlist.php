<?php
/**
 * Buyer Wishlist Management
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
Session::requireLogin();

$db = db();
$userId = Session::getUserId();

// Get or create buyer record
$buyerQuery = "SELECT * FROM buyers WHERE user_id = ?";
$buyerStmt = $db->prepare($buyerQuery);
$buyerStmt->execute([$userId]);
$buyer = $buyerStmt->fetch();

if (!$buyer) {
    $createBuyerQuery = "INSERT INTO buyers (user_id) VALUES (?)";
    $createBuyerStmt = $db->prepare($createBuyerQuery);
    $createBuyerStmt->execute([$userId]);
    $buyerId = $db->lastInsertId();
    
    $buyerStmt->execute([$userId]);
    $buyer = $buyerStmt->fetch();
} else {
    $buyerId = $buyer['id'];
}

// Get wishlist items (graceful fallback for missing table)
$wishlistItems = [];
try {
    $wishlistQuery = "
        SELECT bw.*, p.name, p.price, p.image_url, p.status as product_status,
               CASE WHEN p.stock_quantity > 0 THEN 1 ELSE 0 END as in_stock
        FROM buyer_wishlist bw
        JOIN products p ON bw.product_id = p.id
        WHERE bw.buyer_id = ?
        ORDER BY bw.added_at DESC
    ";
    $wishlistStmt = $db->prepare($wishlistQuery);
    $wishlistStmt->execute([$buyerId]);
    $wishlistItems = $wishlistStmt->fetchAll();
} catch (Exception $e) {
    // Table doesn't exist yet, use regular wishlist table
    try {
        $wishlistQuery = "
            SELECT w.*, p.name, p.price, p.image_url, p.status as product_status,
                   CASE WHEN p.stock_quantity > 0 THEN 1 ELSE 0 END as in_stock
            FROM wishlists w
            JOIN products p ON w.product_id = p.id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ";
        $wishlistStmt = $db->prepare($wishlistQuery);
        $wishlistStmt->execute([$userId]);
        $wishlistItems = $wishlistStmt->fetchAll();
    } catch (Exception $e2) {
        // No wishlist table available
        $wishlistItems = [];
    }
}

$page_title = 'My Wishlist';
includeHeader($page_title);
?>

<div class="buyer-dashboard">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Wishlist</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="badge badge-secondary me-2"><?php echo count($wishlistItems); ?> items</span>
                        <a href="/products.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add More Items
                        </a>
                    </div>
                </div>

                <?php if (!empty($wishlistItems)): ?>
                    <div class="row">
                        <?php foreach ($wishlistItems as $item): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card shadow h-100">
                                    <div class="wishlist-item-image">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="placeholder-image">
                                                <i class="fas fa-image fa-3x text-gray-300"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Stock status badge -->
                                        <?php if (!$item['in_stock']): ?>
                                            <div class="stock-badge out-of-stock">
                                                <i class="fas fa-exclamation-triangle"></i> Out of Stock
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        
                                        <div class="price-section mb-2">
                                            <span class="current-price">$<?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                        
                                        <?php if (isset($item['notes']) && $item['notes']): ?>
                                            <div class="wishlist-notes mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-sticky-note"></i> 
                                                    <?php echo htmlspecialchars($item['notes']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="added-date mb-3">
                                            <small class="text-muted">
                                                Added <?php echo timeAgo($item['added_at'] ?? $item['created_at']); ?>
                                            </small>
                                        </div>
                                        
                                        <div class="mt-auto">
                                            <div class="btn-group w-100" role="group">
                                                <?php if ($item['in_stock'] && $item['product_status'] === 'active'): ?>
                                                    <button type="button" class="btn btn-primary flex-fill" 
                                                            onclick="addToCart(<?php echo $item['product_id']; ?>)">
                                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-secondary flex-fill" disabled>
                                                        <i class="fas fa-times"></i> Unavailable
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h6 class="mb-0">Wishlist Actions</h6>
                                    <small class="text-muted">Manage your entire wishlist</small>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button type="button" class="btn btn-outline-primary me-2">
                                        <i class="fas fa-share"></i> Share Wishlist
                                    </button>
                                    <button type="button" class="btn btn-outline-warning me-2">
                                        <i class="fas fa-shopping-cart"></i> Add All to Cart
                                    </button>
                                    <button type="button" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i> Clear Wishlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-wishlist">
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-5x text-gray-300 mb-4"></i>
                            <h3>Your Wishlist is Empty</h3>
                            <p class="text-muted mb-4">Save items you love for later by clicking the heart icon on any product.</p>
                            
                            <div class="row justify-content-center">
                                <div class="col-md-6">
                                    <div class="search-suggestions">
                                        <h5>Popular Categories</h5>
                                        <div class="category-links">
                                            <a href="/products.php?category=electronics" class="btn btn-outline-primary m-1">Electronics</a>
                                            <a href="/products.php?category=fashion" class="btn btn-outline-primary m-1">Fashion</a>
                                            <a href="/products.php?category=home" class="btn btn-outline-primary m-1">Home & Garden</a>
                                            <a href="/products.php?category=books" class="btn btn-outline-primary m-1">Books</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="/products.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-shopping-bag"></i> Start Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.buyer-dashboard {
    background-color: #f8f9fc;
    min-height: 100vh;
}

.main-content {
    padding: 0 1.5rem;
}

.wishlist-item-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.wishlist-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.placeholder-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background-color: #f8f9fc;
}

.stock-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background-color: rgba(231, 74, 59, 0.9);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: bold;
}

.current-price {
    font-size: 1.25rem;
    font-weight: bold;
    color: #1cc88a;
}

.wishlist-notes {
    background-color: #f8f9fc;
    padding: 0.5rem;
    border-radius: 0.25rem;
    border-left: 3px solid #4e73df;
}

.added-date {
    border-top: 1px solid #e3e6f0;
    padding-top: 0.5rem;
}

.empty-wishlist {
    background: white;
    border-radius: 0.35rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    margin-bottom: 2rem;
}

.search-suggestions {
    text-align: center;
    margin-bottom: 2rem;
}

.category-links {
    margin-top: 1rem;
}
</style>

<script>
function addToCart(productId) {
    // Add to cart functionality
    fetch('/api/cart/add', {
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
            // Show success message
            alert('Item added to cart!');
        } else {
            alert('Error adding item to cart: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding item to cart');
    });
}

function removeFromWishlist(productId) {
    if (confirm('Remove this item from your wishlist?')) {
        fetch('/api/wishlist/remove', {
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
                location.reload();
            } else {
                alert('Error removing item: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item');
        });
    }
}
</script>

<?php
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    
    return date('M j, Y', strtotime($datetime));
}

includeFooter();
?>