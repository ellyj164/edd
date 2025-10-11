<?php
/**
 * User Watchlist
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require user login
Session::requireLogin();

$watchlist = new Watchlist();
$product = new Product();

$watchlistItems = $watchlist->getUserWatchlist(Session::getUserId());

$page_title = 'My Watchlist';
includeHeader($page_title);
?>

<div class="container">
    <div class="watchlist-header">
        <h1>My Watchlist</h1>
        <p class="watchlist-subtitle">Items you're watching for changes</p>
    </div>

    <?php if (!empty($watchlistItems)): ?>
        <div class="watchlist-grid">
            <?php foreach ($watchlistItems as $item): ?>
                <div class="watchlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                    <div class="item-image">
                        <img src="<?php echo getSafeProductImageUrl($item); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <button class="remove-watchlist-btn" onclick="removeFromWatchlist(<?php echo $item['product_id']; ?>)">
                            ❌
                        </button>
                    </div>
                    
                    <div class="item-details">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                        
                        <div class="item-actions">
                            <?php if ($item['stock_quantity'] > 0): ?>
                                <button class="btn" onclick="addToCart(<?php echo $item['product_id']; ?>)">
                                    Add to Cart
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline" onclick="moveToWishlist(<?php echo $item['product_id']; ?>)">
                                Move to Wishlist
                            </button>
                        </div>
                        
                        <div class="item-added">
                            Added <?php echo formatDate($item['added_at']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="watchlist-actions">
            <button class="btn btn-outline" onclick="shareWatchlist()">Share Watchlist</button>
            <button class="btn btn-outline" onclick="printWatchlist()">Print Watchlist</button>
            <a href="/products.php" class="btn">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="empty-watchlist">
            <div class="empty-icon">👀</div>
            <h2>Your watchlist is empty</h2>
            <p>Add items you want to keep an eye on for price changes or stock updates.</p>
            <a href="/products.php" class="btn">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<style>
.watchlist-header {
    text-align: center;
    margin-bottom: 2rem;
}

.watchlist-subtitle {
    color: #666;
    font-size: 1.1rem;
}

.watchlist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.watchlist-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s;
}

.watchlist-item:hover {
    transform: translateY(-2px);
}

.item-image {
    position: relative;
    height: 200px;
    background: #f5f5f5;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-watchlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-details {
    padding: 1rem;
}

.item-details h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.item-price {
    font-size: 1.25rem;
    font-weight: bold;
    color: #e74c3c;
    margin-bottom: 1rem;
}

.item-actions {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.item-actions .btn {
    flex: 1;
    padding: 0.5rem;
    border-radius: 4px;
    text-align: center;
    text-decoration: none;
    border: none;
    cursor: pointer;
    background: #3498db;
    color: white;
}

.item-actions .btn-outline {
    background: transparent;
    color: #3498db;
    border: 1px solid #3498db;
}

.item-added {
    font-size: 0.9rem;
    color: #666;
}

.watchlist-actions {
    text-align: center;
    padding: 2rem 0;
}

.watchlist-actions .btn {
    margin: 0 0.5rem;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    background: #3498db;
    color: white;
}

.watchlist-actions .btn-outline {
    background: transparent;
    color: #3498db;
    border: 1px solid #3498db;
}

.empty-watchlist {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-watchlist h2 {
    margin-bottom: 1rem;
    color: #333;
}

.empty-watchlist p {
    color: #666;
    margin-bottom: 2rem;
}

.empty-watchlist .btn {
    padding: 0.75rem 2rem;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 4px;
}
</style>

<script>
function removeFromWatchlist(productId) {
    if (!confirm('Remove this item from your watchlist?')) return;
    
    fetch('/api/watchlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            action: 'remove',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from the page
            const item = document.querySelector(`[data-product-id="${productId}"]`);
            if (item) {
                item.style.opacity = '0.5';
                setTimeout(() => {
                    item.remove();
                    // Check if watchlist is now empty
                    if (document.querySelectorAll('.watchlist-item').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
        } else {
            alert('Error removing from watchlist: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error removing item from watchlist');
    });
}

function addToCart(productId) {
    fetch('/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
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
            alert('Item added to cart successfully!');
            // Update cart count if visible
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert('Error adding to cart: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding item to cart');
    });
}

function moveToWishlist(productId) {
    fetch('/api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from watchlist
            removeFromWatchlist(productId);
            alert('Item moved to wishlist!');
        } else {
            alert('Error moving to wishlist: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error moving item to wishlist');
    });
}

function shareWatchlist() {
    if (navigator.share) {
        navigator.share({
            title: 'My FezaMarket Watchlist',
            text: 'Check out my watchlist on FezaMarket!',
            url: window.location.href
        });
    } else {
        // Fallback to copying URL
        navigator.clipboard.writeText(window.location.href);
        alert('Watchlist URL copied to clipboard!');
    }
}

function printWatchlist() {
    window.print();
}
</script>

<?php includeFooter(); ?>