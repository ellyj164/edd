<?php
/**
 * Order Management - Admin Module
 * Comprehensive Order & Transaction Management System
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();
RoleMiddleware::requireAdmin();

$page_title = 'Order Management';
$action = $_GET['action'] ?? 'list';
$order_id = $_GET['id'] ?? null;

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        $order = new Order();
        
        switch ($_POST['action']) {
            case 'update_status':
                $orderId = $_POST['order_id'];
                $newStatus = sanitizeInput($_POST['status']);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Update order status
                $updated = $order->update($orderId, ['status' => $newStatus]);
                
                if ($updated) {
                    // Add to status history
                    Database::query(
                        "INSERT INTO order_status_history (order_id, status, notes, updated_by, customer_notified) 
                         VALUES (?, ?, ?, ?, ?)",
                        [$orderId, $newStatus, $notes, Session::getUserId(), 1]
                    );
                    
                    $_SESSION['success_message'] = 'Order status updated successfully.';
                    logAdminActivity(Session::getUserId(), 'order_status_updated', 'order', $orderId, null, [
                        'new_status' => $newStatus,
                        'notes' => $notes
                    ]);
                } else {
                    throw new Exception('Failed to update order status.');
                }
                break;
                
            case 'add_tracking':
                $orderId = $_POST['order_id'];
                $trackingNumber = sanitizeInput($_POST['tracking_number']);
                $carrier = sanitizeInput($_POST['carrier']);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Add tracking information
                Database::query(
                    "INSERT INTO shipments (order_id, tracking_number, carrier_id, status, notes) 
                     VALUES (?, ?, ?, 'shipped', ?)",
                    [$orderId, $trackingNumber, $carrier, $notes]
                );
                
                // Update order status to shipped
                $order->update($orderId, ['status' => 'shipped']);
                
                $_SESSION['success_message'] = 'Tracking information added successfully.';
                logAdminActivity(Session::getUserId(), 'order_tracking_added', 'order', $orderId);
                break;
                
            case 'process_refund':
                $orderId = $_POST['order_id'];
                $refundAmount = floatval($_POST['refund_amount']);
                $refundReason = sanitizeInput($_POST['refund_reason']);
                $refundMethod = sanitizeInput($_POST['refund_method']);
                
                // Create refund record
                Database::query(
                    "INSERT INTO refunds (order_id, user_id, refund_amount, refund_reason, status, refund_method, processed_by) 
                     VALUES (?, (SELECT user_id FROM orders WHERE id = ?), ?, ?, 'approved', ?, ?)",
                    [$orderId, $orderId, $refundAmount, $refundReason, $refundMethod, Session::getUserId()]
                );
                
                $_SESSION['success_message'] = 'Refund processed successfully.';
                logAdminActivity(Session::getUserId(), 'refund_processed', 'order', $orderId);
                break;
                
            case 'bulk_action':
                $orderIds = $_POST['order_ids'] ?? [];
                $bulkAction = $_POST['bulk_action_type'] ?? '';
                
                if (empty($orderIds) || empty($bulkAction)) {
                    throw new Exception('Please select orders and an action.');
                }
                
                $count = 0;
                foreach ($orderIds as $id) {
                    switch ($bulkAction) {
                        case 'mark_processing':
                            if ($order->update($id, ['status' => 'processing'])) $count++;
                            break;
                        case 'mark_shipped':
                            if ($order->update($id, ['status' => 'shipped'])) $count++;
                            break;
                        case 'mark_completed':
                            if ($order->update($id, ['status' => 'completed'])) $count++;
                            break;
                        case 'cancel':
                            if ($order->update($id, ['status' => 'cancelled'])) $count++;
                            break;
                    }
                }
                
                $_SESSION['success_message'] = "Bulk action applied to $count orders.";
                logAdminActivity(Session::getUserId(), 'orders_bulk_action', 'order', null, null, [
                    'action' => $bulkAction,
                    'order_ids' => $orderIds,
                    'count' => $count
                ]);
                break;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        Logger::error("Order management error: " . $e->getMessage());
    }
    
    header('Location: /admin/orders/');
    exit;
}

// Get order data for view/edit
$currentOrder = null;
$orderItems = [];
$orderHistory = [];
if ($action === 'view' && $order_id) {
    try {
        $currentOrder = Database::query(
            "SELECT o.*, u.username, u.email, u.first_name, u.last_name 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             WHERE o.id = ?",
            [$order_id]
        )->fetch();
        
        if (!$currentOrder) {
            $_SESSION['error_message'] = 'Order not found.';
            header('Location: /admin/orders/');
            exit;
        }
        
        // Get order items
        $orderItems = Database::query(
            "SELECT oi.*, p.name as product_name, p.sku 
             FROM order_items oi 
             LEFT JOIN products p ON oi.product_id = p.id 
             WHERE oi.order_id = ?",
            [$order_id]
        )->fetchAll();
        
        // Get status history
        $orderHistory = Database::query(
            "SELECT osh.*, u.username as updated_by_name 
             FROM order_status_history osh 
             LEFT JOIN users u ON osh.updated_by = u.id 
             WHERE osh.order_id = ? 
             ORDER BY osh.created_at DESC",
            [$order_id]
        )->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error fetching order details: " . $e->getMessage());
    }
}

// Get orders list with filtering and pagination
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$whereConditions = [];
$params = [];

// Apply filters
if ($filter !== 'all') {
    $whereConditions[] = "o.status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $whereConditions[] = "(o.id LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR o.billing_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($date_from)) {
    $whereConditions[] = "o.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $whereConditions[] = "o.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

try {
    $orders = Database::query(
        "SELECT o.*, u.username, u.email 
         FROM orders o 
         LEFT JOIN users u ON o.user_id = u.id 
         $whereClause 
         ORDER BY o.created_at DESC 
         LIMIT $limit OFFSET $offset",
        $params
    )->fetchAll();
    
    $totalOrders = Database::query(
        "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause",
        $params
    )->fetchColumn();
    
    $totalPages = ceil($totalOrders / $limit);
} catch (Exception $e) {
    $orders = [];
    $totalOrders = 0;
    $totalPages = 0;
    error_log("Error fetching orders: " . $e->getMessage());
}

// Order statistics
try {
    $order = new Order();
    $stats = [
        'total' => $order->count(),
        'pending' => $order->count("status = 'pending'"),
        'processing' => $order->count("status = 'processing'"),
        'shipped' => $order->count("status = 'shipped'"),
        'completed' => $order->count("status = 'completed'"),
        'cancelled' => $order->count("status = 'cancelled'"),
        'refunded' => $order->count("status = 'refunded'"),
        'today_revenue' => Database::query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE() AND status NOT IN ('cancelled', 'refunded')"
        )->fetchColumn(),
        'total_revenue' => Database::query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status NOT IN ('cancelled', 'refunded')"
        )->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = [
        'total' => 0, 'pending' => 0, 'processing' => 0, 'shipped' => 0,
        'completed' => 0, 'cancelled' => 0, 'refunded' => 0,
        'today_revenue' => 0, 'total_revenue' => 0
    ];
}

// Get carriers for tracking
try {
    $carriers = Database::query("SELECT id, name FROM shipping_carriers WHERE is_active = 1")->fetchAll();
} catch (Exception $e) {
    $carriers = [];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 0;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .stats-card.success { border-left-color: #27ae60; }
        .stats-card.warning { border-left-color: #f39c12; }
        .stats-card.danger { border-left-color: #e74c3c; }
        .stats-card.info { border-left-color: #17a2b8; }
        .order-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.75rem;
            top: 0.25rem;
            width: 0.75rem;
            height: 0.75rem;
            background: #6c757d;
            border-radius: 50%;
            border: 2px solid white;
        }
        .timeline-item.success::before { background: #28a745; }
        .timeline-item.warning::before { background: #ffc107; }
        .timeline-item.danger::before { background: #dc3545; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <small class="text-white-50">Manage orders, payments, and fulfillment</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <!-- Order Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['total']); ?></div>
                    <div class="text-muted small">Total Orders</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card warning">
                    <div class="h4 mb-1 text-warning"><?php echo number_format($stats['pending']); ?></div>
                    <div class="text-muted small">Pending</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card info">
                    <div class="h4 mb-1 text-info"><?php echo number_format($stats['processing']); ?></div>
                    <div class="text-muted small">Processing</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['shipped']); ?></div>
                    <div class="text-muted small">Shipped</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card success">
                    <div class="h4 mb-1 text-success"><?php echo number_format($stats['completed']); ?></div>
                    <div class="text-muted small">Completed</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card success">
                    <div class="h4 mb-1 text-success">$<?php echo number_format($stats['today_revenue'], 2); ?></div>
                    <div class="text-muted small">Today's Revenue</div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                            <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search orders..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100" onclick="toggleBulkActions()">
                            <i class="fas fa-tasks me-1"></i> Bulk Actions
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Orders (<?php echo number_format($totalOrders); ?> total)</h5>
            </div>
            <div class="card-body p-0">
                <form method="POST" id="bulkForm">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="bulk_action">
                    
                    <div id="bulkActionsBar" class="bg-light p-3 border-bottom" style="display: none;">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <select class="form-select" name="bulk_action_type" required>
                                    <option value="">Select Action</option>
                                    <option value="mark_processing">Mark as Processing</option>
                                    <option value="mark_shipped">Mark as Shipped</option>
                                    <option value="mark_completed">Mark as Completed</option>
                                    <option value="cancel">Cancel Orders</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-warning" 
                                        onclick="return confirm('Apply bulk action to selected orders?')">
                                    Apply to Selected
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="toggleBulkActions()">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" class="form-check-input" id="selectAll" onchange="toggleAllOrders()">
                                    </th>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                        <div class="h5 text-muted">No orders found</div>
                                        <p class="text-muted">Try adjusting your search or filter criteria.</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($orders as $orderData): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input order-checkbox" 
                                               name="order_ids[]" value="<?php echo $orderData['id']; ?>">
                                    </td>
                                    <td>
                                        <strong>#<?php echo $orderData['id']; ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($orderData['username'] ?? 'Guest'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($orderData['email'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y H:i', strtotime($orderData['created_at'])); ?>
                                    </td>
                                    <td>
                                        <strong>$<?php echo number_format($orderData['total_amount'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'shipped' => 'primary',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'refunded' => 'secondary'
                                        ][$orderData['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?> order-status">
                                            <?php echo ucfirst($orderData['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $paymentClass = [
                                            'paid' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            'refunded' => 'info'
                                        ][$orderData['payment_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $paymentClass; ?> order-status">
                                            <?php echo ucfirst($orderData['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?action=view&id=<?php echo $orderData['id']; ?>" 
                                               class="btn btn-sm btn-outline-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    title="Update Status" 
                                                    onclick="updateStatus(<?php echo $orderData['id']; ?>, '<?php echo $orderData['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($orderData['status'] === 'processing'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    title="Add Tracking" 
                                                    onclick="addTracking(<?php echo $orderData['id']; ?>)">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Orders pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($action === 'view' && $currentOrder): ?>
        <!-- Order Details View -->
        <div class="row">
            <div class="col-md-8">
                <!-- Order Information -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order #<?php echo $currentOrder['id']; ?></h5>
                        <div>
                            <?php
                            $statusClass = [
                                'pending' => 'warning',
                                'processing' => 'info',
                                'shipped' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'refunded' => 'secondary'
                            ][$currentOrder['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusClass; ?> me-2">
                                <?php echo ucfirst($currentOrder['status']); ?>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="updateStatus(<?php echo $currentOrder['id']; ?>, '<?php echo $currentOrder['status']; ?>')">
                                Update Status
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Customer Information</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Name:</strong></td>
                                        <td><?php echo htmlspecialchars(($currentOrder['first_name'] ?? '') . ' ' . ($currentOrder['last_name'] ?? '')); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($currentOrder['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Order Date:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($currentOrder['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6>Order Summary</h6>
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Subtotal:</strong></td>
                                        <td>$<?php echo number_format($currentOrder['subtotal_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tax:</strong></td>
                                        <td>$<?php echo number_format($currentOrder['tax_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Shipping:</strong></td>
                                        <td>$<?php echo number_format($currentOrder['shipping_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total:</strong></td>
                                        <td><strong>$<?php echo number_format($currentOrder['total_amount'], 2); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Order Items</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($item['sku']); ?></code>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($currentOrder['status'] === 'processing'): ?>
                            <button type="button" class="btn btn-success" 
                                    onclick="addTracking(<?php echo $currentOrder['id']; ?>)">
                                <i class="fas fa-truck me-1"></i> Add Tracking
                            </button>
                            <?php endif; ?>
                            
                            <button type="button" class="btn btn-outline-warning" 
                                    onclick="processRefund(<?php echo $currentOrder['id']; ?>, <?php echo $currentOrder['total_amount']; ?>)">
                                <i class="fas fa-undo me-1"></i> Process Refund
                            </button>
                            
                            <a href="/admin/customers/?id=<?php echo $currentOrder['user_id']; ?>" class="btn btn-outline-info">
                                <i class="fas fa-user me-1"></i> View Customer
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order History -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Order History</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($orderHistory as $history): ?>
                            <div class="timeline-item">
                                <div class="fw-bold"><?php echo ucfirst($history['status']); ?></div>
                                <small class="text-muted">
                                    <?php echo date('M d, Y H:i', strtotime($history['created_at'])); ?>
                                    <?php if ($history['updated_by_name']): ?>
                                    by <?php echo htmlspecialchars($history['updated_by_name']); ?>
                                    <?php endif; ?>
                                </small>
                                <?php if ($history['notes']): ?>
                                <div class="text-muted mt-1"><?php echo htmlspecialchars($history['notes']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Tracking Modal -->
    <div class="modal fade" id="trackingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Tracking Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="add_tracking">
                    <input type="hidden" name="order_id" id="trackingOrderId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="tracking_number" class="form-label">Tracking Number</label>
                            <input type="text" class="form-control" id="tracking_number" name="tracking_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="carrier" class="form-label">Carrier</label>
                            <select class="form-select" id="carrier" name="carrier" required>
                                <option value="">Select Carrier</option>
                                <?php foreach ($carriers as $carrier): ?>
                                <option value="<?php echo $carrier['id']; ?>"><?php echo htmlspecialchars($carrier['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="tracking_notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="tracking_notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Tracking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Refund</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo csrfTokenInput(); ?>
                    <input type="hidden" name="action" value="process_refund">
                    <input type="hidden" name="order_id" id="refundOrderId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="refund_amount" class="form-label">Refund Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="refund_amount" name="refund_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="refund_reason" class="form-label">Refund Reason</label>
                            <textarea class="form-control" id="refund_reason" name="refund_reason" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="refund_method" class="form-label">Refund Method</label>
                            <select class="form-select" id="refund_method" name="refund_method" required>
                                <option value="original_payment">Original Payment Method</option>
                                <option value="store_credit">Store Credit</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning" 
                                onclick="return confirm('Process this refund? This action cannot be undone.')">
                            Process Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleBulkActions() {
            const bar = document.getElementById('bulkActionsBar');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            
            if (bar.style.display === 'none') {
                bar.style.display = 'block';
            } else {
                bar.style.display = 'none';
                document.getElementById('selectAll').checked = false;
                checkboxes.forEach(cb => cb.checked = false);
            }
        }
        
        function toggleAllOrders() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }
        
        function updateStatus(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }
        
        function addTracking(orderId) {
            document.getElementById('trackingOrderId').value = orderId;
            new bootstrap.Modal(document.getElementById('trackingModal')).show();
        }
        
        function processRefund(orderId, totalAmount) {
            document.getElementById('refundOrderId').value = orderId;
            document.getElementById('refund_amount').value = totalAmount;
            new bootstrap.Modal(document.getElementById('refundModal')).show();
        }
    </script>
</body>
</html>