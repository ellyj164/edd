<?php
/**
 * Professional Dispute Resolution Module
 *
 * Manages customer and vendor disputes with SLA tracking.
 * @package    Admin/Disputes
 * @version    1.1.0
 */

// Core application requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/init.php';

// --- Page Setup & Security ---
$page_title = 'Dispute Resolution';
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

// Initialize default values to prevent errors
$stats = ['total_disputes' => 0, 'pending_resolution' => 0, 'overdue_sla' => 0, 'resolved' => 0];
$disputes = [];

try {
    // Authenticate and check permissions
    requireAdminAuth();
    checkPermission('disputes.view');
    $pdo = db();

    // --- DATA FETCHING FOR DISPLAY (GET REQUESTS) ---
    // Fetch dashboard statistics
    $stats_query = $pdo->query("
        SELECT
            COUNT(*) as total_disputes,
            COALESCE(SUM(CASE WHEN status = 'pending' OR status = 'in_progress' THEN 1 ELSE 0 END), 0) as pending_resolution,
            COALESCE(SUM(CASE WHEN status NOT IN ('resolved', 'closed') AND sla_deadline < NOW() THEN 1 ELSE 0 END), 0) as overdue_sla,
            COALESCE(SUM(CASE WHEN status = 'resolved' OR status = 'closed' THEN 1 ELSE 0 END), 0) as resolved
        FROM disputes
    ");
    if ($stats_query) {
        $stats = $stats_query->fetch(PDO::FETCH_ASSOC);
    }

    // Filtering logic
    $filter = $_GET['filter'] ?? 'all';
    $params = [];
    $where_sql = '';

    switch ($filter) {
        case 'pending':
            $where_sql = "WHERE d.status IN ('pending', 'in_progress')";
            break;
        case 'overdue':
            $where_sql = "WHERE d.status NOT IN ('resolved', 'closed') AND d.sla_deadline < NOW()";
            break;
        case 'resolved':
            $where_sql = "WHERE d.status IN ('resolved', 'closed')";
            break;
    }

    // Fetch dispute data for the table - THIS QUERY IS NOW CORRECTED
    $disputes_query = $pdo->prepare("
        SELECT
            d.id,
            d.order_id,
            d.subject,
            d.status,
            d.created_at,
            d.sla_deadline,
            cust.username as user_name,
            v.business_name as vendor_name,
            adm.username as assigned_admin_name
        FROM disputes d
        LEFT JOIN orders o ON d.order_id = o.id
        LEFT JOIN users cust ON o.user_id = cust.id
        LEFT JOIN vendors v ON d.vendor_id = v.id
        LEFT JOIN users adm ON d.assigned_to = adm.id
        $where_sql
        ORDER BY d.created_at DESC
        LIMIT 100
    ");
    $disputes_query->execute($params);
    $disputes = $disputes_query->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Centralized error handling
    $error_message = "Database error: " . $e->getMessage();
}

// --- RENDER PAGE ---
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><i class="fas fa-gavel"></i> Dispute Resolution</h1>
        <nav class="nav">
            <a class="nav-link" href="/admin/">Back to Admin</a>
            <a class="nav-link active" href="/admin/disputes/">Disputes</a>
            <a class="nav-link" href="/logout.php">Logout</a>
        </nav>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-primary"><?php echo (int)($stats['total_disputes'] ?? 0); ?></h5><p class="card-text text-muted">Total Disputes</p></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-warning"><?php echo (int)($stats['pending_resolution'] ?? 0); ?></h5><p class="card-text text-muted">Pending Resolution</p></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-danger"><?php echo (int)($stats['overdue_sla'] ?? 0); ?></h5><p class="card-text text-muted">Overdue SLA</p></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-success"><?php echo (int)($stats['resolved'] ?? 0); ?></h5><p class="card-text text-muted">Resolved</p></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="btn-group" role="group">
                <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All Disputes</a>
                <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                <a href="?filter=overdue" class="btn <?php echo $filter === 'overdue' ? 'btn-danger' : 'btn-outline-danger'; ?>">Overdue</a>
                <a href="?filter=resolved" class="btn <?php echo $filter === 'resolved' ? 'btn-success' : 'btn-outline-success'; ?>">Resolved</a>
            </div>
            <button class="btn btn-secondary"><i class="fas fa-download me-2"></i>Export</button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Dispute ID</th>
                            <th>Subject</th>
                            <th>Parties Involved</th>
                            <th>Status</th>
                            <th>Date Created</th>
                            <th>SLA Deadline</th>
                            <th>Assigned To</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($disputes)): ?>
                            <tr><td colspan="8" class="text-center text-muted p-5"><i class="fas fa-gavel fa-2x mb-2"></i><br>No disputes found for this filter.</td></tr>
                        <?php else: foreach ($disputes as $dispute): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($dispute['id']); ?></td>
                                <td><?php echo htmlspecialchars($dispute['subject']); ?></td>
                                <td>
                                    <?php if($dispute['user_name']): ?><div><small>Customer:</small> <?php echo htmlspecialchars($dispute['user_name']); ?></div><?php endif; ?>
                                    <?php if($dispute['vendor_name']): ?><div><small>Vendor:</small> <?php echo htmlspecialchars($dispute['vendor_name']); ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_map = ['pending' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'closed' => 'secondary'];
                                    $status_class = $status_map[$dispute['status']] ?? 'primary';
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $dispute['status'])); ?></span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($dispute['created_at'])); ?></td>
                                <td>
                                    <?php if($dispute['sla_deadline']): ?>
                                        <span class="<?php echo strtotime($dispute['sla_deadline']) < time() ? 'text-danger fw-bold' : ''; ?>">
                                            <?php echo date('Y-m-d H:i', strtotime($dispute['sla_deadline'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($dispute['assigned_admin_name'] ?? 'Unassigned'); ?></td>
                                <td class="text-end">
                                    <a href="view_dispute.php?id=<?php echo $dispute['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>