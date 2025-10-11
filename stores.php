<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Stores';
includeHeader($page_title);
?>

<div class="container">
    <div class="stores-header">
        <h1>FezaMarket Stores</h1>
        <p>Discover unique stores from trusted sellers around the world</p>
    </div>

    <div class="stores-content">
        <section class="intro-section">
            <h2>Why Shop FezaMarket Stores?</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">🏪</div>
                    <h3>Curated Collections</h3>
                    <p>Browse carefully curated stores featuring specialized products and unique finds.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">⭐</div>
                    <h3>Trusted Sellers</h3>
                    <p>All stores are verified and rated by real customers for your peace of mind.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🎁</div>
                    <h3>Exclusive Deals</h3>
                    <p>Store owners offer special promotions and discounts to loyal customers.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">💬</div>
                    <h3>Direct Communication</h3>
                    <p>Connect directly with store owners for personalized service and support.</p>
                </div>
            </div>
        </section>

        <section class="categories-section">
            <h2>Browse by Category</h2>
            <div class="categories-grid">
                <a href="/search.php?category=electronics" class="category-card">
                    <h3>Electronics</h3>
                    <p>1,234 stores</p>
                </a>
                <a href="/search.php?category=fashion" class="category-card">
                    <h3>Fashion & Apparel</h3>
                    <p>2,456 stores</p>
                </a>
                <a href="/search.php?category=home" class="category-card">
                    <h3>Home & Garden</h3>
                    <p>987 stores</p>
                </a>
                <a href="/search.php?category=collectibles" class="category-card">
                    <h3>Collectibles</h3>
                    <p>654 stores</p>
                </a>
                <a href="/search.php?category=sports" class="category-card">
                    <h3>Sports & Outdoors</h3>
                    <p>432 stores</p>
                </a>
                <a href="/search.php?category=art" class="category-card">
                    <h3>Art & Crafts</h3>
                    <p>789 stores</p>
                </a>
            </div>
        </section>

        <section class="features-section">
            <h2>Store Features</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <h3>🔍 Advanced Search</h3>
                    <p>Find stores by location, specialty, ratings, and more. Our powerful search helps you discover exactly what you're looking for.</p>
                </div>
                <div class="feature-item">
                    <h3>🔔 Follow Stores</h3>
                    <p>Follow your favorite stores to get notifications about new products, sales, and special offers.</p>
                </div>
                <div class="feature-item">
                    <h3>📊 Seller Analytics</h3>
                    <p>View detailed seller statistics including ratings, response time, shipping speed, and customer feedback.</p>
                </div>
                <div class="feature-item">
                    <h3>🛡️ Buyer Protection</h3>
                    <p>Every purchase is protected by our Money Back Guarantee. Shop with confidence.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <h2>Want to Open Your Own Store?</h2>
            <p>Join thousands of successful sellers on FezaMarket</p>
            <a href="/seller-register.php" class="btn btn-primary btn-lg">Start Selling Today</a>
        </section>
    </div>
</div>

<style>
.stores-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);color:white;margin-bottom:40px}
.stores-header h1{margin:0 0 10px 0;font-size:42px}
.stores-header p{margin:0;font-size:20px;opacity:0.9}
.stores-content{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:60px}
section h2{font-size:32px;margin-bottom:30px;color:#1f2937;text-align:center}
.benefits-grid,.categories-grid,.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.benefit-card,.category-card,.feature-item{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);text-align:center;transition:transform 0.3s}
.benefit-card:hover,.category-card:hover{transform:translateY(-5px)}
.benefit-icon{font-size:48px;margin-bottom:15px}
.benefit-card h3,.feature-item h3{margin:15px 0;color:#1f2937;font-size:20px}
.category-card{text-decoration:none;color:inherit}
.category-card h3{margin:0 0 10px 0}
.category-card p{color:#6b7280;margin:0}
.cta-section{text-align:center;padding:60px 30px;background:linear-gradient(135deg,#f9fafb 0%,#e5e7eb 100%);border-radius:16px}
.cta-section h2{margin-bottom:15px}
.cta-section p{margin-bottom:25px;font-size:18px;color:#6b7280}
.btn-lg{padding:16px 48px;font-size:18px}
@media (max-width:768px){.benefits-grid,.categories-grid,.features-grid{grid-template-columns:1fr}.stores-header h1{font-size:32px}}
</style>

<?php includeFooter(); ?>