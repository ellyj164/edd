<?php
/**
 * Seller Dashboard Header - Uses Main eBay Header + Seller Banner
 */

// Include the main eBay-style header first
$page_title = $page_title ?? 'Seller Dashboard - FezaMarket';
$meta_description = $meta_description ?? 'Manage your FezaMarket seller account, products, orders, and performance analytics.';

include __DIR__ . '/header.php';
?>

<!-- Seller Dashboard Banner -->
<div class="seller-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h1 class="h4 mb-0 text-white">
                    <i class="fas fa-store me-2"></i>
                    Seller Dashboard
                </h1>
                <span class="badge bg-success ms-3">Active Seller</span>
            </div>
            
            <div class="seller-nav">
                <a href="/seller/dashboard.php" class="seller-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/seller/products.php" class="seller-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' || strpos($_SERVER['REQUEST_URI'], '/products/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="/seller/orders.php" class="seller-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="/seller/live.php" class="seller-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'live.php' ? 'active' : ''; ?>">
                    <i class="fas fa-video"></i> Live
                </a>
                <a href="/seller/analytics.php" class="seller-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
                <a href="/seller/profile.php" class="seller-nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog"></i> Account
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .seller-banner {
        background: linear-gradient(135deg, #0654ba, #4f46e5);
        color: white;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .seller-nav {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .seller-nav-link {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .seller-nav-link:hover,
    .seller-nav-link.active {
        color: white;
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    @media (max-width: 768px) {
        .seller-banner .d-flex {
            flex-direction: column;
            gap: 1rem;
        }
        
        .seller-nav {
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .seller-nav-link {
            font-size: 13px;
            padding: 0.4rem 0.8rem;
        }
    }
</style>

<div class="seller-content-wrapper">
    <!-- Seller page content will go here -->