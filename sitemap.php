<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Site Map';
includeHeader($page_title);
?>
<div class="container">
    <div class="card mt-4">
        <div class="card-body">
            <h1>FezaMarket Site Map</h1>
            <div class="sitemap-content">
                <div class="sitemap-section">
                    <h3>Shopping</h3>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="/search.php">Search</a></li>
                        <li><a href="/search/advanced.php">Advanced Search</a></li>
                        <li><a href="/deals.php">Daily Deals</a></li>
                        <li><a href="/brands.php">Brand Outlet</a></li>
                    </ul>
                </div>
                <div class="sitemap-section">
                    <h3>Categories</h3>
                    <ul>
                        <li><a href="/category.php?name=electronics">Electronics</a></li>
                        <li><a href="/category.php?name=fashion">Fashion</a></li>
                        <li><a href="/category.php?name=home-garden">Home & Garden</a></li>
                        <li><a href="/category.php?name=sports">Sports</a></li>
                    </ul>
                </div>
                <div class="sitemap-section">
                    <h3>Account</h3>
                    <ul>
                        <li><a href="/register.php">Register</a></li>
                        <li><a href="/login.php">Sign In</a></li>
                        <li><a href="/account.php">My Account</a></li>
                        <li><a href="/wishlist.php">Wishlist</a></li>
                        <li><a href="/cart.php">Shopping Cart</a></li>
                    </ul>
                </div>
                <div class="sitemap-section">
                    <h3>Selling</h3>
                    <ul>
                        <li><a href="<?php echo getSellingUrl(); ?>">Start Selling</a></li>
                        <li><a href="/seller-center.php">Seller Center</a></li>
                    </ul>
                </div>
                <div class="sitemap-section">
                    <h3>Help & Support</h3>
                    <ul>
                        <li><a href="/contact.php">Contact Us</a></li>
                        <li><a href="/help.php">Help Center</a></li>
                        <li><a href="/returns.php">Returns</a></li>
                        <li><a href="/money-back.php">Money Back Guarantee</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php includeFooter(); ?>
