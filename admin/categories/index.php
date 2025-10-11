<?php
/**
 * Categories & SEO Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Hierarchical category management
 * - SEO optimization for categories and products
 * - Category sorting and organization
 * - Bulk operations
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
checkPermission('categories.manage');
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;

requireAdminAuth();
checkPermission('categories.manage');

// Handle actions
$action = $_GET['action'] ?? 'list';
$category_id = $_GET['id'] ?? '';
$message = '';
$error = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $name = sanitizeInput($_POST['name']);
                    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                    $description = sanitizeInput($_POST['description']);
                    $slug = sanitizeInput($_POST['slug']);
                    $sort_order = (int)($_POST['sort_order'] ?? 0);
                    $meta_title = sanitizeInput($_POST['meta_title']);
                    $meta_description = sanitizeInput($_POST['meta_description']);
                    
                    if (empty($slug)) {
                        $slug = generateSlug($name);
                    } else {
                        $slug = generateSlug($slug);
                    }
                    
                    // Check if slug exists
                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
                    $stmt->execute([$slug]);
                    if ($stmt->fetch()) {
                        throw new Exception('Slug already exists. Please choose a different one.');
                    }
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO categories 
                        (name, parent_id, description, slug, sort_order, meta_title, meta_description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $parent_id, $description, $slug, $sort_order, $meta_title, $meta_description]);
                    
                    $new_id = $pdo->lastInsertId();
                    logAuditEvent('category', $new_id, 'create', [
                        'name' => $name,
                        'parent_id' => $parent_id,
                        'slug' => $slug
                    ]);
                    
                    $message = 'Category created successfully.';
                    break;
                    
                case 'update':
                    $id = (int)$_POST['id'];
                    $name = sanitizeInput($_POST['name']);
                    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
                    $description = sanitizeInput($_POST['description']);
                    $slug = sanitizeInput($_POST['slug']);
                    $sort_order = (int)($_POST['sort_order'] ?? 0);
                    $is_active = isset($_POST['is_active']) ? 1 : 0;
                    $meta_title = sanitizeInput($_POST['meta_title']);
                    $meta_description = sanitizeInput($_POST['meta_description']);
                    
                    if (empty($slug)) {
                        $slug = generateSlug($name);
                    } else {
                        $slug = generateSlug($slug);
                    }
                    
                    // Prevent self-parenting or circular references
                    if ($parent_id === $id) {
                        throw new Exception('Category cannot be its own parent.');
                    }
                    
                    // Check if slug exists (excluding current category)
                    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                    $stmt->execute([$slug, $id]);
                    if ($stmt->fetch()) {
                        throw new Exception('Slug already exists. Please choose a different one.');
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE categories 
                        SET name = ?, parent_id = ?, description = ?, slug = ?, 
                            sort_order = ?, is_active = ?, meta_title = ?, meta_description = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $parent_id, $description, $slug, $sort_order, $is_active, $meta_title, $meta_description, $id]);
                    
                    logAuditEvent('category', $id, 'update', [
                        'name' => $name,
                        'parent_id' => $parent_id,
                        'slug' => $slug,
                        'is_active' => $is_active
                    ]);
                    
                    $message = 'Category updated successfully.';
                    break;
                    
                case 'delete':
                    $id = (int)$_POST['id'];
                    
                    // Check if category has children
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Cannot delete category with subcategories.');
                    }
                    
                    // Check if category has products
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id = ?");
                    $stmt->execute([$id]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception('Cannot delete category with assigned products.');
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    logAuditEvent('category', $id, 'delete');
                    
                    $message = 'Category deleted successfully.';
                    break;
                    
                case 'update_seo':
                    $entity_type = sanitizeInput($_POST['entity_type']);
                    $entity_id = (int)$_POST['entity_id'];
                    $meta_title = sanitizeInput($_POST['meta_title']);
                    $meta_description = sanitizeInput($_POST['meta_description']);
                    $canonical_url = sanitizeInput($_POST['canonical_url']);
                    $og_title = sanitizeInput($_POST['og_title']);
                    $og_description = sanitizeInput($_POST['og_description']);
                    $og_image = sanitizeInput($_POST['og_image']);
                    $robots = sanitizeInput($_POST['robots']);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO seo_meta 
                        (entity_type, entity_id, meta_title, meta_description, canonical_url, 
                         og_title, og_description, og_image, robots)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        meta_title = VALUES(meta_title),
                        meta_description = VALUES(meta_description),
                        canonical_url = VALUES(canonical_url),
                        og_title = VALUES(og_title),
                        og_description = VALUES(og_description),
                        og_image = VALUES(og_image),
                        robots = VALUES(robots),
                        updated_at = NOW()
                    ");
                    $stmt->execute([
                        $entity_type, $entity_id, $meta_title, $meta_description, $canonical_url,
                        $og_title, $og_description, $og_image, $robots
                    ]);
                    
                    logAuditEvent('seo_meta', $entity_id, 'update', [
                        'entity_type' => $entity_type,
                        'meta_title' => $meta_title
                    ]);
                    
                    $message = 'SEO metadata updated successfully.';
                    break;
                    
                case 'reorder':
                    $category_orders = $_POST['category_order'] ?? [];
                    
                    $pdo->beginTransaction();
                    foreach ($category_orders as $id => $order) {
                        $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
                        $stmt->execute([(int)$order, (int)$id]);
                    }
                    $pdo->commit();
                    
                    logAuditEvent('categories', 0, 'reorder', $category_orders);
                    $message = 'Category order updated successfully.';
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

// Get categories for display
$categories = [];
$category_tree = [];
$selected_category = null;

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
checkPermission('categories.manage');
    // Get all categories
    $stmt = $pdo->query("
        SELECT c.*, COUNT(pc.product_id) as product_count
        FROM categories c
        LEFT JOIN product_categories pc ON c.id = pc.category_id
        GROUP BY c.id
        ORDER BY c.parent_id, c.sort_order, c.name
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build category tree
    $category_tree = buildCategoryTree($categories);
    
    // Get selected category if editing
    if ($action === 'edit' && $category_id) {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $selected_category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get SEO data for this category
        if ($selected_category) {
            $stmt = $pdo->prepare("SELECT * FROM seo_meta WHERE entity_type = 'category' AND entity_id = ?");
            $stmt->execute([$category_id]);
            $seo_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($seo_data) {
                $selected_category = array_merge($selected_category, $seo_data);
            }
        }
    }

// Helper functions
function buildCategoryTree($categories, $parent_id = null, $level = 0) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parent_id) {
            $category['level'] = $level;
            $category['children'] = buildCategoryTree($categories, $category['id'], $level + 1);
            $tree[] = $category;
        }
    }
    return $tree;
}

function renderCategoryTree($tree, $selected_id = null) {
    foreach ($tree as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $category['level']);
        $selected = ($selected_id == $category['id']) ? 'selected' : '';
        echo "<option value='{$category['id']}' {$selected}>{$indent}{$category['name']}</option>";
        if (!empty($category['children'])) {
            renderCategoryTree($category['children'], $selected_id);
        }
    }
}

function generateSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\-]/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories & SEO Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; }
        .sidebar a { color: #bdc3c7; text-decoration: none; }
        .sidebar a:hover { color: #fff; background-color: #34495e; }
        .category-tree { padding-left: 20px; }
        .category-item { 
            padding: 8px;
            border: 1px solid #dee2e6;
            margin-bottom: 5px;
            border-radius: 4px;
            background: #f8f9fa;
        }
        .level-0 { margin-left: 0; }
        .level-1 { margin-left: 20px; }
        .level-2 { margin-left: 40px; }
        .level-3 { margin-left: 60px; }
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
                            <i class="fas fa-sitemap"></i> Categories & SEO
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../products/index.php">
                            <i class="fas fa-cube"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../orders/index.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-sitemap text-primary"></i> Categories & SEO Management</h2>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                        <?php if (hasPermission('seo.manage')): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#seoModal">
                            <i class="fas fa-search"></i> SEO Tools
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
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Categories List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5 class="mb-0">Category Tree</h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleReorderMode()">
                                    <i class="fas fa-sort"></i> Reorder
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="category-tree">
                                    <?php renderCategoryTreeAdmin($category_tree); ?>
                                </div>
                                
                                <form method="POST" action="?action=reorder" id="reorder-form" style="display: none;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-success btn-sm">Save Order</button>
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReorderMode()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Category Form -->
                    <div class="col-md-4">
                        <?php if ($action === 'edit' && $selected_category): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Edit Category</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="?action=update">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="id" value="<?= $selected_category['id'] ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($selected_category['name']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Parent Category</label>
                                        <select name="parent_id" class="form-select">
                                            <option value="">None (Root Category)</option>
                                            <?php renderCategoryTree($category_tree, $selected_category['parent_id']); ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Slug</label>
                                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($selected_category['slug']) ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($selected_category['description']) ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" name="sort_order" class="form-control" value="<?= $selected_category['sort_order'] ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_active" class="form-check-input" <?= $selected_category['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label">Active</label>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO Fields -->
                                    <h6 class="border-top pt-3">SEO Settings</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Meta Title</label>
                                        <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($selected_category['meta_title'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Meta Description</label>
                                        <textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($selected_category['meta_description'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">Update Category</button>
                                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <h4 class="text-primary"><?= count($categories) ?></h4>
                                            <small class="text-muted">Total Categories</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <h4 class="text-success"><?= count(array_filter($categories, fn($c) => $c['is_active'])) ?></h4>
                                            <small class="text-muted">Active Categories</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="?action=create">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Name *</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Parent Category</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">None (Root Category)</option>
                                        <?php renderCategoryTree($category_tree); ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Slug</label>
                                    <input type="text" name="slug" class="form-control" placeholder="Leave empty to auto-generate">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="0">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="4"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" name="meta_title" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SEO Tools Modal -->
    <div class="modal fade" id="seoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="?action=update_seo">
                    <div class="modal-header">
                        <h5 class="modal-title">SEO Metadata Management</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Entity Type</label>
                                    <select name="entity_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="category">Category</option>
                                        <option value="product">Product</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Entity ID</label>
                                    <input type="number" name="entity_id" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Title</label>
                                    <input type="text" name="meta_title" class="form-control" maxlength="200">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Meta Description</label>
                                    <textarea name="meta_description" class="form-control" rows="3" maxlength="300"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Canonical URL</label>
                                    <input type="url" name="canonical_url" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Open Graph Title</label>
                                    <input type="text" name="og_title" class="form-control" maxlength="200">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Open Graph Description</label>
                                    <textarea name="og_description" class="form-control" rows="3" maxlength="300"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Open Graph Image URL</label>
                                    <input type="url" name="og_image" class="form-control">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Robots</label>
                                    <select name="robots" class="form-select">
                                        <option value="index,follow">Index, Follow</option>
                                        <option value="index,nofollow">Index, No Follow</option>
                                        <option value="noindex,follow">No Index, Follow</option>
                                        <option value="noindex,nofollow">No Index, No Follow</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Update SEO</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let reorderMode = false;
        
        function toggleReorderMode() {
            reorderMode = !reorderMode;
            const form = document.getElementById('reorder-form');
            const tree = document.getElementById('category-tree');
            
            if (reorderMode) {
                form.style.display = 'block';
                // Add drag handles and reorder inputs
                tree.querySelectorAll('.category-item').forEach((item, index) => {
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.name = `category_order[${item.dataset.id}]`;
                    input.value = index;
                    input.className = 'form-control form-control-sm d-inline-block w-auto me-2';
                    item.insertBefore(input, item.firstChild);
                });
            } else {
                form.style.display = 'none';
                // Remove reorder inputs
                tree.querySelectorAll('input[name^="category_order"]').forEach(input => {
                    input.remove();
                });
            }
        }
        
        function deleteCategory(id, name) {
            if (confirm(`Are you sure you want to delete the category "${name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '?action=delete';
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= generateCSRFToken() ?>';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                
                form.appendChild(csrfInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

<?php
function renderCategoryTreeAdmin($tree, $level = 0) {
    foreach ($tree as $category) {
        echo "<div class='category-item level-{$level} d-flex justify-content-between align-items-center' data-id='{$category['id']}'>";
        echo "<div>";
        echo "<strong>{$category['name']}</strong>";
        echo " <small class='text-muted'>({$category['product_count']} products)</small>";
        if (!$category['is_active']) {
            echo " <span class='badge bg-warning'>Inactive</span>";
        }
        echo "</div>";
        echo "<div class='btn-group btn-group-sm'>";
        echo "<a href='?action=edit&id={$category['id']}' class='btn btn-outline-primary btn-sm'>";
        echo "<i class='fas fa-edit'></i>";
        echo "</a>";
        echo "<button type='button' class='btn btn-outline-danger btn-sm' onclick='deleteCategory({$category['id']}, \"{$category['name']}\")'>";
        echo "<i class='fas fa-trash'></i>";
        echo "</button>";
        echo "</div>";
        echo "</div>";
        
        if (!empty($category['children'])) {
            renderCategoryTreeAdmin($category['children'], $level + 1);
        }
    }
}
?>