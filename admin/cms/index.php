<?php
/**
 * Content Management System Module
 * E-Commerce Platform - Admin Panel
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper function for safe input sanitization
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Helper function to generate slug
if (!function_exists('generateSlug')) {
    function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }
}

// Define fallback functions
if (!function_exists('requireAdminAuth')) {
    function requireAdminAuth() {
        return true; // Simplified for testing
    }
}

if (!function_exists('checkPermission')) {
    function checkPermission($permission) {
        return true; // Simplified for testing
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        return true; // Simplified for testing
    }
}

if (!function_exists('logAuditEvent')) {
    function logAuditEvent($entity, $entity_id, $action, $data = []) {
        error_log("Audit: $action on $entity $entity_id: " . json_encode($data));
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
    }
}

// Database connection with error handling
function getDbConnection() {
    try {
        // Try to include the db file
        if (file_exists(__DIR__ . '/../../includes/db.php')) {
            require_once __DIR__ . '/../../includes/db.php';
            if (function_exists('db')) {
                $pdo = db();
                $pdo->query('SELECT 1');
                return $pdo;
            }
        }
        return null;
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Initialize
requireAdminAuth();
checkPermission('cms.manage');

// Handle actions
$action = $_GET['action'] ?? 'list';
$tab = $_GET['tab'] ?? 'pages';
$item_id = $_GET['id'] ?? '';
$message = '';
$error = '';

// Get database connection
$pdo = getDbConnection();
$database_available = ($pdo !== null);

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $database_available) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            switch ($action) {
                case 'save_page':
                    checkPermission('cms.manage');
                    $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
                    $title = sanitizeInput($_POST['title']);
                    $slug = sanitizeInput($_POST['slug']);
                    $content = $_POST['content']; // Allow HTML content
                    $excerpt = sanitizeInput($_POST['excerpt']);
                    $status = sanitizeInput($_POST['status']);
                    $meta_title = sanitizeInput($_POST['meta_title']);
                    $meta_description = sanitizeInput($_POST['meta_description']);
                    
                    if (empty($slug)) {
                        $slug = generateSlug($title);
                    } else {
                        $slug = generateSlug($slug);
                    }
                    
                    if ($id) {
                        // Update existing page
                        $stmt = $pdo->prepare("
                            UPDATE cms_pages 
                            SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, 
                                meta_title = ?, meta_description = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$title, $slug, $content, $excerpt, $status, $meta_title, $meta_description, $id]);
                        $message = 'Page updated successfully.';
                    } else {
                        // Create new page
                        $stmt = $pdo->prepare("
                            INSERT INTO cms_pages 
                            (title, slug, content, excerpt, status, meta_title, meta_description, created_by, created_at, updated_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $stmt->execute([
                            $title, $slug, $content, $excerpt, $status, $meta_title, $meta_description, 
                            $_SESSION['admin_id'] ?? 1
                        ]);
                        $message = 'Page created successfully.';
                    }
                    break;
                    
                case 'delete_item':
                    $item_type = sanitizeInput($_POST['item_type']);
                    $item_id = (int)$_POST['item_id'];
                    
                    if ($item_type === 'page') {
                        $stmt = $pdo->prepare("DELETE FROM cms_pages WHERE id = ?");
                        $stmt->execute([$item_id]);
                        $message = 'Page deleted successfully.';
                    }
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data for display
$pages = [];
$cms_stats = [
    'published_pages' => 0,
    'draft_pages' => 0,
    'total_pages' => 0,
    'active_banners' => 0,
    'total_banners' => 0,
    'total_clicks' => 0,
    'total_impressions' => 0
];

if ($database_available) {
    try {
        // Get CMS pages - simple query
        $stmt = $pdo->query("SELECT *, 'System Administrator' as author_name FROM cms_pages ORDER BY created_at DESC");
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get simple statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_pages,
                COUNT(CASE WHEN status = 'published' THEN 1 END) as published_pages,
                COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_pages
            FROM cms_pages
        ");
        $page_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($page_stats) {
            $cms_stats = array_merge($cms_stats, $page_stats);
        }
        
    } catch (Exception $e) {
        $error = 'Error loading CMS data: ' . $e->getMessage();
        $pages = [];
    }
} else {
    $error = 'Database connection required. Please check your database configuration.';
    $pages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; }
        .sidebar a { color: #bdc3c7; text-decoration: none; padding: 10px 15px; display: block; }
        .sidebar a:hover { color: #fff; background-color: #34495e; }
        .sidebar a.active { background-color: #3498db; color: #fff; }
        .content-editor { min-height: 400px; }
        .btn-homepage-editor {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-homepage-editor:hover {
            background: linear-gradient(45deg, #ee5a24, #ff6b6b);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(238, 90, 36, 0.3);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3">
                    <h4 class="text-white mb-4">Admin Panel</h4>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="../index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-file-alt"></i> Content
                    </a>
                    <a class="nav-link" href="../coupons/index.php">
                        <i class="fas fa-tags"></i> Coupons
                    </a>
                    <a class="nav-link" href="../users/index.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-file-alt text-primary"></i> Content Management</h2>
                    <div class="btn-group">
                        <?php if (hasPermission('cms.manage')): ?>
                        <!-- Homepage Editor Button - ADDED -->
                        <a href="homepage-editor.php" class="btn btn-homepage-editor me-2">
                            <i class="fas fa-home me-1"></i> Homepage Editor
                        </a>
                        <button type="button" class="btn btn-primary" onclick="createPage()">
                            <i class="fas fa-plus"></i> New Page
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- CMS Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= number_format((int)$cms_stats['published_pages']) ?></h4>
                                        <p class="mb-0">Published Pages</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-file-alt fa-2x"></i>
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
                                        <h4><?= number_format((int)$cms_stats['draft_pages']) ?></h4>
                                        <p class="mb-0">Draft Pages</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-edit fa-2x"></i>
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
                                        <h4><?= number_format((int)$cms_stats['total_pages']) ?></h4>
                                        <p class="mb-0">Total Pages</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-copy fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card <?= $database_available ? 'bg-success' : 'bg-danger' ?> text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?= $database_available ? 'Online' : 'Offline' ?></h4>
                                        <p class="mb-0">Database Status</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-<?= $database_available ? 'check-circle' : 'exclamation-triangle' ?> fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <a href="homepage-editor.php" class="btn btn-homepage-editor w-100 py-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-home fa-2x me-3"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">Homepage Editor</div>
                                                    <small>Edit homepage layout & banners</small>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <button onclick="createPage()" class="btn btn-primary w-100 py-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-file-plus fa-2x me-3"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">New Page</div>
                                                    <small>Create a new content page</small>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="../media/index.php" class="btn btn-success w-100 py-3">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-images fa-2x me-3"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">Media Library</div>
                                                    <small>Manage images & files</small>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pages List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-alt"></i> Pages</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Slug</th>
                                        <th>Status</th>
                                        <th>Author</th>
                                        <th>Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pages)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-file-alt fa-3x mb-3"></i>
                                                <h5>No Pages Found</h5>
                                                <p>Create your first page to get started.</p>
                                                <?php if ($database_available): ?>
                                                <button class="btn btn-primary" onclick="createPage()">
                                                    <i class="fas fa-plus"></i> Create First Page
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($pages as $page): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($page['title']) ?></strong>
                                                <?php if (!empty($page['excerpt'])): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(substr($page['excerpt'], 0, 100)) ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><code>/<?= htmlspecialchars($page['slug']) ?></code></td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'published' => 'success',
                                                    'draft' => 'warning',
                                                    'archived' => 'secondary'
                                                ];
                                                $color = $status_colors[$page['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>"><?= ucfirst($page['status']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($page['author_name']) ?></td>
                                            <td>
                                                <?php 
                                                $updated_at = $page['updated_at'] ?? $page['created_at'];
                                                echo $updated_at ? date('M j, Y', strtotime($updated_at)) : 'Unknown';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (hasPermission('cms.manage') && $database_available): ?>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editPage(<?= $page['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteItem('page', <?= $page['id'] ?>, '<?= htmlspecialchars($page['title']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                                <a href="/<?= htmlspecialchars($page['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function createPage() {
            alert('Page creation form would open here. Currently in simplified mode.');
        }
        
        function editPage(pageId) {
            alert('Edit page form would open for page ID: ' + pageId);
        }
        
        function deleteItem(type, itemId, itemName) {
            if (confirm(`Are you sure you want to delete the ${type} "${itemName}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete_item';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= generateCSRFToken() ?>';
                
                const typeInput = document.createElement('input');
                typeInput.type = 'hidden';
                typeInput.name = 'item_type';
                typeInput.value = type;
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'item_id';
                idInput.value = itemId;
                
                form.appendChild(csrfInput);
                form.appendChild(typeInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>