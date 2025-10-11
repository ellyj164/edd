<?php
/**
 * Payout Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Vendor payout requests management
 * - Payment processing and approval
 * - Payout history and tracking
 * - Automated payout scheduling
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
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
checkPermission('payouts.view');

// Handle actions
$action = $_GET['action'] ?? 'list';
$payout_id = $_GET['id'] ?? '';
$message = '';
$error = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            switch ($action) {
                case 'approve':
                    checkPermission('payouts.approve');
                    if (!$database_available) {
                        throw new Exception('Database connection required for this operation.');
                    }
                    $id = (int)$_POST['id'];
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    try {
                        // Update payout status
                        $stmt = $pdo->prepare("UPDATE seller_payouts SET status = 'processing', processed_by = ?, processed_at = NOW() WHERE id = ? AND status = 'requested'");
                        $stmt->execute([$_SESSION['user_id'], $id]);
                        
                        if ($stmt->rowCount() === 0) {
                            throw new Exception('Payout not found or already processed.');
                        }
                        
                        // Log the action
                        logAuditEvent('payout', $id, 'approve');
                        
                        $pdo->commit();
                        $message = 'Payout request approved and moved to processing.';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
                    
                case 'complete':
                    checkPermission('payouts.approve');
                    if (!$database_available) {
                        throw new Exception('Database connection required for this operation.');
                    }
                    $id = (int)$_POST['id'];
                    $transaction_id = sanitizeInput($_POST['transaction_id'] ?? '');
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    try {
                        // Get payout details
                        $stmt = $pdo->prepare("SELECT * FROM seller_payouts WHERE id = ?");
                        $stmt->execute([$id]);
                        $payout = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$payout) {
                            throw new Exception('Payout not found.');
                        }
                        
                        // Update payout status
                        $stmt = $pdo->prepare("
                            UPDATE seller_payouts 
                            SET status = 'completed', 
                                external_transaction_id = ?,
                                admin_notes = ?,
                                completed_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$transaction_id, $notes, $id]);
                        
                        // Update seller wallet
                        $stmt = $pdo->prepare("
                            UPDATE seller_wallets 
                            SET pending_balance = pending_balance - ?,
                                total_withdrawn = total_withdrawn + ?
                            WHERE vendor_id = ?
                        ");
                        $stmt->execute([$payout['request_amount'], $payout['final_amount'], $payout['vendor_id']]);
                        
                        // Record in transaction ledger
                        $stmt = $pdo->prepare("
                            INSERT INTO wallet_transactions 
                            (wallet_id, wallet_type, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, description, created_by)
                            SELECT 
                                sw.id,
                                'seller',
                                'debit',
                                ?,
                                sw.balance,
                                sw.balance,
                                'payout',
                                ?,
                                CONCAT('Payout completed - ', ?),
                                ?
                            FROM seller_wallets sw
                            WHERE sw.vendor_id = ?
                        ");
                        $stmt->execute([
                            $payout['final_amount'],
                            $id,
                            $payout['reference_number'],
                            $_SESSION['user_id'],
                            $payout['vendor_id']
                        ]);
                        
                        logAuditEvent('payout', $id, 'complete', ['transaction_id' => $transaction_id]);
                        
                        $pdo->commit();
                        $message = 'Payout completed successfully.';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
                    
                case 'reject':
                    checkPermission('payouts.approve');
                    if (!$database_available) {
                        throw new Exception('Database connection required for this operation.');
                    }
                    $id = (int)$_POST['id'];
                    $reason = sanitizeInput($_POST['reason']);
                    
                    // Start transaction
                    $pdo->beginTransaction();
                    try {
                        // Get payout details
                        $stmt = $pdo->prepare("SELECT * FROM seller_payouts WHERE id = ?");
                        $stmt->execute([$id]);
                        $payout = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$payout) {
                            throw new Exception('Payout not found.');
                        }
                        
                        // Update payout status
                        $stmt = $pdo->prepare("
                            UPDATE seller_payouts 
                            SET status = 'cancelled', 
                                failure_reason = ?,
                                processed_by = ?,
                                processed_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$reason, $_SESSION['user_id'], $id]);
                        
                        // Return funds to seller wallet
                        $stmt = $pdo->prepare("
                            UPDATE seller_wallets 
                            SET balance = balance + ?,
                                pending_balance = pending_balance - ?
                            WHERE vendor_id = ?
                        ");
                        $stmt->execute([$payout['request_amount'], $payout['request_amount'], $payout['vendor_id']]);
                        
                        logAuditEvent('payout', $id, 'reject', ['reason' => $reason]);
                        
                        $pdo->commit();
                        $message = 'Payout request rejected and funds returned to seller.';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data for display
$payouts = [];
$payout_stats = ['total_requests' => 0, 'pending_requests' => 0, 'approved_amount' => 0, 'total_amount' => 0];

if (!$database_available) {
    $error = 'Database connection required. Please check your database configuration.';
} else {
    try {
        // Get payout requests
        $stmt = $pdo->query("
            SELECT 
                p.*,
                v.business_name as vendor_name,
                u.email as vendor_email,
                u2.email as processed_by_email
            FROM seller_payouts p
            LEFT JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN users u ON v.user_id = u.id
            LEFT JOIN users u2 ON p.processed_by = u2.id
            ORDER BY p.requested_at DESC
            LIMIT 100
        ");
        $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'requested' THEN 1 END) as pending_requests,
                SUM(CASE WHEN status = 'completed' THEN final_amount ELSE 0 END) as approved_amount,
                SUM(request_amount) as total_amount
            FROM seller_payouts
        ");
        $payout_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $error = 'Error loading payout data: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; }
        .sidebar a { color: #bdc3c7; text-decoration: none; }
        .sidebar a:hover { color: #fff; background-color: #34495e; }
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
                            <i class="fas fa-money-bill-wave"></i> Payouts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../finance/index.php">
                            <i class="fas fa-chart-line"></i> Finance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../vendors/index.php">
                            <i class="fas fa-store"></i> Vendors
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development. 
                    To disable, set ADMIN_BYPASS=false in your .env file.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-money-bill-wave text-success"></i> Payout Management</h2>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="exportPayouts()">
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

                <!-- Payout Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($payout_stats['total_requests'] ?? 0) ?></h4>
                                        <p class="mb-0">Total Requests</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-invoice-dollar fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format($payout_stats['pending_requests'] ?? 0) ?></h4>
                                        <p class="mb-0">Pending</p>
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
                                        <h4>$<?= number_format($payout_stats['approved_amount'] ?? 0, 2) ?></h4>
                                        <p class="mb-0">Approved Amount</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4>$<?= number_format($payout_stats['total_amount'] ?? 0, 2) ?></h4>
                                        <p class="mb-0">Total Amount</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-money-bill-wave fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payout Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payout Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Vendor</th>
                                        <th>Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payouts as $payout): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($payout['vendor_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($payout['vendor_email']) ?></small>
                                        </td>
                                        <td>
                                            <strong>$<?= number_format($payout['amount'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $method_icons = [
                                                'bank_transfer' => 'fa-university',
                                                'paypal' => 'fa-paypal',
                                                'stripe' => 'fa-stripe'
                                            ];
                                            $icon = $method_icons[$payout['payment_method']] ?? 'fa-credit-card';
                                            ?>
                                            <i class="fab <?= $icon ?>"></i> <?= ucfirst(str_replace('_', ' ', $payout['payment_method'])) ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_colors = [
                                                'pending' => 'warning',
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                'processed' => 'info'
                                            ];
                                            $color = $status_colors[$payout['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= ucfirst($payout['status']) ?></span>
                                        </td>
                                        <td>
                                            <?= date('M j, Y', strtotime($payout['created_at'])) ?>
                                            <br><small class="text-muted"><?= date('g:i A', strtotime($payout['created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($payout['status'] === 'pending' && hasPermission('payouts.approve')): ?>
                                            <button class="btn btn-sm btn-success" onclick="approvePayout(<?= $payout['id'] ?>, '<?= htmlspecialchars($payout['vendor_name']) ?>')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectPayout(<?= $payout['id'] ?>, '<?= htmlspecialchars($payout['vendor_name']) ?>')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="viewPayout(<?= $payout['id'] ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Payout Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=approve">
                    <div class="modal-header">
                        <h5 class="modal-title">Approve Payout</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" id="approve_payout_id">
                        
                        <p>Are you sure you want to approve the payout for <strong id="approve_vendor_name"></strong>?</p>
                        <p class="text-muted">This will mark the payout as approved and ready for processing.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Approve Payout</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Payout Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=reject">
                    <div class="modal-header">
                        <h5 class="modal-title">Reject Payout</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="id" id="reject_payout_id">
                        
                        <p>Reject payout for <strong id="reject_vendor_name"></strong>?</p>
                        
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason *</label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Payout</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approvePayout(payoutId, vendorName) {
            document.getElementById('approve_payout_id').value = payoutId;
            document.getElementById('approve_vendor_name').textContent = vendorName;
            
            var modal = new bootstrap.Modal(document.getElementById('approveModal'));
            modal.show();
        }
        
        function rejectPayout(payoutId, vendorName) {
            document.getElementById('reject_payout_id').value = payoutId;
            document.getElementById('reject_vendor_name').textContent = vendorName;
            
            var modal = new bootstrap.Modal(document.getElementById('rejectModal'));
            modal.show();
        }
        
        function viewPayout(payoutId) {
            // Implementation for viewing payout details
            alert('View payout functionality to be implemented');
        }
        
        function exportPayouts() {
            window.location.href = '?export=1';
        }
    </script>
</body>
</html>