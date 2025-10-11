<?php
/**
 * Homepage - FezaMarket E-Commerce Platform
 * Complete Walmart Layout with Dynamic Product Integration
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/template-helpers.php';

/* ---------- Currency Detection and Initialization ---------- */
try {
    $currency = Currency::getInstance();
    
    // Auto-detect and set currency on first visit
    if (!Session::get('currency_code')) {
        $currency->detectAndSetCurrency();
    }
    
    // Update exchange rates if needed (once per day)
    if ($currency->shouldUpdateRates()) {
        $currency->updateExchangeRates();
    }
} catch (Exception $e) {
    error_log("Currency initialization error: " . $e->getMessage());
}

/* ---------- Admin Authorization Check ---------- */
$is_admin_logged_in = false;
$isLoggedIn = false;
try {
    $isLoggedIn = Session::isLoggedIn();
    if ($isLoggedIn) {
        $user_role = Session::getUserRole();
        $is_admin_logged_in = ($user_role === 'admin');
    }
} catch (Exception $e) {
    // Fallback: check session directly if database fails
    $is_admin_logged_in = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
    $isLoggedIn = isset($_SESSION['user_id']);
    error_log("Admin check fallback: " . ($is_admin_logged_in ? 'true' : 'false'));
}

/* ---------- Safe Helpers ---------- */
if (!function_exists('h')) {
    function h($v): string { 
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); 
    }
}

if (!function_exists('safeNormalizeProduct')) {
    function safeNormalizeProduct($p): array {
        if (!is_array($p)) {
            $fallback_id = rand(1, 1000);
            return [
                'id' => $fallback_id,
                'title' => 'Sample Product',
                'price' => '$' . number_format(rand(10, 200), 2),
                'original_price' => '$' . number_format(rand(210, 300), 2),
                'discount_percent' => rand(10, 50),
                'image' => 'https://picsum.photos/400/400?random=' . $fallback_id,
                'url' => '/product.php?id=' . $fallback_id,
                'store_name' => 'FezaMarket Store',
                'seller_name' => 'FezaMarket',
                'rating' => rand(4, 5),
                'reviews_count' => rand(10, 100),
                'featured' => true
            ];
        }
        
        return [
            'id' => isset($p['id']) ? (int)$p['id'] : rand(1, 1000),
            'title' => isset($p['title']) ? (string)$p['title'] : 'Sample Product',
            'price' => isset($p['price']) ? (string)$p['price'] : '$' . number_format(rand(10, 200), 2),
            'original_price' => isset($p['original_price']) ? (string)$p['original_price'] : null,
            'discount_percent' => isset($p['discount_percent']) ? (int)$p['discount_percent'] : null,
            'image' => isset($p['image']) ? (string)$p['image'] : 'https://picsum.photos/400/400?random=' . ($p['id'] ?? rand(1, 1000)),
            'url' => isset($p['url']) ? (string)$p['url'] : '/product.php?id=' . (isset($p['id']) ? (int)$p['id'] : rand(1, 1000)),
            'store_name' => isset($p['store_name']) ? (string)$p['store_name'] : 'FezaMarket Store',
            'seller_name' => isset($p['seller_name']) ? (string)$p['seller_name'] : 'FezaMarket',
            'rating' => isset($p['rating']) ? (float)$p['rating'] : rand(4, 5),
            'reviews_count' => isset($p['reviews_count']) ? (int)$p['reviews_count'] : rand(10, 100),
            'featured' => isset($p['featured']) ? (bool)$p['featured'] : false
        ];
    }
}

/* ---------- Safe Fallback Product Generator ---------- */
if (!function_exists('createSampleProducts')) {
    function createSampleProducts($count = 12): array {
        $sample_products = [];
        $product_names = [
            'Wireless Bluetooth Headphones',
            'Smartphone Case with Card Holder',
            'Portable Power Bank 10000mAh',
            'LED Desk Lamp with USB Charging',
            'Water Resistant Fitness Tracker',
            'Premium Coffee Mug Set',
            'Ergonomic Laptop Stand',
            'Wireless Charging Pad',
            'Bluetooth Speaker Waterproof',
            'USB-C Cable 6ft Braided',
            'Phone Car Mount Magnetic',
            'Laptop Backpack Professional'
        ];
        
        $currency = Currency::getInstance();
        
        for ($i = 0; $i < $count; $i++) {
            $price = rand(15, 199);
            $original_price = rand($price + 10, $price + 50);
            $discount = round((($original_price - $price) / $original_price) * 100);
            
            $sample_products[] = [
                'id' => $i + 1,
                'title' => $product_names[$i % count($product_names)],
                'price' => $currency->formatPrice($price),
                'price_raw' => $price,
                'original_price' => $currency->formatPrice($original_price),
                'discount_percent' => $discount,
                'image' => '/images/placeholder-product.jpg',
                'url' => '/product/' . ($i + 1),
                'store_name' => 'FezaMarket',
                'seller_name' => 'FezaMarket',
                'rating' => 4 + (rand(0, 10) / 10),
                'reviews_count' => rand(15, 250),
                'featured' => true
            ];
        }
        
        return $sample_products;
    }
}

