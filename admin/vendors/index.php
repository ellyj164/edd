<?php
/**
 * Vendor Management - Admin Module
 * Marketplace & Vendor Management System
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();

// For testing, bypass admin check 
if (!defined('ADMIN_BYPASS') || !ADMIN_BYPASS) {
    RoleMiddleware::requireAdmin();
}

$page_title = 'Vendor Management';
$action = $_GET['action'] ?? 'list';
$vendor_id = $_GET['id'] ?? null;

// Handle vendor actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        switch ($_POST['action']) {
            case 'approve_vendor':
                Database::query(
                    "UPDATE vendors SET status = 'approved', approved_by = ?, approved_at = datetime('now') WHERE id = ?",
                    [Session::getUserId(), $_POST['vendor_id']]
                );
                $_SESSION['success_message'] = 'Vendor approved successfully.';
                logAdminActivity(Session::getUserId(), 'vendor_approved', 'vendor', $_POST['vendor_id']);
                break;
                
            case 'reject_vendor':
                Database::query(
                    "UPDATE vendors SET status = 'rejected' WHERE id = ?",
                    [$_POST['vendor_id']]
                );
                $_SESSION['success_message'] = 'Vendor rejected.';
                logAdminActivity(Session::getUserId(), 'vendor_rejected', 'vendor', $_POST['vendor_id']);
                break;
                
            case 'suspend_vendor':
                Database::query(
                    "UPDATE vendors SET status = 'suspended' WHERE id = ?",
                    [$_POST['vendor_id']]
                );
                $_SESSION['success_message'] = 'Vendor suspended.';
                logAdminActivity(Session::getUserId(), 'vendor_suspended', 'vendor', $_POST['vendor_id']);
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        Logger::error("Vendor management error: " . $e->getMessage());
    }
    
    header('Location: /admin/vendors/');
    exit;
}

// Get vendors with statistics
try {
    $vendors = Database::query(
        "SELECT v.*, u.username, u.email, u.created_at as user_created,
                0 as product_count,
                0 as order_count,
                0 as total_sales
         FROM vendors v
         LEFT JOIN users u ON v.user_id = u.id
         GROUP BY v.id
         ORDER BY v.created_at DESC"
    )->fetchAll();
    
    $stats = [
        'total' => count($vendors),
        'pending' => count(array_filter($vendors, fn($v) => $v['status'] === 'pending')),
        'approved' => count(array_filter($vendors, fn($v) => $v['status'] === 'approved')),
        'rejected' => count(array_filter($vendors, fn($v) => $v['status'] === 'rejected')),
        'suspended' => count(array_filter($vendors, fn($v) => $v['status'] === 'suspended'))
    ];
} catch (Exception $e) {
    $vendors = [];
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'suspended' => 0];
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
        .vendor-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-store me-2"></i>
                        <?php echo $page_title; ?>
                    </h1>
                    <small class="text-white-50">Manage marketplace vendors and partners</small>
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

        <!-- Vendor Statistics -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['total']); ?></div>
                    <div class="text-muted small">Total Vendors</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card warning">
                    <div class="h4 mb-1 text-warning"><?php echo number_format($stats['pending']); ?></div>
                    <div class="text-muted small">Pending Approval</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card success">
                    <div class="h4 mb-1 text-success"><?php echo number_format($stats['approved']); ?></div>
                    <div class="text-muted small">Approved</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card danger">
                    <div class="h4 mb-1 text-danger"><?php echo number_format($stats['suspended']); ?></div>
                    <div class="text-muted small">Suspended</div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="stats-card">
                    <div class="h4 mb-1"><?php echo number_format($stats['rejected']); ?></div>
                    <div class="text-muted small">Rejected</div>
                </div>
            </div>
        </div>

        <!-- Vendors Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Vendor Applications & Management</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Vendor</th>
                                <th>Business Info</th>
                                <th>Performance</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vendors)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-store fa-3x text-muted mb-3"></i>
                                    <div class="h5 text-muted">No vendors found</div>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($vendors as $vendor): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($vendor['logo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($vendor['logo_url']); ?>" 
                                             class="vendor-logo me-3" alt="Logo">
                                        <?php else: ?>
                                        <div class="vendor-logo me-3 bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-store text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($vendor['username']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($vendor['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($vendor['business_name'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo ucfirst($vendor['business_type'] ?? 'individual'); ?></small>
                                </td>
                                <td>
                                    <div><strong><?php echo number_format($vendor['product_count']); ?></strong> products</div>
                                    <div><strong><?php echo number_format($vendor['order_count']); ?></strong> orders</div>
                                    <div><strong>$<?php echo number_format($vendor['total_sales'], 2); ?></strong> sales</div>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'suspended' => 'secondary'
                                    ][$vendor['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($vendor['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($vendor['user_created'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($vendor['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="approve_vendor">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" 
                                                    title="Approve" onclick="return confirm('Approve this vendor?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="reject_vendor">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    title="Reject" onclick="return confirm('Reject this vendor application?')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                        <?php elseif ($vendor['status'] === 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrfTokenInput(); ?>
                                            <input type="hidden" name="action" value="suspend_vendor">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" 
                                                    title="Suspend" onclick="return confirm('Suspend this vendor?')">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <a href="/admin/products/?vendor=<?php echo $vendor['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-info" title="View Products">
                                            <i class="fas fa-box"></i>
                                        </a>
                                        <a href="/admin/orders/?vendor=<?php echo $vendor['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-success" title="View Orders">
                                            <i class="fas fa-shopping-cart"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>