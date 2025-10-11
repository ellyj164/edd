<?php
/**
 * Seller Center Dashboard
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require user login
Session::requireLogin();

$vendor = new Vendor();
$product = new Product();
$order = new Order();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) {
    // Redirect to seller registration if not a vendor
    header('Location: ' . getSellingUrl());
    exit;
}

// Get vendor statistics
$vendorProducts = $product->getByVendorId($vendorInfo['id']);
$recentOrders = $order->getVendorOrders($vendorInfo['id'], 5);
$stats = [
    'total_products' => count($vendorProducts),
    'active_products' => count(array_filter($vendorProducts, function($p) { return $p['status'] === 'active'; })),
    'total_orders' => count($order->getVendorOrders($vendorInfo['id'])),
    'pending_orders' => count(array_filter($recentOrders, function($o) { return $o['status'] === 'pending'; }))
];

$page_title = 'Seller Center - Manage Your Store';
includeHeader($page_title);
?>

<div class="container">
    <!-- Seller Header -->
    <div class="seller-header">
        <div class="seller-info">
            <h1>Welcome back, <?php echo htmlspecialchars($vendorInfo['business_name']); ?>!</h1>
            <p class="seller-subtitle">Manage your store and track your performance</p>
            <div class="seller-status">
                <span class="status-badge status-<?php echo strtolower($vendorInfo['status']); ?>">
                    <?php echo ucfirst($vendorInfo['status']); ?>
                </span>
                <span class="member-since">Member since <?php echo formatDate($vendorInfo['created_at']); ?></span>
            </div>
        </div>
        <div class="seller-actions">
            <a href="/seller/products/add.php" class="btn btn-primary">+ Add Product</a>
            <a href="/seller/profile.php" class="btn btn-outline">Edit Profile</a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $stats['total_products']; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $stats['active_products']; ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏰</div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="seller-content">
        <!-- Quick Actions -->
        <div class="seller-section">
            <div class="section-card">
                <h2>Quick Actions</h2>
                <div class="quick-actions-grid">
                    <a href="/seller/products.php" class="action-card">
                        <span class="action-icon">📋</span>
                        <div class="action-info">
                            <div class="action-title">Manage Products</div>
                            <div class="action-subtitle">Edit listings, update inventory</div>
                        </div>
                    </a>
                    <a href="/seller/orders.php" class="action-card">
                        <span class="action-icon">📦</span>
                        <div class="action-info">
                            <div class="action-title">Process Orders</div>
                            <div class="action-subtitle">View and fulfill orders</div>
                        </div>
                    </a>
                    <a href="/seller/analytics.php" class="action-card">
                        <span class="action-icon">📊</span>
                        <div class="action-info">
                            <div class="action-title">View Analytics</div>
                            <div class="action-subtitle">Track sales performance</div>
                        </div>
                    </a>
                    <a href="/seller/marketing.php" class="action-card">
                        <span class="action-icon">📈</span>
                        <div class="action-info">
                            <div class="action-title">Marketing Tools</div>
                            <div class="action-subtitle">Promote your products</div>
                        </div>
                    </a>
                    <a href="/seller/settings.php" class="action-card">
                        <span class="action-icon">⚙️</span>
                        <div class="action-info">
                            <div class="action-title">Account Settings</div>
                            <div class="action-subtitle">Update preferences</div>
                        </div>
                    </a>
                    <a href="/help/selling.php" class="action-card">
                        <span class="action-icon">❓</span>
                        <div class="action-info">
                            <div class="action-title">Get Help</div>
                            <div class="action-subtitle">Seller support center</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="seller-section">
            <div class="section-card">
                <div class="section-header">
                    <h2>Recent Orders</h2>
                    <a href="/seller/orders.php" class="view-all-link">View All Orders</a>
                </div>
                
                <?php if (!empty($recentOrders)): ?>
                    <div class="orders-table">
                        <div class="table-header">
                            <div class="col-order">Order</div>
                            <div class="col-customer">Customer</div>
                            <div class="col-date">Date</div>
                            <div class="col-status">Status</div>
                            <div class="col-total">Total</div>
                            <div class="col-actions">Actions</div>
                        </div>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="table-row">
                                <div class="col-order">
                                    <a href="/seller/order.php?id=<?php echo $order['id']; ?>" class="order-link">
                                        #<?php echo $order['id']; ?>
                                    </a>
                                </div>
                                <div class="col-customer">
                                    <?php echo htmlspecialchars($order['customer_name'] ?? 'Customer'); ?>
                                </div>
                                <div class="col-date">
                                    <?php echo formatDate($order['created_at']); ?>
                                </div>
                                <div class="col-status">
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="col-total">
                                    <?php echo formatPrice($order['total']); ?>
                                </div>
                                <div class="col-actions">
                                    <a href="/seller/order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h3>No orders yet</h3>
                        <p>Your orders will appear here once customers start buying your products.</p>
                        <a href="/seller/marketing.php" class="btn">Promote Your Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Performance -->
        <div class="seller-section">
            <div class="section-card">
                <div class="section-header">
                    <h2>Top Products</h2>
                    <a href="/seller/products.php" class="view-all-link">Manage Products</a>
                </div>
                
                <?php if (!empty($vendorProducts)): ?>
                    <div class="products-grid">
                        <?php foreach (array_slice($vendorProducts, 0, 4) as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo getSafeProductImageUrl($product); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="product-status status-<?php echo strtolower($product['status']); ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h4 class="product-title">
                                        <a href="/product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h4>
                                    <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                                    <div class="product-stock">
                                        Stock: <?php echo $product['stock_quantity']; ?> units
                                    </div>
                                    <div class="product-actions">
                                        <a href="/seller/products/edit.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-outline">Edit</a>
                                        <a href="/product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>No products listed</h3>
                        <p>Start selling by adding your first product to your store.</p>
                        <a href="/seller/products/add.php" class="btn">Add Your First Product</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Seller Tips -->
        <div class="seller-section">
            <div class="section-card tips-card">
                <h2>💡 Seller Tips</h2>
                <div class="tips-list">
                    <div class="tip-item">
                        <strong>Optimize your listings:</strong> Use high-quality photos and detailed descriptions to attract more buyers.
                    </div>
                    <div class="tip-item">
                        <strong>Competitive pricing:</strong> Research similar products to set competitive prices and increase sales.
                    </div>
                    <div class="tip-item">
                        <strong>Fast shipping:</strong> Quick fulfillment leads to better customer reviews and increased visibility.
                    </div>
                    <div class="tip-item">
                        <strong>Excellent service:</strong> Respond to customer messages promptly to build trust and loyalty.
                    </div>
                </div>
                <a href="/help/selling.php" class="learn-more-link">Learn more selling tips →</a>
            </div>
        </div>
    </div>
</div>

<style>
.seller-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 1px solid #e5e7eb;
}

.seller-header h1 {
    font-size: 28px;
    color: #1f2937;
    margin-bottom: 5px;
}

.seller-subtitle {
    color: #6b7280;
    margin-bottom: 15px;
}

.seller-status {
    display: flex;
    gap: 15px;
    align-items: center;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-active { background: #dcfce7; color: #166534; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-suspended { background: #fee2e2; color: #991b1b; }

.member-since {
    color: #6b7280;
    font-size: 14px;
}

.seller-actions {
    display: flex;
    gap: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 36px;
    flex-shrink: 0;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #1f2937;
}

.stat-label {
    color: #6b7280;
    font-size: 14px;
}

.seller-content {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.section-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.section-header h2 {
    color: #1f2937;
    font-size: 20px;
}

.view-all-link {
    color: #0654ba;
    text-decoration: none;
    font-weight: 600;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.action-card:hover {
    background: #f3f4f6;
    transform: translateY(-2px);
}

.action-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.action-title {
    font-weight: 600;
    color: #1f2937;
}

.action-subtitle {
    color: #6b7280;
    font-size: 14px;
}

.orders-table {
    background: #f9fafb;
    border-radius: 8px;
    overflow: hidden;
}

.table-header, .table-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 100px 100px 80px;
    gap: 15px;
    align-items: center;
    padding: 15px 20px;
}

.table-header {
    background: #f3f4f6;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}

.table-row {
    border-bottom: 1px solid #e5e7eb;
    background: white;
}

.table-row:last-child {
    border-bottom: none;
}

.order-link {
    color: #0654ba;
    text-decoration: none;
    font-weight: 600;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.product-card {
    background: #f9fafb;
    border-radius: 8px;
    overflow: hidden;
}

.product-image {
    position: relative;
    height: 150px;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.product-info {
    padding: 15px;
}

.product-title a {
    color: #1f2937;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
}

.product-price {
    color: #dc2626;
    font-weight: 600;
    margin: 8px 0;
}

.product-stock {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 15px;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 20px;
}

.tips-card {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border: 1px solid #d1d5db;
}

.tips-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.tip-item {
    padding: 15px;
    background: white;
    border-radius: 6px;
    color: #374151;
    line-height: 1.5;
}

.tip-item strong {
    color: #1f2937;
}

.learn-more-link {
    color: #0654ba;
    text-decoration: none;
    font-weight: 600;
}

@media (max-width: 768px) {
    .seller-header {
        flex-direction: column;
        gap: 20px;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .table-header, .table-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
}
</style>

<?php includeFooter(); ?>