<?php
/**
 * Returns & Refunds Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Return merchandise authorization (RMA)
 * - Return request processing
 * - Refund processing and tracking
 * - Return reason management
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/audit_log.php';

// Initialize with graceful fallback
require_once __DIR__ . '/../../includes/init.php';

// Database graceful fallback
$database_available = false;
$pdo = null;
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

requireAdminAuth();
checkPermission('returns.view');
    require_once __DIR__ . '/../../includes/init.php';
    // Initialize PDO global variable for this module
    $pdo = db();
    requireAdminAuth();
    checkPermission('returns.view');

// Handle actions
$action = $_GET['action'] ?? 'list';
$return_id = $_GET['id'] ?? '';
$message = '';
$error = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            switch ($action) {
                case 'approve_return':
                    checkPermission('returns.manage');
                    $id = (int)$_POST['id'];
                    $admin_notes = sanitizeInput($_POST['admin_notes']);
                    
                    $pdo->beginTransaction();
                    
                    // Update return status
                    $stmt = $pdo->prepare("
                        UPDATE returns 
                        SET status = 'approved', admin_notes = ?, processed_by = ?, processed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin_notes, $_SESSION['admin_id'], $id]);
                    
                    // Get return details for inventory adjustment
                    $stmt = $pdo->prepare("
                        SELECT r.*, o.user_id, o.vendor_id
                        FROM returns r
                        JOIN orders o ON r.order_id = o.id
                        WHERE r.id = ?
                    ");
                    $stmt->execute([$id]);
                    $return_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($return_data) {
                        // Adjust inventory for returned items
                        $items = json_decode($return_data['items'], true);
                        foreach ($items as $item) {
                            // Add back to inventory (assuming main warehouse)
                            $stmt = $pdo->prepare("
                                INSERT INTO inventory (product_id, warehouse_id, qty)
                                VALUES (?, 1, ?)
                                ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
                            ");
                            $stmt->execute([$item['product_id'], $item['quantity']]);
                        }
                        
                        // Send approval notification email
                        if (function_exists('sendNotificationEmail')) {
                            sendNotificationEmail(
                                $return_data['user_id'],
                                'return_approved',
                                ['return_id' => $id, 'admin_notes' => $admin_notes]
                            );
                        }
                    }
                    
                    $pdo->commit();
                    
                    logAuditEvent('return', $id, 'approve', ['admin_notes' => $admin_notes]);
                    $message = 'Return approved successfully.';
                    break;
                    
                case 'reject_return':
                    checkPermission('returns.manage');
                    $id = (int)$_POST['id'];
                    $admin_notes = sanitizeInput($_POST['admin_notes']);
                    
                    $stmt = $pdo->prepare("
                        UPDATE returns 
                        SET status = 'rejected', admin_notes = ?, processed_by = ?, processed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin_notes, $_SESSION['admin_id'], $id]);
                    
                    // Get return details for notification
                    $stmt = $pdo->prepare("
                        SELECT r.*, o.user_id
                        FROM returns r
                        JOIN orders o ON r.order_id = o.id
                        WHERE r.id = ?
                    ");
                    $stmt->execute([$id]);
                    $return_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($return_data && function_exists('sendNotificationEmail')) {
                        sendNotificationEmail(
                            $return_data['user_id'],
                            'return_rejected',
                            ['return_id' => $id, 'admin_notes' => $admin_notes]
                        );
                    }
                    
                    logAuditEvent('return', $id, 'reject', ['admin_notes' => $admin_notes]);
                    $message = 'Return rejected successfully.';
                    break;
                    
                case 'process_refund':
                    checkPermission('refunds.process');
                    $return_id = (int)$_POST['return_id'];
                    $payment_id = (int)$_POST['payment_id'];
                    $amount = (float)$_POST['amount'];
                    $method = sanitizeInput($_POST['method']);
                    $reason = sanitizeInput($_POST['reason']);
                    
                    $pdo->beginTransaction();
                    
                    // Create refund record
                    $stmt = $pdo->prepare("
                        INSERT INTO refunds 
                        (payment_id, return_id, amount, reason, method, status, processed_by, processed_at)
                        VALUES (?, ?, ?, ?, ?, 'processing', ?, NOW())
                    ");
                    $stmt->execute([$payment_id, $return_id, $amount, $reason, $method, $_SESSION['admin_id']]);
                    
                    $refund_id = $pdo->lastInsertId();
                    
                    // Update return status
                    $stmt = $pdo->prepare("
                        UPDATE returns 
                        SET status = 'completed'
                        WHERE id = ?
                    ");
                    $stmt->execute([$return_id]);
                    
                    // Update payment status if full refund
                    $stmt = $pdo->prepare("SELECT amount FROM payments WHERE id = ?");
                    $stmt->execute([$payment_id]);
                    $payment_amount = $stmt->fetchColumn();
                    
                    if ($amount >= $payment_amount) {
                        $stmt = $pdo->prepare("
                            UPDATE payments 
                            SET status = 'refunded'
                            WHERE id = ?
                        ");
                        $stmt->execute([$payment_id]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE payments 
                            SET status = 'partially_refunded'
                            WHERE id = ?
                        ");
                        $stmt->execute([$payment_id]);
                    }
                    
                    $pdo->commit();
                    
                    logAuditEvent('refund', $refund_id, 'create', [
                        'return_id' => $return_id,
                        'amount' => $amount,
                        'method' => $method
                    ]);
                    
                    $message = 'Refund processed successfully.';
                    break;
                    
                case 'update_return_status':
                    checkPermission('returns.manage');
                    $id = (int)$_POST['id'];
                    $status = sanitizeInput($_POST['status']);
                    $tracking_number = sanitizeInput($_POST['tracking_number']);
                    $admin_notes = sanitizeInput($_POST['admin_notes']);
                    
                    $stmt = $pdo->prepare("
                        UPDATE returns 
                        SET status = ?, tracking_number = ?, admin_notes = ?, 
                            processed_by = ?, processed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$status, $tracking_number, $admin_notes, $_SESSION['admin_id'], $id]);
                    
                    logAuditEvent('return', $id, 'status_update', [
                        'new_status' => $status,
                        'tracking_number' => $tracking_number
                    ]);
                    
                    $message = 'Return status updated successfully.';
                    break;
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

// Get data for display
$returns = [];
$refunds = [];
$return_stats = [];
$filters = [
    'status' => $_GET['status'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// Initialize with graceful fallback
require_once __DIR__ . '/../../includes/init.php';

// Database graceful fallback
$database_available = false;
$pdo = null;
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

requireAdminAuth();
checkPermission('returns.view');
    // Build WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($filters['status'])) {
        $where_conditions[] = "r.status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['date_from'])) {
        $where_conditions[] = "DATE(r.created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $where_conditions[] = "DATE(r.created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Get returns with pagination
    $page = (int)($_GET['page'] ?? 1);
    $per_page = 25;
    $offset = ($page - 1) * $per_page;
    
    $stmt = $pdo->prepare("
        SELECT r.*, o.id as order_number, (u.first_name || ' ' || u.last_name) as customer_name, u.email as customer_email,
               (admin.first_name || ' ' || admin.last_name) as processed_by_name
        FROM returns r
        JOIN orders o ON r.order_id = o.id
        JOIN users u ON r.user_id = u.id
        LEFT JOIN users admin ON r.processed_by = admin.id
        {$where_clause}
        ORDER BY r.created_at DESC
        LIMIT {$per_page} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM returns r
        JOIN orders o ON r.order_id = o.id
        {$where_clause}
    ");
    $stmt->execute($params);
    $total_returns = $stmt->fetchColumn();
    $total_pages = ceil($total_returns / $per_page);
    
    // Get return statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_returns,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_returns,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_returns,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_returns,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_returns
        FROM returns
        WHERE date(created_at) >= date('now', '-30 days')
    ");
    $return_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent refunds
    $stmt = $pdo->query("
        SELECT rf.*, r.id as return_number, p.transaction_id, p.amount as payment_amount,
               admin.name as processed_by_name
        FROM refunds rf
        LEFT JOIN returns r ON rf.return_id = r.id
        JOIN payments p ON rf.payment_id = p.id
        LEFT JOIN users admin ON rf.processed_by = admin.id
        ORDER BY rf.created_at DESC
        LIMIT 20
    ");
    $refunds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

// Get selected return details if viewing
$selected_return = null;
if ($action === 'view' && $return_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, o.id as order_number, o.total as order_total,
                   u.name as customer_name, u.email as customer_email,
                   admin.name as processed_by_name,
                   p.id as payment_id, p.amount as payment_amount, p.transaction_id
            FROM returns r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON r.user_id = u.id
            LEFT JOIN users admin ON r.processed_by = admin.id
            LEFT JOIN payments p ON o.id = p.order_id AND p.status IN ('captured', 'authorized')
            WHERE r.id = ?
        ");
        $stmt->execute([$return_id]);
        $selected_return = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($selected_return) {
            $selected_return['items'] = json_decode($selected_return['items'], true);
        }
    } catch (Exception $e) {
        $error = 'Error loading return details: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Refunds Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; }
        .sidebar a { color: #bdc3c7; text-decoration: none; }
        .sidebar a:hover { color: #fff; background-color: #34495e; }
        .status-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        .return-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-3">
                <h4 class="text-white mb-4">Admin Panel</h4>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-undo"></i> Returns & Refunds
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../orders/index.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../payments/index.php">
                            <i class="fas fa-credit-card"></i> Payments
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php if ($action === 'view' && $selected_return): ?>
                <!-- Return Details View -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-undo text-primary"></i> Return #<?= $selected_return['id'] ?></h2>
                    <div class="btn-group">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                        <?php if (hasPermission('returns.manage') && $selected_return['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-success" onclick="approveReturn(<?= $selected_return['id'] ?>)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button type="button" class="btn btn-danger" onclick="rejectReturn(<?= $selected_return['id'] ?>)">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <?php endif; ?>
                        <?php if (hasPermission('refunds.process') && $selected_return['status'] === 'approved' && $selected_return['payment_id']): ?>
                        <button type="button" class="btn btn-warning" onclick="processRefund(<?= $selected_return['id'] ?>, <?= $selected_return['payment_id'] ?>)">
                            <i class="fas fa-money-bill-wave"></i> Process Refund
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Return Details</h5>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-3">Order:</dt>
                                    <dd class="col-sm-9">
                                        <a href="../orders/index.php?action=view&id=<?= $selected_return['order_id'] ?>">
                                            #<?= $selected_return['order_number'] ?>
                                        </a>
                                    </dd>
                                    
                                    <dt class="col-sm-3">Customer:</dt>
                                    <dd class="col-sm-9">
                                        <?= htmlspecialchars($selected_return['customer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($selected_return['customer_email']) ?></small>
                                    </dd>
                                    
                                    <dt class="col-sm-3">Return Method:</dt>
                                    <dd class="col-sm-9"><?= ucfirst($selected_return['return_method']) ?></dd>
                                    
                                    <dt class="col-sm-3">Status:</dt>
                                    <dd class="col-sm-9">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            'processing' => 'info',
                                            'completed' => 'primary',
                                            'cancelled' => 'secondary'
                                        ];
                                        $color = $status_colors[$selected_return['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($selected_return['status']) ?></span>
                                    </dd>
                                    
                                    <dt class="col-sm-3">Reason:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($selected_return['reason']) ?></dd>
                                    
                                    <?php if ($selected_return['tracking_number']): ?>
                                    <dt class="col-sm-3">Tracking:</dt>
                                    <dd class="col-sm-9"><code><?= htmlspecialchars($selected_return['tracking_number']) ?></code></dd>
                                    <?php endif; ?>
                                    
                                    <?php if ($selected_return['admin_notes']): ?>
                                    <dt class="col-sm-3">Admin Notes:</dt>
                                    <dd class="col-sm-9"><?= htmlspecialchars($selected_return['admin_notes']) ?></dd>
                                    <?php endif; ?>
                                    
                                    <dt class="col-sm-3">Requested:</dt>
                                    <dd class="col-sm-9"><?= date('M j, Y g:i A', strtotime($selected_return['created_at'])) ?></dd>
                                    
                                    <?php if ($selected_return['processed_at']): ?>
                                    <dt class="col-sm-3">Processed:</dt>
                                    <dd class="col-sm-9">
                                        <?= date('M j, Y g:i A', strtotime($selected_return['processed_at'])) ?>
                                        <?php if ($selected_return['processed_by_name']): ?>
                                        <br><small class="text-muted">by <?= htmlspecialchars($selected_return['processed_by_name']) ?></small>
                                        <?php endif; ?>
                                    </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>

                        <!-- Returned Items -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Returned Items</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($selected_return['items'] as $item): ?>
                                <div class="return-item">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong><?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']) ?></strong>
                                            <?php if (isset($item['variant_name'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($item['variant_name']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Qty:</strong> <?= $item['quantity'] ?>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Price:</strong> $<?= number_format($item['price'], 2) ?>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Total:</strong> $<?= number_format($item['quantity'] * $item['price'], 2) ?>
                                        </div>
                                    </div>
                                    <?php if (isset($item['reason'])): ?>
                                    <div class="mt-2">
                                        <small class="text-muted"><strong>Reason:</strong> <?= htmlspecialchars($item['reason']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <!-- Quick Actions -->
                        <?php if (hasPermission('returns.manage')): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="updateReturnStatus(<?= $selected_return['id'] ?>)">
                                    <i class="fas fa-edit"></i> Update Status
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="addTrackingNumber(<?= $selected_return['id'] ?>)">
                                    <i class="fas fa-truck"></i> Add Tracking
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php else: ?>
                <!-- Returns List View -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-undo text-primary"></i> Returns & Refunds Management</h2>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="exportReturns()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Return Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($return_stats['pending_returns']) ?></h4>
                                        <p class="mb-0">Pending Returns</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($return_stats['approved_returns']) ?></h4>
                                        <p class="mb-0">Approved Returns</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($return_stats['rejected_returns']) ?></h4>
                                        <p class="mb-0">Rejected Returns</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-times fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($return_stats['completed_returns']) ?></h4>
                                        <p class="mb-0">Completed Returns</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filters['date_from']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filters['date_to']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2 d-md-block">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="index.php" class="btn btn-secondary">Clear</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Returns Table -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Return Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($returns as $return): ?>
                                    <tr>
                                        <td>#<?= $return['id'] ?></td>
                                        <td>
                                            <a href="../orders/index.php?action=view&id=<?= $return['order_id'] ?>">
                                                #<?= $return['order_number'] ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($return['customer_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($return['customer_email']) ?></small>
                                        </td>
                                        <td><?= ucfirst($return['return_method']) ?></td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'processing' => 'info',
                                                'completed' => 'primary',
                                                'cancelled' => 'secondary'
                                            ];
                                            $color = $status_colors[$return['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= ucfirst($return['status']) ?></span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($return['created_at'])) ?></td>
                                        <td>
                                            <a href="?action=view&id=<?= $return['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (hasPermission('returns.manage') && $return['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-outline-success" onclick="approveReturn(<?= $return['id'] ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="rejectReturn(<?= $return['id'] ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Returns pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>"><?= $i ?></a>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Refunds -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Refunds</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Refund ID</th>
                                        <th>Return</th>
                                        <th>Payment</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Processed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($refunds as $refund): ?>
                                    <tr>
                                        <td>#<?= $refund['id'] ?></td>
                                        <td>
                                            <?php if ($refund['return_number']): ?>
                                            <a href="?action=view&id=<?= $refund['return_id'] ?>">#<?= $refund['return_number'] ?></a>
                                            <?php else: ?>
                                            <span class="text-muted">Direct Refund</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= htmlspecialchars($refund['transaction_id']) ?></code></td>
                                        <td>$<?= number_format($refund['amount'], 2) ?></td>
                                        <td><?= ucfirst(str_replace('_', ' ', $refund['method'])) ?></td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                'cancelled' => 'secondary'
                                            ];
                                            $color = $status_colors[$refund['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= ucfirst($refund['status']) ?></span>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($refund['processed_at'])) ?>
                                            <?php if ($refund['processed_by_name']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($refund['processed_by_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action Modals -->
    
    <!-- Approve Return Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=approve_return">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Return</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" id="approve_return_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Admin Notes</label>
                            <textarea name="admin_notes" class="form-control" rows="3" placeholder="Optional notes for approval..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Return Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=reject_return">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Return</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" id="reject_return_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason *</label>
                            <textarea name="admin_notes" class="form-control" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Process Refund Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=process_refund">
                    <div class="modal-header">
                        <h5 class="modal-title">Process Refund</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="return_id" id="refund_return_id">
                        <input type="hidden" name="payment_id" id="refund_payment_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Refund Amount *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Refund Method *</label>
                            <select name="method" class="form-select" required>
                                <option value="original_payment">Original Payment Method</option>
                                <option value="store_credit">Store Credit</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="2" placeholder="Refund reason..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Process Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveReturn(returnId) {
            document.getElementById('approve_return_id').value = returnId;
            var modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        }
        
        function rejectReturn(returnId) {
            document.getElementById('reject_return_id').value = returnId;
            var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }
        
        function processRefund(returnId, paymentId) {
            document.getElementById('refund_return_id').value = returnId;
            document.getElementById('refund_payment_id').value = paymentId;
            var modal = new bootstrap.Modal(document.getElementById('refundModal'));
            modal.show();
        }
        
        function updateReturnStatus(returnId) {
            // Implementation for updating return status
            alert('Update return status functionality to be implemented');
        }
        
        function addTrackingNumber(returnId) {
            // Implementation for adding tracking number
            alert('Add tracking number functionality to be implemented');
        }
        
        function exportReturns() {
            window.location.href = '?export=1';
        }
    </script>
</body>
</html>