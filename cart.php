<?php
/**
 * Shopping Cart Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require login
Session::requireLogin();

$userId = Session::getUserId();
$cart = new Cart();
$cartItems = $cart->getCartItems($userId);

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$taxRate = 8.25; // Could come from settings
$taxAmount = $subtotal * ($taxRate / 100);
$shippingAmount = $subtotal >= 50 ? 0 : 9.99; // Free shipping over $50
$total = $subtotal + $taxAmount + $shippingAmount;

$page_title = 'Shopping Cart';
includeHeader($page_title);
?>

<div class="container">
    <h1 class="mb-4">Shopping Cart</h1>
    
    <?php if (empty($cartItems)): ?>
        <div class="text-center" style="padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
            <h3>Your cart is empty</h3>
            <p class="text-muted mb-3">Looks like you haven't added anything to your cart yet.</p>
            <a href="/products.php" class="btn btn-lg">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-8">
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" style="display: flex; align-items: center; padding: 1.5rem 0; border-bottom: 1px solid #eee;">
                                <div class="item-image" style="width: 100px; height: 100px; margin-right: 1rem;">
                                    <img src="<?php echo getSafeProductImageUrl($item); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name'] ?? ''); ?>"
                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                </div>
                                
                                <div class="item-info" style="flex: 1;">
                                    <h4 style="margin-bottom: 0.5rem;">
                                        <a href="/product.php?id=<?php echo $item['product_id']; ?>">
                                            <?php echo htmlspecialchars($item['product_name'] ?? ''); ?>
                                        </a>
                                    </h4>
                                    <p class="text-muted">by <?php echo htmlspecialchars($item['vendor_name'] ?? 'Unknown Vendor'); ?></p>
                                    <p class="text-muted">SKU: <?php echo htmlspecialchars($item['sku'] ?? ''); ?></p>
                                    <p><strong><?php echo formatPrice($item['price']); ?></strong></p>
                                </div>
                                
                                <div class="item-quantity" style="margin: 0 1rem;">
                                    <label for="qty_<?php echo $item['product_id']; ?>" style="display: block; margin-bottom: 0.5rem;">Quantity:</label>
                                    <input type="number" 
                                           id="qty_<?php echo $item['product_id']; ?>"
                                           class="form-control quantity-input" 
                                           data-product-id="<?php echo $item['product_id']; ?>"
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" 
                                           max="<?php echo $item['stock_quantity']; ?>"
                                           style="width: 80px;">
                                    <small class="text-muted"><?php echo $item['stock_quantity']; ?> available</small>
                                </div>
                                
                                <div class="item-total" style="text-align: right; margin: 0 1rem;">
                                    <p><strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong></p>
                                </div>
                                
                                <div class="item-actions">
                                    <button class="btn btn-sm btn-danger remove-from-cart" 
                                            data-product-id="<?php echo $item['product_id']; ?>">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="/products.php" class="btn btn-outline">Continue Shopping</a>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Order Summary</h3>
                        
                        <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Subtotal:</span>
                            <span class="cart-subtotal"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Tax (<?php echo $taxRate; ?>%):</span>
                            <span><?php echo formatPrice($taxAmount); ?></span>
                        </div>
                        
                        <div class="summary-line" style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($shippingAmount > 0): ?>
                                    <?php echo formatPrice($shippingAmount); ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($subtotal < 50 && $subtotal > 0): ?>
                            <div class="alert alert-info" style="font-size: 0.875rem;">
                                Add <?php echo formatPrice(50 - $subtotal); ?> more for free shipping!
                            </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div class="summary-line" style="display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: bold; margin-bottom: 1.5rem;">
                            <span>Total:</span>
                            <span class="cart-total"><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <a href="/checkout.php" class="btn btn-lg btn-success" style="width: 100%;">
                            Proceed to Checkout
                        </a>
                        
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i>🔒 Secure Checkout</i><br>
                                SSL encrypted and safe
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Recommended Products -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h4>You might also like</h4>
                        <div id="cart-recommendations">
                            <!-- Will be loaded via JavaScript -->
                            <p class="text-muted">Loading recommendations...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Update cart totals when quantities change
document.addEventListener('DOMContentLoaded', function() {
    // Load recommendations for cart items
    if (document.getElementById('cart-recommendations')) {
        loadCartRecommendations();
    }
});

function loadCartRecommendations() {
    // Simple AJAX call to get recommendations
    fetch('/api/recommendations.php?type=cart')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.length > 0) {
                const container = document.getElementById('cart-recommendations');
                container.innerHTML = data.data.map(product => `
                    <div style="display: flex; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                        <img src="${product.image_url || '/images/placeholder-product.jpg'}" 
                             alt="${product.name}" 
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-right: 1rem;">
                        <div style="flex: 1;">
                            <h6 style="margin-bottom: 0.25rem;">
                                <a href="/product.php?id=${product.id}">${product.name}</a>
                            </h6>
                            <p style="margin-bottom: 0.5rem; font-weight: bold;">${product.price}</p>
                            <button class="btn btn-sm add-to-cart" data-product-id="${product.id}">Add</button>
                        </div>
                    </div>
                `).join('');
            } else {
                document.getElementById('cart-recommendations').innerHTML = '<p class="text-muted">No recommendations available.</p>';
            }
        })
        .catch(err => {
            document.getElementById('cart-recommendations').innerHTML = '<p class="text-muted">Unable to load recommendations.</p>';
        });
}
</script>

<?php includeFooter(); ?>