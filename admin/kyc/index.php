<?php
/**
 * KYC & Verification Management - Admin Module
 * Document verification and compliance system
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';

// Initialize with graceful fallback
require_once __DIR__ . '/../../includes/init.php';

// Database connection with proper error handling
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
checkPermission('kyc.view');

$page_title = 'KYC & Verification';
$action = $_GET['action'] ?? 'list';
$document_id = $_GET['id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
    } else {
        try {
            switch ($_POST['action']) {
                case 'approve_document':
                    checkPermission('kyc.approve');
                    
                    $documentId = intval($_POST['document_id']);
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    if ($database_available) {
                        // Update document status
                        $stmt = $pdo->prepare(
                            "UPDATE kyc_documents SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? 
                             WHERE id = ?"
                        );
                        $stmt->execute([$_SESSION['admin_id'], $notes, $documentId]);
                        
                        // Get document info
                        $stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE id = ?");
                        $stmt->execute([$documentId]);
                        $document = $stmt->fetch();
                        
                        if ($document) {
                            // Update user KYC verification status
                            $stmt = $pdo->prepare(
                                "INSERT INTO kyc_verifications (user_id, status, verified_by, verified_at, notes) 
                                 VALUES (?, 'approved', ?, NOW(), ?) 
                                 ON DUPLICATE KEY UPDATE 
                                 status = 'approved', verified_by = ?, verified_at = NOW(), notes = ?"
                            );
                            $stmt->execute([$document['user_id'], $_SESSION['admin_id'], $notes, $_SESSION['admin_id'], $notes]);
                            
                            logAuditEvent('kyc_document_approved', $documentId, 'approve', [
                                'status' => 'approved', 
                                'notes' => $notes
                            ]);
                        }
                    }
                    
                    $_SESSION['success_message'] = 'Document approved successfully.';
                    break;
                    
                case 'reject_document':
                    checkPermission('kyc.reject');
                    
                    $documentId = intval($_POST['document_id']);
                    $notes = sanitizeInput($_POST['notes'] ?? '');
                    
                    if (empty($notes)) {
                        $_SESSION['error_message'] = 'Rejection reason is required.';
                        break;
                    }
                    
                    if ($database_available) {
                        // Update document status
                        $stmt = $pdo->prepare(
                            "UPDATE kyc_documents SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? 
                             WHERE id = ?"
                        );
                        $stmt->execute([$_SESSION['admin_id'], $notes, $documentId]);
                        
                        // Get document info
                        $stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE id = ?");
                        $stmt->execute([$documentId]);
                        $document = $stmt->fetch();
                        
                        if ($document) {
                            logAuditEvent('kyc_document_rejected', $documentId, 'reject', [
                                'status' => 'rejected', 
                                'notes' => $notes
                            ]);
                        }
                    }
                    
                    $_SESSION['success_message'] = 'Document rejected successfully.';
                    break;
                    
                case 'bulk_action':
                    $bulkAction = $_POST['bulk_action'] ?? '';
                    $selectedItems = $_POST['selected_items'] ?? [];
                    
                    if (empty($selectedItems)) {
                        $_SESSION['error_message'] = 'No items selected.';
                        break;
                    }
                    
                    $count = 0;
                    if ($database_available) {
                        foreach ($selectedItems as $documentId) {
                            $documentId = intval($documentId);
                            
                            switch ($bulkAction) {
                                case 'approve':
                                    checkPermission('kyc.approve');
                                    $stmt = $pdo->prepare(
                                        "UPDATE kyc_documents SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() 
                                         WHERE id = ? AND status = 'pending'"
                                    );
                                    $stmt->execute([$_SESSION['admin_id'], $documentId]);
                                    $count++;
                                    break;
                                    
                                case 'mark_pending':
                                    checkPermission('kyc.approve');
                                    $stmt = $pdo->prepare(
                                        "UPDATE kyc_documents SET status = 'pending', reviewed_by = NULL, reviewed_at = NULL 
                                         WHERE id = ?"
                                    );
                                    $stmt->execute([$documentId]);
                                    $count++;
                                    break;
                            }
                        }
                        
                        logAuditEvent('kyc_bulk_action', null, 'bulk_update', [
                            'action' => $bulkAction, 
                            'count' => $count
                        ]);
                    }
                    
                    $_SESSION['success_message'] = "Bulk action completed on {$count} items.";
                    break;
            }
        } catch (Exception $e) {
            error_log("KYC management error: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred while processing your request.';
        }
    }
    
    header('Location: /admin/kyc/');
    exit;
}

// Get KYC documents with filtering
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$documents = [];
$totalDocuments = 0;
$totalPages = 0;
$stats = [
    'total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'expired' => 0, 'verified_users' => 0
];

if ($database_available) {
    try {
        // Build WHERE clause for filters
        $whereConditions = [];
        $params = [];
        
        if ($filter !== 'all') {
            $whereConditions[] = "kd.status = ?";
            $params[] = $filter;
        }
        
        if (!empty($search)) {
            $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ? OR kd.document_type LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get documents
        $stmt = $pdo->prepare("
            SELECT kd.*, u.username, u.email, u.first_name, u.last_name,
                   reviewer.username as reviewer_name
            FROM kyc_documents kd
            JOIN users u ON kd.user_id = u.id
            LEFT JOIN users reviewer ON kd.reviewed_by = reviewer.id
            {$whereClause}
            ORDER BY kd.uploaded_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM kyc_documents kd
            JOIN users u ON kd.user_id = u.id
            {$whereClause}
        ");
        $stmt->execute($params);
        $totalDocuments = $stmt->fetchColumn();
        $totalPages = ceil($totalDocuments / $limit);
        
        // Get statistics
        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_documents");
        $stats['total'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'approved'");
        $stats['approved'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'rejected'");
        $stats['rejected'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'expired'");
        $stats['expired'] = $stmt->fetchColumn();
        
        // Try to get verified users, fallback if table doesn't exist
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM kyc_verifications WHERE status = 'approved'");
            $stats['verified_users'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $stats['verified_users'] = $stats['approved']; // Fallback
        }
        
    } catch (Exception $e) {
        error_log("Error fetching KYC documents: " . $e->getMessage());
        $database_available = false;
    }
}

// Get current document for review
$currentDocument = null;
if ($action === 'review' && $document_id && $database_available) {
    try {
        $stmt = $pdo->prepare("
            SELECT kd.*, u.username, u.email, u.first_name, u.last_name
            FROM kyc_documents kd
            JOIN users u ON kd.user_id = u.id
            WHERE kd.id = ?
        ");
        $stmt->execute([$document_id]);
        $currentDocument = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching document details: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.4 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
        }
        
        body { 
            background-color: #f8f9fa; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 2rem 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .dashboard-card h5 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid var(--admin-accent);
        }
        
        .stat-card.warning { border-left-color: var(--admin-warning); }
        .stat-card.success { border-left-color: var(--admin-success); }
        .stat-card.danger { border-left-color: var(--admin-danger); }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--admin-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-expired { background-color: #f5f5f5; color: #666; }
        
        .table-actions {
            white-space: nowrap;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-1">
                        <i class="fas fa-id-card me-2"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <p class="mb-0 opacity-75">Review and verify user documents and identities</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="../index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
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

        <?php if (!$database_available): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                <div>
                    <h6 class="mb-1">Database Connection Issue</h6>
                    <p class="mb-0">Unable to connect to database. Please check your database configuration.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <!-- KYC Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Total Documents</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?php echo number_format($stats['pending']); ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo number_format($stats['approved']); ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?php echo number_format($stats['rejected']); ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['expired']); ?></div>
                <div class="stat-label">Expired</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?php echo number_format($stats['verified_users']); ?></div>
                <div class="stat-label">Verified Users</div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="dashboard-card mb-4">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select class="form-select" onchange="updateFilter(this.value)">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                        <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="expired" <?php echo $filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <form method="GET" class="d-flex">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by user, email, or document type..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-md-3 text-end">
                    <button class="btn btn-outline-success" onclick="exportKycData()">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="dashboard-card">
            <?php if (empty($documents)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-id-card fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No KYC Documents Found</h5>
                    <p class="text-muted">No documents match your current filter criteria.</p>
                </div>
            <?php else: ?>
            <form method="POST" class="bulk-action-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="bulk_action">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <select class="form-select me-2" name="bulk_action" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <?php if (hasPermission('kyc.approve')): ?>
                            <option value="approve">Approve Selected</option>
                            <option value="mark_pending">Mark as Pending</option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" class="btn btn-outline-primary">Apply</button>
                    </div>
                    <div>
                        Showing <?php echo number_format($totalDocuments); ?> documents
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="select-all"></th>
                                <th>User</th>
                                <th>Document Type</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th>Reviewed By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $document): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="item-select" name="selected_items[]" value="<?php echo $document['id']; ?>">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($document['username'] ?? 'Unknown'); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($document['email'] ?? 'No email'); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $document['document_type'] ?? 'Unknown')); ?></span><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($document['original_filename'] ?? 'No filename'); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $document['status'] ?? 'pending'; ?>">
                                        <?php echo ucfirst($document['status'] ?? 'Pending'); ?>
                                    </span>
                                    <?php if (!empty($document['review_notes'])): ?>
                                    <br><small class="text-muted" title="<?php echo htmlspecialchars($document['review_notes']); ?>">
                                        <?php echo strlen($document['review_notes']) > 50 ? substr($document['review_notes'], 0, 50) . '...' : $document['review_notes']; ?>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($document['uploaded_at'])); ?><br>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($document['uploaded_at'])); ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($document['reviewer_name'])): ?>
                                        <?php echo htmlspecialchars($document['reviewer_name']); ?><br>
                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($document['reviewed_at'])); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions">
                                    <a href="?action=review&id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Review
                                    </a>
                                    <?php if (($document['status'] ?? 'pending') === 'pending'): ?>
                                        <?php if (hasPermission('kyc.approve')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" 
                                                onclick="quickApprove(<?php echo $document['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('kyc.reject')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="quickReject(<?php echo $document['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($action === 'review' && $currentDocument): ?>
        <!-- Document Review -->
        <div class="row">
            <div class="col-md-8">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5>Review Document</h5>
                        <a href="/admin/kyc/" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to List
                        </a>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>User Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars(($currentDocument['first_name'] ?? '') . ' ' . ($currentDocument['last_name'] ?? '')); ?></p>
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($currentDocument['username'] ?? 'Unknown'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($currentDocument['email'] ?? 'No email'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Document Details</h6>
                            <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $currentDocument['document_type'] ?? 'Unknown')); ?></p>
                            <p><strong>Filename:</strong> <?php echo htmlspecialchars($currentDocument['original_filename'] ?? 'No filename'); ?></p>
                            <p><strong>Size:</strong> <?php echo number_format(($currentDocument['file_size'] ?? 0) / 1024, 2); ?> KB</p>
                            <p><strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($currentDocument['uploaded_at'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo $currentDocument['status'] ?? 'pending'; ?>">
                                    <?php echo ucfirst($currentDocument['status'] ?? 'Pending'); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Document Preview -->
                    <div class="mb-4">
                        <h6>Document Preview</h6>
                        <div class="border rounded p-3 text-center bg-light">
                            <?php if (!empty($currentDocument['file_path']) && in_array($currentDocument['mime_type'] ?? '', ['image/jpeg', 'image/png', 'image/gif'])): ?>
                                <img src="<?php echo htmlspecialchars($currentDocument['file_path']); ?>" 
                                     alt="Document" class="img-fluid" style="max-height: 500px;">
                            <?php elseif (!empty($currentDocument['file_path']) && ($currentDocument['mime_type'] ?? '') === 'application/pdf'): ?>
                                <iframe src="<?php echo htmlspecialchars($currentDocument['file_path']); ?>" 
                                        width="100%" height="500px" style="border: none;"></iframe>
                            <?php else: ?>
                                <p><i class="fas fa-file fa-3x text-muted"></i></p>
                                <p>Preview not available for this file type</p>
                                <?php if (!empty($currentDocument['file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($currentDocument['file_path']); ?>" 
                                   target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download File
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="dashboard-card">
                    <h6>Review Actions</h6>
                    
                    <?php if (($currentDocument['status'] ?? 'pending') === 'pending'): ?>
                    <!-- Approve Form -->
                    <?php if (hasPermission('kyc.approve')): ?>
                    <form method="POST" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="approve_document">
                        <input type="hidden" name="document_id" value="<?php echo $currentDocument['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Approval Notes (Optional)</label>
                            <textarea class="form-control" name="notes" rows="3" 
                                      placeholder="Add any notes about the approval..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i>Approve Document
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Reject Form -->
                    <?php if (hasPermission('kyc.reject')): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="reject_document">
                        <input type="hidden" name="document_id" value="<?php echo $currentDocument['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Rejection Reason *</label>
                            <textarea class="form-control" name="notes" rows="3" required
                                      placeholder="Please explain why this document is being rejected..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="fas fa-times me-1"></i>Reject Document
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        This document has already been reviewed.
                        <?php if (!empty($currentDocument['review_notes'])): ?>
                        <hr>
                        <strong>Review Notes:</strong><br>
                        <?php echo htmlspecialchars($currentDocument['review_notes']); ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Document Not Found</h5>
            <p class="text-muted">The requested document could not be found.</p>
            <a href="/admin/kyc/" class="btn btn-primary">Back to KYC List</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function updateFilter(value) {
        const url = new URL(window.location);
        url.searchParams.set("filter", value);
        url.searchParams.delete("page");
        window.location = url;
    }

    function quickApprove(documentId) {
        if (confirm("Are you sure you want to approve this document?")) {
            const form = document.createElement("form");
            form.method = "POST";
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="approve_document">
                <input type="hidden" name="document_id" value="${documentId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function quickReject(documentId) {
        const reason = prompt("Please provide a reason for rejection:");
        if (reason && reason.trim()) {
            const form = document.createElement("form");
            form.method = "POST";
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="reject_document">
                <input type="hidden" name="document_id" value="${documentId}">
                <input type="hidden" name="notes" value="${reason.trim()}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function exportKycData() {
        alert('Export functionality would be implemented here.');
    }

    // Select all functionality
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.querySelector('.select-all');
        const itemSelects = document.querySelectorAll('.item-select');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                itemSelects.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });
        }
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (alert.querySelector('.btn-close')) {
                    alert.querySelector('.btn-close').click();
                }
            });
        }, 5000);
    });
    </script>
</body>
</html>