/* ---------- Real Product Fetcher from Database ---------- */
if (!function_exists('fetchRealProducts')) {
    function fetchRealProducts($limit = 12, $category_id = null): array {
        try {
            $pdo = db();
            
            // Build query to fetch real products
            $sql = "SELECT p.id, p.name as title, p.price, p.compare_price as original_price, 
                           p.image_url as image, p.slug, p.description, 
                           p.stock_quantity,
                           CASE 
                               WHEN p.compare_price IS NOT NULL AND p.compare_price > p.price 
                               THEN ROUND(((p.compare_price - p.price) / p.compare_price) * 100)
                               ELSE NULL
                           END as discount_percent
                    FROM products p 
                    WHERE p.status = 'active' AND p.stock_quantity > 0";
            
            if ($category_id) {
                $sql .= " AND p.category_id = :category_id";
            }
            
            $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            if ($category_id) {
                $stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            // Normalize product data for template use
            $currency = Currency::getInstance();
            $normalized = [];
            foreach ($products as $product) {
                $priceUSD = (float)$product['price'];
                $originalPriceUSD = $product['original_price'] ? (float)$product['original_price'] : null;
                
                $normalized[] = [
                    'id' => (int)$product['id'],
                    'title' => (string)$product['title'],
                    'price' => $currency->formatPrice($priceUSD),
                    'price_raw' => $priceUSD,
                    'original_price' => $originalPriceUSD ? $currency->formatPrice($originalPriceUSD) : null,
                    'discount_percent' => $product['discount_percent'] ? (int)$product['discount_percent'] : null,
                    'image' => $product['image'] ?: '/images/placeholder-product.jpg',
                    'url' => '/product/' . ($product['slug'] ?: $product['id']),
                    'store_name' => 'FezaMarket',
                    'seller_name' => 'FezaMarket',
                    'rating' => 4.5,
                    'reviews_count' => rand(10, 200),
                    'featured' => true
                ];
            }
            
            return $normalized;
            
        } catch (Exception $e) {
            error_log("Error fetching real products: " . $e->getMessage());
            // Fallback to sample products when database is unavailable
            return createSampleProducts($limit);
        }
    }
}

/* ---------- Banner Management Functions ---------- */
if (!function_exists('fetchBannerBySlotKey')) {
    function fetchBannerBySlotKey($slot_key): ?array {
        try {
            $pdo = db();
            
            $sql = "SELECT slot_key, title, subtitle, link_url, image_url, 
                           bg_image_path, fg_image_path, width, height
                    FROM banners 
                    WHERE slot_key = :slot_key";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':slot_key', $slot_key);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            
        } catch (Exception $e) {
            error_log("Error fetching banner by slot key: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('fetchBanners')) {
    function fetchBanners($position = 'hero'): array {
        try {
            $pdo = db();
            
            $sql = "SELECT id, title, subtitle, description, image_url, link_url, button_text,
                           background_color, text_color, sort_order
                    FROM homepage_banners 
                    WHERE status = 'active' 
                    AND position = :position
                    AND (start_date IS NULL OR start_date <= NOW())
                    AND (end_date IS NULL OR end_date >= NOW())
                    ORDER BY sort_order ASC";
                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':position', $position);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error fetching banners: " . $e->getMessage());
            return [];
        }
    }
}

$page_title = 'FezaMarket - Save Money. Live Better.';

// Fetch real products from database instead of mock data
try {
    $featured_products = fetchRealProducts(20);
} catch (Exception $e) {
    $featured_products = [];
}

// Use template helpers for curated product sections
try {
    // Flash Deals: Get products with significant discounts
    $deals = get_deals_section(12);
} catch (Exception $e) {
    $deals = [];
}

try {
    $electronics = fetchRealProducts(12, 1); // Category ID 1 for electronics
} catch (Exception $e) {
    $electronics = [];
}

try {
    $fashion = fetchRealProducts(15, 2); // Category ID 2 for fashion
} catch (Exception $e) {
    $fashion = [];
}

try {
    $home_garden = fetchRealProducts(12, 3); // Category ID 3 for home & garden
} catch (Exception $e) {
    $home_garden = [];
}

try {
    // Furniture section: Use category-specific products
    $furniture = get_furniture_section_content();
    if (empty($furniture)) {
        $furniture = fetchRealProducts(10, 4); // Fallback to category ID 4
    }
} catch (Exception $e) {
    $furniture = [];
}

try {
    // Trending products: Use products based on recent sales/views
    $trending_products = get_trending_products(10);
} catch (Exception $e) {
    $trending_products = [];
}

// NEW: Fetch new product sections for homepage overhaul
try {
    $best_selling = get_best_sellers(12);
} catch (Exception $e) {
    $best_selling = [];
}

try {
    $popular_products = get_popular_products(12);
} catch (Exception $e) {
    $popular_products = [];
}

try {
    $ai_recommended = get_ai_recommended_products(12);
} catch (Exception $e) {
    $ai_recommended = [];
}

try {
    $sponsored_products = get_sponsored_products(12);
} catch (Exception $e) {
    $sponsored_products = [];
}

// Fetch banners from database
try {
    $hero_banners = fetchBanners('hero');
    $grid_banners = fetchBanners('top');
} catch (Exception $e) {
    $hero_banners = [];
    $grid_banners = [];
}



includeHeader($page_title);
?>

<!-- Complete Walmart Homepage Layout -->
<div class="walmart-exact-layout">
    

    
    <!-- Top Grid Section -->
    <section class="top-grid-section">
        <div class="container-wide">
            <div class="walmart-grid">
                
                <!-- Fall Shoe Edit - Large Left -->
                <?php 
                $shoes_banner = fetchBannerBySlotKey('shoes-banner');
                $shoes_title = $shoes_banner && isset($shoes_banner['title']) ? $shoes_banner['title'] : 'The fall shoe edit';
                $shoes_bg = $shoes_banner && isset($shoes_banner['bg_image_path']) ? $shoes_banner['bg_image_path'] : null;
                $shoes_link = $shoes_banner && isset($shoes_banner['link_url']) ? $shoes_banner['link_url'] : '/category/shoes';
                $shoes_bg_style = $shoes_bg ? "background-image: url('$shoes_bg');" : "background: linear-gradient(45deg, #8B4513 0%, #D2691E 100%);";
                ?>
                <div class="grid-card card-1-1 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 1 / 1 / 3 / 3;" 
                     data-banner-type="grid" data-slot-key="shoes-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('shoes-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="<?php echo h($shoes_link); ?>" class="grid-card-link">
                        <div class="card-bg" style="<?php echo $shoes_bg_style; ?> background-size: cover;">
                            <div class="card-content-wrapper">
                                <div class="text-content">
                                    <span class="small-tag"><?php echo h($shoes_title); ?></span>
                                    <?php if ($shoes_banner && (isset($shoes_banner['fg_image_path']) || isset($shoes_banner['image_url']))): ?>
                                        <div class="card-image-small">
                                            <img src="<?php echo h($shoes_banner['fg_image_path'] ?: $shoes_banner['image_url']); ?>" alt="<?php echo h($shoes_title); ?>" style="object-fit: cover;">
                                        </div>
                                    <?php else: ?>
                                        <div class="card-image-small">
                                            <img src="https://picsum.photos/200/150?random=shoes1" alt="Fall Shoes" style="object-fit: cover;">
                                        </div>
                                    <?php endif; ?>
                                    <span class="shop-now-link">Shop now</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- FezaMarket Cash Back - Medium Center -->
                <div class="grid-card card-1-2 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 1 / 3 / 3 / 5;"
                     data-banner-type="grid" data-banner-id="cashback-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('cashback-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/membership" class="grid-card-link">
                        <div class="card-bg" style="background: linear-gradient(135deg, #004c91 0%, #0071ce 100%); background-size: cover;">
                            <div class="cashback-content">
                                <div class="cashback-text">
                                    <span class="cashback-small">FezaMarket members earn</span>
                                    <div class="cashback-big">
                                        <span class="percent">5%</span> <span class="cashback-desc">cash back at<br><strong>FezaMarket</strong></span>
                                    </div>
                                    <span class="learn-link">Learn how</span>
                                </div>
                                <div class="card-visual-right">
                                    <div class="credit-card-visual">
                                        <div class="card-inner">
                                            <div class="card-chip"></div>
                                            <div class="card-brand">FezaPay</div>
                                            <div class="card-logo">★</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Leaf Blowers - Small Right -->
                <div class="grid-card card-1-3 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 1 / 5 / 2 / 7;"
                     data-banner-type="grid" data-banner-id="leaf-blowers-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('leaf-blowers-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/category/garden" class="grid-card-link">
                        <div class="card-bg" style="background: linear-gradient(45deg, #27ae60 0%, #2ecc71 100%); background-size: cover;">
                            <div class="small-promo-content">
                                <span class="promo-tag-small">Leaf blowers, mowers & more</span>
                                <div class="promo-image-small">
                                    <img src="https://picsum.photos/120/80?random=garden1" alt="Garden Tools" style="object-fit: cover;">
                                </div>
                                <span class="shop-now-link">Shop now</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Dreamy Bedding - Medium Left -->
                <div class="grid-card card-2-1 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 3 / 1 / 4 / 3;"
                     data-banner-type="grid" data-banner-id="dreamy-bedding-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('dreamy-bedding-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/category/bedding" class="grid-card-link">
                        <div class="card-bg" style="background: #f8f9fa; background-size: cover;">
                            <div class="bedding-content">
                                <span class="promo-text">Save on dreamy bedding</span>
                                <div class="product-showcase-inline">
                                    <?php 
                                    $bedding_product = !empty($home_garden) ? safeNormalizeProduct($home_garden[0]) : safeNormalizeProduct(null);
                                    ?>
                                    <img src="<?php echo h($bedding_product['image']); ?>" alt="Bedding" class="bedding-img" style="object-fit: cover;">
                                    <div class="price-tag">from $50</div>
                                </div>
                                <span class="shop-now-link">Shop now</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Tech Savings - Small Right -->
                <div class="grid-card card-2-2 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 2 / 5 / 3 / 7;"
                     data-banner-type="grid" data-banner-id="tech-savings-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('tech-savings-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/category/electronics" class="grid-card-link">
                        <div class="card-bg" style="background: #fff3e0; background-size: cover;">
                            <div class="tech-savings-content">
                                <span class="savings-tag">Savings on tech—delivered fast</span>
                                <div class="tech-image-small">
                                    <img src="https://picsum.photos/120/80?random=laptop1" alt="Tech" style="object-fit: cover;">
                                </div>
                                <span class="shop-now-small">Shop now</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Resell FezaMarket - Medium Center -->
                <div class="grid-card card-2-3 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 3 / 3 / 4 / 5;"
                     data-banner-type="grid" data-banner-id="resell-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('resell-banner', 'grid')" title="Edit Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/resell" class="grid-card-link">
                        <div class="card-bg" style="background: #e8f5e8;">
                            <div class="resell-content">
                                <span class="resell-title">Resell at FezaMarket: fave rewards & cash</span>
                                <div class="resell-product">
                                    <div class="watch-container">
                                        <?php 
                                        $watch_product = !empty($electronics) ? safeNormalizeProduct($electronics[0]) : safeNormalizeProduct(null);
                                        ?>
                                        <img src="<?php echo h($watch_product['image']); ?>" alt="Smart Watch" class="watch-img">
                                        <div class="discount-badge-yellow">Up to 65% off</div>
                                    </div>
                                    <div class="flash-deal-badge">Flash Deal</div>
                                </div>
                                <span class="learn-more-link">Learn more</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Flash Deal - Small Right -->
                <div class="grid-card card-2-4 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 3 / 5 / 4 / 7;"
                     data-banner-type="grid" data-slot-key="flash-deal-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('flash-deal-banner', 'grid')" title="Edit Flash Deal Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/deals/flash" class="grid-card-link">
                        <div class="card-bg" style="background: #fff9c4;">
                            <div class="flash-item-content">
                                <?php 
                                $flash_product = !empty($electronics) && count($electronics) > 1 ? 
                                    safeNormalizeProduct($electronics[1]) : safeNormalizeProduct(null);
                                ?>
                                <div class="flash-item-image">
                                    <img src="<?php echo h($flash_product['image']); ?>" alt="Flash Deal">
                                </div>
                                <div class="flash-deal-text">
                                    <div class="flash-badge">Flash Deal</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Miss Mouth's - Small Left -->
                <div class="grid-card card-3-1 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 4 / 1 / 5 / 2;"
                     data-banner-type="grid" data-slot-key="messy-eater-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('messy-eater-banner', 'grid')" title="Edit Messy Eater Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/category/baby" class="grid-card-link">
                        <div class="card-bg" style="background: linear-gradient(45deg, #e3f2fd 0%, #bbdefb 100%);">
                            <div class="messy-eater-content">
                                <span class="product-tag-small">Miss Mouth's Messy Eater</span>
                                <div class="product-image-container">
                                    <img src="https://picsum.photos/100/120?random=baby1" alt="Baby Product">
                                </div>
                                <span class="shop-now-tiny">Shop now</span>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- New for Him & Her - Large Right -->
                <div class="grid-card card-3-2 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" style="grid-area: 4 / 5 / 5 / 7;">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-manage-btn" onclick="manageHimHerSection()" title="Manage Products">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                                Manage
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="card-bg" style="background: #fce4ec;">
                        <div class="him-her-content">
                            <h3 class="section-title-small">New for him & her</h3>
                            <div class="fashion-items-row">
                                <?php 
                                $fashion_items = array_slice($fashion, 0, 3);
                                if (empty($fashion_items)) {
                                    // Try to get real fashion products
                                    $fashion_items = fetchRealProducts(3, 2);
                                    if (empty($fashion_items)) {
                                        $fashion_items = fetchRealProducts(3); // Any products
                                    }
                                }
                                
                                // Only display if we have real items
                                if (!empty($fashion_items)):
                                    foreach($fashion_items as $fashion_item): 
                                        $item = safeNormalizeProduct($fashion_item); ?>
                                        <div class="fashion-item-small">
                                            <div class="fashion-image-container">
                                                <img src="<?php echo h($item['image']); ?>" alt="<?php echo h($item['title']); ?>" style="object-fit: cover;">
                                                <div class="heart-icon">♡</div>
                                            </div>
                                            <div class="item-price-small"><?php echo h($item['price']); ?></div>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <div class="no-fashion-items">
                                        <p>No fashion items available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="navigation-arrows">
                                <span class="arrow-left">‹</span>
                                <span class="arrow-right">›</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Burger King Partnership - Medium Center -->
                <div class="grid-card card-3-3 <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="grid-area: 4 / 2 / 5 / 5;"
                     data-banner-type="grid" data-slot-key="fezamarket-members-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('fezamarket-members-banner', 'grid')" title="Edit FezaMarket+ Members Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <a href="/partnership" class="grid-card-link">
                        <div class="card-bg" style="background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);">
                            <div class="burger-king-content">
                                <div class="bk-text">
                                    <span class="bk-title">FezaMarket+ Members get 25% off Burger King®</span>
                                    <span class="learn-more-btn-orange">Learn more</span>
                                </div>
                                <div class="bk-food-image">
                                    <img src="https://picsum.photos/180/120?random=burger1" alt="Food">
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- Mobile Category Cards Section -->
    <section class="mobile-categories-section">
        <div class="container">
            <div class="category-cards">
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=electronics" alt="Electronics">
                    <h3>Electronics</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=fashion" alt="Fashion">
                    <h3>Fashion</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=home" alt="Home">
                    <h3>Home</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=sports" alt="Sports">
                    <h3>Sports</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=auto" alt="Auto">
                    <h3>Auto</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=beauty" alt="Beauty">
                    <h3>Beauty</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=toys" alt="Toys">
                    <h3>Toys</h3>
                </div>
                <div class="category-card">
                    <img src="https://picsum.photos/80/80?random=books" alt="Books">
                    <h3>Books</h3>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Promo Cards Section -->
    <section class="mobile-promos-section">
        <div class="container">
            <div class="promo-cards">
                <?php 
                // Fetch promo banners for each slot
                for ($i = 1; $i <= 6; $i++):
                    $slot_key = "promo-grid-$i";
                    $promo_banner = fetchBannerBySlotKey($slot_key);
                    
                    // Default values
                    $default_titles = ['Flash Sale', 'Free Shipping', 'New Arrivals', 'Daily Deals', 'Top Rated', 'Best Sellers'];
                    $default_subtitles = [
                        'Up to 50% off electronics',
                        'On orders over $35',
                        'Latest fashion trends',
                        'Limited time offers',
                        'Highly rated products',
                        'Popular items'
                    ];
                    $default_backgrounds = [
                        'linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%)',
                        'linear-gradient(135deg, #4834d4 0%, #686de0 100%)',
                        'linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%)',
                        'linear-gradient(135deg, #26de81 0%, #20bf6b 100%)',
                        'linear-gradient(135deg, #fd79a8 0%, #e84393 100%)',
                        'linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%)'
                    ];
                    
                    $title = $promo_banner && isset($promo_banner['title']) ? $promo_banner['title'] : $default_titles[$i-1];
                    $subtitle = $promo_banner && isset($promo_banner['subtitle']) ? $promo_banner['subtitle'] : $default_subtitles[$i-1];
                    $bg_style = $promo_banner && isset($promo_banner['bg_image_path']) 
                        ? "background-image: url('" . h($promo_banner['bg_image_path']) . "'); background-size: cover; background-position: center;" 
                        : "background: " . $default_backgrounds[$i-1] . ";";
                    $link_url = $promo_banner && isset($promo_banner['link_url']) ? $promo_banner['link_url'] : '#';
                ?>
                <div class="promo-card <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     style="<?php echo $bg_style; ?>"
                     data-banner-type="promo" data-slot-key="<?php echo $slot_key; ?>">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('<?php echo $slot_key; ?>', 'promo')" title="Edit Promo Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if ($link_url && $link_url !== '#'): ?>
                        <a href="<?php echo h($link_url); ?>" class="promo-card-link">
                            <h3><?php echo h($title); ?></h3>
                            <p><?php echo h($subtitle); ?></p>
                        </a>
                    <?php else: ?>
                        <div class="promo-card-content">
                            <h3><?php echo h($title); ?></h3>
                            <p><?php echo h($subtitle); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

    <!-- Free Assembly Banner -->
    <section class="assembly-full-banner">
        <div class="container-wide">
            <div class="assembly-banner-content <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                 style="background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);"
                 data-banner-type="assembly" data-banner-id="free-assembly-banner">
                <?php if ($is_admin_logged_in): ?>
                    <div class="admin-edit-overlay">
                        <button class="admin-edit-btn" onclick="editBanner('free-assembly-banner', 'assembly')" title="Edit Assembly Banner">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                <div class="assembly-left">
                    <span class="assembly-tag">Only at FezaMarket</span>
                    <h2 class="assembly-title">Free Assembly</h2>
                    <span class="assembly-subtitle">fall prep</span>
                    <div class="assembly-fine-print">FREE ASSEMBLY</div>
                </div>
                <div class="assembly-right">
                    <img src="https://picsum.photos/500/250?random=furniture1" alt="Free Assembly" class="assembly-hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Styles for all your plans -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Styles for all your plans</h2>
                <a href="/fashion" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="styles-track">
                    <?php 
                    $style_products = !empty($fashion) ? array_slice($fashion, 0, 8) : [];
                    // If no real products, fallback but try to minimize sample data usage
                    if (empty($style_products)) {
                        // Try again with different approach
                        $style_products = fetchRealProducts(8, 2); // Try fashion category again
                        if (empty($style_products)) {
                            $style_products = fetchRealProducts(8); // Any products
                        }
                        // Do not create sample products - only display real products
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($style_products)):
                        foreach($style_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>" loading="lazy">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping available</span>
                                </div>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; 
                    else: ?>
                        <div class="no-products-message">
                            <p>No fashion products available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('styles-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- PrettyGarden Banner -->
    <section class="prettygarden-banner">
        <div class="container-wide">
            <div class="prettygarden-content <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                 style="background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%);"
                 data-banner-type="prettygarden" data-banner-id="prettygarden-banner">
                <?php if ($is_admin_logged_in): ?>
                    <div class="admin-edit-overlay">
                        <button class="admin-edit-btn" onclick="editBanner('prettygarden-banner', 'prettygarden')" title="Edit PrettyGarden Banner">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
                <div class="prettygarden-left">
                    <h2 class="pg-title">Dresses to sweaters</h2>
                    <h3 class="pg-subtitle">Just in from PrettyGarden</h3>
                    <div class="pg-brand">
                        <div class="pg-circle">PG</div>
                        <span class="pg-name">PrettyGarden</span>
                    </div>
                </div>
                <div class="prettygarden-right">
                    <?php 
                    $dress_product = !empty($fashion) ? safeNormalizeProduct($fashion[0]) : safeNormalizeProduct(null);
                    ?>
                    <img src="<?php echo h($dress_product['image']); ?>" alt="PrettyGarden Fashion" class="pg-model">
                </div>
            </div>
        </div>
    </section>

    <!-- Get it all right here -->
    <section class="categories-row-section <?php echo $is_admin_logged_in ? 'admin-editable-section' : ''; ?>">
        <div class="container">
            <div class="row-header">
                <h2>Get it all right here</h2>
                <div class="header-actions">
                    <a href="/categories" class="shop-all-link">Shop all</a>
                    <?php if ($is_admin_logged_in): ?>
                        <button class="admin-manage-btn" onclick="manageCategoryScroller()" title="Manage Categories">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            Manage
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="categories-horizontal-container">
                <div class="categories-track" id="category-scroller-track">
                    <?php
                    // Default categories
                    $default_categories = [
                        ['name' => 'Outdoor', 'image' => 'https://picsum.photos/120/120?random=outdoor1', 'url' => '/category/outdoor'],
                        ['name' => 'Gaming', 'image' => 'https://picsum.photos/120/120?random=gaming1', 'url' => '/category/gaming'],
                        ['name' => 'Auto', 'image' => 'https://picsum.photos/120/120?random=auto1', 'url' => '/category/auto'],
                        ['name' => 'Electronics', 'image' => 'https://picsum.photos/120/120?random=electronics1', 'url' => '/category/electronics'],
                        ['name' => 'Home', 'image' => 'https://picsum.photos/120/120?random=home1', 'url' => '/category/home'],
                        ['name' => 'Fashion', 'image' => 'https://picsum.photos/120/120?random=fashion1', 'url' => '/category/fashion'],
                        ['name' => 'Sports & outdoors', 'image' => 'https://picsum.photos/120/120?random=sports1', 'url' => '/category/sports']
                    ];
                    
                    // Try to fetch from database
                    try {
                        $pdo = db();
                        $stmt = $pdo->query("SELECT section_data FROM homepage_sections WHERE section_key = 'category_scroller' LIMIT 1");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($result && !empty($result['section_data'])) {
                            $custom_categories = json_decode($result['section_data'], true);
                            if ($custom_categories && is_array($custom_categories)) {
                                $default_categories = $custom_categories;
                            }
                        }
                    } catch (Exception $e) {
                        // Use defaults
                    }
                    
                    foreach ($default_categories as $category):
                    ?>
                    <div class="category-circle-item">
                        <a href="<?php echo h($category['url']); ?>">
                            <img src="<?php echo h($category['image']); ?>" alt="<?php echo h($category['name']); ?>" class="category-circle-img">
                            <span class="category-name"><?php echo h($category['name']); ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollCategories('right')">›</button>
            </div>
        </div>
    </section>

    <!-- Save on furniture -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Save on furniture</h2>
                <a href="/furniture" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="furniture-track">
                    <?php 
                    $furniture_products = !empty($furniture) ? $furniture : [];
                    // Try to get real furniture products instead of samples
                    if (empty($furniture_products)) {
                        $furniture_products = fetchRealProducts(6, 4); // Try furniture category
                        if (empty($furniture_products)) {
                            $furniture_products = fetchRealProducts(6); // Any products
                        }
                        // Do not create sample products - only display real products
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($furniture_products)):
                        foreach($furniture_products as $index => $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>" loading="lazy">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                                <?php if ($index < 2): ?>
                                    <div class="rollback-badge">Rollback</div>
                                <?php endif; ?>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="now-text">Now </span>
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No furniture products available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('furniture-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Flash Deals -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>Flash Deals</h2>
                <a href="/deals" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="deals-track">
                    <?php 
                    $deal_products = !empty($deals) ? $deals : [];
                    // Try to get real deal products
                    if (empty($deal_products)) {
                        $deal_products = fetchRealProducts(6); // Try to get any products as deals
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($deal_products)):
                        foreach($deal_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="now-text">Now </span>
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping, arrives in 3+ days</span>
                                </div>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No deals available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('deals-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Best Selling Products -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>🔥 Best Selling</h2>
                <a href="/products?sort=best-selling" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="best-selling-track">
                    <?php 
                    if (!empty($best_selling)):
                        foreach($best_selling as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                                <span class="badge badge-bestseller">Bestseller</span>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping available</span>
                                </div>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No bestsellers available yet. Check back soon!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('best-selling-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Popular Products (By Views) -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>⭐ Popular Now</h2>
                <a href="/products?sort=popular" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="popular-track">
                    <?php 
                    if (!empty($popular_products)):
                        foreach($popular_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                                <span class="badge badge-popular">Popular</span>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping available</span>
                                </div>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>Discovering popular products...</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('popular-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- AI Recommended Products -->
    <?php if (!empty($ai_recommended)): ?>
    <section class="product-row-section ai-recommended-section">
        <div class="container">
            <div class="row-header">
                <h2>🤖 AI Recommended for You</h2>
                <a href="/products?filter=ai-recommended" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="ai-recommended-track">
                    <?php 
                    foreach($ai_recommended as $product): 
                        $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                                <span class="badge badge-ai">AI Pick</span>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping available</span>
                                </div>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('ai-recommended-track', 'right')">›</button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sponsored Products -->
    <?php if (!empty($sponsored_products)): ?>
    <section class="product-row-section sponsored-section">
        <div class="container">
            <div class="row-header">
                <h2>💎 Sponsored Products</h2>
                <span class="sponsored-label">Ad</span>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="sponsored-track">
                    <?php 
                    foreach($sponsored_products as $product): 
                        $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>" onclick="trackSponsoredClick(<?php echo $product['id']; ?>)">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                                <span class="badge badge-sponsored">Sponsored</span>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="star-rating">
                                    <span class="stars">★★★★★</span>
                                    <span class="review-number"><?php echo $product['reviews_count']; ?></span>
                                </div>
                                <div class="shipping-text">
                                    <span class="free-shipping-text">Free shipping available</span>
                                </div>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('sponsored-track', 'right')">›</button>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Halloween Section - Three Cards -->
    <section class="halloween-section">
        <div class="container-wide">
            <div class="halloween-grid">
                <!-- Halloween Coziness -->
                <div class="halloween-card halloween-coziness <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     data-banner-type="halloween" data-banner-id="halloween-coziness-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('halloween-coziness-banner', 'halloween')" title="Edit Halloween Coziness Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="halloween-content" style="background: linear-gradient(135deg, #ff6600 0%, #ff9900 100%);">
                        <div class="halloween-text">
                            <span class="halloween-tag">Family-friendly & beyond</span>
                            <h2 class="halloween-title">Halloween coziness</h2>
                            <a href="/halloween" class="shop-now-halloween">Shop now</a>
                        </div>
                        <div class="halloween-image">
                            <img src="https://picsum.photos/250/200?random=halloween-family" alt="Halloween Family">
                        </div>
                    </div>
                </div>

                <!-- Halloween Kitchen -->
                <div class="halloween-card halloween-kitchen <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     data-banner-type="halloween" data-banner-id="halloween-kitchen-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('halloween-kitchen-banner', 'halloween')" title="Edit Halloween Kitchen Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="halloween-content" style="background: #663399;">
                        <h2 class="halloween-kitchen-title">Halloween kitchen & dining</h2>
                        <div class="kitchen-items">
                            <div class="kitchen-item">
                                <img src="https://picsum.photos/80/80?random=candy1" alt="Halloween Candy">
                                <span class="kitchen-text">Halloween candy $10 & under</span>
                            </div>
                            <div class="kitchen-item">
                                <img src="https://picsum.photos/80/80?random=candy2" alt="Halloween Treats">
                                <span class="kitchen-text">Halloween bites & how from $9.98</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Halloween Fashion -->
                <div class="halloween-card halloween-fashion <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     data-banner-type="halloween" data-banner-id="halloween-fashion-banner">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('halloween-fashion-banner', 'halloween')" title="Edit Halloween Fashion Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="halloween-content" style="background: linear-gradient(135deg, #ff6b9d 0%, #ffa8cc 100%);">
                        <h2 class="halloween-fashion-title">Fierce & festive Halloween fashion</h2>
                        <div class="fashion-halloween-grid">
                            <img src="https://picsum.photos/60/80?random=costume1" alt="Costume 1">
                            <img src="https://picsum.photos/60/80?random=costume2" alt="Costume 2">
                            <img src="https://picsum.photos/60/80?random=costume3" alt="Costume 3">
                            <img src="https://picsum.photos/60/80?random=costume4" alt="Costume 4">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- All the Halloween feels -->
    <section class="product-row-section">
        <div class="container">
            <div class="row-header">
                <h2>All the Halloween feels</h2>
                <a href="/halloween" class="shop-all-link">Shop all</a>
            </div>
            <div class="products-horizontal-container">
                <div class="products-track" id="halloween-track">
                    <?php 
                    // Use new arrivals for this section to show different products
                    $halloween_products = get_new_arrivals(6);
                    // Fallback if no products from helper
                    if (empty($halloween_products)) {
                        $halloween_products = fetchRealProducts(6); // Any products
                    }
                    
                    // Only display products if we have real ones
                    if (!empty($halloween_products)):
                        foreach($halloween_products as $product): 
                            $product = safeNormalizeProduct($product); ?>
                        <div class="walmart-product-card">
                            <div class="product-image-container">
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" aria-label="View <?php echo h($product['title']); ?>">
                                    <img src="<?php echo h($product['image']); ?>" alt="<?php echo h($product['title']); ?>">
                                </a>
                                <button class="wishlist-heart" onclick="toggleWishlist(<?php echo $product['id']; ?>)">♡</button>
                            </div>
                            <div class="product-details">
                                <div class="price-section">
                                    <span class="current-price-large"><?php echo h($product['price']); ?></span>
                                    <?php if ($product['original_price']): ?>
                                        <span class="crossed-price"><?php echo h($product['original_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/product.php?id=<?= (int)$product['id'] ?>" class="product-name-link">
                                    <p class="product-name"><?php echo h($product['title']); ?></p>
                                </a>
                                <div class="action-buttons">
                                    <form action="/cart/add.php" method="POST" class="add-to-cart-form">
                                        <?php echo getCsrfToken(); ?>
                                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
                                    </form>
                                    <a href="/product.php?id=<?= (int)$product['id'] ?>" class="options-button">Options</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach;
                    else: ?>
                        <div class="no-products-message">
                            <p>No trending products available at the moment. Please check back later!</p>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="scroll-right-btn" onclick="scrollProducts('halloween-track', 'right')">›</button>
            </div>
        </div>
    </section>

    <!-- Trending on social -->
    <section class="social-trending-section">
        <div class="container">
            <div class="row-header">
                <h2>Trending on social</h2>
            </div>
            <div class="social-images-grid">
                <?php 
                for ($i = 1; $i <= 3; $i++):
                    $slot_key = "trending-$i";
                    $trending_banner = fetchBannerBySlotKey($slot_key);
                    
                    $image_url = $trending_banner && isset($trending_banner['bg_image_path']) 
                        ? $trending_banner['bg_image_path'] 
                        : "https://picsum.photos/400/400?random=social$i";
                    $link_url = $trending_banner && isset($trending_banner['link_url']) 
                        ? $trending_banner['link_url'] 
                        : '#';
                    $button_text = $trending_banner && isset($trending_banner['title']) 
                        ? $trending_banner['title'] 
                        : 'Shop the look';
                ?>
                <div class="social-image-card <?php echo $is_admin_logged_in ? 'admin-editable' : ''; ?>" 
                     data-banner-type="trending" data-slot-key="<?php echo $slot_key; ?>">
                    <?php if ($is_admin_logged_in): ?>
                        <div class="admin-edit-overlay">
                            <button class="admin-edit-btn" onclick="editBanner('<?php echo $slot_key; ?>', 'trending')" title="Edit Trending Banner">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>
                    <?php if ($link_url && $link_url !== '#'): ?>
                        <a href="<?php echo h($link_url); ?>" class="social-card-link">
                            <img src="<?php echo h($image_url); ?>" alt="Trending <?php echo $i; ?>">
                            <div class="social-overlay">
                                <span class="shop-the-look"><?php echo h($button_text); ?></span>
                            </div>
                        </a>
                    <?php else: ?>
                        <img src="<?php echo h($image_url); ?>" alt="Trending <?php echo $i; ?>">
                        <div class="social-overlay">
                            <span class="shop-the-look"><?php echo h($button_text); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>

</div>

<!-- Complete Walmart Styling -->
<style>
/* Reset and Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    background-color: #f7f7f7;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}

.container-wide {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 16px;
}

/* Top Grid */
.top-grid-section {
    background: white;
    padding: 16px 0;
}

.walmart-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    grid-template-rows: repeat(4, 180px);
    gap: 12px;
    max-width: 1200px;
    margin: 0 auto;
}

.grid-card {
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.grid-card:hover {
    transform: translateY(-2px);
}

/* Admin Edit Functionality */
.admin-editable {
    position: relative;
}

.admin-edit-overlay {
    position: absolute;
    top: 8px;
    right: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1000;
    pointer-events: auto;
}

.admin-editable:hover .admin-edit-overlay {
    opacity: 1;
}

.admin-edit-btn {
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.admin-edit-btn:hover {
    background: #0071ce;
    transform: scale(1.1);
}

/* Admin manage button for sections */
.admin-manage-btn {
    background: rgba(0, 113, 206, 0.9);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    margin-left: 12px;
}

.admin-manage-btn:hover {
    background: #0071ce;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 113, 206, 0.3);
}

.admin-manage-btn svg {
    flex-shrink: 0;
}

.admin-editable-section {
    position: relative;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Clickable banner links */
.promo-card-link,
.social-card-link,
.grid-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    width: 100%;
    height: 100%;
}

.promo-card-link:hover,
.social-card-link:hover,
.grid-card-link:hover {
    transform: translateY(-2px);
    transition: transform 0.2s ease;
}

.promo-card {
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.promo-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.social-card-link {
    position: relative;
}

.grid-card-link {
    position: relative;
    display: block;
}

.social-image-card {
    transition: transform 0.2s ease;
}

.social-image-card:hover {
    transform: translateY(-2px);
}

.category-circle-item a {
    text-decoration: none;
    color: inherit;
    display: block;
}

.category-circle-item a:hover .category-circle-img {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}

/* Enhanced Image Handling - Apply to ALL banners */
.banner {
    position: relative;
    overflow: hidden;
}

.banner > img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.card-bg,
.hero-content,
.assembly-banner-content,
.prettygarden-content,
.halloween-content {
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}

/* Fix banner gaps and ensure full coverage */
.grid-card,
.walmart-grid .grid-card {
    margin: 0;
    padding: 0;
    overflow: hidden;
}

.walmart-grid {
    gap: 12px;
    margin: 0 auto;
    padding: 0;
}

.product-image-container img,
.card-image-small img,
.bedding-img,
.tech-image-small img,
.assembly-hero-image,
.pg-model {
    object-fit: cover !important;
    width: 100%;
    height: 100%;
}

/* Placeholder image handling */
.product-image-container img[src="/images/placeholder-product.jpg"],
.product-image-container img[src*="placeholder"] {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 14px;
    text-align: center;
}

.product-image-container img[src="/images/placeholder-product.jpg"]:before {
    content: "Product Image";
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

/* Hero Banner Styles */
.hero-banner-section {
    margin: 20px 0 30px 0;
}

.hero-banner {
    position: relative;
    width: 100%;
    height: 400px;
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.hero-content {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding: 40px;
    position: relative;
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}

.hero-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1;
}

.hero-text {
    position: relative;
    z-index: 2;
    color: white;
    max-width: 600px;
}

.hero-text h1 {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.hero-subtitle {
    font-size: 1.5rem;
    margin-bottom: 1rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.hero-description {
    font-size: 1.1rem;
    margin-bottom: 2rem;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
}

.hero-cta-btn {
    background: #0071ce;
    color: white;
    padding: 15px 30px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    transition: background-color 0.3s ease;
    text-shadow: none;
}

.hero-cta-btn:hover {
    background: #004c91;
    color: white;
    text-decoration: none;
}

/* Add to Cart Button Styling */
.add-to-cart-btn {
    background: #0071ce;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
    margin-right: 8px;
}

.add-to-cart-btn:hover {
    background: #004c91;
}

/* No Products Message Styling */
.no-products-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-style: italic;
}

.no-fashion-items {
    text-align: center;
    padding: 20px;
    color: #666;
    font-size: 0.9rem;
}

.card-bg {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    color: white;
}

/* Card Content Styles */
.card-content-wrapper {
    display: flex;
    flex-direction: column;
    height: 100%;
    justify-content: space-between;
}

.small-tag, .promo-tag-small, .product-tag-small {
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    align-self: flex-start;
    margin-bottom: 8px;
}

.card-image-small, .promo-image-small {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-image-small img, .promo-image-small img {
    max-width: 80%;
    height: auto;
    border-radius: 4px;
}

.shop-now-link, .shop-now-small, .shop-now-tiny {
    color: rgba(255,255,255,0.9);
    text-decoration: underline;
    font-size: 12px;
    font-weight: 600;
    margin-top: auto;
}

/* Cashback Card */
.cashback-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    height: 100%;
}

.cashback-text {
    flex: 1;
}

.cashback-small {
    font-size: 13px;
    font-weight: 400;
    display: block;
    margin-bottom: 8px;
}

.cashback-big {
    font-size: 18px;
    line-height: 1.2;
    margin-bottom: 12px;
}

.cashback-big .percent {
    font-size: 36px;
    font-weight: 700;
}

.learn-link {
    color: white;
    text-decoration: underline;
    font-size: 12px;
}

.card-visual-right {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.credit-card-visual {
    width: 140px;
    height: 90px;
    background: linear-gradient(45deg, #1e3a8a, #3b82f6);
    border-radius: 8px;
    position: relative;
    padding: 12px;
}

.card-chip {
    width: 18px;
    height: 14px;
    background: #ffd700;
    border-radius: 2px;
    position: absolute;
    top: 12px;
    left: 12px;
}

.card-brand {
    position: absolute;
    bottom: 12px;
    left: 12px;
    font-size: 12px;
    font-weight: 700;
    color: white;
}

.card-logo {
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 16px;
    color: #ffd700;
}

/* Bedding Card */
.bedding-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.promo-text {
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.product-showcase-inline {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.bedding-img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 8px;
}

.price-tag {
    font-size: 16px;
    font-weight: 700;
    color: #000;
}

/* Tech Savings */
.tech-savings-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.savings-tag {
    font-size: 12px;
    font-weight: 600;
    color: #f57c00;
}

.tech-image-small {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.tech-image-small img {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

/* Resell Card */
.resell-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.resell-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    line-height: 1.3;
}

.resell-product {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.watch-container {
    position: relative;
    margin-bottom: 8px;
}

.watch-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.discount-badge-yellow {
    background: #ffeb3b;
    color: #333;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
}

.flash-deal-badge {
    background: #ff4444;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

.learn-more-link {
    font-size: 11px;
    color: #0071ce;
    text-decoration: underline;
    font-weight: 600;
}

/* Flash Item */
.flash-item-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    align-items: center;
    justify-content: space-between;
    color: #333;
}

.flash-item-image {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flash-item-image img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

.flash-badge {
    background: #ff4444;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
}

/* Messy Eater */
.messy-eater-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
    justify-content: space-between;
}

.product-image-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image-container img {
    width: 60px;
    height: auto;
    border-radius: 4px;
}

/* Him & Her */
.him-her-content {
    display: flex;
    flex-direction: column;
    height: 100%;
    color: #333;
}

.section-title-small {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.fashion-items-row {
    display: flex;
    gap: 8px;
    flex: 1;
    align-items: center;
}

.fashion-item-small {
    text-align: center;
}

.fashion-image-container {
    position: relative;
    margin-bottom: 4px;
}

.fashion-image-container img {
    width: 50px;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
}

.heart-icon {
    position: absolute;
    top: 2px;
    right: 2px;
    background: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.item-price-small {
    font-size: 11px;
    font-weight: 600;
    color: #000;
}

.navigation-arrows {
    display: flex;
    justify-content: flex-end;
    gap: 4px;
    margin-top: 8px;
}

.arrow-left, .arrow-right {
    background: white;
    border: 1px solid #ccc;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    cursor: pointer;
}

/* Burger King */
.burger-king-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    height: 100%;
    color: white;
}

.bk-text {
    flex: 1;
    padding-right: 12px;
}

.bk-title {
    font-size: 16px;
    font-weight: 600;
    line-height: 1.3;
    margin-bottom: 12px;
}

.learn-more-btn-orange {
    background: white;
    color: #ff6b35;
    padding: 6px 12px;
    border-radius: 16px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
}

.bk-food-image img {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
}

/* Assembly Banner */
.assembly-full-banner {
    margin: 16px 0;
}

.assembly-banner-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 32px;
    border-radius: 8px;
    color: white;
    min-height: 200px;
}

.assembly-left {
    flex: 1;
}

.assembly-tag {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 12px;
}

.assembly-title {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 4px;
    line-height: 1;
}

.assembly-subtitle {
    font-size: 24px;
    font-weight: 300;
    display: block;
    margin-bottom: 12px;
}

.assembly-fine-print {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 2px;
}

.assembly-right {
    flex: 1;
    display: flex;
    justify-content: center;
}

.assembly-hero-image {
    max-width: 400px;
    height: 160px;
    object-fit: cover;
    border-radius: 8px;
}

/* Product Rows */
.product-row-section {
    background: white;
    padding: 24px 0;
    margin-bottom: 12px;
}

.row-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.row-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.shop-all-link {
    color: #0071ce;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.shop-all-link:hover {
    text-decoration: underline;
}

.products-horizontal-container {
    position: relative;
    overflow: hidden;
}

.products-track {
    display: flex;
    gap: 16px;
    transition: transform 0.3s ease;
    padding-bottom: 8px;
}

.walmart-product-card {
    background: white;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    min-width: 200px;
    flex-shrink: 0;
    overflow: hidden;
    transition: box-shadow 0.2s ease;
}

.walmart-product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.product-image-container {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-image-container img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
    max-width: 100%;
    max-height: 100%;
}

.wishlist-heart {
    position: absolute;
    top: 8px;
    right: 8px;
    background: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    color: #666;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.wishlist-heart:hover {
    color: #ff4444;
    background: #f9f9f9;
}

.rollback-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #ff4444;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

/* New Product Badges */
.badge {
    position: absolute;
    top: 8px;
    left: 8px;
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.badge-bestseller {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
}

.badge-popular {
    background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
}

.badge-ai {
    background: linear-gradient(135deg, #00d2d3 0%, #54a0ff 100%);
}

.badge-sponsored {
    background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);
    color: #333;
}

/* Sponsored Section Styles */
.sponsored-section .row-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.sponsored-label {
    background: #f0f0f0;
    color: #666;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

/* AI Recommended Section */
.ai-recommended-section {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 24px 0;
    margin: 24px 0;
}

.product-details {
    padding: 16px;
}

.price-section {
    margin-bottom: 8px;
}

.now-text {
    font-size: 14px;
    color: #666;
    margin-right: 2px;
}

.current-price-large {
    font-size: 18px;
    font-weight: 700;
    color: #000;
}

.crossed-price {
    font-size: 14px;
    color: #666;
    text-decoration: line-through;
    margin-left: 6px;
}

.product-name {
    font-size: 14px;
    color: #333;
    line-height: 1.3;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 36px;
}

/* Product name link wrapper */
.product-name-link {
    text-decoration: none;
    color: inherit;
}

.product-name-link:hover .product-name {
    color: #0654ba;
    text-decoration: underline;
}

/* Product image link */
.product-image-container a {
    display: block;
    width: 100%;
    height: 100%;
}

.product-image-container a img {
    transition: transform 0.3s ease;
}

.product-image-container a:hover img {
    transform: scale(1.05);
}

.star-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 8px;
}

.stars {
    font-size: 12px;
    color: #ffc107;
}

.review-number {
    font-size: 12px;
    color: #666;
}

.shipping-text {
    margin-bottom: 12px;
}

.free-shipping-text {
    font-size: 12px;
    color: #2e7d32;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-buttons .add-to-cart-form {
    flex: 1;
    display: flex;
}

.action-buttons .add-to-cart-form button {
    flex: 1;
}

.options-button, .add-button {
    flex: 1;
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.options-button {
    background: transparent;
    border: 1px solid #0071ce;
    color: #0071ce;
}

.options-button:hover {
    background: #0071ce;
    color: white;
}

.add-button {
    background: #0071ce;
    border: 1px solid #0071ce;
    color: white;
}

.add-button:hover {
    background: #004c91;
}

.scroll-right-btn {
    position: absolute;
    right: -16px;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #ddd;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    color: #666;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 10;
}

.scroll-right-btn:hover {
    background: #f5f5f5;
    color: #333;
}

/* PrettyGarden Banner */
.prettygarden-banner {
    margin: 16px 0;
}

.prettygarden-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 32px;
    border-radius: 8px;
    color: white;
    min-height: 180px;
}

.prettygarden-left {
    flex: 1;
}

.pg-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
}

.pg-subtitle {
    font-size: 24px;
    font-weight: 400;
    margin-bottom: 16px;
}

.pg-brand {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pg-circle {
    background: white;
    color: #ff69b4;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
}

.pg-name {
    font-size: 18px;
    font-weight: 400;
}

.prettygarden-right {
    flex: 1;
    display: flex;
    justify-content: center;
}

.pg-model {
    max-width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 8px;
}

/* Categories */
.categories-row-section {
    background: white;
    padding: 24px 0;
    margin-bottom: 12px;
}

.categories-horizontal-container {
    position: relative;
    overflow: hidden;
}

.categories-track {
    display: flex;
    gap: 24px;
    transition: transform 0.3s ease;
    padding-bottom: 8px;
}

.category-circle-item {
    text-align: center;
    min-width: 100px;
    flex-shrink: 0;
}

.category-circle-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 8px;
}

.category-name {
    font-size: 14px;
    color: #333;
    font-weight: 500;
    line-height: 1.2;
}

/* Halloween Section */
.halloween-section {
    background: white;
    padding: 32px 0;
    margin: 16px 0;
}

.halloween-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
}

.halloween-card {
    border-radius: 8px;
    overflow: hidden;
    min-height: 280px;
}

.halloween-content {
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    color: white;
}

.halloween-coziness .halloween-content {
    justify-content: space-between;
}

.halloween-tag {
    font-size: 12px;
    background: rgba(255,255,255,0.2);
    padding: 4px 8px;
    border-radius: 12px;
    display: inline-block;
    align-self: flex-start;
    margin-bottom: 12px;
}

.halloween-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 16px;
}

.shop-now-halloween {
    background: white;
    color: #ff6600;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    align-self: flex-start;
}

.halloween-image {
    text-align: center;
}

.halloween-image img {
    max-width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

.halloween-kitchen-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
}

.kitchen-items {
    flex: 1;
}

.kitchen-item {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.kitchen-item img {
    width: 50px;
    height: 50px;
    border-radius: 4px;
    object-fit: cover;
}

.kitchen-text {
    font-size: 13px;
    line-height: 1.3;
    color: white;
}

.halloween-fashion-title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    color: white;
}

.fashion-halloween-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    flex: 1;
}

.fashion-halloween-grid img {
    width: 100%;
    height: 70px;
    object-fit: cover;
    border-radius: 4px;
}

/* Social Trending */
.social-trending-section {
    background: white;
    padding: 24px 0;
    margin-bottom: 12px;
}

.social-images-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
}

.social-image-card {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    height: 400px;
}

.social-image-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.social-overlay {
    position: absolute;
    bottom: 16px;
    left: 16px;
    right: 16px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 12px;
    border-radius: 4px;
    text-align: center;
}

.shop-the-look {
    font-size: 14px;
    font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
    /* Hero Banner Mobile Styles */
    .hero-content {
        min-height: 300px;
        padding: 20px;
    }
    
    .hero-text h1 {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1.2rem;
    }
    
    .hero-description {
        font-size: 1rem;
    }
    
    .hero-cta-btn {
        padding: 12px 24px;
        font-size: 1rem;
    }
    
    .walmart-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: repeat(8, 160px);
    }
    
    .walmart-grid .grid-card {
        grid-column: span 1 !important;
        grid-row: span 1 !important;
    }
    
    .assembly-banner-content,
    .prettygarden-content {
        flex-direction: column;
        text-align: center;
        gap: 16px;
    }
    
    .halloween-grid {
        grid-template-columns: 1fr;
    }
    
    .social-images-grid {
        grid-template-columns: 1fr;
    }
    
    .walmart-product-card {
        min-width: 160px;
    }
    
    .scroll-right-btn {
        display: none;
    }
}
</style>

<!-- JavaScript for Functionality -->
<script>
/* ---------- Admin Banner Editing Functions ---------- */
function editBanner(slotKey, bannerType) {
    // Create modal for editing banner
    const modal = document.createElement('div');
    modal.className = 'admin-edit-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeEditModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3>Edit Banner</h3>
                    <button onclick="closeEditModal()" class="close-btn">&times;</button>
                </div>
                <form id="edit-banner-form" onsubmit="saveBanner(event, '${slotKey}', '${bannerType}')" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Title:</label>
                        <input type="text" name="title" id="banner-title" maxlength="200">
                    </div>
                    <div class="form-group">
                        <label>Subtitle:</label>
                        <input type="text" name="subtitle" id="banner-subtitle" maxlength="255">
                    </div>
                    <div class="form-group">
                        <label>Background Image (Upload):</label>
                        <input type="file" name="bg_image" id="banner-bg-image" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small>Max size: 5MB. Supports JPEG, PNG, WebP</small>
                        <div id="current-bg-preview" style="margin-top: 10px;"></div>
                    </div>
                    <div class="form-group">
                        <label>Foreground/Overlay Image URL:</label>
                        <input type="url" name="image_url" id="banner-image-url" placeholder="https://example.com/overlay.jpg">
                    </div>
                    <div class="form-group">
                        <label>Foreground/Overlay Image (Upload):</label>
                        <input type="file" name="fg_image" id="banner-fg-image" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small>Optional overlay image upload</small>
                        <div id="current-fg-preview" style="margin-top: 10px;"></div>
                    </div>
                    <div class="form-group">
                        <label>Link URL:</label>
                        <input type="url" name="link_url" id="banner-link">
                    </div>
                    <div class="form-group">
                        <label>Width (pixels):</label>
                        <input type="number" name="width" id="banner-width" min="1" placeholder="e.g., 1200">
                    </div>
                    <div class="form-group">
                        <label>Height (pixels):</label>
                        <input type="number" name="height" id="banner-height" min="1" placeholder="e.g., 400">
                    </div>
                    <input type="hidden" name="slot_key" value="${slotKey}">
                    <div class="modal-actions">
                        <button type="button" onclick="closeEditModal()">Cancel</button>
                        <button type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    `;
    
    // Add modal styles
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .admin-edit-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; }
            .modal-overlay { background: rgba(0,0,0,0.8); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
            .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .image-upload-options { border: 1px solid #e0e0e0; padding: 15px; border-radius: 4px; background: #f9f9f9; }
            .upload-option { margin-bottom: 10px; }
            .upload-divider { text-align: center; margin: 15px 0; font-weight: bold; color: #666; }
            .upload-option small { color: #666; font-size: 12px; display: block; margin-top: 4px; }
            .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
            .modal-actions button { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
            .modal-actions button[type="submit"] { background: #0071ce; color: white; }
            .modal-actions button[type="button"] { background: #ccc; }
            .current-image-preview { max-width: 200px; max-height: 150px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; }
        `;
        document.head.appendChild(styles);
    }
    
    document.body.appendChild(modal);
    
    // Load existing banner data
    loadBannerData(slotKey);
}

function loadBannerData(slotKey) {
    fetch(`/api/banners/get.php?slot_key=${encodeURIComponent(slotKey)}`)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.banner) {
                const banner = result.banner;
                
                // Populate form fields
                if (banner.title) document.getElementById('banner-title').value = banner.title;
                if (banner.subtitle) document.getElementById('banner-subtitle').value = banner.subtitle;
                if (banner.link_url) document.getElementById('banner-link').value = banner.link_url;
                if (banner.image_url) document.getElementById('banner-image-url').value = banner.image_url;
                if (banner.width) document.getElementById('banner-width').value = banner.width;
                if (banner.height) document.getElementById('banner-height').value = banner.height;
                
                // Show current background image preview
                if (banner.bg_image_path) {
                    const bgPreview = document.getElementById('current-bg-preview');
                    bgPreview.innerHTML = `
                        <div style="margin-top: 10px;">
                            <strong>Current Background:</strong><br>
                            <img src="${banner.bg_image_path}" class="current-image-preview" alt="Current background">
                        </div>
                    `;
                }
                
                // Show current foreground image preview
                if (banner.fg_image_path) {
                    const fgPreview = document.getElementById('current-fg-preview');
                    fgPreview.innerHTML = `
                        <div style="margin-top: 10px;">
                            <strong>Current Overlay:</strong><br>
                            <img src="${banner.fg_image_path}" class="current-image-preview" alt="Current overlay">
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error loading banner data:', error);
        });
}

function closeEditModal() {
    const modal = document.querySelector('.admin-edit-modal');
    if (modal) {
        modal.remove();
    }
}

function saveBanner(event, slotKey, bannerType) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Ensure slot_key is set
    if (!formData.has('slot_key')) {
        formData.append('slot_key', slotKey);
    }
    
    // Show loading state
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    // Send request to our new dedicated endpoint
    fetch('/admin/banner-save.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(result => {
        if (result.ok) {
            alert('Banner updated successfully!');
            closeEditModal();
            // Refresh the page to show changes
            location.reload();
        } else {
            alert('Error updating banner: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating banner. Please try again.');
    })
    .finally(() => {
        // Restore button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

/* ---------- Add to Cart Functionality - Progressive Enhancement ---------- */
// Delegated form submit handler for Add to Cart forms
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions for add-to-cart forms
    document.addEventListener('submit', function(event) {
        // Check if this is an add-to-cart form
        if (event.target.classList.contains('add-to-cart-form')) {
            // If user is logged in and JavaScript is enabled, use AJAX
            if (window.isLoggedIn) {
                event.preventDefault();
                
                const form = event.target;
                const submitBtn = form.querySelector('button[type="submit"]');
                const productId = form.querySelector('input[name="product_id"]').value;
                const quantity = form.querySelector('input[name="quantity"]').value || 1;
                
                // Show loading state
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Adding...';
                submitBtn.disabled = true;
                
                // Send AJAX request to add product to cart
                fetch('/cart/ajax-add.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        product_id: parseInt(productId),
                        quantity: parseInt(quantity)
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        // Show success message
                        submitBtn.textContent = 'Added!';
                        submitBtn.classList.add('btn-success');
                        
                        // Update cart count if element exists
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount && result.cart_count) {
                            cartCount.textContent = result.cart_count;
                        }
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            submitBtn.textContent = originalText;
                            submitBtn.classList.remove('btn-success');
                            submitBtn.disabled = false;
                        }, 2000);
                    } else {
                        // If login required, store action and redirect
                        if (result.login_required) {
                            fetch('/api/store-action.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    action: 'add_to_cart',
                                    product_id: parseInt(productId),
                                    quantity: parseInt(quantity)
                                })
                            })
                            .then(() => {
                                window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
                            })
                            .catch(() => {
                                window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
                            });
                        } else {
                            alert('Error: ' + (result.message || 'Could not add to cart'));
                            submitBtn.textContent = originalText;
                            submitBtn.disabled = false;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error adding product to cart. Please try again.');
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            }
            // If not logged in or JavaScript is disabled, allow form to submit normally
            // This will POST to /cart/add.php which handles login redirect
        }
    });
});

// Legacy function for backward compatibility (if called from external scripts)
function addToCart(event, productId) {
    console.warn('Legacy addToCart function called. Forms with progressive enhancement are now preferred.');
    if (event) {
        event.preventDefault();
    }
}

/* ---------- Existing Functions ---------- */
function scrollProducts(trackId, direction) {
    const track = document.getElementById(trackId);
    const scrollAmount = 220;
    
    if (direction === 'right') {
        track.scrollLeft += scrollAmount;
        
        // Reset to start if at the end
        if (track.scrollLeft >= track.scrollWidth - track.offsetWidth) {
            setTimeout(() => {
                track.scrollLeft = 0;
            }, 100);
        }
    } else {
        track.scrollLeft -= scrollAmount;
    }
}

// Track sponsored product clicks
function trackSponsoredClick(productId) {
    // Send tracking data to backend
    if (navigator.sendBeacon) {
        navigator.sendBeacon('/api/track-sponsored-click.php', JSON.stringify({
            product_id: productId,
            timestamp: Date.now()
        }));
    } else {
        // Fallback for older browsers
        fetch('/api/track-sponsored-click.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({product_id: productId, timestamp: Date.now()})
        }).catch(() => {}); // Silent fail
    }
}

function scrollCategories(direction) {
    const track = document.querySelector('.categories-track');
    const scrollAmount = 200;
    
    if (direction === 'right') {
        track.scrollLeft += scrollAmount;
        
        // Reset to start if at the end
        if (track.scrollLeft >= track.scrollWidth - track.offsetWidth) {
            setTimeout(() => {
                track.scrollLeft = 0;
            }, 100);
        }
    } else {
        track.scrollLeft -= scrollAmount;
    }
}

/* ---------- Category Scroller Management ---------- */
function manageCategoryScroller() {
    // Add modal styles if not already present
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .admin-edit-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; }
            .modal-overlay { background: rgba(0,0,0,0.8); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
            .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .image-upload-options { border: 1px solid #e0e0e0; padding: 15px; border-radius: 4px; background: #f9f9f9; }
            .upload-option { margin-bottom: 10px; }
            .upload-divider { text-align: center; margin: 15px 0; font-weight: bold; color: #666; }
            .upload-option small { color: #666; font-size: 12px; display: block; margin-top: 4px; }
            .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
            .modal-actions button { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
            .modal-actions button[type="submit"] { background: #0071ce; color: white; }
            .modal-actions button[type="button"] { background: #ccc; }
            .current-image-preview { max-width: 200px; max-height: 150px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; }
        `;
        document.head.appendChild(styles);
    }
    
    // Create modal for managing category scroller
    const modal = document.createElement('div');
    modal.className = 'admin-edit-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeCategoryModal()">
            <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 800px;">
                <div class="modal-header">
                    <h3>Manage Category Scroller</h3>
                    <button onclick="closeCategoryModal()" class="close-btn">&times;</button>
                </div>
                <div class="category-manager">
                    <p>Category management interface coming soon. This will allow you to:</p>
                    <ul style="margin: 15px 0; padding-left: 30px;">
                        <li>Add new categories with images and links</li>
                        <li>Edit existing categories</li>
                        <li>Reorder categories by drag and drop</li>
                        <li>Delete categories</li>
                    </ul>
                    <p style="color: #666; font-size: 14px; margin-top: 20px;">
                        For now, categories are managed in the database table <code>homepage_sections</code> 
                        with section_key = 'category_scroller'. The data is stored as JSON.
                    </p>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeCategoryModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeCategoryModal() {
    const modal = document.querySelector('.admin-edit-modal');
    if (modal) {
        modal.remove();
    }
}

function manageHimHerSection() {
    // Add modal styles if not already present
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .admin-edit-modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 10000; }
            .modal-overlay { background: rgba(0,0,0,0.8); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; }
            .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; }
            .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
            .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
            .form-group { margin-bottom: 15px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
            .modal-actions button { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; }
            .modal-actions button[type="submit"] { background: #0071ce; color: white; }
            .modal-actions button[type="button"] { background: #ccc; }
        `;
        document.head.appendChild(styles);
    }
    
    // Create modal for managing "New for him & her" section
    const modal = document.createElement('div');
    modal.className = 'admin-edit-modal';
    modal.innerHTML = `
        <div class="modal-overlay" onclick="closeHimHerModal()">
            <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 800px;">
                <div class="modal-header">
                    <h3>Manage "New for him & her" Section</h3>
                    <button onclick="closeHimHerModal()" class="close-btn">&times;</button>
                </div>
                <div class="him-her-manager">
                    <p>Product section management interface coming soon. This will allow you to:</p>
                    <ul style="margin: 15px 0; padding-left: 30px;">
                        <li>Select which products to display in this section</li>
                        <li>Choose a category to pull products from</li>
                        <li>Set the number of products to display</li>
                        <li>Reorder products by drag and drop</li>
                        <li>Manually add specific products</li>
                    </ul>
                    <p style="color: #666; font-size: 14px; margin-top: 20px;">
                        For now, this section displays products from the fashion category. 
                        Products can be managed in the database table <code>homepage_sections</code> 
                        with section_key = 'him_her_products'. The data is stored as JSON.
                    </p>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeHimHerModal()">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeHimHerModal() {
    const modal = document.querySelector('.admin-edit-modal');
    if (modal) {
        modal.remove();
    }
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('Homepage with admin editing and add to cart functionality loaded');
});
</script>

<!-- Purchase Flows Scripts -->
<script src="/assets/js/ui.js"></script>
<script src="/assets/js/purchase-flows.js"></script>
<script>
    // Initialize purchase flows with globals
    window.isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    window.csrfToken = '<?php echo csrfToken(); ?>';
</script>

<?php if ($is_admin_logged_in): ?>
    <!-- Include Banner Edit Modal for Admin Users -->
    <?php include __DIR__ . '/includes/banner-edit-modal.php'; ?>
    <script src="/js/banner-admin.js"></script>
<?php endif; ?>

<?php includeFooter(); ?>