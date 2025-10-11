<?php
/**
 * User Wishlist
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require user login
Session::requireLogin();

$wishlist = new Wishlist();
$product = new Product();

$wishlistItems = $wishlist->getUserWishlist(Session::getUserId());

$page_title = 'My Wishlist';
includeHeader($page_title);
?>

<div class="container">
    <div class="wishlist-header">
        <h1>My Wishlist</h1>
        <p class="wishlist-subtitle">Items you've saved for later</p>
    </div>

    <?php if (!empty($wishlistItems)): ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlistItems as $item): ?>
                <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                    <div class="item-image">
                        <img src="<?php echo getSafeProductImageUrl($item); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <button class="remove-wishlist-btn" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)">
                            ❌
                        </button>
                    </div>
                    <div class="item-info">
                        <h3 class="item-title">
                            <a href="/product.php?id=<?php echo $item['product_id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </h3>
                        <p class="item-vendor">by <?php echo htmlspecialchars($item['vendor_name'] ?? 'FezaMarket'); ?></p>
                        <p class="item-price"><?php echo formatPrice($item['price']); ?></p>
                        
                        <?php if ($item['stock_quantity'] > 0): ?>
                            <div class="item-availability in-stock">
                                ✅ In Stock (<?php echo $item['stock_quantity']; ?> available)
                            </div>
                        <?php else: ?>
                            <div class="item-availability out-of-stock">
                                ❌ Out of Stock
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-actions">
                            <?php if ($item['stock_quantity'] > 0): ?>
                                <button type="button" class="btn add-to-cart-btn" onclick="addToCartFromWishlist(<?php echo $item['product_id']; ?>)">
                                    Add to Cart
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn btn-outline" onclick="moveToList(<?php echo $item['product_id']; ?>)">
                                Move to List
                            </button>
                        </div>
                        
                        <div class="item-added">
                            Added <?php echo formatDate($item['added_at']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="wishlist-actions">
            <button class="btn btn-outline" onclick="shareWishlist()">Share Wishlist</button>
            <button class="btn btn-outline" onclick="printWishlist()">Print Wishlist</button>
            <a href="/products.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="empty-wishlist">
            <div class="empty-icon">💝</div>
            <h2>Your wishlist is empty</h2>
            <p>Save items you love by clicking the heart icon on any product.</p>
            <a href="/products.php" class="btn">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<style>
.wishlist-header {
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 1px solid #e5e7eb;
}

.wishlist-header h1 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 5px;
}

.wishlist-subtitle {
    color: #6b7280;
    font-size: 16px;
}

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.wishlist-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 15px;
    transition: transform 0.3s ease;
}

.wishlist-item:hover {
    transform: translateY(-2px);
}

.item-image {
    position: relative;
    margin-bottom: 15px;
}

.item-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 6px;
}

.remove-wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s ease;
}

.remove-wishlist-btn:hover {
    background: rgba(255, 255, 255, 1);
}

.item-title {
    margin-bottom: 8px;
}

.item-title a {
    color: #1f2937;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
}

.item-title a:hover {
    text-decoration: underline;
}

.item-vendor {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 8px;
}

.item-price {
    font-size: 18px;
    font-weight: 600;
    color: #dc2626;
    margin-bottom: 10px;
}

.item-availability {
    font-size: 14px;
    margin-bottom: 15px;
    padding: 5px 10px;
    border-radius: 4px;
}

.in-stock {
    background: #dcfce7;
    color: #166534;
}

.out-of-stock {
    background: #fee2e2;
    color: #991b1b;
}

.item-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
}

.item-added {
    color: #6b7280;
    font-size: 12px;
}

.wishlist-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding: 20px 0;
    border-top: 1px solid #e5e7eb;
}

.empty-wishlist {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
}

.empty-wishlist h2 {
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-wishlist p {
    color: #6b7280;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .wishlist-grid {
        grid-template-columns: 1fr;
    }
    
    .item-actions {
        flex-direction: column;
    }
    
    .wishlist-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
function removeFromWishlist(productId) {
    if (confirm('Remove this item from your wishlist?')) {
        fetch('/api/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`[data-product-id="${productId}"]`).remove();
                
                // If no more items, show empty state
                if (document.querySelectorAll('.wishlist-item').length === 0) {
                    location.reload();
                }
            } else {
                alert('Error removing item: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item from wishlist');
        });
    }
}

function addToCartFromWishlist(productId) {
    fetch('/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI to show item was added
            const button = event.target;
            button.textContent = 'Added ✓';
            button.disabled = true;
            button.classList.add('btn-success');
            
            // Update cart count
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Error adding to cart: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding item to cart');
    });
}

function moveToList(productId) {
    alert('Move to list functionality will be implemented in a future update.');
}

function shareWishlist() {
    if (navigator.share) {
        navigator.share({
            title: 'My FezaMarket Wishlist',
            text: 'Check out my wishlist on FezaMarket!',
            url: window.location.href
        });
    } else {
        // Fallback to copying URL
        navigator.clipboard.writeText(window.location.href);
        alert('Wishlist URL copied to clipboard!');
    }
}

function printWishlist() {
    window.print();
}
</script>

<?php includeFooter(); ?>