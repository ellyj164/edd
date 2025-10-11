<?php
/**
 * eBay-Style Header - Exact Match Implementation
 * This header replicates the eBay design exactly as shown in the reference image
 */

// Ensure user session and functions are available
if (!function_exists('Session')) {
    require_once __DIR__ . '/functions.php';
}
if (!function_exists('getCurrentUserRole')) {
    require_once __DIR__ . '/functions.php';
}

$isLoggedIn = class_exists('Session') ? Session::isLoggedIn() : false;
$userRole = getCurrentUserRole();
$userName = $isLoggedIn ? (Session::get('user_name') ?? Session::get('email')) : null;

// Get badge counts for logged in users
$cartCount = 0;
$wishlistCount = 0;
$watchlistCount = 0;

if ($isLoggedIn && function_exists('db')) {
    try {
        $pdo = db();
        $userId = Session::getUserId();
        
        // Get cart count (sum of quantities)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cartCount = (int)$stmt->fetchColumn();
        
        // Get wishlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wishlistCount = (int)$stmt->fetchColumn();
        
        // Get watchlist count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM watchlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        $watchlistCount = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Header badge count error: " . $e->getMessage());
    }
}

$page_title = $page_title ?? 'Fezamarket - Electronics, Cars, Fashion, Collectibles & More | Fezamarket';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? 'Buy & sell electronics, cars, clothes, collectibles & more on Fezamarket, the world\'s online marketplace. Top brands, low prices & free shipping on many items.'); ?>">
    <meta name="keywords" content="buy, sell, auction, online marketplace, electronics, fashion, home, garden, collectibles, cars, sporting goods">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo csrfToken(); ?>">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/icons.css">
    <link rel="stylesheet" href="/css/mobile-responsive.css">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Local jQuery replacement -->
    <script src="/js/jquery-shim.js"></script>
    
    <style>
        /* Global Reset and Base Styles */
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
        
        /* Top Header Bar - Exact eBay Style */
        .ebay-top-header {
            background-color: #f7f7f7;
            border-bottom: 1px solid #e5e5e5;
            height: 36px;
            display: flex;
            align-items: center;
            font-size: 13px;
        }
        
        .ebay-top-header-content {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
        }
        
        .ebay-top-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .ebay-top-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .ebay-top-header a {
            color: #0654ba;
            font-size: 13px;
            line-height: 16px;
            padding: 4px 0;
        }
        
        .ebay-top-header a:hover {
            text-decoration: underline;
        }
        
        .ebay-greeting {
            color: #333;
            font-size: 13px;
        }
        
        /* Dropdown Styles */
        .ebay-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .ebay-dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 4px;
            color: #0654ba;
            cursor: pointer;
        }
        
        .ebay-dropdown-arrow {
            width: 0;
            height: 0;
            border-left: 4px solid transparent;
            border-right: 4px solid transparent;
            border-top: 4px solid #767676;
            margin-left: 4px;
        }
        
        .ebay-dropdown-content {
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
        
        .ebay-dropdown:hover .ebay-dropdown-content {
            display: block;
        }
        
        .ebay-dropdown-content a {
            display: block;
            padding: 8px 16px;
            color: #333 !important;
            font-size: 13px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .ebay-dropdown-content a:hover {
            background-color: #f7f7f7;
            text-decoration: none !important;
        }
        
        .ebay-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        /* Main Header - Exact eBay Style */
        .ebay-main-header {
            background-color: white;
            height: 80px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #e5e5e5;
        }
        
        .ebay-main-header-content {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 16px;
        }
        
        /* eBay Logo - Exact Colors */
        .ebay-logo {
            font-size: 36px;
            font-weight: bold;
            font-family: "Helvetica Neue", Arial, sans-serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            margin-right: 8px;
        }
        
        .ebay-logo .e1 { color: #e53238; }  /* Red e */
        .ebay-logo .b { color: #0064d2; }   /* Blue b */
        .ebay-logo .a { color: #f5af02; }   /* Yellow a */
        .ebay-logo .y { color: #86b817; }   /* Green y */
        
        /* Category Dropdown */
        .ebay-category-dropdown {
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
        
        .ebay-category-dropdown::after {
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
        
        .ebay-category-dropdown:hover {
            background-color: #f7f7f7;
        }
        
        .ebay-category-dropdown-content {
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
        
        .ebay-category-dropdown:hover .ebay-category-dropdown-content {
            display: block;
        }
        
        .ebay-category-dropdown-content a {
            display: block;
            padding: 12px 16px;
            color: #333;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .ebay-category-dropdown-content a:hover {
            background-color: #f7f7f7;
            color: #0654ba;
        }
        
        .ebay-category-dropdown-content a:last-child {
            border-bottom: none;
        }
        
        /* Search Container - Exact eBay Style */
        .ebay-search-container {
            flex: 1;
            display: flex;
            max-width: 800px;
            position: relative;
        }
        
        .ebay-search-form {
            display: flex;
            width: 100%;
            position: relative;
        }
        
        .ebay-search-input {
            flex: 1;
            border: 2px solid #767676;
            border-right: none;
            height: 44px;
            padding: 0 12px;
            font-size: 16px;
            outline: none;
            font-family: "Helvetica Neue", Arial, sans-serif;
        }
        
        .ebay-search-input:focus {
            border-color: #0064d2;
        }
        
        .ebay-search-input::placeholder {
            color: #767676;
            font-size: 16px;
        }
        
        .ebay-search-category {
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
            position: relative;
        }
        
        .ebay-search-category-wrapper {
            position: relative;
        }
        
        .ebay-search-category-wrapper::after {
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
        
        .ebay-search-button {
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
        
        .ebay-search-button:hover {
            background: linear-gradient(135deg, #3367d6, #1557b0);
        }
        
        .ebay-advanced-link {
            color: #0654ba;
            font-size: 13px;
            margin-left: 12px;
            align-self: flex-end;
            margin-bottom: 12px;
        }
        
        .ebay-advanced-link:hover {
            text-decoration: underline;
        }
        
        /* Header Icons */
        .ebay-header-icons {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .ebay-header-icon {
            color: #333;
            font-size: 20px;
            position: relative;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .ebay-header-icon:hover {
            background-color: #f7f7f7;
            color: #0654ba;
        }
        
        .ebay-notification-badge {
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
        
        /* Navigation Bar - Exact eBay Style */
        .ebay-nav-bar {
            background-color: white;
            border-bottom: 1px solid #e5e5e5;
            height: 40px;
            display: flex;
            align-items: center;
        }
        
        .ebay-nav-content {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            padding: 0 24px;
        }
        
        .ebay-nav-links {
            display: flex;
            align-items: center;
            list-style: none;
            gap: 0;
        }
        
        .ebay-nav-links li {
            position: relative;
        }
        
        .ebay-nav-links a {
            display: block;
            padding: 12px 16px;
            color: #333;
            font-size: 14px;
            font-weight: 400;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .ebay-nav-links a:hover,
        .ebay-nav-links a.active {
            color: #0654ba;
            border-bottom-color: #0654ba;
        }
        
        /* Search Suggestions */
        .ebay-search-suggestions {
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
        
        .ebay-search-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .ebay-search-suggestion:hover {
            background-color: #f7f7f7;
        }
        
        .ebay-search-suggestion:last-child {
            border-bottom: none;
        }
        
        .ebay-suggestion-text {
            color: #333;
        }
        
        .ebay-suggestion-type {
            font-size: 12px;
            color: #767676;
            font-style: italic;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .ebay-main-header-content {
                flex-wrap: wrap;
                height: auto;
                padding: 12px 16px;
            }
            
            .ebay-search-container {
                order: 3;
                width: 100%;
                max-width: none;
                margin-top: 12px;
            }
            
            .ebay-category-dropdown {
                display: none;
            }
            
            .ebay-top-header-content {
                padding: 0 16px;
            }
        }
        
        @media (max-width: 768px) {
            .ebay-top-header {
                font-size: 12px;
            }
            
            .ebay-top-left > *:nth-child(n+3),
            .ebay-top-right > *:nth-child(n+3) {
                display: none;
            }
            
            .ebay-logo {
                font-size: 30px;
            }
            
            .ebay-nav-links {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .ebay-nav-links a {
                padding: 10px 12px;
                font-size: 13px;
            }
            
            .ebay-header-icons {
                gap: 12px;
            }
        }
        
        /* Additional eBay-specific styles */
        .ebay-separator {
            color: #767676;
            margin: 0 4px;
        }
        
        .ebay-beta-badge {
            background: #0654ba;
            color: white;
            font-size: 10px;
            padding: 2px 4px;
            border-radius: 2px;
            margin-left: 4px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="ebay-top-header">
        <div class="ebay-top-header-content">
            <div class="ebay-top-left">
                <?php if ($isLoggedIn): ?>
                    <span class="ebay-greeting">Hi <strong><?php echo htmlspecialchars($userName ?? 'User'); ?></strong>!</span>
                <?php else: ?>
                    <span class="ebay-greeting">Hi! <a href="/login.php">Sign in</a> or <a href="/register.php">register</a></span>
                <?php endif; ?>
                <a href="/deals.php">Daily Deals</a>
                <a href="/brands.php">Brand Outlet</a>
                <a href="/gift-cards.php">Gift Cards</a>
                <a href="/help.php">Help & Contact</a>
            </div>
            <div class="ebay-top-right">
                <a href="/shipping.php">Ship to</a>
                <a href="<?php echo getSellingUrl(); ?>">Sell</a>
                
                <?php if ($isLoggedIn): ?>
                    <div class="ebay-dropdown">
                        <div class="ebay-dropdown-trigger">
                            <a href="/saved.php">Watchlist</a>
                            <div class="ebay-dropdown-arrow"></div>
                        </div>
                        <div class="ebay-dropdown-content">
                            <a href="/saved.php?tab=watching">Watch list</a>
                            <a href="/saved.php?tab=recently-viewed">Recently viewed</a>
                            <a href="/saved.php?tab=saved-searches">Saved searches</a>
                            <a href="/saved.php?tab=saved-sellers">Saved sellers</a>
                        </div>
                    </div>
                    <div class="ebay-dropdown">
                        <div class="ebay-dropdown-trigger">
                            <a href="/account.php">My Feza</a>
                            <div class="ebay-dropdown-arrow"></div>
                        </div>
                        <div class="ebay-dropdown-content">
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
    <div class="ebay-main-header">
        <div class="ebay-main-header-content">
            <a href="/" class="ebay-logo">
                <img src="/assets/images/logo.png" alt="FezaMarket" style="height: 40px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                <span style="display:none; font-weight: bold; font-size: 28px; color: #2563eb;">FezaMarket</span>
            </a>
            
            <div class="ebay-category-dropdown">
                Shop by category
                <div class="ebay-category-dropdown-content">
                    <a href="/category.php?cat=electronics">Electronics</a>
                    <a href="/category.php?cat=motors">Motors</a>
                    <a href="/category.php?cat=fashion">Fashion</a>
                    <a href="/category.php?cat=collectibles">Collectibles & Art</a>
                    <a href="/category.php?cat=sports">Sports</a>
                    <a href="/category.php?cat=health">Health & Beauty</a>
                    <a href="/category.php?cat=industrial">Industrial equipment</a>
                    <a href="/category.php?cat=home">Home & Garden</a>
                    <a href="/deals.php">Deals & Savings</a>
                </div>
            </div>
            
            <div class="ebay-search-container">
                <form action="/search.php" method="GET" class="ebay-search-form" id="ebaySearchForm">
                    <input 
                        type="text" 
                        name="q" 
                        class="ebay-search-input" 
                        placeholder="Search for anything" 
                        value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                        autocomplete="off"
                        id="ebaySearchInput"
                    >
                    <div class="ebay-search-category-wrapper">
                        <select name="category" class="ebay-search-category">
                            <option value="">All Categories</option>
                            <option value="electronics" <?php echo ($_GET['category'] ?? '') === 'electronics' ? 'selected' : ''; ?>>Electronics</option>
                            <option value="motors" <?php echo ($_GET['category'] ?? '') === 'motors' ? 'selected' : ''; ?>>Motors</option>
                            <option value="fashion" <?php echo ($_GET['category'] ?? '') === 'fashion' ? 'selected' : ''; ?>>Fashion</option>
                            <option value="collectibles" <?php echo ($_GET['category'] ?? '') === 'collectibles' ? 'selected' : ''; ?>>Collectibles</option>
                            <option value="sports" <?php echo ($_GET['category'] ?? '') === 'sports' ? 'selected' : ''; ?>>Sports</option>
                            <option value="health" <?php echo ($_GET['category'] ?? '') === 'health' ? 'selected' : ''; ?>>Health & Beauty</option>
                            <option value="industrial" <?php echo ($_GET['category'] ?? '') === 'industrial' ? 'selected' : ''; ?>>Industrial</option>
                            <option value="home" <?php echo ($_GET['category'] ?? '') === 'home' ? 'selected' : ''; ?>>Home & Garden</option>
                        </select>
                    </div>
                    <button type="submit" class="ebay-search-button">Search</button>
                    <div class="ebay-search-suggestions" id="ebaySearchSuggestions"></div>
                </form>
                <a href="/search.php?advanced=1" class="ebay-advanced-link">Advanced</a>
            </div>
            
            <div class="ebay-header-icons">
                <a href="/notifications.php" class="ebay-header-icon" title="Notifications">
                    <i class="far fa-bell"></i>
                    <?php 
                    // Check for unread notifications
                    $unreadCount = 0; // TODO: Implement unread notifications count
                    if ($unreadCount > 0): 
                    ?>
                        <span class="ebay-notification-badge"><?php echo min($unreadCount, 99); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/wishlist.php" class="ebay-header-icon" title="Wishlist">
                    <i class="far fa-heart"></i>
                    <?php if ($wishlistCount > 0): ?>
                        <span class="ebay-notification-badge"><?php echo min($wishlistCount, 99); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/watchlist.php" class="ebay-header-icon" title="Watchlist">
                    <i class="far fa-eye"></i>
                    <?php if ($watchlistCount > 0): ?>
                        <span class="ebay-notification-badge"><?php echo min($watchlistCount, 99); ?></span>
                    <?php endif; ?>
                </a>
                <a href="/cart.php" class="ebay-header-icon" title="Shopping Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="ebay-notification-badge"><?php echo min($cartCount, 99); ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigation Bar -->
    <nav class="ebay-nav-bar">
        <div class="ebay-nav-content">
            <!-- Hamburger Menu Button (Mobile Only) -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
                <span class="hamburger-icon"></span>
            </button>
            
            <ul class="ebay-nav-links" id="mainNavLinks">
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

    <!-- JavaScript for Enhanced eBay Functionality -->
    <script>
        // Search suggestions functionality
        const ebaySearchInput = document.getElementById('ebaySearchInput');
        const ebaySearchSuggestions = document.getElementById('ebaySearchSuggestions');
        
        if (ebaySearchInput && ebaySearchSuggestions) {
            let timeout;
            
            ebaySearchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                clearTimeout(timeout);
                
                if (query.length < 2) {
                    ebaySearchSuggestions.style.display = 'none';
                    return;
                }
                
                timeout = setTimeout(() => {
                    // Fetch suggestions from API
                    fetch(`/api/search-suggestions.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.suggestions) {
                                const suggestions = data.data.suggestions.slice(0, 8);
                                
                                ebaySearchSuggestions.innerHTML = suggestions.map(suggestion => 
                                    `<div class="ebay-search-suggestion" onclick="selectEbaySuggestion('${suggestion.name.replace(/'/g, "\\'")}')" data-type="${suggestion.type}">
                                        <span class="ebay-suggestion-text">${suggestion.name}</span>
                                        ${suggestion.type === 'category' ? '<span class="ebay-suggestion-type">in Category</span>' : ''}
                                    </div>`
                                ).join('');
                                
                                if (suggestions.length > 0) {
                                    ebaySearchSuggestions.style.display = 'block';
                                }
                            }
                        })
                        .catch(error => {
                            console.log('Search suggestions error:', error);
                            // Fallback suggestions
                            const suggestions = [
                                query + ' electronics',
                                query + ' deals', 
                                query + ' new',
                                query + ' used'
                            ];
                            
                            ebaySearchSuggestions.innerHTML = suggestions.map(suggestion => 
                                `<div class="ebay-search-suggestion" onclick="selectEbaySuggestion('${suggestion.replace(/'/g, "\\'")}')">${suggestion}</div>`
                            ).join('');
                            
                            ebaySearchSuggestions.style.display = 'block';
                        });
                }, 250);
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!ebaySearchInput.contains(e.target) && !ebaySearchSuggestions.contains(e.target)) {
                    ebaySearchSuggestions.style.display = 'none';
                }
            });
            
            // Hide suggestions on escape key
            ebaySearchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    ebaySearchSuggestions.style.display = 'none';
                }
            });
        }
        
        function selectEbaySuggestion(suggestion) {
            if (ebaySearchInput) {
                ebaySearchInput.value = suggestion;
                ebaySearchSuggestions.style.display = 'none';
                document.getElementById('ebaySearchForm').submit();
            }
        }
        
        // Add active class to current page nav link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const navLinks = document.querySelectorAll('.ebay-nav-links a');
            
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
        });
        
        // Enhanced dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.ebay-dropdown');
            
            dropdowns.forEach(dropdown => {
                const trigger = dropdown.querySelector('.ebay-dropdown-trigger');
                const content = dropdown.querySelector('.ebay-dropdown-content');
                
                if (trigger && content) {
                    let hoverTimeout;
                    
                    dropdown.addEventListener('mouseenter', function() {
                        clearTimeout(hoverTimeout);
                        content.style.display = 'block';
                    });
                    
                    dropdown.addEventListener('mouseleave', function() {
                        hoverTimeout = setTimeout(() => {
                            content.style.display = 'none';
                        }, 100);
                    });
                }
            });
        });
        
        // Category dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryDropdown = document.querySelector('.ebay-category-dropdown');
            const categoryContent = document.querySelector('.ebay-category-dropdown-content');
            
            if (categoryDropdown && categoryContent) {
                let hoverTimeout;
                
                categoryDropdown.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                    categoryContent.style.display = 'block';
                });
                
                categoryDropdown.addEventListener('mouseleave', function() {
                    hoverTimeout = setTimeout(() => {
                        categoryContent.style.display = 'none';
                    }, 100);
                });
                
                categoryContent.addEventListener('mouseenter', function() {
                    clearTimeout(hoverTimeout);
                });
                
                categoryContent.addEventListener('mouseleave', function() {
                    hoverTimeout = setTimeout(() => {
                        categoryContent.style.display = 'none';
                    }, 100);
                });
            }
        });
        
        // Mobile menu toggle functionality
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const mainNavLinks = document.getElementById('mainNavLinks');
        
        if (mobileMenuToggle && mainNavLinks) {
            mobileMenuToggle.addEventListener('click', function() {
                this.classList.toggle('active');
                mainNavLinks.classList.toggle('mobile-open');
            });
            
            // Close menu when clicking a link
            const navLinks = mainNavLinks.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    mobileMenuToggle.classList.remove('active');
                    mainNavLinks.classList.remove('mobile-open');
                });
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInside = mobileMenuToggle.contains(event.target) || mainNavLinks.contains(event.target);
                if (!isClickInside && mainNavLinks.classList.contains('mobile-open')) {
                    mobileMenuToggle.classList.remove('active');
                    mainNavLinks.classList.remove('mobile-open');
                }
            });
        }
    </script>

    <!-- Main Content Container Start -->
    <div id="main-content">
        <!-- Page content will be inserted here by individual pages -->