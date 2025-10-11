<?php
/**
 * Product Detail Page - Production Ready
 * Feza Marketplace - Working with existing models
 */

require_once __DIR__ . '/includes/init.php';

// Check if user is logged in for certain actions
$isLoggedIn = Session::isLoggedIn();
$userId = $isLoggedIn ? Session::getUserId() : null;

// Get product ID from URL parameters
$productId = null;
$productSlug = null;

// Handle route parameters
if (isset($_GET['route_params']) && !empty($_GET['route_params'][0])) {
    $param = $_GET['route_params'][0];
    if (is_numeric($param)) {
        $productId = (int)$param;
    } else {
        $productSlug = $param;
    }
}

// Fallback to direct GET parameters
if (!$productId && !$productSlug) {
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $productId = (int)$_GET['id'];
    } elseif (isset($_GET['slug'])) {
        $productSlug = $_GET['slug'];
    }
}

// Redirect if no valid parameter
if (!$productId && !$productSlug) {
    header('Location: /');
    exit;
}

// Initialize models
try {
    $productModel = new Product();
    $cartModel = new Cart();
    $wishlistModel = new Wishlist();
    $reviewModel = new Review();
    $categoryModel = new Category();
    
    // Get cart count for logged-in users
    $cart_count = 0;
    if ($isLoggedIn && $userId) {
        $cartItems = $cartModel->getCartItems($userId);
        $cart_count = count($cartItems);
    }
} catch (Exception $e) {
    error_log("Model initialization failed: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo "Internal Server Error";
    exit;
}

// Find product
$product = null;
try {
    if ($productId) {
        $product = $productModel->findWithVendor($productId);
    } elseif ($productSlug) {
        $product = $productModel->findBySlug($productSlug);
        if ($product) {
            $productId = $product['id'];
        }
    }
} catch (Exception $e) {
    error_log("Product fetch failed: " . $e->getMessage());
}

if (!$product) {
    header('HTTP/1.1 404 Not Found');
    echo "Product not found";
    exit;
}

// Track product view
try {
    $db = db();
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    
    $viewStmt = $db->prepare("
        INSERT INTO product_views (product_id, user_id, session_id, ip_address, user_agent, referrer, viewed_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $viewStmt->execute([$productId, $userId, $sessionId, $ipAddress, $userAgent, $referrer]);
} catch (Exception $e) {
    error_log("Failed to track product view: " . $e->getMessage());
}

// Get 24-hour view count
$viewCount24h = 0;
try {
    $viewCountStmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM product_views 
        WHERE product_id = ? 
        AND viewed_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $viewCountStmt->execute([$productId]);
    $viewCount24h = (int)$viewCountStmt->fetchColumn();
} catch (Exception $e) {
    error_log("Failed to get view count: " . $e->getMessage());
}

// Get additional product data
$images = [];
$reviews = [];
$avgRating = 0;
$reviewCount = 0;
$relatedProducts = [];
$isWishlisted = false;
$isWatchlisted = false;
$category = null;

try {
    // Product images
    $images = $productModel->getImages($productId);
    
    // Reviews and ratings
    $reviews = $reviewModel->getProductReviews($productId, 10);
    $ratingStats = $reviewModel->getProductRatingStats($productId);
    $avgRating = $ratingStats['average_rating'] ?? 0;
    $reviewCount = (int)($ratingStats['total_reviews'] ?? 0);
    
    // Related products - Try multiple strategies for finding similar items
    // Strategy 1: Find products from same category
    if ($product['category_id']) {
        $relatedProducts = $productModel->findByCategory($product['category_id'], 12);
        // Remove current product from related products
        $relatedProducts = array_filter($relatedProducts, function($p) use ($productId) {
            return $p['id'] != $productId;
        });
    }
    
    // Strategy 2: If no category matches found, try name/keyword similarity
    if (empty($relatedProducts)) {
        $relatedProducts = $productModel->findSimilarByNameAndKeywords(
            $productId,
            $product['name'],
            $product['keywords'] ?? '',
            12
        );
    }
    
    // Strategy 3: If still empty, get recent products from same vendor
    if (empty($relatedProducts) && !empty($product['vendor_id'])) {
        try {
            $stmt = $db->prepare("
                SELECT id, name, price, image_url, vendor_id, vendor_name 
                FROM products 
                WHERE vendor_id = ? AND id != ? AND status = 'active'
                ORDER BY created_at DESC
                LIMIT 12
            ");
            $stmt->execute([$product['vendor_id'], $productId]);
            $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Vendor products query failed: " . $e->getMessage());
        }
    }
    
    // Strategy 4: Final fallback - get any recent active products
    if (empty($relatedProducts)) {
        try {
            $stmt = $db->prepare("
                SELECT id, name, price, image_url, vendor_id, vendor_name 
                FROM products 
                WHERE id != ? AND status = 'active'
                ORDER BY created_at DESC
                LIMIT 12
            ");
            $stmt->execute([$productId]);
            $relatedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Fallback products query failed: " . $e->getMessage());
        }
    }
    
    // Get sponsored/recommended products for sidebar
    $sponsoredProducts = [];
    try {
        // Get products with active sponsorships OR featured products from same category
        $stmt = $db->prepare("
            SELECT DISTINCT p.id, p.name, p.price, p.image_url, p.vendor_id, p.vendor_name, 
                   p.is_featured,
                   sp.id as sponsored_id,
                   CASE WHEN sp.id IS NOT NULL THEN 1 ELSE 0 END as is_sponsored
            FROM products p
            LEFT JOIN sponsored_products sp ON p.id = sp.product_id 
                AND sp.status = 'active' 
                AND sp.sponsored_until > NOW()
            WHERE p.id != ? AND p.status = 'active'
            AND (sp.id IS NOT NULL OR p.is_featured = 1 OR p.category_id = ?)
            ORDER BY is_sponsored DESC, p.is_featured DESC, RAND()
            LIMIT 8
        ");
        $stmt->execute([$productId, $product['category_id'] ?? 0]);
        $sponsoredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Track impressions for sponsored products
        if (!empty($sponsoredProducts)) {
            $sponsoredIds = array_filter(array_column($sponsoredProducts, 'sponsored_id'));
            if (!empty($sponsoredIds)) {
                $updateImpressions = "
                    UPDATE sponsored_products 
                    SET impressions = impressions + 1 
                    WHERE id IN (" . implode(',', array_map('intval', $sponsoredIds)) . ")
                ";
                $db->exec($updateImpressions);
            }
        }
    } catch (Exception $e) {
        error_log("Sponsored products query failed: " . $e->getMessage());
    }
    
    // Check if in user's wishlist and watchlist
    if ($userId) {
        $isWishlisted = $wishlistModel->isInWishlist($userId, $productId);
        $watchlistModel = new Watchlist();
        $isWatchlisted = $watchlistModel->isInWatchlist($userId, $productId);
    }
    
    // Get category
    if ($product['category_id']) {
        $category = $categoryModel->find($product['category_id']);
    }
    
} catch (Exception $e) {
    error_log("Additional data fetch failed: " . $e->getMessage());
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }
    
    // CSRF validation
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page and try again.']);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'add_to_cart':
            try {
                $quantity = (int)($_POST['quantity'] ?? 1);
                if ($quantity < 1) $quantity = 1;
                
                // Check stock - consider existing cart quantity
                $existingCartItems = $cartModel->getCartItems($userId);
                $existingQuantity = 0;
                foreach ($existingCartItems as $item) {
                    if ($item['product_id'] == $productId) {
                        $existingQuantity = $item['quantity'];
                        break;
                    }
                }
                
                $totalQuantity = $existingQuantity + $quantity;
                if (isset($product['stock_quantity']) && $product['stock_quantity'] < $totalQuantity) {
                    if ($existingQuantity > 0) {
                        echo json_encode(['success' => false, 'message' => 'Cannot add more items. You already have ' . $existingQuantity . ' in cart and only ' . $product['stock_quantity'] . ' available.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $product['stock_quantity'] . ' available.']);
                    }
                    exit;
                }
                
                $result = $cartModel->addItem($userId, $productId, $quantity);
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
                }
            } catch (Exception $e) {
                error_log("Add to cart error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error adding to cart']);
            }
            exit;
            
        case 'add_to_wishlist':
            try {
                if ($isWishlisted) {
                    $result = $wishlistModel->removeFromWishlist($userId, $productId);
                    $action = 'removed';
                    $message = 'Item removed from wishlist';
                } else {
                    $result = $wishlistModel->addToWishlist($userId, $productId);
                    $action = 'added';
                    $message = 'Item added to wishlist';
                }
                
                if ($result !== false) {
                    echo json_encode(['success' => true, 'message' => $message, 'action' => $action]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update wishlist']);
                }
            } catch (Exception $e) {
                error_log("Wishlist error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error updating wishlist']);
            }
            exit;
            
        case 'add_to_watchlist':
            try {
                $watchlistModel = new Watchlist();
                $isInWatchlist = $watchlistModel->isInWatchlist($userId, $productId);
                
                if ($isInWatchlist) {
                    $result = $watchlistModel->removeFromWatchlist($userId, $productId);
                    $action = 'removed';
                    $message = 'Item removed from watchlist';
                } else {
                    $result = $watchlistModel->addToWatchlist($userId, $productId);
                    $action = 'added';
                    $message = 'Item added to watchlist';
                }
                
                if ($result !== false) {
                    echo json_encode(['success' => true, 'message' => $message, 'action' => $action]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update watchlist']);
                }
            } catch (Exception $e) {
                error_log("Watchlist error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error updating watchlist']);
            }
            exit;
            
        case 'buy_now':
            try {
                $quantity = (int)($_POST['quantity'] ?? 1);
                if ($quantity < 1) $quantity = 1;
                
                // Check stock
                if (isset($product['stock_quantity']) && $product['stock_quantity'] < $quantity) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                    exit;
                }
                
                // Add to cart and redirect to checkout
                $cartModel->addItem($userId, $productId, $quantity);
                echo json_encode(['success' => true, 'redirect' => '/checkout.php']);
            } catch (Exception $e) {
                error_log("Buy now error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error processing purchase']);
            }
            exit;
            
        case 'make_offer':
            try {
                $offerAmount = (float)($_POST['offer_amount'] ?? 0);
                if ($offerAmount <= 0 || $offerAmount >= $product['price']) {
                    echo json_encode(['success' => false, 'message' => 'Invalid offer amount']);
                    exit;
                }
                
                // Use the new Offer model to create the offer
                $offerModel = new Offer();
                $message = $_POST['offer_message'] ?? null;
                $expiresAt = null; // Could be set to 7 days from now: date('Y-m-d H:i:s', strtotime('+7 days'))
                
                $result = $offerModel->createOffer($productId, $userId, $offerAmount, $message, $expiresAt);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Offer submitted successfully! We will contact you soon.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to submit offer. Please try again.']);
                }
            } catch (Exception $e) {
                error_log("Offer error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error submitting offer']);
            }
            exit;
    }
}

// Prepare data for display
$primaryImage = !empty($images) ? $images[0]['image_url'] : '';
$price = (float)($product['price'] ?? 0);
$comparePrice = isset($product['compare_price']) ? (float)$product['compare_price'] : null;
$hasDiscount = $comparePrice && $comparePrice > $price;
$discountPercent = $hasDiscount ? round((($comparePrice - $price) / $comparePrice) * 100) : 0;

// Build breadcrumbs
$breadcrumbs = [
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Products', 'url' => '/products.php'],
];
if ($category) {
    $breadcrumbs[] = ['label' => $category['name'], 'url' => '/category.php?id=' . $category['id']];
}
$breadcrumbs[] = ['label' => $product['name'], 'url' => null];

// Page meta
$pageTitle = $product['name'] . ' - Feza Marketplace';
$metaDescription = $product['short_description'] ?? substr(strip_tags($product['description'] ?? ''), 0, 160);

// Helper functions
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Include header
if (file_exists(__DIR__ . '/templates/header.php')) {
    include __DIR__ . '/templates/header.php';
} elseif (file_exists(__DIR__ . '/includes/header.php')) {
    include __DIR__ . '/includes/header.php';
} else {
    echo "<!DOCTYPE html><html><head><title>" . h($pageTitle) . "</title></head><body>";
}
?>

<style>
/* Same CSS styles as before */
:root {
    --primary-color: #0654ba;
    --secondary-color: #3665f3;
    --success-color: #118a00;
    --warning-color: #f5af02;
    --danger-color: #e53238;
    --border-color: #e5e5e5;
    --text-color: #191919;
    --text-secondary: #707070;
    --bg-color: #ffffff;
}

/* Mobile Product Page Header - Only for mobile view */
@media (max-width: 768px) {
    .mobile-product-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 56px;
        background: white;
        border-bottom: 1px solid #e5e5e5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 12px;
        z-index: 1100;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-product-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .mobile-product-header-back {
        background: none;
        border: none;
        font-size: 24px;
        color: #333;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .mobile-product-header-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    
    .mobile-product-header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .mobile-product-header-icon {
        background: none;
        border: none;
        font-size: 20px;
        color: #333;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    
    .mobile-product-header-cart-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #e53238;
        color: white;
        border-radius: 10px;
        min-width: 16px;
        height: 16px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        padding: 0 4px;
    }
    
    /* Mobile Search Bar Styles */
    .mobile-product-search {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 56px;
        background: white;
        display: none;
        align-items: center;
        padding: 0 12px;
        gap: 12px;
        z-index: 1101;
    }
    
    .mobile-product-search.active {
        display: flex;
    }
    
    .mobile-product-search-input {
        flex: 1;
        height: 40px;
        border: 1px solid #e5e5e5;
        border-radius: 20px;
        padding: 0 16px;
        font-size: 16px;
        outline: none;
    }
    
    .mobile-product-search-input:focus {
        border-color: #0654ba;
    }
    
    .mobile-product-search-close {
        background: none;
        border: none;
        font-size: 24px;
        color: #333;
        cursor: pointer;
        padding: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Adjust body padding to account for fixed header on product pages */
    body.product-page {
        padding-top: 56px;
    }
    
    /* Hide the main desktop header on mobile product pages */
    body.product-page .feza-top-header,
    body.product-page .feza-main-header,
    body.product-page .feza-nav-bar,
    body.product-page .fezamarket-header {
        display: none !important;
    }
}

/* Desktop - hide mobile product header */
@media (min-width: 769px) {
    .mobile-product-header {
        display: none;
    }
    
    .mobile-product-search {
        display: none !important;
    }
}

.product-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 20px 24px;
    background: var(--bg-color);
}

.product-layout {
    display: grid;
    grid-template-columns: 400px 1fr 320px;
    gap: 24px;
    align-items: start;
    margin-top: 20px;
}

@media (max-width: 1024px) {
    .product-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

.image-gallery {
    display: flex;
    flex-direction: column;
}

.main-image {
    position: relative;
    background: #fff;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 12px;
    text-align: center;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-image img {
    max-width: 100%;
    max-height: 360px;
    object-fit: contain;
}

.thumbnail-strip {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 4px 0;
}

.thumbnail {
    flex: 0 0 60px;
    width: 60px;
    height: 60px;
    border: 2px solid transparent;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s;
}

.thumbnail:hover,
.thumbnail.active {
    border-color: var(--primary-color);
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info {
    padding: 0 12px;
}

.product-title {
    font-size: 24px;
    font-weight: 400;
    line-height: 1.3;
    color: var(--text-color);
    margin: 0 0 16px 0;
}

.seller-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 14px;
}

.condition-section {
    margin-bottom: 20px;
}

.condition-label {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.condition-value {
    font-size: 16px;
    font-weight: 500;
    color: var(--text-color);
}

.price-section {
    margin-bottom: 24px;
    padding: 16px 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

.current-price {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 8px;
}

.purchase-panel {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    background: #fff;
    position: sticky;
    top: 20px;
}

.quantity-selector {
    margin-bottom: 16px;
}

.quantity-label {
    font-size: 14px;
    color: var(--text-color);
    margin-bottom: 8px;
    display: block;
}

.quantity-input {
    border: 1px solid var(--border-color);
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    width: 80px;
}

.btn {
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 24px;
    width: 100%;
    cursor: pointer;
    margin-bottom: 12px;
    transition: all 0.2s;
    text-align: center;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: var(--secondary-color);
    color: white;
}

.btn-primary:hover {
    background: #2851e6;
}

.btn-secondary {
    background: #fff;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-secondary:hover {
    background: var(--primary-color);
    color: white;
}

.btn-outline {
    background: #fff;
    color: var(--text-color);
    border: 1px solid var(--border-color);
}

.btn-outline:hover {
    background: #f7f7f7;
}

.stock-status {
    font-size: 14px;
    color: var(--success-color);
    font-weight: 500;
    margin-bottom: 16px;
}

.description-section {
    margin: 32px 0;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff;
}

.description-section h2 {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 16px;
    color: var(--text-color);
}

.item-description {
    max-height: 9em;
    line-height: 1.5em;
    overflow: hidden;
    position: relative;
    transition: max-height 0.3s ease;
}

.item-description[aria-expanded="true"] {
    max-height: none;
}

.item-description::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3em;
    background: linear-gradient(to bottom, transparent, #fff);
    pointer-events: none;
    opacity: 1;
    transition: opacity 0.3s ease;
}

.item-description[aria-expanded="true"]::after {
    opacity: 0;
}

.description-toggle-btn {
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    padding: 8px 0;
    margin-top: 8px;
    text-decoration: underline;
    transition: color 0.2s ease;
}

.description-toggle-btn:hover {
    color: var(--secondary-color);
}

.reviews-section {
    margin: 32px 0;
    padding: 20px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: #fff;
}

.review-item {
    padding: 16px 0;
    border-bottom: 1px solid var(--border-color);
}

.review-item:last-child {
    border-bottom: none;
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
    font-size: 14px;
}

.review-stars {
    color: #ffc107;
}

.related-products {
    margin: 32px 0;
}

.products-grid {
    display: flex;
    gap: 16px;
    overflow-x: auto;
    padding-bottom: 12px;
    scroll-behavior: smooth;
}

/* Hide scrollbar for Chrome, Safari and Opera */
.products-grid::-webkit-scrollbar {
    height: 8px;
}

.products-grid::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.products-grid::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.products-grid::-webkit-scrollbar-thumb:hover {
    background: #555;
}

.product-card {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
    text-align: center;
    background: #fff;
    transition: box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
    flex: 0 0 220px;
    min-width: 220px;
    max-width: 220px;
    height: 320px;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.product-card img {
    width: 100%;
    height: 180px;
    object-fit: contain;
    border-radius: 4px;
    margin-bottom: 12px;
    flex-shrink: 0;
}

.product-card-name {
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 8px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    flex-grow: 1;
}

.product-card-price {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-color);
    margin-top: auto;
}

/* Responsive design for tablets */
@media (max-width: 1024px) {
    .product-card {
        flex: 0 0 180px;
        min-width: 180px;
        max-width: 180px;
        height: 280px;
    }
    
    .product-card img {
        height: 140px;
    }
}

/* Responsive design for mobile */
@media (max-width: 768px) {
    .products-grid {
        gap: 12px;
    }
    
    .product-card {
        flex: 0 0 160px;
        min-width: 160px;
        max-width: 160px;
        height: 260px;
        padding: 12px;
    }
    
    .product-card img {
        height: 120px;
    }
}

.sponsored-product-card:hover {
    background-color: #f8f9fa;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.error-message {
    color: var(--danger-color);
    font-size: 14px;
    margin-top: 8px;
}

.success-message {
    color: var(--success-color);
    font-size: 14px;
    margin-top: 8px;
}

/* Toast notification styles */
.product-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 24px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    font-size: 14px;
    font-weight: 500;
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.3s ease;
    max-width: 400px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.product-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.product-toast-success {
    background: #10b981;
    color: white;
}

.product-toast-error {
    background: #ef4444;
    color: white;
}

.product-toast-warning {
    background: #f59e0b;
    color: white;
}

.product-toast-info {
    background: #3b82f6;
    color: white;
}

@media (max-width: 768px) {
    .product-toast {
        left: 20px;
        right: 20px;
        max-width: none;
    }
}
</style>

<!-- Mobile Product Header - Only visible on mobile -->
<div class="mobile-product-header" id="mobileProductHeader">
    <div class="mobile-product-header-left">
        <button class="mobile-product-header-back" onclick="goBack()" aria-label="Go back">
            <i class="fas fa-arrow-left"></i>
        </button>
        <h1 class="mobile-product-header-title">Item</h1>
    </div>
    <div class="mobile-product-header-right">
        <button class="mobile-product-header-icon" onclick="toggleMobileSearch()" aria-label="Search" id="mobileSearchIcon">
            <i class="fas fa-search"></i>
        </button>
        <button class="mobile-product-header-icon" onclick="window.location.href='/cart.php'" aria-label="Cart">
            <i class="fas fa-shopping-cart"></i>
            <?php if (($cart_count ?? 0) > 0): ?>
            <span class="mobile-product-header-cart-badge"><?= $cart_count; ?></span>
            <?php endif; ?>
        </button>
        <button class="mobile-product-header-icon" onclick="shareProduct()" aria-label="Share">
            <i class="fas fa-share-alt"></i>
        </button>
        <button class="mobile-product-header-icon" onclick="showMoreOptions()" aria-label="More options">
            <i class="fas fa-ellipsis-v"></i>
        </button>
    </div>
</div>

<!-- Mobile Search Bar - Hidden by default, shown when search icon is clicked -->
<div class="mobile-product-search" id="mobileProductSearch">
    <button class="mobile-product-search-close" onclick="toggleMobileSearch()" aria-label="Close search">
        <i class="fas fa-arrow-left"></i>
    </button>
    <form onsubmit="handleMobileSearchSubmit(event)" style="flex: 1; display: flex;">
        <input type="text" 
               class="mobile-product-search-input" 
               id="mobileSearchInput"
               placeholder="Search for anything" 
               aria-label="Search products">
    </form>
</div>

<div class="product-container">
    <!-- Main Product Layout -->
    <div class="product-layout">
        
        <!-- Left Column - Images -->
        <div class="image-gallery">
            <div class="main-image">
                <img id="mainImage" src="<?= getProductImageUrl($primaryImage); ?>" alt="<?= h($product['name']); ?>">
            </div>
            
            <?php if (count($images) > 1): ?>
            <div class="thumbnail-strip">
                <?php foreach ($images as $idx => $img): ?>
                <div class="thumbnail <?= $idx === 0 ? 'active' : ''; ?>" 
                     onclick="changeMainImage('<?= getProductImageUrl($img['image_url']); ?>', <?= $idx; ?>)">
                    <img src="<?= getProductImageUrl($img['image_url']); ?>" alt="<?= h($img['alt_text'] ?? $product['name']); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Center Column - Product Info -->
        <div class="product-info">
            <h1 class="product-title"><?= h($product['name']); ?></h1>
            
            <div class="seller-info">
                <span>Sold by </span>
                <?php if (!empty($product['vendor_id'])): ?>
                    <a href="/seller.php?id=<?= $product['vendor_id']; ?>" style="color: var(--primary-color); text-decoration: none;">
                        <?= h($product['vendor_name'] ?? 'Unknown Seller'); ?>
                    </a>
                <?php else: ?>
                    <span><?= h($product['vendor_name'] ?? 'Feza Marketplace'); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($viewCount24h > 0): ?>
            <div class="product-views-24h" style="color: #dc2626; font-size: 14px; margin-top: 10px;">
                <i class="fas fa-eye"></i> <?= number_format($viewCount24h); ?> views in the last 24 hours
            </div>
            <?php endif; ?>
            
            <div class="condition-section">
                <div class="condition-label">Condition:</div>
                <div class="condition-value">New</div>
            </div>
            
            <div class="price-section">
                <div class="current-price"><?= formatPrice($price); ?></div>
                <?php if ($hasDiscount): ?>
                <div class="price-details">
                    <span class="original-price"><?= formatPrice($comparePrice); ?></span>
                    <span class="discount">Save <?= $discountPercent; ?>%</span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($product['description'])): ?>
            <div class="description-section">
                <h2>About this item</h2>
                <div class="item-description" id="productDescription" aria-expanded="false">
                    <?= nl2br(h($product['description'])); ?>
                </div>
                <button class="description-toggle-btn" id="descriptionToggle" onclick="toggleDescription()" aria-label="Toggle description">
                    Continue reading
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Purchase Options -->
        <div class="purchase-panel">
            <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
            <div class="quantity-selector">
                <label class="quantity-label" for="quantity">Quantity:</label>
                <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?= min(10, $product['stock_quantity']); ?>">
            </div>
            
            <button class="btn btn-primary" onclick="buyNow()">Buy It Now</button>
            <button class="btn btn-secondary" onclick="addToCart()">Add to cart</button>
            <button class="btn btn-outline" onclick="showOfferModal()">Make offer</button>
            <button class="btn btn-outline" onclick="toggleWishlist()">
                <?= $isWishlisted ? '❤️ Remove from Wishlist' : '🤍 Add to Wishlist'; ?>
            </button>
            <button class="btn btn-outline" onclick="toggleWatchlist()">
                <?= $isWatchlisted ? '👁️ Remove from Watchlist' : '👁️ Add to Watchlist'; ?>
            </button>
            
            <div class="stock-status">
                <?= $product['stock_quantity']; ?> available
            </div>
            <?php else: ?>
            <div class="stock-status" style="color: var(--danger-color);">
                Currently unavailable
            </div>
            <?php endif; ?>
            
            <!-- Sponsored/Recommended Products in Sidebar -->
            <?php if (!empty($sponsoredProducts)): ?>
            <div class="sponsored-section" style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color);">
                <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px; color: var(--text-color);">
                    Sponsored items
                </h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach (array_slice($sponsoredProducts, 0, 4) as $sponsored): ?>
                    <a href="/product.php?id=<?= $sponsored['id']; ?>" 
                       class="sponsored-product-card" 
                       style="display: flex; gap: 12px; text-decoration: none; color: inherit; padding: 8px; border-radius: 4px; transition: background-color 0.2s; position: relative;">
                        <div style="flex: 0 0 80px; height: 80px; background: #f8f9fa; border-radius: 4px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="<?= getProductImageUrl($sponsored['image_url'] ?? ''); ?>" 
                                 alt="<?= h($sponsored['name']); ?>"
                                 style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-size: 13px; line-height: 1.4; margin-bottom: 4px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?= h($sponsored['name']); ?>
                            </div>
                            <div style="font-size: 15px; font-weight: 600; color: var(--text-color);">
                                <?= formatPrice($sponsored['price']); ?>
                            </div>
                            <div style="display: flex; gap: 8px; margin-top: 4px;">
                                <?php if (!empty($sponsored['is_sponsored'])): ?>
                                <div style="font-size: 10px; background: #3b82f6; color: white; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
                                    SPONSORED
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($sponsored['is_featured'])): ?>
                                <div style="font-size: 11px; color: #0654ba;">
                                    ⭐ Featured
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <?php if (!empty($reviews)): ?>
    <div class="reviews-section">
        <h2>Customer Reviews (<?= $reviewCount; ?>)</h2>
        <?php foreach ($reviews as $review): ?>
        <div class="review-item">
            <div class="review-meta">
                <strong><?= h($review['reviewer_name'] ?? 'Anonymous'); ?></strong>
                <span class="review-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= ($review['rating'] ?? 0) ? '?' : '?'; ?>
                    <?php endfor; ?>
                </span>
                <time><?= date('M j, Y', strtotime($review['created_at'])); ?></time>
            </div>
            <?php if (!empty($review['title'])): ?>
            <div style="font-weight: 600; margin-bottom: 4px;"><?= h($review['title']); ?></div>
            <?php endif; ?>
            <div><?= nl2br(h($review['review_text'] ?? '')); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- AI Recommended Products -->
    <div id="aiRecommendations" class="ai-recommended-products" style="display: none;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
            <h2 style="margin: 0;">🤖 AI Recommended for You</h2>
            <span style="background: linear-gradient(135deg, #8b5cf6, #6366f1); color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">POWERED BY FEZA AI</span>
        </div>
        <p style="color: #6b7280; margin-bottom: 20px; font-size: 14px;">Based on your browsing history and preferences</p>
        <div class="products-grid" id="aiRecommendationsGrid"></div>
    </div>
    
    <!-- Related Products -->
    <div class="related-products">
        <h2>Similar items</h2>
        <?php if (!empty($relatedProducts)): ?>
        <div class="products-grid">
            <?php foreach (array_slice($relatedProducts, 0, 10) as $related): ?>
            <a href="/product.php?id=<?= $related['id']; ?>" class="product-card">
                <img src="<?= getProductImageUrl($related['image_url'] ?? ''); ?>" 
                     alt="<?= h($related['name']); ?>">
                <div class="product-card-name">
                    <?= h($related['name']); ?>
                </div>
                <div class="product-card-price">
                    <?= formatPrice($related['price'] ?? 0); ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5 text-muted">
            <p>No similar products available at the moment</p>
            <?php if (isset($product['category_id']) && $product['category_id']): ?>
                <a href="/category.php?cat=<?= $product['category_id']; ?>" class="btn btn-primary" style="margin-top: 16px;">Browse Category</a>
            <?php else: ?>
                <a href="/products.php" class="btn btn-primary" style="margin-top: 16px;">Browse All Products</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Offer Modal -->
<div id="offerModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeOfferModal()">&times;</span>
        <h2>Make an Offer</h2>
        <form id="offerForm">
            <div style="margin-bottom: 16px;">
                <label for="offerAmount">Your offer:</label>
                <input type="number" id="offerAmount" step="0.01" min="0.01" max="<?= $price * 0.9; ?>" style="width: 100%; padding: 8px; margin-top: 4px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label for="offerMessage">Message (optional):</label>
                <textarea id="offerMessage" style="width: 100%; height: 80px; padding: 8px; margin-top: 4px;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Offer</button>
        </form>
        <div id="offerError" class="error-message" style="display: none;"></div>
        <div id="offerSuccess" class="success-message" style="display: none;"></div>
    </div>
</div>

<script>
// Global variables
const productId = <?= $productId; ?>;
const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false'; ?>;
const csrfToken = '<?= csrfToken(); ?>';
let isWishlisted = <?= $isWishlisted ? 'true' : 'false'; ?>;
let isWatchlisted = <?= $isWatchlisted ? 'true' : 'false'; ?>;

// Toast notification system
function showToast(message, type = 'info') {
    // Remove any existing toast
    const existingToast = document.querySelector('.product-toast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `product-toast product-toast-${type}`;
    toast.textContent = message;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Image gallery functions
function changeMainImage(imageUrl, index) {
    document.getElementById('mainImage').src = imageUrl;
    document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
        thumb.classList.toggle('active', i === index);
    });
}

// Description toggle functionality
function toggleDescription() {
    const description = document.getElementById('productDescription');
    const toggleBtn = document.getElementById('descriptionToggle');
    const isExpanded = description.getAttribute('aria-expanded') === 'true';
    
    description.setAttribute('aria-expanded', !isExpanded);
    toggleBtn.textContent = isExpanded ? 'Continue reading' : 'Show less';
}

// Cart functionality
async function addToCart() {
    if (!isLoggedIn) {
        showToast('Please login to add items to cart', 'warning');
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 1500);
        return;
    }
    
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_to_cart&quantity=${quantity}&csrf_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✓ Item added to cart successfully!', 'success');
        } else {
            showToast(data.message || 'Failed to add item to cart', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error adding item to cart', 'error');
    }
}

// Buy now functionality
async function buyNow() {
    if (!isLoggedIn) {
        showToast('Please login to purchase', 'warning');
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 1500);
        return;
    }
    
    const quantity = document.getElementById('quantity').value;
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=buy_now&quantity=${quantity}&csrf_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✓ Redirecting to checkout...', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1000);
            }
        } else {
            showToast(data.message || 'Failed to process purchase', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error processing purchase', 'error');
    }
}

// Wishlist functionality
async function toggleWishlist() {
    if (!isLoggedIn) {
        showToast('Please login to use wishlist', 'warning');
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 1500);
        return;
    }
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_to_wishlist&csrf_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            isWishlisted = data.action === 'added';
            const btn = event.target;
            btn.textContent = isWishlisted ? '❤️ Remove from Wishlist' : '🤍 Add to Wishlist';
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Failed to update wishlist', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error updating wishlist', 'error');
    }
}

// Watchlist functionality
async function toggleWatchlist() {
    if (!isLoggedIn) {
        showToast('Please login to use watchlist', 'warning');
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 1500);
        return;
    }
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=add_to_watchlist&csrf_token=${encodeURIComponent(csrfToken)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            isWatchlisted = data.action === 'added';
            const btn = event.target;
            btn.textContent = isWatchlisted ? '👁️ Remove from Watchlist' : '👁️ Add to Watchlist';
            showToast(data.message, 'success');
        } else {
            showToast(data.message || 'Failed to update watchlist', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error updating watchlist', 'error');
    }
}

// Offer modal functions
function showOfferModal() {
    if (!isLoggedIn) {
        showToast('Please login to make an offer', 'warning');
        setTimeout(() => {
            window.location.href = '/login.php';
        }, 1500);
        return;
    }
    document.getElementById('offerModal').style.display = 'block';
}

function closeOfferModal() {
    document.getElementById('offerModal').style.display = 'none';
    document.getElementById('offerForm').reset();
    document.getElementById('offerError').style.display = 'none';
    document.getElementById('offerSuccess').style.display = 'none';
}

// Handle offer form submission
document.getElementById('offerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const offerAmount = document.getElementById('offerAmount').value;
    const offerMessage = document.getElementById('offerMessage').value;
    
    if (!offerAmount || offerAmount <= 0) {
        document.getElementById('offerError').textContent = 'Please enter a valid offer amount';
        document.getElementById('offerError').style.display = 'block';
        return;
    }
    
    try {
        const response = await fetch('/api/product-offers.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'submit',
                product_id: <?= $productId; ?>,
                offer_price: parseFloat(offerAmount),
                message: offerMessage
            })
        });
        
        const data = await response.json();
        
        document.getElementById('offerError').style.display = 'none';
        
        if (data.success) {
            document.getElementById('offerSuccess').textContent = data.message;
            document.getElementById('offerSuccess').style.display = 'block';
            setTimeout(() => {
                closeOfferModal();
            }, 2000);
        } else {
            document.getElementById('offerError').textContent = data.error || data.message || 'Error submitting offer';
            document.getElementById('offerError').style.display = 'block';
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('offerError').textContent = 'Error submitting offer';
        document.getElementById('offerError').style.display = 'block';
    }
});

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('offerModal');
    if (event.target == modal) {
        closeOfferModal();
    }
}

// Track product view for AI recommendations
let viewStartTime = Date.now();
window.addEventListener('beforeunload', function() {
    const viewDuration = Math.floor((Date.now() - viewStartTime) / 1000);
    
    // Use sendBeacon for reliable tracking on page unload
    if (navigator.sendBeacon) {
        const data = JSON.stringify({
            product_id: productId,
            duration: viewDuration
        });
        navigator.sendBeacon('/api/track-view.php', data);
    }
});

// Load AI recommendations
fetch(`/api/ai-recommendations.php?product_id=${productId}&limit=8`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data.recommendations && data.data.recommendations.length > 0) {
            const grid = document.getElementById('aiRecommendationsGrid');
            const section = document.getElementById('aiRecommendations');
            
            grid.innerHTML = data.data.recommendations.map(product => `
                <a href="/product/${product.id}" class="product-card">
                    <img src="${product.image_url}" alt="${escapeHtml(product.name)}">
                    <div>${escapeHtml(product.name)}</div>
                    <div>$${formatPrice(product.price)}</div>
                    ${product.sale_price ? `<div class="sale-badge">Sale</div>` : ''}
                </a>
            `).join('');
            
            section.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error loading AI recommendations:', error);
    });

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function formatPrice(price) {
    return parseFloat(price).toFixed(2);
}

// Mobile Product Header Functions
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}

