<?php
/**
 * Feza Marketplace Header Template - eBay Style Design
 * Complete modern header with eBay-style layout and "Feza" branding
 * Created: 2025-09-29
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/includes/init.php';

$isLoggedIn = Session::isLoggedIn();
$currentUser = null;
if ($isLoggedIn) {
    $user = new User();
    $currentUser = $user->find(Session::getUserId());
}

$userName = $currentUser ? ($currentUser['first_name'] ?? $currentUser['username'] ?? $currentUser['email']) : null;
$userRole = getCurrentUserRole();
$cart_count = 0; // Implement your cart count logic here

// Get categories from database
$categories = [];
try {
    if (function_exists('db') && db()) {
        $categoryModel = new Category();
        $categories = $categoryModel->getActive();
    }
} catch (Exception $e) {
    error_log("Category loading failed: " . $e->getMessage());
}

// Fallback categories
if (empty($categories)) {
    $categories = [
        ['id' => 1, 'name' => 'Electronics', 'slug' => 'electronics'],
        ['id' => 2, 'name' => 'Motors', 'slug' => 'motors'],
        ['id' => 3, 'name' => 'Fashion', 'slug' => 'fashion'],
        ['id' => 4, 'name' => 'Collectibles & Art', 'slug' => 'collectibles'],
        ['id' => 5, 'name' => 'Sports', 'slug' => 'sports'],
        ['id' => 6, 'name' => 'Health & Beauty', 'slug' => 'health'],
        ['id' => 7, 'name' => 'Industrial equipment', 'slug' => 'industrial'],
        ['id' => 8, 'name' => 'Home & Garden', 'slug' => 'home']
    ];
}

$page_title = $page_title ?? 'Feza - Electronics, Cars, Fashion, Collectibles & More | Feza Marketplace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'Buy & sell electronics, cars, clothes, collectibles & more on Feza, the world\'s online marketplace. Top brands, low prices & free shipping on many items.'); ?>">
    <meta name="keywords" content="buy, sell, auction, online marketplace, electronics, fashion, home, garden, collectibles, cars, sporting goods">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/icons.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Local JavaScript -->
    <script src="/js/jquery-shim.js"></script>
    
    <!-- Admin Charts -->
    <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            line-height: 1.4;
            background-color: #ffffff;
            color: #333;
        }
        
        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Top Header Bar - eBay Style */
        .feza-top-header {
            background-color: #f7f7f7;
            border-bottom: 1px solid #e5e5e5;
            height: 36px;
            display: flex;
            align-items: center;
            font-size: 13px;
        }
        
        .feza-top-content {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
        }
        
        .feza-top-left, .feza-top-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .feza-top-header a {
            color: #0654ba;
            font-size: 13px;
            padding: 4px 0;
        }
        
        .feza-top-header a:hover {
            text-decoration: underline;
        }
        
        .feza-greeting {
            color: #333;
            font-size: 13px;
        }
        
        /* Dropdown Styles */
        .feza-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .feza-dropdown-trigger {
            display: flex;
            align-items: center;
            color: #0654ba;
            cursor: pointer;
        }
        
        .feza-dropdown-arrow {
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #767676;
            margin-left: 4px;
        }
        
        .feza-dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 200px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border: 1px solid #ccc;
            border-radius: 4px;
            z-index: 1000;
            margin-top: 4px;
        }
        
        .feza-dropdown:hover .feza-dropdown-content {
            display: block;
        }
        
        .feza-dropdown-content a {
            display: block;
            padding: 8px 16px;
            color: #333 !important;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .feza-dropdown-content a:hover {
            background-color: #f7f7f7;
            text-decoration: none !important;
        }
        
        .feza-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        /* Main Header - eBay Style */
        .feza-main-header {
            background-color: white;
            height: 80px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .feza-main-content {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
        }
        
        /* Feza Logo - Colorful Design */
        .feza-logo {
            font-size: 36px;
            font-weight: bold;
            font-family: "Helvetica Neue", Arial, sans-serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            margin-right: 8px;
        }
        
        .feza-logo .f { color: #e53238; }  /* Red F */
        .feza-logo .e { color: #0064d2; }  /* Blue e */
        .feza-logo .z { color: #f5af02; }  /* Yellow z */
        .feza-logo .a { color: #86b817; }  /* Green a */
        
        /* Category Dropdown */
        .feza-category-dropdown {
            position: relative;
            background: white;
            border: 2px solid #767676;
            border-radius: 4px 0 0 4px;
            height: 44px;
            display: flex;
            align-items: center;
            padding: 0 32px 0 12px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            white-space: nowrap;
            min-width: 140px;
        }
        
        .feza-category-dropdown::after {
            content: '';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #767676;
        }
        
        .feza-category-dropdown:hover {
            background-color: #f7f7f7;
        }
        
        .feza-category-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            min-width: 220px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border: 1px solid #ccc;
            border-radius: 4px;
            z-index: 1000;
            margin-top: 2px;
        }
        
        .feza-category-dropdown:hover .feza-category-content {
            display: block;
        }
        
        .feza-category-content a {
            display: block;
            padding: 12px 16px;
            color: #333;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .feza-category-content a:hover {
            background-color: #f7f7f7;
            color: #0654ba;
        }
        
        .feza-category-content a:last-child {
            border-bottom: none;
        }
        
        /* Search Container - eBay Style */
        .feza-search-container {
            flex: 1;
            display: flex;
            max-width: 800px;
            position: relative;
        }
        
        .feza-search-form {
            display: flex;
            width: 100%;
            position: relative;
        }
        
        .feza-search-input {
            flex: 1;
            border: 2px solid #767676;
            border-right: none;
            height: 44px;
            padding: 0 12px;
            font-size: 16px;
            outline: none;
            font-family: "Helvetica Neue", Arial, sans-serif;
        }
        
        .feza-search-input:focus {
            border-color: #0064d2;
        }
        
        .feza-search-input::placeholder {
            color: #767676;
            font-size: 16px;
        }
        
        .feza-search-category-wrapper {
            position: relative;
        }
        
        .feza-search-category {
            background: #f7f7f7;
            border: 2px solid #767676;
            border-left: none;
            border-right: none;
            height: 44px;
            padding: 0 32px 0 12px;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            min-width: 140px;
        }
        
        .feza-search-category-wrapper::after {
            content: '';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #767676;
            pointer-events: none;
        }
        
        .feza-search-button {
            background: linear-gradient(135deg, #4285f4, #1a73e8);
            border: 2px solid #1a73e8;
            border-radius: 0 4px 4px 0;
            height: 44px;
            color: white;
            padding: 0 24px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        
        .feza-search-button:hover {
            background: linear-gradient(135deg, #3367d6, #1557b0);
        }
        
        .feza-advanced-link {
            color: #0654ba;
            font-size: 13px;
            margin-left: 12px;
            align-self: flex-end;
            margin-bottom: 12px;
        }
        
        .feza-advanced-link:hover {
            text-decoration: underline;
        }
        
        /* Header Icons */
        .feza-header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .feza-header-icon {
            color: #333;
            font-size: 20px;
            position: relative;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .feza-header-icon:hover {
            background-color: #f7f7f7;
            color: #0654ba;
        }
        
        .feza-notification-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            background-color: #e53238;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Navigation Bar - eBay Style */
        .feza-nav-bar {
            background-color: white;
            border-bottom: 1px solid #e5e5e5;
            height: 40px;
            display: flex;
            align-items: center;
        }
        
        .feza-nav-content {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 24px;
        }
        
        .feza-nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 0;
        }
        
        .feza-nav-links li {
            position: relative;
        }
        
        .feza-nav-links a {
            display: block;
            padding: 12px 16px;
            color: #333;
            font-size: 14px;
            font-weight: 400;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .feza-nav-links a:hover,
        .feza-nav-links a.active {
            color: #0654ba;
            border-bottom-color: #0654ba;
        }
        
        /* Search Suggestions */
        .feza-search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            border-top: none;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        
        .feza-search-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        
        .feza-search-suggestion:hover {
            background-color: #f7f7f7;
        }
        
        .feza-search-suggestion:last-child {
            border-bottom: none;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .feza-main-content {
                flex-wrap: wrap;
                height: auto;
                padding: 12px 16px;
            }
            
            .feza-search-container {
                order: 3;
                width: 100%;
                max-width: none;
                margin-top: 12px;
            }
            
            .feza-category-dropdown {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .feza-top-header {
                display: none;
            }
            
            .feza-main-header {
                height: auto;
                padding: 0;
                margin: 0;
                position: sticky;
                top: 0;
                z-index: 1000;
                transition: transform 0.3s ease;
                background: white;
            }
            
            .feza-main-content {
                flex-direction: column;
                gap: 0;
                padding: 0;
            }
            
            /* Remove any body padding that might cause white space */
            body {
                padding-top: 0 !important;
                margin-top: 0 !important;
            }
            
            .mobile-header-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                order: -1;
                padding: 12px 16px;
                transition: max-height 0.3s ease, opacity 0.3s ease, padding 0.3s ease;
                overflow: hidden;
            }
            
            /* Header scroll behavior - hide logo when scrolling down */
            .feza-main-header.scroll-down .mobile-header-row {
                max-height: 0;
                opacity: 0;
                padding: 0 16px;
            }
            
            .feza-main-header.scroll-down .feza-search-container {
                padding: 8px 16px;
            }
            
            /* Hide desktop logo and show mobile logo */
            .feza-logo-desktop {
                display: none;
            }
            
            .feza-logo-mobile {
                display: flex;
            }
            
            .feza-logo {
                font-size: 28px;
                flex: 0;
                text-align: left;
            }
            
            /* Hide desktop header icons on mobile */
            .feza-header-icons-desktop {
                display: none;
            }
            
            .feza-category-dropdown {
                display: none;
            }
            
            .mobile-menu-toggle {
                background: none;
                border: none;
                font-size: 20px;
                color: #333;
                cursor: pointer;
                padding: 8px;
            }
            
            .feza-search-container {
                width: 100%;
                order: 2;
                padding: 0 16px 12px 16px;
            }
            
            .feza-search-form {
                background: #f7f7f7;
                border-radius: 24px;
                overflow: hidden;
                border: 1px solid #ddd;
            }
            
            .feza-search-input {
                border: none;
                background: transparent;
                padding: 12px 16px;
                border-radius: 24px 0 0 24px;
            }
            
            .feza-search-category-wrapper {
                display: none;
            }
            
            .feza-search-button {
                border: none;
                border-radius: 0 24px 24px 0;
                background: #0064d2;
                min-width: 80px;
            }
            
            .feza-advanced-link {
                display: none;
            }
            
            .feza-nav-bar {
                display: none;
            }
            
            /* Mobile Navigation */
            .mobile-nav-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                display: none;
            }
            
            .mobile-nav-overlay.active {
                display: flex;
            }
            
            .mobile-nav {
                width: 80%;
                max-width: 320px;
                height: 100%;
                background: white;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                overflow-y: auto;
            }
            
            .mobile-nav-overlay.active .mobile-nav {
                transform: translateX(0);
            }
            
            .mobile-nav-header {
                padding: 1rem;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8f9fa;
            }
            
            .mobile-nav-header h3 {
                margin: 0;
                font-size: 1.2rem;
                color: #374151;
            }
            
            .mobile-nav-close {
                background: none;
                border: none;
                font-size: 1.5rem;
                color: #6b7280;
                cursor: pointer;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
            }
            
            .mobile-nav-content {
                padding: 1rem 0;
            }
            
            .mobile-nav-link {
                display: block;
                padding: 0.75rem 1rem;
                color: #374151;
                font-size: 1rem;
                border-bottom: 1px solid #f3f4f6;
                transition: background-color 0.2s ease;
            }
            
            .mobile-nav-link:hover {
                background-color: #f8f9fa;
                color: #0654ba;
            }
        }
        
        @media (min-width: 769px) {
            .mobile-header-row {
                display: none;
            }
            
            .feza-logo-mobile {
                display: none;
            }
            
            .feza-logo-desktop {
                display: flex;
            }
            
            .mobile-menu-toggle {
                display: none;
            }
            
            .mobile-nav-overlay {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="feza-top-header">
        <div class="feza-top-content">
            <div class="feza-top-left">
                <?php if ($isLoggedIn): ?>
                    <span class="feza-greeting">Hi <strong><?php echo htmlspecialchars($userName ?? 'User'); ?></strong>!</span>
                <?php else: ?>
                    <span class="feza-greeting">Hi! <a href="/login.php">Sign in</a> or <a href="/register.php">register</a></span>
                <?php endif; ?>
                <a href="/deals.php">Daily Deals</a>
                <a href="/brands.php">Brand Outlet</a>
                <a href="/gift-cards.php">Gift Cards</a>
                <a href="/help.php">Help & Contact</a>
            </div>
            <div class="feza-top-right">
                <a href="/shipping.php">Ship to</a>
                <a href="<?php echo getSellingUrl(); ?>">Sell</a>
                
                <?php if ($isLoggedIn): ?>
                    <div class="feza-dropdown">
                        <div class="feza-dropdown-trigger">
                            <a href="/saved.php">Watchlist</a>
                            <div class="feza-dropdown-arrow"></div>
                        </div>
                        <div class="feza-dropdown-content">
                            <a href="/saved.php?tab=watching">Watch list</a>
                            <a href="/saved.php?tab=recently-viewed">Recently viewed</a>
                            <a href="/saved.php?tab=saved-searches">Saved searches</a>
                            <a href="/saved.php?tab=saved-sellers">Saved sellers</a>
                        </div>
                    </div>
                    <div class="feza-dropdown">
                        <div class="feza-dropdown-trigger">
                            <a href="/account.php">My Feza</a>
                            <div class="feza-dropdown-arrow"></div>
                        </div>
                        <div class="feza-dropdown-content">
                            <a href="/account.php">Summary</a>
                            <a href="/account.php?section=recently-viewed">Recently Viewed</a>
                            <a href="/account.php?section=bids">Bids/Offers</a>
                            <a href="/saved.php">Watchlist</a>
                            <a href="/account.php?section=purchase-history">Purchase History</a>
                            <a href="/account.php?section=buy-again">Buy Again</a>
                            <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                                <a href="<?php echo getSellingUrl(); ?>">Selling</a>
                            <?php endif; ?>
                            <a href="/saved.php?tab=saved-searches">Saved Searches</a>
                            <a href="/saved.php?tab=saved-sellers">Saved Sellers</a>
                            <a href="/messages.php">Messages</a>
                            <a href="/collection.php">Collection beta</a>
                            <?php if ($userRole === 'admin'): ?>
                                <a href="/admin/">Admin Panel</a>
                            <?php endif; ?>
                            <hr style="margin: 4px 0; border: none; border-top: 1px solid #e0e0e0;">
                            <a href="/account.php?section=settings">Account settings</a>
                            <a href="/logout.php">Sign out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/saved.php">Watchlist</a>
                    <a href="/login.php">My Feza</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Header -->
    <div class="feza-main-header">
        <div class="feza-main-content">
            <!-- Mobile Header Row -->
            <div class="mobile-header-row">
                <a href="/" class="feza-logo feza-logo-mobile">
                    <span class="f">F</span><span class="e">e</span><span class="z">z</span><span class="a">a</span>
                </a>
                
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <!-- Desktop Logo -->
            <a href="/" class="feza-logo feza-logo-desktop">
                <span class="f">F</span><span class="e">e</span><span class="z">z</span><span class="a">a</span>
            </a>
            
            <!-- Category Dropdown -->
            <div class="feza-category-dropdown">
                Shop by category
                <div class="feza-category-content">
                    <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
                        <a href="/category.php?cat=<?php echo urlencode($cat['slug'] ?? $cat['id']); ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="/deals.php">Deals & Savings</a>
                </div>
            </div>
            
            <!-- Search Container -->
            <div class="feza-search-container">
                <form action="/search.php" method="GET" class="feza-search-form" id="fezaSearchForm">
                    <input 
                        type="text" 
                        name="q" 
                        class="feza-search-input" 
                        placeholder="Search for anything" 
                        value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                        autocomplete="off"
                        id="fezaSearchInput"
                    >
                    <div class="feza-search-category-wrapper">
                        <select name="category" class="feza-search-category">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['slug'] ?? $cat['id']); ?>" 
                                        <?php echo (isset($_GET['category']) && $_GET['category'] === ($cat['slug'] ?? $cat['id'])) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="feza-search-button">Search</button>
                    <div class="feza-search-suggestions" id="fezaSearchSuggestions"></div>
                </form>
                <a href="/search.php?advanced=1" class="feza-advanced-link">Advanced</a>
            </div>
            
            <!-- Header Icons -->
            <div class="feza-header-icons feza-header-icons-desktop">
                <a href="/notifications.php" class="feza-header-icon" title="Notifications">
                    <i class="far fa-bell"></i>
                    <?php 
                    $unreadCount = 0;
                    if ($unreadCount > 0): 
                    ?>
                        <span class="feza-notification-badge"><?php echo min($unreadCount, 99); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/cart.php" class="feza-header-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="feza-notification-badge"><?php echo min($cart_count, 99); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="feza-nav-bar">
        <div class="feza-nav-content">
            <ul class="feza-nav-links">
                <li><a href="/live.php">Fezamarket Live</a></li>
                <li><a href="/saved.php">Saved</a></li>
                <li><a href="/category.php?cat=electronics">Electronics</a></li>
                <li><a href="/category.php?cat=motors">Motors</a></li>
                <li><a href="/category.php?cat=fashion">Fashion</a></li>
                <li><a href="/category.php?cat=collectibles">Collectibles and Art</a></li>
                <li><a href="/category.php?cat=sports">Sports</a></li>
                <li><a href="/category.php?cat=health">Health & Beauty</a></li>
                <li><a href="/category.php?cat=industrial">Industrial equipment</a></li>
                <li><a href="/category.php?cat=home">Home & Garden</a></li>
                <li><a href="/deals.php">Deals</a></li>
                <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                    <li><a href="<?php echo getSellingUrl(); ?>">Sell</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Mobile Navigation Overlay -->
    <div class="mobile-nav-overlay" id="mobileNavOverlay">
        <div class="mobile-nav">
            <div class="mobile-nav-header">
                <h3>Menu</h3>
                <button class="mobile-nav-close" onclick="toggleMobileMenu()">&times;</button>
            </div>
            <div class="mobile-nav-content">
                <a href="/" class="mobile-nav-link">Home</a>
                <a href="/live.php" class="mobile-nav-link">Fezamarket Live</a>
                <a href="/saved.php" class="mobile-nav-link">Saved</a>
                <a href="/category.php?cat=electronics" class="mobile-nav-link">Electronics</a>
                <a href="/category.php?cat=motors" class="mobile-nav-link">Motors</a>
                <a href="/category.php?cat=fashion" class="mobile-nav-link">Fashion</a>
                <a href="/deals.php" class="mobile-nav-link">Deals</a>
                <a href="/help.php" class="mobile-nav-link">Help</a>
                <?php if ($isLoggedIn): ?>
                    <a href="/account.php" class="mobile-nav-link">My Account</a>
                    <a href="/logout.php" class="mobile-nav-link">Sign Out</a>
                <?php else: ?>
                    <a href="/login.php" class="mobile-nav-link">Sign In</a>
                    <a href="/register.php" class="mobile-nav-link">Register</a>
                <?php endif; ?>
                <?php if ($userRole === 'seller' || $userRole === 'admin'): ?>
                    <a href="<?php echo getSellingUrl(); ?>" class="mobile-nav-link">Seller Center</a>
                <?php else: ?>
                    <a href="<?php echo getSellingUrl(); ?>" class="mobile-nav-link">Start Selling</a>
                <?php endif; ?>
                <?php if ($userRole === 'admin'): ?>
                    <a href="/admin/" class="mobile-nav-link">Admin Panel</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div id="main-content">
        <!-- Page content will be inserted here -->

    <!-- JavaScript for Functionality -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const overlay = document.getElementById('mobileNavOverlay');
            if (overlay) {
                overlay.classList.toggle('active');
            }
        }
        
        // Search suggestions
        const fezaSearchInput = document.getElementById('fezaSearchInput');
        const fezaSearchSuggestions = document.getElementById('fezaSearchSuggestions');
        
        if (fezaSearchInput && fezaSearchSuggestions) {
            let timeout;
            
            fezaSearchInput.addEventListener('input', function() {
                const query = this.value.trim();
                clearTimeout(timeout);
                
                if (query.length < 2) {
                    fezaSearchSuggestions.style.display = 'none';
                    return;
                }
                
                timeout = setTimeout(() => {
                    const suggestions = [
                        query + ' electronics',
                        query + ' deals', 
                        query + ' new',
                        query + ' used'
                    ];
                    
                    fezaSearchSuggestions.innerHTML = suggestions.map(suggestion => 
                        `<div class="feza-search-suggestion" onclick="selectFezaSuggestion('${suggestion.replace(/'/g, "\\'")}')">${suggestion}</div>`
                    ).join('');
                    
                    fezaSearchSuggestions.style.display = 'block';
                }, 250);
            });
            
            document.addEventListener('click', function(e) {
                if (!fezaSearchInput.contains(e.target) && !fezaSearchSuggestions.contains(e.target)) {
                    fezaSearchSuggestions.style.display = 'none';
                }
            });
        }
        
        function selectFezaSuggestion(suggestion) {
            if (fezaSearchInput) {
                fezaSearchInput.value = suggestion;
                fezaSearchSuggestions.style.display = 'none';
                document.getElementById('fezaSearchForm').submit();
            }
        }
        
        // Active nav link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.feza-nav-links a');
            
            navLinks.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                if (linkPath === currentPath || 
                    (currentPath.startsWith('/category') && link.href.includes('cat=')) ||
                    (currentPath === '/deals.php' && link.href.includes('deals')) ||
                    (currentPath === '/live.php' && link.href.includes('live')) ||
                    (currentPath === '/saved.php' && link.href.includes('saved'))) {
                    link.classList.add('active');
                }
            });
            
            // Close mobile menu on overlay click
            const overlay = document.getElementById('mobileNavOverlay');
            if (overlay) {
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) {
                        toggleMobileMenu();
                    }
                });
            }
            
            // Mobile scroll behavior - only on mobile devices
            function initMobileScrollBehavior() {
                // Check if mobile
                if (window.innerWidth > 768) return;
                
                let lastScrollTop = 0;
                let scrollThreshold = 5; // Minimum scroll distance to trigger
                let ticking = false;
                
                function handleScroll() {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                    const header = document.querySelector('.feza-main-header');
                    
                    if (!header) return;
                    
                    // Only trigger if scroll is beyond threshold
                    if (Math.abs(scrollTop - lastScrollTop) < scrollThreshold) {
                        ticking = false;
                        return;
                    }
                    
                    if (scrollTop > lastScrollTop && scrollTop > 50) {
                        // Scrolling down
                        header.classList.add('scroll-down');
                        header.classList.remove('scroll-up');
                    } else {
                        // Scrolling up
                        header.classList.remove('scroll-down');
                        header.classList.add('scroll-up');
                    }
                    
                    lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
                    ticking = false;
                }
                
                window.addEventListener('scroll', function() {
                    if (!ticking) {
                        window.requestAnimationFrame(handleScroll);
                        ticking = true;
                    }
                });
            }
            
            // Initialize on load
            initMobileScrollBehavior();
            
            // Re-initialize on resize (handles device rotation)
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    initMobileScrollBehavior();
                }, 250);
            });
        });
    </script>