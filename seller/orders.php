<?php
/**
 * Seller Order Management
 * E-Commerce Platform - Comprehensive Order Processing
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php'; // Seller authentication guard

// Initialize database connection
$db = db();

$vendor = new Vendor();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = (int)$_POST['order_id'];
    $orderItemId = isset($_POST['order_item_id']) ? (int)$_POST['order_item_id'] : null;
    
    try {
        switch ($action) {
            case 'update_status':
                $newStatus = $_POST['status'];
                $notes = $_POST['notes'] ?? '';
                
                if ($orderItemId) {
                    // Update specific order item
                    $updateQuery = "UPDATE order_items SET status = ? WHERE id = ? AND vendor_id = ?";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([$newStatus, $orderItemId, $vendorId]);
                    
                    // Log the status change
                    $logQuery = "INSERT INTO order_status_logs (order_item_id, old_status, new_status, notes, changed_by, changed_at) VALUES (?, ?, ?, ?, ?, NOW())";
                    $logStmt = $db->prepare($logQuery);
                    $logStmt->execute([$orderItemId, $_POST['old_status'], $newStatus, $notes, Session::getUserId()]);
                }
                
                Session::setFlash('success', 'Order status updated successfully.');
                break;
                
            case 'add_tracking':
                $trackingNumber = $_POST['tracking_number'];
                $carrier = $_POST['carrier'];
                
                if ($orderItemId) {
                    $updateQuery = "UPDATE order_items SET tracking_number = ?, status = 'shipped', shipped_at = NOW() WHERE id = ? AND vendor_id = ?";
                    $updateStmt = $db->prepare($updateQuery);
                    $updateStmt->execute([$trackingNumber, $orderItemId, $vendorId]);
                    
                    // Add tracking info
                    $trackingQuery = "INSERT INTO order_tracking (order_item_id, tracking_number, carrier, status, created_at) VALUES (?, ?, ?, 'shipped', NOW())";
                    $trackingStmt = $db->prepare($trackingQuery);
                    $trackingStmt->execute([$orderItemId, $trackingNumber, $carrier]);
                }
                
                Session::setFlash('success', 'Tracking information added successfully.');
                break;
        }
        
    } catch (Exception $e) {
        Session::setFlash('error', 'Error updating order: ' . $e->getMessage());
    }
    
    redirect('/seller/orders.php');
}

// Handle filters and search
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$dateRange = $_GET['date_range'] ?? '';
$sort = $_GET['sort'] ?? 'created_at_desc';

// Build query with filters
$whereConditions = ['oi.vendor_id = ?'];
$params = [$vendorId];

if (!empty($search)) {
    $whereConditions[] = '(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR oi.product_name LIKE ?)';
    $searchTerm = "%{$search}%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $whereConditions[] = 'oi.status = ?';
    $params[] = $status;
}

if (!empty($dateRange)) {
    switch ($dateRange) {
        case 'today':
            $whereConditions[] = 'DATE(o.created_at) = CURDATE()';
            break;
        case 'week':
            $whereConditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
            break;
        case 'month':
            $whereConditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
            break;
        case 'quarter':
            $whereConditions[] = 'o.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
            break;
    }
}

// Handle sorting
$orderClause = match($sort) {
    'created_at_asc' => 'ORDER BY o.created_at ASC',
    'order_number_asc' => 'ORDER BY o.order_number ASC',
    'order_number_desc' => 'ORDER BY o.order_number DESC',
    'customer_name' => 'ORDER BY u.first_name ASC, u.last_name ASC',
    'amount_asc' => 'ORDER BY oi.subtotal ASC',
    'amount_desc' => 'ORDER BY oi.subtotal DESC',
    'status' => 'ORDER BY oi.status ASC',
    default => 'ORDER BY o.created_at DESC'
};

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Get orders with pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$ordersQuery = "
    SELECT 
        o.id as order_id, o.order_number, o.status as order_status, o.total as order_total,
        o.payment_status, o.created_at as order_date, o.shipping_address, o.billing_address,
        oi.id as order_item_id, oi.product_id, oi.product_name, oi.sku, oi.qty,
        oi.price, oi.subtotal, oi.status as item_status, oi.tracking_number,
        oi.shipped_at, oi.delivered_at,
        u.id as customer_id, u.first_name, u.last_name, u.email, u.phone,
        p.id as product_exists
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    LEFT JOIN products p ON oi.product_id = p.id
    $whereClause
    $orderClause
    LIMIT $limit OFFSET $offset
";

$ordersStmt = $db->prepare($ordersQuery);
$ordersStmt->execute($params);
$orders = $ordersStmt->fetchAll();

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    $whereClause
";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Get order statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN oi.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN oi.status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN oi.status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN oi.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(oi.subtotal) as total_revenue
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE oi.vendor_id = ? AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$vendorId]);
$stats = $statsStmt->fetch();

// Ensure numeric values for display
$stats['total_revenue'] = (float)($stats['total_revenue'] ?? 0);
$stats['total_orders'] = (int)($stats['total_orders'] ?? 0);
$stats['pending_orders'] = (int)($stats['pending_orders'] ?? 0);
$stats['processing_orders'] = (int)($stats['processing_orders'] ?? 0);
$stats['shipped_orders'] = (int)($stats['shipped_orders'] ?? 0);
$stats['delivered_orders'] = (int)($stats['delivered_orders'] ?? 0);

$page_title = 'Order Management - Seller Center';
includeHeader($page_title);
?>

<div class="seller-orders-page">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <nav class="breadcrumb">
                    <a href="/seller/dashboard.php">Dashboard</a>
                    <span>/</span>
                    <span>Orders</span>
                </nav>
                <h1>Order Management</h1>
                <p class="subtitle">Track and manage your orders</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="exportOrders()">
                    📊 Export Data
                </button>
                <button class="btn btn-outline" onclick="printOrders()">
                    🖨️ Print Labels
                </button>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pending">📦</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon processing">⚙️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['processing_orders']; ?></div>
                <div class="stat-label">Processing</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon shipped">🚚</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['shipped_orders']; ?></div>
                <div class="stat-label">Shipped</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon delivered">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $stats['delivered_orders']; ?></div>
                <div class="stat-label">Delivered</div>
            </div>
        </div>
        <div class="stat-card revenue">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Revenue (30 days)</div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="search-group">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search orders, customers, products..." class="search-input">
                <button type="submit" class="search-btn">🔍</button>
            </div>
            
            <div class="filter-group">
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>

            <div class="filter-group">
                <select name="date_range" class="filter-select">
                    <option value="">All Time</option>
                    <option value="today" <?php echo $dateRange === 'today' ? 'selected' : ''; ?>>Today</option>
                    <option value="week" <?php echo $dateRange === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="month" <?php echo $dateRange === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="quarter" <?php echo $dateRange === 'quarter' ? 'selected' : ''; ?>>Last 3 Months</option>
                </select>
            </div>

            <div class="filter-group">
                <select name="sort" class="filter-select">
                    <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="order_number_asc" <?php echo $sort === 'order_number_asc' ? 'selected' : ''; ?>>Order # A-Z</option>
                    <option value="customer_name" <?php echo $sort === 'customer_name' ? 'selected' : ''; ?>>Customer Name</option>
                    <option value="amount_desc" <?php echo $sort === 'amount_desc' ? 'selected' : ''; ?>>Amount High-Low</option>
                    <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Status</option>
                </select>
            </div>

            <button type="submit" class="btn btn-outline">Apply Filters</button>
            <?php if (!empty($search) || !empty($status) || !empty($dateRange) || $sort !== 'created_at_desc'): ?>
                <a href="/seller/orders.php" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="orders-section">
        <?php if (!empty($orders)): ?>
            <div class="orders-header">
                <div class="orders-count">
                    Showing <?php echo count($orders); ?> of <?php echo $totalOrders; ?> orders
                </div>
                <div class="bulk-actions">
                    <select id="bulkAction" class="filter-select">
                        <option value="">Bulk Actions</option>
                        <option value="mark_processing">Mark as Processing</option>
                        <option value="mark_shipped">Mark as Shipped</option>
                        <option value="export_selected">Export Selected</option>
                    </select>
                    <button onclick="executeBulkAction()" class="btn btn-outline">Apply</button>
                </div>
            </div>

            <div class="orders-table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr class="order-row" data-order-id="<?php echo $order['order_id']; ?>" data-item-id="<?php echo $order['order_item_id']; ?>">
                                <td>
                                    <input type="checkbox" class="order-checkbox" value="<?php echo $order['order_item_id']; ?>">
                                </td>
                                <td>
                                    <div class="order-number">
                                        <a href="/seller/orders/view.php?id=<?php echo $order['order_id']; ?>" class="order-link">
                                            #<?php echo $order['order_number']; ?>
                                        </a>
                                    </div>
                                    <div class="payment-status">
                                        <span class="payment-badge payment-<?php echo strtolower($order['payment_status']); ?>">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-name">
                                            <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                        </div>
                                        <div class="customer-email"><?php echo htmlspecialchars($order['email']); ?></div>
                                        <?php if ($order['phone']): ?>
                                            <div class="customer-phone"><?php echo htmlspecialchars($order['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="product-info">
                                        <div class="product-name">
                                            <?php if ($order['product_exists']): ?>
                                                <a href="/seller/products/edit.php?id=<?php echo $order['product_id']; ?>">
                                                    <?php echo htmlspecialchars($order['product_name']); ?>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($order['product_name']); ?>
                                                <span class="product-deleted">(Deleted)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-details">
                                            <?php if ($order['sku']): ?>
                                                <span class="sku">SKU: <?php echo htmlspecialchars($order['sku']); ?></span>
                                            <?php endif; ?>
                                            <span class="quantity">Qty: <?php echo $order['qty']; ?></span>
                                            <span class="unit-price">@$<?php echo number_format((float)($order['price'] ?? 0), 2); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="amount-info">
                                        <div class="item-total">$<?php echo number_format((float)($order['subtotal'] ?? 0), 2); ?></div>
                                        <div class="order-total">Order: $<?php echo number_format((float)($order['order_total'] ?? 0), 2); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-info">
                                        <span class="status-badge status-<?php echo strtolower($order['item_status']); ?>">
                                            <?php echo ucfirst($order['item_status']); ?>
                                        </span>
                                        <?php if ($order['tracking_number']): ?>
                                            <div class="tracking-info">
                                                <span class="tracking-number">📦 <?php echo htmlspecialchars($order['tracking_number']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <div class="order-date"><?php echo formatDate($order['order_date']); ?></div>
                                        <div class="time-ago"><?php echo formatTimeAgo($order['order_date']); ?></div>
                                        <?php if ($order['shipped_at']): ?>
                                            <div class="shipped-date">Shipped: <?php echo formatDate($order['shipped_at']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="order-actions">
                                        <div class="actions-dropdown">
                                            <button class="actions-trigger" onclick="toggleActions(this)">⋮</button>
                                            <div class="actions-menu">
                                                <a href="/seller/orders/view.php?id=<?php echo $order['order_id']; ?>">👁️ View Details</a>
                                                <?php if (in_array($order['item_status'], ['pending', 'processing'])): ?>
                                                    <button onclick="updateOrderStatus(<?php echo $order['order_item_id']; ?>, 'processing', '<?php echo $order['item_status']; ?>')">
                                                        ⚙️ Mark Processing
                                                    </button>
                                                    <button onclick="showShippingModal(<?php echo $order['order_item_id']; ?>)">
                                                        🚚 Add Shipping
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($order['item_status'] === 'shipped'): ?>
                                                    <button onclick="updateOrderStatus(<?php echo $order['order_item_id']; ?>, 'delivered', '<?php echo $order['item_status']; ?>')">
                                                        ✅ Mark Delivered
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="showRefundModal(<?php echo $order['order_item_id']; ?>)">
                                                    💰 Process Refund
                                                </button>
                                                <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>?subject=Order #<?php echo $order['order_number']; ?>">
                                                    ✉️ Contact Customer
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $baseUrl = '/seller/orders.php?' . http_build_query($queryParams);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl; ?>&page=1" class="page-link">First</a>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $page - 1; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $i; ?>" 
                           class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $page + 1; ?>" class="page-link">Next →</a>
                        <a href="<?php echo $baseUrl; ?>&page=<?php echo $totalPages; ?>" class="page-link">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h2>No orders found</h2>
                <?php if (!empty($search) || !empty($status) || !empty($dateRange)): ?>
                    <p>No orders match your current filters.</p>
                    <a href="/seller/orders.php" class="btn btn-outline">Clear Filters</a>
                <?php else: ?>
                    <p>You haven't received any orders yet.</p>
                    <a href="/seller/products.php" class="btn btn-primary">Manage Products</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Shipping Modal -->
<div class="modal-overlay" id="shippingModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Shipping Information</h3>
            <button class="modal-close" onclick="closeShippingModal()">✕</button>
        </div>
        <form id="shippingForm" class="modal-content">
            <input type="hidden" id="shippingOrderItemId" name="order_item_id">
            <input type="hidden" name="action" value="add_tracking">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="carrier">Shipping Carrier</label>
                <select id="carrier" name="carrier" required>
                    <option value="">Select Carrier</option>
                    <option value="UPS">UPS</option>
                    <option value="FedEx">FedEx</option>
                    <option value="USPS">USPS</option>
                    <option value="DHL">DHL</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="trackingNumber">Tracking Number</label>
                <input type="text" id="trackingNumber" name="tracking_number" required>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closeShippingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Tracking Info</button>
            </div>
        </form>
    </div>
</div>

<style>
.seller-orders-page {
    max-width: 1600px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    color: white;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}

.breadcrumb {
    font-size: 14px;
    margin-bottom: 8px;
    opacity: 0.8;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.page-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
}

.subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    font-size: 24px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f8fafc;
}

.stat-icon.pending { background: #fef3c7; }
.stat-icon.processing { background: #dbeafe; }
.stat-icon.shipped { background: #e0f2fe; }
.stat-icon.delivered { background: #dcfce7; }

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
}

.filters-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.filters-form {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
}

.search-group {
    display: flex;
    flex: 1;
    min-width: 300px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px 0 0 8px;
    font-size: 14px;
}

.search-btn {
    padding: 12px 16px;
    background: #3b82f6;
    color: white;
    border: 1px solid #3b82f6;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
}

.filter-select {
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    min-width: 150px;
}

.orders-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow: hidden;
}

.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.orders-count {
    color: #6b7280;
    font-size: 14px;
}

.bulk-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.orders-table-container {
    overflow-x: auto;
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th {
    background: #f9fafb;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.orders-table td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.order-row:hover {
    background: #f9fafb;
}

.order-link {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
}

.order-link:hover {
    text-decoration: underline;
}

.payment-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 4px;
}

.payment-paid { background: #dcfce7; color: #166534; }
.payment-pending { background: #fef3c7; color: #92400e; }
.payment-failed { background: #fee2e2; color: #dc2626; }

.customer-info, .product-info, .amount-info, .status-info, .date-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.customer-name, .product-name {
    font-weight: 600;
    color: #1f2937;
}

.customer-email, .customer-phone, .product-details {
    font-size: 12px;
    color: #6b7280;
}

.product-details {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.product-deleted {
    color: #dc2626;
    font-style: italic;
}

.item-total {
    font-weight: 600;
    color: #1f2937;
}

.order-total {
    font-size: 12px;
    color: #6b7280;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fef3c7; color: #92400e; }
.status-processing { background: #dbeafe; color: #1e40af; }
.status-shipped { background: #e0f2fe; color: #0369a1; }
.status-delivered { background: #dcfce7; color: #166534; }
.status-cancelled { background: #fee2e2; color: #dc2626; }
.status-refunded { background: #f3e8ff; color: #7c3aed; }

.tracking-number {
    font-size: 11px;
    color: #6b7280;
    margin-top: 4px;
}

.order-date {
    font-weight: 600;
    color: #1f2937;
}

.time-ago, .shipped-date {
    font-size: 12px;
    color: #6b7280;
}

.actions-dropdown {
    position: relative;
}

.actions-trigger {
    padding: 4px 8px;
    background: none;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    color: #6b7280;
}

.actions-trigger:hover {
    background: #f3f4f6;
}

.actions-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 8px 0;
    min-width: 180px;
    z-index: 10;
    display: none;
}

.actions-menu a,
.actions-menu button {
    display: block;
    width: 100%;
    padding: 8px 16px;
    text-align: left;
    color: #374151;
    text-decoration: none;
    background: none;
    border: none;
    font-size: 14px;
    cursor: pointer;
}

.actions-menu a:hover,
.actions-menu button:hover {
    background: #f3f4f6;
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 24px;
}

.page-link {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    color: #374151;
    text-decoration: none;
    transition: all 0.2s ease;
}

.page-link:hover {
    background: #f3f4f6;
}

.page-link.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #6b7280;
    padding: 4px;
}

.modal-content {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

@media (max-width: 768px) {
    .seller-orders-page {
        padding: 16px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-group {
        min-width: auto;
    }
    
    .orders-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .orders-table {
        font-size: 12px;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 8px;
    }
}
</style>

<script>
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Actions dropdown toggle
function toggleActions(button) {
    const menu = button.nextElementSibling;
    
    // Close other open menus
    document.querySelectorAll('.actions-menu').forEach(m => {
        if (m !== menu) m.style.display = 'none';
    });
    
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.actions-dropdown')) {
        document.querySelectorAll('.actions-menu').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// Order status update
function updateOrderStatus(orderItemId, status, oldStatus) {
    const notes = prompt('Add notes (optional):');
    if (notes === null) return; // User cancelled
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="order_item_id" value="${orderItemId}">
        <input type="hidden" name="status" value="${status}">
        <input type="hidden" name="old_status" value="${oldStatus}">
        <input type="hidden" name="notes" value="${notes}">
        <?php echo csrfTokenInput(); ?>
    `;
    document.body.appendChild(form);
    form.submit();
}

// Shipping modal
function showShippingModal(orderItemId) {
    document.getElementById('shippingOrderItemId').value = orderItemId;
    document.getElementById('shippingModal').style.display = 'flex';
}

function closeShippingModal() {
    document.getElementById('shippingModal').style.display = 'none';
    document.getElementById('shippingForm').reset();
}

// Handle shipping form submission
document.getElementById('shippingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Create and submit form
    const form = document.createElement('form');
    form.method = 'POST';
    for (let [key, value] of formData) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
});

// Bulk actions
function executeBulkAction() {
    const action = document.getElementById('bulkAction').value;
    const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
    
    if (!action) {
        alert('Please select an action.');
        return;
    }
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one order.');
        return;
    }
    
    const orderItemIds = Array.from(checkedBoxes).map(box => box.value);
    
    switch (action) {
        case 'mark_processing':
        case 'mark_shipped':
            if (confirm(`Are you sure you want to ${action.replace('mark_', 'mark ')} ${orderItemIds.length} order(s)?`)) {
                // Implementation would go here
                console.log(`Bulk action: ${action}`, orderItemIds);
            }
            break;
        case 'export_selected':
            exportSelectedOrders(orderItemIds);
            break;
    }
}

// Export functions
function exportOrders() {
    window.open('/seller/orders/export.php', '_blank');
}

function exportSelectedOrders(orderItemIds) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/seller/orders/export.php';
    form.target = '_blank';
    
    orderItemIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'order_item_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function printOrders() {
    window.open('/seller/orders/print-labels.php', '_blank');
}

// Auto-submit filters on change
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function() {
        this.closest('form').submit();
    });
});
</script>

<?php includeFooter(); ?>