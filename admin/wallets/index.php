<?php
/**
 * Professional Wallet Management Dashboard
 * Features: Search, Filtering, Pagination, and Full Admin Controls.
 * @version 4.0.0
 */

// Core application requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/init.php';

// --- Page Setup & Security ---
$page_title = 'Wallet Management';
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

// Initialize default values
$stats = ['total_users' => 0, 'active_wallets' => 0, 'total_balance' => 0.00, 'suspended_wallets' => 0];
$user_wallets = [];
$pagination_links = '';

try {
    requireAdminAuth();
    checkPermission('wallets.view');
    $pdo = db();
    $admin_id = $_SESSION['user_id'] ?? null;

    // --- ACTION HANDLING (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // (The POST handling logic from the previous version is robust and remains unchanged)
        // It handles 'create_wallet', 'credit_debit', 'change_status' securely.
        if (!validateCSRFToken($_POST['csrf_token'])) throw new Exception('Invalid security token.');
        checkPermission('wallets.edit');
        $action = $_POST['action'] ?? '';
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if (!$user_id) throw new Exception('Invalid user specified.');
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        switch ($action) {
            case 'create_wallet':
                if ($wallet) throw new Exception('User already has a wallet.');
                $pdo->prepare("INSERT INTO wallets (user_id, balance, status) VALUES (?, 0.00, 'active')")->execute([$user_id]);
                $_SESSION['success_message'] = 'Wallet created successfully.';
                break;
            case 'credit_debit':
                if (!$wallet) throw new Exception('No wallet found.');
                $type = $_POST['type'];
                $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
                $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
                if ($amount <= 0) throw new Exception("Amount must be positive.");
                if (empty($description)) throw new Exception("A description is required.");
                if ($type === 'debit' && $amount > $wallet['balance']) throw new Exception("Cannot debit more than the current balance.");
                $balance_before = $wallet['balance'];
                $balance_after = ($type === 'credit') ? $balance_before + $amount : $balance_before - $amount;
                $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?")->execute([$balance_after, $wallet['id']]);
                $log_stmt = $pdo->prepare("INSERT INTO wallet_transactions (wallet_id, admin_id, type, amount, balance_before, balance_after, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $log_stmt->execute([$wallet['id'], $admin_id, $type, $amount, $balance_before, $balance_after, $description]);
                $_SESSION['success_message'] = 'Wallet balance updated.';
                break;
            case 'change_status':
                 if (!$wallet) throw new Exception('No wallet found.');
                 $new_status = in_array($_POST['status'], ['active', 'suspended']) ? $_POST['status'] : 'active';
                 $pdo->prepare("UPDATE wallets SET status = ? WHERE id = ?")->execute([$new_status, $wallet['id']]);
                 $_SESSION['success_message'] = 'Wallet status updated.';
                break;
        }
        $pdo->commit();
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    // --- DATA FETCHING (GET) ---
    // Stats
    $stats_query = $pdo->query("SELECT (SELECT COUNT(*) FROM users) as total_users, COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active_wallets, COALESCE(SUM(balance), 0) as total_balance, COALESCE(SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END), 0) as suspended_wallets FROM wallets");
    if ($stats_query) $stats = $stats_query->fetch(PDO::FETCH_ASSOC);

    // Filtering and Searching
    $search = trim($_GET['search'] ?? '');
    $status_filter = trim($_GET['status_filter'] ?? '');
    $params = [];
    $where_clauses = [];

    if ($search) {
        $where_clauses[] = "(u.username LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($status_filter) {
        $where_clauses[] = "w.status = ?";
        $params[] = $status_filter;
    }
    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(' AND ', $where_clauses) : '';

    // Pagination
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
    $limit = 15;
    $offset = ($page - 1) * $limit;
    
    $total_records_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN wallets w ON u.id = w.user_id $where_sql");
    $total_records_stmt->execute($params);
    $total_records = $total_records_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Fetch user data with pagination
    $users_stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.role, w.id as wallet_id, w.balance, w.status as wallet_status FROM users u LEFT JOIN wallets w ON u.id = w.user_id $where_sql ORDER BY u.created_at DESC LIMIT ? OFFSET ?");
    $users_stmt->execute(array_merge($params, [$limit, $offset]));
    $user_wallets = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $error_message = "A critical error occurred: " . $e->getMessage();
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2">Wallet Management</h1>
        <nav class="nav"><a class="nav-link" href="/admin/">Dashboard</a><a class="nav-link active" href="/admin/wallets/">Wallets</a><a class="nav-link" href="/logout.php">Logout</a></nav>
    </div>

    <?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2"><?php echo (int)($stats['total_users'] ?? 0); ?></h5><p class="card-text text-muted">TOTAL USERS</p></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-success"><?php echo (int)($stats['active_wallets'] ?? 0); ?></h5><p class="card-text text-muted">ACTIVE WALLETS</p></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-primary">$<?php echo number_format((float)($stats['total_balance'] ?? 0), 2); ?></h5><p class="card-text text-muted">TOTAL BALANCE</p></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-danger"><?php echo (int)($stats['suspended_wallets'] ?? 0); ?></h5><p class="card-text text-muted">SUSPENDED WALLETS</p></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">User Wallets Overview</h5>
            <form method="GET" class="row g-3 mt-2">
                <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="col-md-4">
                    <select name="status_filter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?php if($status_filter == 'active') echo 'selected'; ?>>Active</option>
                        <option value="suspended" <?php if($status_filter == 'suspended') echo 'selected'; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Filter</button></div>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Balance</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($user_wallets)): ?>
                            <tr><td colspan="6" class="text-center text-muted p-4">No users found matching your criteria.</td></tr>
                        <?php else: foreach ($user_wallets as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($user['role']); ?></span></td>
                                <td><b><?php echo $user['wallet_id'] ? '$' . number_format($user['balance'], 2) : '<span class="text-muted">No Wallet</span>'; ?></b></td>
                                <td>
                                    <?php $status = $user['wallet_status'] ?? 'not_created'; $badges = ['active' => 'bg-success', 'suspended' => 'bg-danger', 'not_created' => 'bg-warning text-dark']; ?>
                                    <span class="badge <?php echo $badges[$status]; ?>"><?php echo str_replace('_', ' ', ucfirst($status)); ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if (!$user['wallet_id']): ?>
                                        <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="create_wallet"><input type="hidden" name="user_id" value="<?php echo $user['id']; ?>"><button type="submit" class="btn btn-sm btn-primary">Create</button></form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#creditDebitModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['username']); ?>">Fund</button>
                                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#statusModal" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['username']); ?>" data-current-status="<?php echo $user['wallet_status']; ?>">Status</button>
                                        <a href="wallet_history.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-dark">History</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($total_pages > 1): ?>
            <nav><ul class="pagination justify-content-center">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php if($i == $page) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
            </ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="creditDebitModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5 class="modal-title">Credit / Debit Wallet</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="credit_debit"><input type="hidden" name="user_id" class="modal-user-id"><p>User: <strong class="modal-user-name"></strong></p><div class="mb-3"><label class="form-label">Action</label><select name="type" class="form-select" required><option value="credit">Credit (Add)</option><option value="debit">Debit (Remove)</option></select></div><div class="mb-3"><label class="form-label">Amount</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" required></div><div class="mb-3"><label class="form-label">Reason / Description</label><input type="text" name="description" class="form-control" required placeholder="e.g., Manual refund, Bonus credit"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Submit</button></div></form></div></div></div>
<div class="modal fade" id="statusModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="POST"><div class="modal-header"><h5 class="modal-title">Change Wallet Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>"><input type="hidden" name="action" value="change_status"><input type="hidden" name="user_id" class="modal-user-id"><p>User: <strong class="modal-user-name"></strong></p><div class="mb-3"><label class="form-label">New Status</label><select name="status" class="form-select modal-current-status" required><option value="active">Active</option><option value="suspended">Suspended</option></select></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Update</button></div></form></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function populateModal(modalElement, button) {
        modalElement.querySelector('.modal-user-id').value = button.getAttribute('data-user-id');
        modalElement.querySelector('.modal-user-name').textContent = button.getAttribute('data-user-name');
    }
    var creditDebitModal = document.getElementById('creditDebitModal');
    if(creditDebitModal) creditDebitModal.addEventListener('show.bs.modal', (e) => populateModal(creditDebitModal, e.relatedTarget));
    var statusModal = document.getElementById('statusModal');
    if(statusModal) statusModal.addEventListener('show.bs.modal', function (e) {
        populateModal(statusModal, e.relatedTarget);
        statusModal.querySelector('.modal-current-status').value = e.relatedTarget.getAttribute('data-current-status');
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>