function toggleMobileSearch() {
    const searchBar = document.getElementById('mobileProductSearch');
    const header = document.getElementById('mobileProductHeader');
    const searchInput = document.getElementById('mobileSearchInput');
    
    // Safety check - ensure elements exist
    if (!searchBar || !header) {
        console.warn('Mobile search elements not found');
        return;
    }
    
    if (searchBar.classList.contains('active')) {
        // Hide search bar, show header
        searchBar.classList.remove('active');
        header.style.display = 'flex';
    } else {
        // Show search bar, hide header
        searchBar.classList.add('active');
        header.style.display = 'none';
        // Focus on search input
        if (searchInput) {
            setTimeout(() => searchInput.focus(), 100);
        }
    }
}

function handleMobileSearchSubmit(event) {
    event.preventDefault();
    const searchInput = document.getElementById('mobileSearchInput');
    const query = searchInput.value.trim();
    
    if (query) {
        window.location.href = '/search.php?q=' + encodeURIComponent(query);
    }
}

function openMobileSearch() {
    // Legacy function - redirect to toggleMobileSearch
    toggleMobileSearch();
}

function shareProduct() {
    // Use Web Share API if available
    if (navigator.share) {
        navigator.share({
            title: document.title,
            text: 'Check out this product on Feza Marketplace',
            url: window.location.href
        }).then(() => {
            showToast('✓ Shared successfully!', 'success');
        }).catch((error) => {
            if (error.name !== 'AbortError') {
                fallbackShare();
            }
        });
    } else {
        fallbackShare();
    }
}

