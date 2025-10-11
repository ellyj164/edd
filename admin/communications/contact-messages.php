<?php
/**
 * Contact Messages - Admin Communications
 * View and manage contact form submissions
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

$page_title = 'Contact Messages';
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $message_id = $_POST['message_id'] ?? null;
    
    try {
        if ($action === 'mark_read' && $message_id) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
            $stmt->execute([$message_id]);
            $message = 'Message marked as read.';
        } elseif ($action === 'reply' && $message_id) {
            $reply = trim($_POST['reply'] ?? '');
            if (empty($reply)) {
                throw new Exception('Reply message is required.');
            }
            
            // Get message details
            $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $msg = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($msg) {
                // Send reply email
                $mailer = new Mailer();
                $mailer->send(
                    $msg['email'],
                    'Re: ' . $msg['subject'],
                    $reply
                );
                
                // Update database
                $stmt = $pdo->prepare("
                    UPDATE contact_messages 
                    SET status = 'replied', admin_reply = ?, replied_by = ?, replied_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$reply, $_SESSION['user_id'], $message_id]);
                
                $message = 'Reply sent successfully.';
            }
        } elseif ($action === 'archive' && $message_id) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = 'archived' WHERE id = ?");
            $stmt->execute([$message_id]);
            $message = 'Message archived.';
        } elseif ($action === 'delete' && $message_id) {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $message = 'Message deleted.';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'unread';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = '';
if ($filter === 'unread') {
    $where_clause = "WHERE status = 'unread'";
} elseif ($filter === 'read') {
    $where_clause = "WHERE status = 'read'";
} elseif ($filter === 'replied') {
    $where_clause = "WHERE status = 'replied'";
} elseif ($filter === 'archived') {
    $where_clause = "WHERE status = 'archived'";
}

// Get messages
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM contact_messages 
    {$where_clause}
");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total / $per_page);

$stmt = $pdo->query("
    SELECT * 
    FROM contact_messages 
    {$where_clause}
    ORDER BY created_at DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for badges
$stmt = $pdo->query("
    SELECT 
        SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread_count,
        SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
    FROM contact_messages
");
$counts = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1>📧 Contact Messages</h1>
                <p class="text-muted">View and respond to customer inquiries</p>
            </div>
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
                <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>" 
                   href="?filter=unread">
                    Unread <span class="badge bg-danger"><?php echo $counts['unread_count']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" 
                   href="?filter=read">
                    Read <span class="badge bg-secondary"><?php echo $counts['read_count']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'replied' ? 'active' : ''; ?>" 
                   href="?filter=replied">
                    Replied <span class="badge bg-success"><?php echo $counts['replied_count']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'archived' ? 'active' : ''; ?>" 
                   href="?filter=archived">
                    Archived <span class="badge bg-secondary"><?php echo $counts['archived_count']; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
                   href="?filter=all">
                    All <span class="badge bg-primary"><?php echo $total; ?></span>
                </a>
            </li>
        </ul>

        <!-- Messages Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 4rem;"></i>
                        <p class="mt-3">No messages found</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Subject</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $msg): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                        <td>
                                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>">
                                                <?php echo htmlspecialchars($msg['email']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                        <td>
                                            <?php if ($msg['category']): ?>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($msg['category']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'unread' => 'bg-danger',
                                                'read' => 'bg-secondary',
                                                'replied' => 'bg-success',
                                                'archived' => 'bg-dark'
                                            ];
                                            $badge_class = $badges[$msg['status']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($msg['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#messageModal<?php echo $msg['id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Message Modal -->
                                    <div class="modal fade" id="messageModal<?php echo $msg['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Message from <?php echo htmlspecialchars($msg['name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>From:</strong> <?php echo htmlspecialchars($msg['name']); ?> 
                                                        &lt;<?php echo htmlspecialchars($msg['email']); ?>&gt;
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                                                    </div>
                                                    <?php if ($msg['category']): ?>
                                                        <div class="mb-3">
                                                            <strong>Category:</strong> 
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($msg['category']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <hr>
                                                    <div class="mb-3">
                                                        <strong>Message:</strong>
                                                        <div class="mt-2 p-3 bg-light rounded">
                                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                        </div>
                                                    </div>

                                                    <?php if ($msg['status'] === 'replied' && $msg['admin_reply']): ?>
                                                        <hr>
                                                        <div class="mb-3">
                                                            <strong>Your Reply:</strong>
                                                            <div class="mt-2 p-3 bg-success bg-opacity-10 rounded">
                                                                <?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                Replied on <?php echo date('M d, Y H:i', strtotime($msg['replied_at'])); ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($msg['status'] !== 'replied'): ?>
                                                        <hr>
                                                        <form method="POST">
                                                            <input type="hidden" name="action" value="reply">
                                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label"><strong>Reply:</strong></label>
                                                                <textarea name="reply" class="form-control" rows="5" required></textarea>
                                                            </div>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="bi bi-send"></i> Send Reply
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <?php if ($msg['status'] === 'unread'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="mark_read">
                                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                            <button type="submit" class="btn btn-secondary">
                                                                <i class="bi bi-check2"></i> Mark as Read
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($msg['status'] !== 'archived'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="archive">
                                                            <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="bi bi-archive"></i> Archive
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this message?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                        <button type="submit" class="btn btn-danger">
                                                            <i class="bi bi-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                    
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
