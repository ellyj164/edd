<?php
/**
 * Brands Directory
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$product = new Product();

// Mock brands data (in real implementation, this would come from database)
$brands = [
    [
        'id' => 1,
        'name' => 'Apple',
        'slug' => 'apple',
        'description' => 'Innovative technology products',
        'logo' => '/images/brands/apple.png',
        'product_count' => 150,
        'featured' => true
    ],
    [
        'id' => 2,
        'name' => 'Samsung',
        'slug' => 'samsung',
        'description' => 'Electronics and mobile devices',
        'logo' => '/images/brands/samsung.png',
        'product_count' => 200,
        'featured' => true
    ],
    [
        'id' => 3,
        'name' => 'Nike',
        'slug' => 'nike',
        'description' => 'Athletic wear and footwear',
        'logo' => '/images/brands/nike.png',
        'product_count' => 300,
        'featured' => true
    ],
    [
        'id' => 4,
        'name' => 'Adidas',
        'slug' => 'adidas',
        'description' => 'Sports apparel and equipment',
        'logo' => '/images/brands/adidas.png',
        'product_count' => 250,
        'featured' => false
    ],
    [
        'id' => 5,
        'name' => 'Sony',
        'slug' => 'sony',
        'description' => 'Entertainment and electronics',
        'logo' => '/images/brands/sony.png',
        'product_count' => 180,
        'featured' => true
    ],
    [
        'id' => 6,
        'name' => 'Dell',
        'slug' => 'dell',
        'description' => 'Computers and technology solutions',
        'logo' => '/images/brands/dell.png',
        'product_count' => 120,
        'featured' => false
    ]
];

// Filter featured brands
$featuredBrands = array_filter($brands, function($brand) { return $brand['featured']; });
$allBrands = $brands;

// Handle search
$searchTerm = $_GET['search'] ?? '';
if ($searchTerm) {
    $allBrands = array_filter($brands, function($brand) use ($searchTerm) {
        return stripos($brand['name'], $searchTerm) !== false || 
               stripos($brand['description'], $searchTerm) !== false;
    });
}

$page_title = 'Brand Directory - Shop by Brand';
includeHeader($page_title);
?>

<div class="container">
    <!-- Page Header -->
    <div class="brands-header">
        <h1>Brand Directory</h1>
        <p class="brands-subtitle">Discover products from top brands and trusted sellers</p>
        
        <!-- Search Bar -->
        <div class="brand-search">
            <form method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="search" placeholder="Search brands..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>" class="search-input">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Featured Brands -->
    <?php if (empty($searchTerm)): ?>
    <section class="featured-brands-section">
        <h2>Featured Brands</h2>
        <div class="featured-brands-grid">
            <?php foreach ($featuredBrands as $brand): ?>
                <div class="featured-brand-card" onclick="window.location.href='/brand.php?slug=<?php echo $brand['slug']; ?>'">
                    <div class="brand-logo">
                        <div class="brand-logo-placeholder">
                            <?php echo strtoupper(substr($brand['name'], 0, 2)); ?>
                        </div>
                    </div>
                    <div class="brand-info">
                        <h3><?php echo htmlspecialchars($brand['name']); ?></h3>
                        <p><?php echo htmlspecialchars($brand['description']); ?></p>
                        <div class="brand-stats">
                            <span class="product-count"><?php echo number_format($brand['product_count']); ?> products</span>
                        </div>
                    </div>
                    <div class="brand-badge">Featured</div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- All Brands Section -->
    <section class="all-brands-section">
        <div class="section-header">
            <h2><?php echo $searchTerm ? 'Search Results' : 'All Brands'; ?></h2>
            <div class="brands-count"><?php echo count($allBrands); ?> brands found</div>
        </div>

        <?php if (!empty($allBrands)): ?>
            <!-- Alphabetical Index -->
            <div class="alphabet-index">
                <?php for ($i = 65; $i <= 90; $i++): ?>
                    <a href="#letter-<?php echo chr($i); ?>" class="alphabet-link"><?php echo chr($i); ?></a>
                <?php endfor; ?>
            </div>

            <!-- Brands Grid -->
            <div class="brands-grid">
                <?php foreach ($allBrands as $brand): ?>
                    <div class="brand-card" onclick="window.location.href='/brand.php?slug=<?php echo $brand['slug']; ?>'">
                        <div class="brand-logo-small">
                            <?php echo strtoupper(substr($brand['name'], 0, 2)); ?>
                        </div>
                        <div class="brand-details">
                            <h3 class="brand-name"><?php echo htmlspecialchars($brand['name']); ?></h3>
                            <p class="brand-description"><?php echo htmlspecialchars($brand['description']); ?></p>
                            <div class="brand-meta">
                                <span class="product-count"><?php echo number_format($brand['product_count']); ?> products</span>
                                <?php if ($brand['featured']): ?>
                                    <span class="featured-tag">★ Featured</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="brand-arrow">→</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Results -->
            <div class="no-results">
                <div class="no-results-icon">🔍</div>
                <h3>No brands found</h3>
                <p>Try adjusting your search or browse all brands below.</p>
                <a href="/brands.php" class="btn">View All Brands</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Brand Categories -->
    <?php if (empty($searchTerm)): ?>
    <section class="brand-categories-section">
        <h2>Shop by Category</h2>
        <div class="category-brands-grid">
            <div class="category-brand-card" onclick="window.location.href='/category.php?name=electronics'">
                <div class="category-icon">📱</div>
                <h3>Electronics</h3>
                <p>Apple, Samsung, Sony, Dell</p>
                <div class="category-count">15 brands</div>
            </div>
            <div class="category-brand-card" onclick="window.location.href='/category.php?name=fashion'">
                <div class="category-icon">👕</div>
                <h3>Fashion</h3>
                <p>Nike, Adidas, Zara, H&M</p>
                <div class="category-count">25 brands</div>
            </div>
            <div class="category-brand-card" onclick="window.location.href='/category.php?name=home-garden'">
                <div class="category-icon">🏠</div>
                <h3>Home & Garden</h3>
                <p>IKEA, Home Depot, Wayfair</p>
                <div class="category-count">12 brands</div>
            </div>
            <div class="category-brand-card" onclick="window.location.href='/category.php?name=sports'">
                <div class="category-icon">⚽</div>
                <h3>Sports</h3>
                <p>Nike, Adidas, Under Armour</p>
                <div class="category-count">18 brands</div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Popular Searches -->
    <section class="popular-searches">
        <h3>Popular Brand Searches</h3>
        <div class="popular-tags">
            <a href="/brands.php?search=apple" class="tag">Apple</a>
            <a href="/brands.php?search=samsung" class="tag">Samsung</a>
            <a href="/brands.php?search=nike" class="tag">Nike</a>
            <a href="/brands.php?search=sony" class="tag">Sony</a>
            <a href="/brands.php?search=adidas" class="tag">Adidas</a>
            <a href="/brands.php?search=dell" class="tag">Dell</a>
        </div>
    </section>
</div>

<style>
.brands-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
}

.brands-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.brands-subtitle {
    font-size: 18px;
    color: #6b7280;
    margin-bottom: 30px;
}

.brand-search {
    max-width: 500px;
    margin: 0 auto;
}

.search-input-group {
    display: flex;
    gap: 10px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 16px;
}

.search-btn {
    background: #0654ba;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.featured-brands-section {
    margin-bottom: 60px;
}

.featured-brands-section h2 {
    font-size: 24px;
    color: #1f2937;
    margin-bottom: 25px;
}

.featured-brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.featured-brand-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.3s ease;
    position: relative;
    overflow: hidden;
}

.featured-brand-card:hover {
    transform: translateY(-5px);
}

.brand-logo {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px auto;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand-logo-placeholder {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #0654ba, #1e40af);
    color: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
}

.brand-info {
    text-align: center;
}

.brand-info h3 {
    font-size: 20px;
    color: #1f2937;
    margin-bottom: 8px;
}

.brand-info p {
    color: #6b7280;
    margin-bottom: 15px;
}

.brand-stats .product-count {
    background: #f3f4f6;
    color: #374151;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.brand-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #fbbf24;
    color: #1f2937;
    padding: 5px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
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

.brands-count {
    color: #6b7280;
    font-size: 14px;
}

.alphabet-index {
    display: flex;
    gap: 8px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    justify-content: center;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
}

.alphabet-link {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    transition: all 0.3s ease;
}

.alphabet-link:hover {
    background: #0654ba;
    color: white;
    border-color: #0654ba;
}

.brands-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

.brand-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 20px;
}

.brand-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.brand-logo-small {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #0654ba, #1e40af);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
    flex-shrink: 0;
}

.brand-details {
    flex: 1;
}

.brand-name {
    font-size: 18px;
    color: #1f2937;
    margin-bottom: 5px;
}

.brand-description {
    color: #6b7280;
    margin-bottom: 8px;
    font-size: 14px;
}

.brand-meta {
    display: flex;
    gap: 15px;
    align-items: center;
}

.brand-meta .product-count {
    color: #374151;
    font-size: 14px;
}

.featured-tag {
    color: #fbbf24;
    font-size: 12px;
    font-weight: 600;
}

.brand-arrow {
    font-size: 18px;
    color: #6b7280;
}

.category-brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.category-brand-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.category-brand-card:hover {
    transform: translateY(-3px);
}

.category-icon {
    font-size: 36px;
    margin-bottom: 15px;
}

.category-brand-card h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.category-brand-card p {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 10px;
}

.category-count {
    color: #0654ba;
    font-size: 14px;
    font-weight: 600;
}

.popular-searches {
    margin-top: 40px;
    padding: 30px 0;
    border-top: 1px solid #e5e7eb;
}

.popular-searches h3 {
    color: #1f2937;
    margin-bottom: 15px;
}

.popular-tags {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tag {
    background: #f3f4f6;
    color: #374151;
    padding: 6px 12px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.tag:hover {
    background: #0654ba;
    color: white;
}

.no-results {
    text-align: center;
    padding: 60px 20px;
}

.no-results-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-results h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.no-results p {
    color: #6b7280;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .brands-header h1 {
        font-size: 28px;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .featured-brands-grid {
        grid-template-columns: 1fr;
    }
    
    .brand-card {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .brand-meta {
        justify-content: center;
    }
    
    .section-header {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
}
</style>

<?php includeFooter(); ?>