function fallbackShare() {
    // Fallback for browsers that don't support Web Share API
    const url = window.location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            showToast('✓ Link copied to clipboard!', 'success');
        }).catch(() => {
            showToast('Unable to copy link', 'error');
        });
    } else {
        showToast('Sharing not supported on this browser', 'info');
    }
}

function showMoreOptions() {
    // Show a menu with more options
    showToast('More options: Report item, Add to watchlist, Contact seller', 'info');
    // TODO: Implement a proper bottom sheet modal for better mobile UX
}

// Add body class for product pages on mobile
const mobileMediaQuery = window.matchMedia('(max-width: 768px)');

function handleMobileProductPage(e) {
    if (e.matches) {
        document.body.classList.add('product-page');
    } else {
        document.body.classList.remove('product-page');
    }
}

// Initial check
handleMobileProductPage(mobileMediaQuery);

// Listen for changes (use addEventListener for modern browsers)
if (mobileMediaQuery.addEventListener) {
    mobileMediaQuery.addEventListener('change', handleMobileProductPage);
} else {
    // Fallback for older browsers
    mobileMediaQuery.addListener(handleMobileProductPage);
}

</script>

<?php
// Include footer
if (file_exists(__DIR__ . '/templates/footer.php')) {
    include __DIR__ . '/templates/footer.php';
} elseif (file_exists(__DIR__ . '/includes/footer.php')) {
    include __DIR__ . '/includes/footer.php';
} else {
    echo "</body></html>";
}
?>