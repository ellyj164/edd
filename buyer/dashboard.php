<?php
/**
 * Buyer Dashboard - Comprehensive KPIs and Overview
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

// Require buyer login
Session::requireLogin();

// Get buyer information - graceful fallback if tables don't exist yet
$db = db();
$userId = Session::getUserId();

// Initialize default values
$buyer = ['total_orders' => 0, 'total_spent' => 0.00];
$wallet = ['balance' => 0.00];
$loyalty = ['current_points' => 0];
$recentOrders = [];
$notifications = [];
$wishlistCount = 0;

try {
    // Get or create buyer record
    $buyerQuery = "SELECT * FROM buyers WHERE user_id = ?";
    $buyerStmt = $db->prepare($buyerQuery);
    $buyerStmt->execute([$userId]);
    $buyer = $buyerStmt->fetch();

    if (!$buyer) {
        // Create buyer record if it doesn't exist
        $createBuyerQuery = "INSERT INTO buyers (user_id) VALUES (?)";
        $createBuyerStmt = $db->prepare($createBuyerQuery);
        $createBuyerStmt->execute([$userId]);
        $buyerId = $db->lastInsertId();
        
        // Refetch buyer data
        $buyerStmt->execute([$userId]);
        $buyer = $buyerStmt->fetch();
    } else {
        $buyerId = $buyer['id'];
    }

    // Get buyer wallet
    $walletQuery = "SELECT * FROM buyer_wallets WHERE buyer_id = ? AND currency = 'USD'";
    $walletStmt = $db->prepare($walletQuery);
    $walletStmt->execute([$buyerId]);
    $wallet = $walletStmt->fetch();

    if (!$wallet) {
        // Create wallet if doesn't exist
        $createWalletQuery = "INSERT INTO buyer_wallets (buyer_id, currency) VALUES (?, 'USD')";
        $createWalletStmt = $db->prepare($createWalletQuery);
        $createWalletStmt->execute([$buyerId]);
        
        $walletStmt->execute([$buyerId]);
        $wallet = $walletStmt->fetch();
    }

    // Get loyalty account
    $loyaltyQuery = "SELECT * FROM buyer_loyalty_accounts WHERE buyer_id = ? AND program_name = 'main'";
    $loyaltyStmt = $db->prepare($loyaltyQuery);
    $loyaltyStmt->execute([$buyerId]);
    $loyalty = $loyaltyStmt->fetch();

    if (!$loyalty) {
        // Create loyalty account
        $createLoyaltyQuery = "INSERT INTO buyer_loyalty_accounts (buyer_id, program_name) VALUES (?, 'main')";
        $createLoyaltyStmt = $db->prepare($createLoyaltyQuery);
        $createLoyaltyStmt->execute([$buyerId]);
        
        $loyaltyStmt->execute([$buyerId]);
        $loyalty = $loyaltyStmt->fetch();
    }
} catch (Exception $e) {
    // Graceful fallback if new tables don't exist yet
    error_log("Buyer dashboard: " . $e->getMessage());
    $buyerId = $userId; // Use user ID as fallback
}

$page_title = 'Buyer Dashboard';
includeHeader($page_title);
?>

<div class="buyer-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="sidebar-sticky">
                    <h5 class="sidebar-heading">Buyer Center</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/buyer/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/profile.php">
                                <i class="fas fa-user"></i> Profile & Account
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/orders.php">
                                <i class="fas fa-box"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/wallet.php">
                                <i class="fas fa-wallet"></i> Payments & Wallet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/returns.php">
                                <i class="fas fa-undo"></i> Returns & Refunds
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/disputes.php">
                                <i class="fas fa-gavel"></i> Disputes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/loyalty.php">
                                <i class="fas fa-star"></i> Loyalty & Rewards
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/wishlist.php">
                                <i class="fas fa-heart"></i> Wishlist
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/messages.php">
                                <i class="fas fa-envelope"></i> Messages
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/support.php">
                                <i class="fas fa-headset"></i> Support
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/buyer/privacy.php">
                                <i class="fas fa-shield-alt"></i> Privacy & Compliance
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Buyer Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Export Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Orders
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($buyer['total_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Spent
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            $<?php echo number_format($buyer['total_spent'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Loyalty Points
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($loyalty['current_points']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Wallet Balance
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            $<?php echo number_format($wallet['balance'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-wallet fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Welcome to Your Buyer Dashboard</h6>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                                    <h4>Start Your Shopping Journey</h4>
                                    <p class="text-muted mb-4">Discover amazing products from verified sellers and track your orders with ease.</p>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-0 h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-search fa-2x text-info mb-2"></i>
                                                    <h6>Browse Products</h6>
                                                    <p class="text-muted small">Find exactly what you're looking for</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-0 h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-shipping-fast fa-2x text-success mb-2"></i>
                                                    <h6>Track Orders</h6>
                                                    <p class="text-muted small">Monitor your deliveries in real-time</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-0 h-100">
                                                <div class="card-body text-center">
                                                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                                    <h6>Earn Rewards</h6>
                                                    <p class="text-muted small">Get points with every purchase</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <a href="/products.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-shopping-cart"></i> Start Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="/products.php" class="btn btn-primary">
                                        <i class="fas fa-shopping-cart"></i> Browse Products
                                    </a>
                                    <a href="/buyer/orders.php" class="btn btn-outline-primary">
                                        <i class="fas fa-box"></i> View Orders
                                    </a>
                                    <a href="/buyer/wishlist.php" class="btn btn-outline-primary">
                                        <i class="fas fa-heart"></i> My Wishlist
                                    </a>
                                    <a href="/buyer/support.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-headset"></i> Get Support
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Account Status -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Account Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <span class="badge badge-success">Active</span>
                                    <span class="text-muted">Your account is in good standing</span>
                                </div>
                                <div class="mb-3">
                                    <strong>Member Since:</strong><br>
                                    <span class="text-muted"><?php echo date('F Y', strtotime($buyer['created_at'] ?? 'now')); ?></span>
                                </div>
                                <div class="mb-3">
                                    <strong>Loyalty Tier:</strong><br>
                                    <span class="badge badge-<?php echo getBadgeColor($buyer['tier'] ?? 'bronze'); ?>">
                                        <?php echo ucfirst($buyer['tier'] ?? 'Bronze'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.buyer-dashboard {
    background-color: #f8f9fc;
    min-height: 100vh;
}

.sidebar {
    background-color: #ffffff;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.sidebar-heading {
    font-size: 0.75rem;
    font-weight: 800;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    padding: 1.5rem 1rem 0.5rem;
}

.nav-link {
    color: #858796;
    padding: 0.75rem 1rem;
    border-radius: 0.35rem;
    margin: 0.125rem 1rem;
}

.nav-link:hover,
.nav-link.active {
    color: #5a5c69;
    background-color: #eaecf4;
}

.nav-link i {
    margin-right: 0.5rem;
    width: 1rem;
    text-align: center;
}

.main-content {
    padding: 0 1.5rem;
}

.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.badge-bronze { background-color: #cd7f32; }
.badge-silver { background-color: #c0c0c0; }
.badge-gold { background-color: #ffd700; }
.badge-platinum { background-color: #e5e4e2; }
.badge-diamond { background-color: #b9f2ff; }
</style>

<?php
function getBadgeColor($tier) {
    switch (strtolower($tier)) {
        case 'bronze': return 'secondary';
        case 'silver': return 'light';
        case 'gold': return 'warning';
        case 'platinum': return 'info';
        case 'diamond': return 'primary';
        default: return 'secondary';
    }
}

includeFooter();
?>