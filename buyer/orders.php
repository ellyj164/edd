<?php
/**
 * Buyer Orders Management
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
Session::requireLogin();

$db = db();
$userId = Session::getUserId();

// Get buyer ID
$buyerQuery = "SELECT id FROM buyers WHERE user_id = ?";
$buyerStmt = $db->prepare($buyerQuery);
$buyerStmt->execute([$userId]);
$buyer = $buyerStmt->fetch();
$buyerId = $buyer['id'] ?? $userId;

// Handle order actions
if ($_POST && Session::validateCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    
    if ($action === 'cancel_order' && $orderId > 0) {
        // Check if order can be cancelled
        $orderQuery = "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')";
        $orderStmt = $db->prepare($orderQuery);
        $orderStmt->execute([$orderId, $userId]);
        $order = $orderStmt->fetch();
        
        if ($order) {
            $updateQuery = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([$orderId]);
            
            $success = "Order #{$order['order_number']} has been cancelled successfully.";
        } else {
            $error = "Order cannot be cancelled or was not found.";
        }
    }
}

// Get orders with pagination
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$ordersQuery = "
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?
";
$ordersStmt = $db->prepare($ordersQuery);
$ordersStmt->execute([$userId, $limit, $offset]);
$orders = $ordersStmt->fetchAll();

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM orders WHERE user_id = ?";
$countStmt = $db->prepare($countQuery);
$countStmt->execute([$userId]);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

$page_title = 'My Orders';
includeHeader($page_title);
?>

<div class="buyer-dashboard">
    <div class="container-fluid">
        <div class="row">
            <!-- Include sidebar -->
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Orders</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Orders Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Order History</h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                                <td>
                                                    <div class="order-items">
                                                        <span class="badge badge-secondary"><?php echo $order['item_count']; ?> items</span>
                                                        <?php if ($order['product_names']): ?>
                                                            <div class="small text-muted mt-1">
                                                                <?php echo htmlspecialchars(substr($order['product_names'], 0, 50)); ?>
                                                                <?php if (strlen($order['product_names']) > 50): ?>...<?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong>$<?php echo number_format($order['total'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo getStatusBadgeClass($order['status']); ?>">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="/buyer/order-details.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        
                                                        <?php if (in_array($order['status'], ['delivered'])): ?>
                                                            <a href="/buyer/returns.php?order_id=<?php echo $order['id']; ?>" 
                                                               class="btn btn-outline-warning">
                                                                <i class="fas fa-undo"></i> Return
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger"
                                                                    onclick="cancelOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['order_number']); ?>')">
                                                                <i class="fas fa-times"></i> Cancel
                                                            </button>
                                                        <?php endif; ?>
                                                        
                                                        <a href="/order-invoice.php?id=<?php echo $order['id']; ?>" 
                                                           class="btn btn-outline-secondary" target="_blank">
                                                            <i class="fas fa-file-pdf"></i> Invoice
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Orders pagination">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-gray-300 mb-3"></i>
                                <h4>No Orders Yet</h4>
                                <p class="text-muted">Start shopping to see your orders here.</p>
                                <a href="/products.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-cart"></i> Browse Products
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Order Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <p>Are you sure you want to cancel order <strong id="cancelOrderNumber"></strong>?</p>
                    <p class="text-muted small">This action cannot be undone.</p>
                    <input type="hidden" name="action" value="cancel_order">
                    <input type="hidden" name="order_id" id="cancelOrderId">
                    <input type="hidden" name="csrf_token" value="<?php echo Session::generateCSRF(); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Order</button>
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.buyer-dashboard {
    background-color: #f8f9fc;
    min-height: 100vh;
}

.main-content {
    padding: 0 1.5rem;
}

.order-items {
    max-width: 200px;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
</style>

<script>
function cancelOrder(orderId, orderNumber) {
    document.getElementById('cancelOrderId').value = orderId;
    document.getElementById('cancelOrderNumber').textContent = '#' + orderNumber;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
    modal.show();
}
</script>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'processing': return 'secondary';
        case 'shipped': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        case 'refunded': return 'danger';
        default: return 'secondary';
    }
}

includeFooter();
?>