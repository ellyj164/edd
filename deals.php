<?php
/**
 * Deals and Promotions Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$product = new Product();
$category = new Category();

// Get featured deals (products with discount or marked as featured)
$featuredDeals = $product->getFeatured(8);
$flashDeals = $product->findAll(12); // Use findAll instead of getOnSale
$categories = $category->getParents();

$page_title = 'Daily Deals & Promotions';
includeHeader($page_title);
?>

<div class="container">
    <!-- Deals Hero Section -->
    <section class="deals-hero">
        <div class="hero-content">
            <h1>Daily Deals & Flash Sales</h1>
            <p>Discover amazing discounts and limited-time offers on your favorite products</p>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Active Deals</span>
                </div>
                <div class="stat">
                    <span class="stat-number">Up to 70%</span>
                    <span class="stat-label">Off</span>
                </div>
                <div class="stat">
                    <span class="stat-number">24h</span>
                    <span class="stat-label">Flash Sales</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Flash Deals Timer -->
    <section class="flash-deals-section">
        <div class="section-header">
            <h2>⚡ Flash Deals - Limited Time</h2>
            <div class="countdown-timer" id="flashDealsTimer">
                <div class="time-unit">
                    <span class="time-value" id="hours">12</span>
                    <span class="time-label">Hours</span>
                </div>
                <div class="time-unit">
                    <span class="time-value" id="minutes">34</span>
                    <span class="time-label">Minutes</span>
                </div>
                <div class="time-unit">
                    <span class="time-value" id="seconds">56</span>
                    <span class="time-label">Seconds</span>
                </div>
            </div>
        </div>
        
        <div class="deals-grid">
            <?php foreach (array_slice($flashDeals, 0, 6) as $deal): ?>
                <div class="deal-card flash-deal">
                    <div class="deal-image">
                        <img src="<?php echo getSafeProductImageUrl($deal); ?>" 
                             alt="<?php echo htmlspecialchars($deal['name']); ?>">
                        <div class="discount-badge">-<?php echo rand(20, 50); ?>%</div>
                    </div>
                    <div class="deal-info">
                        <h3 class="deal-title">
                            <a href="/product.php?id=<?php echo $deal['id']; ?>">
                                <?php echo htmlspecialchars(substr($deal['name'], 0, 60)); ?>...
                            </a>
                        </h3>
                        <div class="deal-prices">
                            <span class="current-price"><?php echo formatPrice($deal['price']); ?></span>
                            <span class="original-price"><?php echo formatPrice($deal['price'] * 1.4); ?></span>
                        </div>
                        <div class="deal-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo rand(30, 80); ?>%"></div>
                            </div>
                            <span class="sold-count"><?php echo rand(10, 50); ?> sold</span>
                        </div>
                        <button class="btn deal-btn" onclick="addToCart(<?php echo $deal['id']; ?>)">
                            Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Deal Categories -->
    <section class="deal-categories-section">
        <h2>Shop Deals by Category</h2>
        <div class="categories-grid">
            <div class="category-deal" onclick="window.location.href='/category.php?name=electronics&on_sale=1'">
                <div class="category-icon">📱</div>
                <h3>Electronics</h3>
                <p>Up to 60% off tech gadgets</p>
            </div>
            <div class="category-deal" onclick="window.location.href='/category.php?name=fashion&on_sale=1'">
                <div class="category-icon">👗</div>
                <h3>Fashion</h3>
                <p>Designer brands at great prices</p>
            </div>
            <div class="category-deal" onclick="window.location.href='/category.php?name=home-garden&on_sale=1'">
                <div class="category-icon">🏠</div>
                <h3>Home & Garden</h3>
                <p>Transform your space for less</p>
            </div>
            <div class="category-deal" onclick="window.location.href='/category.php?name=sports&on_sale=1'">
                <div class="category-icon">⚽</div>
                <h3>Sports</h3>
                <p>Athletic gear and equipment</p>
            </div>
        </div>
    </section>

    <!-- Featured Deals -->
    <section class="featured-deals-section">
        <div class="section-header">
            <h2>🔥 Featured Deals</h2>
            <a href="/products.php?featured=1" class="view-all-link">View All</a>
        </div>
        
        <div class="featured-deals-grid">
            <?php foreach ($featuredDeals as $deal): ?>
                <div class="featured-deal-card">
                    <div class="deal-image">
                        <img src="<?php echo getSafeProductImageUrl($deal); ?>" 
                             alt="<?php echo htmlspecialchars($deal['name']); ?>">
                        <div class="deal-badge">Featured</div>
                    </div>
                    <div class="deal-content">
                        <h3 class="deal-title">
                            <a href="/product.php?id=<?php echo $deal['id']; ?>">
                                <?php echo htmlspecialchars($deal['name']); ?>
                            </a>
                        </h3>
                        <p class="deal-vendor">by <?php echo htmlspecialchars($deal['vendor_name'] ?? 'FezaMarket'); ?></p>
                        <div class="deal-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-count">(<?php echo rand(50, 500); ?>)</span>
                        </div>
                        <div class="deal-pricing">
                            <span class="current-price"><?php echo formatPrice($deal['price']); ?></span>
                            <span class="savings">Save <?php echo formatPrice($deal['price'] * 0.3); ?></span>
                        </div>
                        <div class="deal-actions">
                            <button class="btn add-to-cart" onclick="addToCart(<?php echo $deal['id']; ?>)">
                                Add to Cart
                            </button>
                            <button class="btn btn-outline add-to-wishlist" onclick="addToWishlist(<?php echo $deal['id']; ?>)">
                                ❤️
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Newsletter Signup -->
    <section class="deals-newsletter">
        <div class="newsletter-content">
            <h2>Never Miss a Deal!</h2>
            <p>Get exclusive offers and flash sale notifications delivered to your inbox</p>
            <form class="newsletter-form" id="newsletterForm">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit" class="btn">Subscribe</button>
            </form>
        </div>
    </section>
</div>

<style>
.deals-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
    text-align: center;
    border-radius: 12px;
}

.hero-content h1 {
    font-size: 36px;
    margin-bottom: 15px;
}

.hero-content p {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.9;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 60px;
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: bold;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
}

.flash-deals-section {
    margin-bottom: 50px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-header h2 {
    font-size: 24px;
    color: #1f2937;
}

.countdown-timer {
    display: flex;
    gap: 15px;
}

.time-unit {
    text-align: center;
    background: #dc2626;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
}

.time-value {
    display: block;
    font-size: 20px;
    font-weight: bold;
}

.time-label {
    font-size: 12px;
}

.deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.deal-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.deal-card:hover {
    transform: translateY(-5px);
}

.deal-image {
    position: relative;
    height: 200px;
}

.deal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.discount-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #dc2626;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    font-size: 14px;
}

.deal-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #fbbf24;
    color: #1f2937;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    font-size: 12px;
}

.deal-info {
    padding: 15px;
}

.deal-title a {
    color: #1f2937;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
}

.deal-prices {
    margin: 10px 0;
}

.current-price {
    font-size: 18px;
    font-weight: bold;
    color: #dc2626;
    margin-right: 10px;
}

.original-price {
    text-decoration: line-through;
    color: #6b7280;
}

.deal-progress {
    margin: 15px 0;
}

.progress-bar {
    background: #e5e7eb;
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-fill {
    background: #10b981;
    height: 100%;
    transition: width 0.3s ease;
}

.sold-count {
    font-size: 12px;
    color: #6b7280;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.category-deal {
    background: white;
    padding: 30px;
    text-align: center;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.3s ease;
}

.category-deal:hover {
    transform: translateY(-3px);
}

.category-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.category-deal h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.category-deal p {
    color: #6b7280;
    font-size: 14px;
}

.featured-deals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.featured-deal-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.deal-content {
    padding: 20px;
}

.deal-vendor {
    color: #6b7280;
    font-size: 14px;
    margin: 5px 0;
}

.deal-rating {
    margin: 10px 0;
}

.stars {
    color: #fbbf24;
    margin-right: 5px;
}

.rating-count {
    color: #6b7280;
    font-size: 14px;
}

.deal-pricing {
    margin: 15px 0;
}

.savings {
    color: #10b981;
    font-weight: 600;
    margin-left: 10px;
}

.deal-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.deals-newsletter {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    padding: 50px 0;
    text-align: center;
    border-radius: 12px;
    margin-top: 50px;
}

.newsletter-content h2 {
    color: #1f2937;
    margin-bottom: 10px;
}

.newsletter-content p {
    color: #6b7280;
    margin-bottom: 25px;
}

.newsletter-form {
    display: flex;
    justify-content: center;
    gap: 10px;
    max-width: 400px;
    margin: 0 auto;
}

.newsletter-form input {
    flex: 1;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .hero-stats {
        flex-direction: column;
        gap: 20px;
    }
    
    .section-header {
        flex-direction: column;
        gap: 15px;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
}
</style>

<script>
// Countdown timer functionality
function updateCountdown() {
    const now = new Date().getTime();
    const tomorrow = new Date();
    tomorrow.setHours(24, 0, 0, 0);
    const distance = tomorrow.getTime() - now;

    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
}

// Update countdown every second
setInterval(updateCountdown, 1000);
updateCountdown();

// Add to cart functionality
function addToCart(productId) {
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
            // Show success message
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = 'Added!';
            button.classList.add('btn-success');
            
            setTimeout(() => {
                button.textContent = originalText;
                button.classList.remove('btn-success');
            }, 2000);
        } else {
            alert('Error adding to cart: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding item to cart');
    });
}

// Add to wishlist functionality
function addToWishlist(productId) {
    fetch('/api/wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const button = event.target;
            button.textContent = '💖';
            button.disabled = true;
        } else {
            alert('Error adding to wishlist: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding item to wishlist');
    });
}

// Newsletter signup
document.getElementById('newsletterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Thank you for subscribing! You\'ll receive deal notifications soon.');
});
</script>

<?php includeFooter(); ?>