<?php
/**
 * Offers Management - Admin Module
 * Manage customer product offers
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO
$pdo = db();

// Admin authentication
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
    }
} else {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
}

$page_title = 'Product Offers Management';
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $offer_id = $_POST['offer_id'] ?? null;
    
    try {
        if ($action === 'approve' && $offer_id) {
            $stmt = $pdo->prepare("
                UPDATE product_offers 
                SET status = 'approved', processed_by = ?, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $offer_id]);
            $message = 'Offer approved successfully.';
            
        } elseif ($action === 'decline' && $offer_id) {
            $admin_message = trim($_POST['admin_message'] ?? '');
            $stmt = $pdo->prepare("
                UPDATE product_offers 
                SET status = 'declined', admin_message = ?, processed_by = ?, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$admin_message, $_SESSION['user_id'], $offer_id]);
            $message = 'Offer declined.';
            
        } elseif ($action === 'counter' && $offer_id) {
            $counter_price = (float)($_POST['counter_price'] ?? 0);
            $admin_message = trim($_POST['admin_message'] ?? '');
            
            if ($counter_price <= 0) {
                throw new Exception('Invalid counter offer price');
            }
            
            $stmt = $pdo->prepare("
                UPDATE product_offers 
                SET status = 'countered', counter_price = ?, admin_message = ?, 
                    processed_by = ?, processed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$counter_price, $admin_message, $_SESSION['user_id'], $offer_id]);
            $message = 'Counter offer sent successfully.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = '';
if ($filter === 'pending') {
    $where_clause = "WHERE po.status = 'pending' AND po.expires_at > NOW()";
} elseif ($filter === 'approved') {
    $where_clause = "WHERE po.status = 'approved'";
} elseif ($filter === 'declined') {
    $where_clause = "WHERE po.status = 'declined'";
} elseif ($filter === 'countered') {
    $where_clause = "WHERE po.status = 'countered'";
} elseif ($filter === 'expired') {
    $where_clause = "WHERE po.status = 'pending' AND po.expires_at <= NOW()";
}

// Get offers
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM product_offers po
    {$where_clause}
");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $per_page);

$stmt = $pdo->query("
    SELECT po.*, 
           p.name as product_name, p.price as product_price,
           u.username, u.email,
           admin.username as admin_username
    FROM product_offers po
    JOIN products p ON po.product_id = p.id
    JOIN users u ON po.user_id = u.id
    LEFT JOIN users admin ON po.processed_by = admin.id
    {$where_clause}
    ORDER BY po.created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$offers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for badges
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'pending' AND expires_at > NOW() THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined_count,
        SUM(CASE WHEN status = 'countered' THEN 1 ELSE 0 END) as countered_count,
        SUM(CASE WHEN status = 'pending' AND expires_at <= NOW() THEN 1 ELSE 0 END) as expired_count
    FROM product_offers
");
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1>💰 Product Offers Management</h1>
        <p>Review and respond to customer offers</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
               href="?filter=pending">
                Pending <span class="badge bg-warning"><?php echo (int)($counts['pending_count'] ?? 0); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'approved' ? 'active' : ''; ?>" 
               href="?filter=approved">
                Approved <span class="badge bg-success"><?php echo (int)($counts['approved_count'] ?? 0); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'countered' ? 'active' : ''; ?>" 
               href="?filter=countered">
                Countered <span class="badge bg-info"><?php echo (int)($counts['countered_count'] ?? 0); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'declined' ? 'active' : ''; ?>" 
               href="?filter=declined">
                Declined <span class="badge bg-danger"><?php echo (int)($counts['declined_count'] ?? 0); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'expired' ? 'active' : ''; ?>" 
               href="?filter=expired">
                Expired <span class="badge bg-secondary"><?php echo (int)($counts['expired_count'] ?? 0); ?></span>
            </a>
        </li>
    </ul>

    <!-- Offers Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($offers)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                    <p class="mt-3">No offers found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Original Price</th>
                                <th>Offer Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($offers as $offer): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($offer['created_at'])); ?></td>
                                    <td>
                                        <a href="/product.php?id=<?php echo $offer['product_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($offer['product_name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($offer['username']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($offer['email']); ?></small>
                                    </td>
                                    <td>$<?php echo number_format($offer['product_price'], 2); ?></td>
                                    <td>
                                        <strong>$<?php echo number_format($offer['offer_price'], 2); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo round(($offer['offer_price'] / $offer['product_price']) * 100); ?>% of price
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'pending' => 'bg-warning',
                                            'approved' => 'bg-success',
                                            'declined' => 'bg-danger',
                                            'countered' => 'bg-info',
                                            'expired' => 'bg-secondary'
                                        ];
                                        $badge_class = $badges[$offer['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>">
                                            <?php echo ucfirst($offer['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($offer['status'] === 'pending'): ?>
                                            <div class="btn-group" role="group">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="decline">
                                                    <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Decline</button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
