<?php
/**
 * Product Management - Admin Module
 * Comprehensive Product & Catalog Management System
 */

require_once __DIR__ . '/../../includes/init.php';

// Safe HTML escape helper (prevents null deprecation warnings in PHP 8.1+)
if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Initialize PDO global variable for this module
$pdo = db();
RoleMiddleware::requireAdmin();

$page_title = 'Product Management';
$action = $_GET['action'] ?? 'list';
$product_id = $_GET['id'] ?? null;

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        $product = new Product();
        
        switch ($_POST['action']) {
            case 'create_product':
                $productData = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'short_description' => sanitizeInput($_POST['short_description'] ?? ''),
                    'sku' => sanitizeInput($_POST['sku']),
                    'price' => floatval($_POST['price']),
                    'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
                    'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
                    'category_id' => intval($_POST['category_id']),
                    'vendor_id' => intval($_POST['vendor_id']),
                    'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 10),
                    'track_inventory' => isset($_POST['track_inventory']) ? 1 : 0,
                    'allow_backorders' => isset($_POST['allow_backorders']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'status' => sanitizeInput($_POST['status']),
                    'tags' => sanitizeInput($_POST['tags'] ?? ''),
                    'meta_title' => sanitizeInput($_POST['meta_title'] ?? ''),
                    'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
                ];
                
                $newProductId = $product->create($productData);
                if ($newProductId) {
                    $_SESSION['success_message'] = 'Product created successfully.';
                    logAdminActivity(Session::getUserId(), 'product_created', 'product', $newProductId, null, $productData);
                } else {
                    throw new Exception('Failed to create product.');
                }
                break;
                
            case 'update_product':
                $productData = [
                    'name' => sanitizeInput($_POST['name']),
                    'description' => sanitizeInput($_POST['description']),
                    'short_description' => sanitizeInput($_POST['short_description'] ?? ''),
                    'sku' => sanitizeInput($_POST['sku']),
                    'price' => floatval($_POST['price']),
                    'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
                    'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
                    'category_id' => intval($_POST['category_id']),
                    'vendor_id' => intval($_POST['vendor_id']),
                    'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
                    'stock_quantity' => intval($_POST['stock_quantity']),
                    'low_stock_threshold' => intval($_POST['low_stock_threshold'] ?? 10),
                    'track_inventory' => isset($_POST['track_inventory']) ? 1 : 0,
                    'allow_backorders' => isset($_POST['allow_backorders']) ? 1 : 0,
                    'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
                    'status' => sanitizeInput($_POST['status']),
                    'tags' => sanitizeInput($_POST['tags'] ?? ''),
                    'meta_title' => sanitizeInput($_POST['meta_title'] ?? ''),
                    'meta_description' => sanitizeInput($_POST['meta_description'] ?? ''),
                ];
                
                $success = $product->update(intval($_POST['product_id']), $productData);
                if ($success) {
                    $_SESSION['success_message'] = 'Product updated successfully.';
                    logAdminActivity(Session::getUserId(), 'product_updated', 'product', intval($_POST['product_id']), null, $productData);
                } else {
                    throw new Exception('Failed to update product.');
                }
                break;
                
            case 'delete_product':
                $success = $product->delete(intval($_POST['product_id']));
                if ($success) {
                    $_SESSION['success_message'] = 'Product deleted successfully.';
                    logAdminActivity(Session::getUserId(), 'product_deleted', 'product', intval($_POST['product_id']));
                } else {
                    throw new Exception('Failed to delete product.');
                }
                break;
                
            case 'bulk_update_status':
                $product_ids = $_POST['product_ids'] ?? [];
                $new_status = sanitizeInput($_POST['bulk_status']);
                
                $success_count = 0;
                foreach ($product_ids as $pid) {
                    if ($product->updateStatus(intval($pid), $new_status)) {
                        $success_count++;
                        logAdminActivity(Session::getUserId(), 'product_status_updated', 'product', intval($pid), null, ['status' => $new_status]);
                    }
                }
                
                $_SESSION['success_message'] = "$success_count product(s) updated successfully.";
                break;
                
            case 'bulk_delete':
                $product_ids = $_POST['product_ids'] ?? [];
                
                $success_count = 0;
                foreach ($product_ids as $pid) {
                    if ($product->delete(intval($pid))) {
                        $success_count++;
                        logAdminActivity(Session::getUserId(), 'product_deleted', 'product', intval($pid));
                    }
                }
                
                $_SESSION['success_message'] = "$success_count product(s) deleted successfully.";
                break;
        }
        
        redirect('/admin/products/');
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        redirect('/admin/products/');
    }
}

// Get products for listing
$product = new Product();
$category = new Category();

// Initialize vendor variable
$vendor = null;
if (class_exists('Vendor')) {
    $vendor = new Vendor();
}

// Filters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$vendor_filter = $_GET['vendor_id'] ?? '';
$search_query = $_GET['search'] ?? '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build filter conditions
$filters = [];
$params = [];

if (!empty($status_filter)) {
    $filters[] = "p.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($category_filter)) {
    $filters[] = "p.category_id = :category_id";
    $params[':category_id'] = $category_filter;
}

if (!empty($vendor_filter)) {
    $filters[] = "p.vendor_id = :vendor_id";
    $params[':vendor_id'] = $vendor_filter;
}

if (!empty($search_query)) {
    $filters[] = "(p.name LIKE :search OR p.sku LIKE :search OR p.description LIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$where_clause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';

// Check if vendors table exists
$table_exists_sql = "SHOW TABLES LIKE 'vendors'";
$table_exists_stmt = $pdo->prepare($table_exists_sql);
$table_exists_stmt->execute();
$vendors_table_exists = $table_exists_stmt->fetchColumn();

if ($vendors_table_exists) {
    $sql = "SELECT p.*, c.name as category_name, v.business_name as vendor_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            $where_clause 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset";
} else {
    $sql = "SELECT p.*, c.name as category_name, 'No Vendor' as vendor_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $where_clause 
            ORDER BY p.created_at DESC 
            LIMIT :limit OFFSET :offset";
}

$params[':limit'] = $per_page;
$params[':offset'] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM products p $where_clause";
$count_params = array_diff_key($params, [':limit' => '', ':offset' => '']);
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get categories and vendors for filters
$categories = $category->getAll();
$vendors = [];
if ($vendor && $vendors_table_exists) {
    $vendors = $vendor->getAll();
}

// Handle specific actions
if ($action === 'edit' && $product_id) {
    $current_product = $product->getById($product_id);
    if (!$current_product) {
        $_SESSION['error_message'] = 'Product not found.';
        redirect('/admin/products/');
    }
}

include_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <div class="admin-header-left">
            <h1><?php echo e($page_title); ?></h1>
            <p class="admin-subtitle">Manage your product catalog</p>
        </div>
        <div class="admin-header-right">
            <?php if ($action === 'list'): ?>
                <a href="?action=create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Product
                </a>
                <button type="button" class="btn btn-secondary" onclick="toggleBulkActions()">
                    <i class="fas fa-list"></i> Bulk Actions
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php displaySessionMessages(); ?>

    <?php if ($action === 'list'): ?>
        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Products</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Category</label>
                    <select name="category_id" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (!empty($vendors)): ?>
                <div class="filter-group">
                    <label>Vendor</label>
                    <select name="vendor_id" class="form-control">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?php echo $v['id']; ?>" 
                                    <?php echo $vendor_filter == $v['id'] ? 'selected' : ''; ?>>
                                <?php echo e($v['business_name'] ?? $v['name'] ?? 'Unknown Vendor'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="filter-group search-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search products..." 
                           value="<?php echo e($search_query); ?>">
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="?" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Bulk Actions (Hidden by default) -->
        <div id="bulk-actions" class="bulk-actions-card" style="display: none;">
            <form method="POST" id="bulk-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <div class="bulk-actions-content">
                    <div class="bulk-selection">
                        <label>
                            <input type="checkbox" id="select-all"> Select All
                        </label>
                        <span class="selected-count">0 selected</span>
                    </div>
                    
                    <div class="bulk-actions-buttons">
                        <select name="bulk_status" class="form-control">
                            <option value="">Change Status</option>
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                        
                        <button type="submit" name="action" value="bulk_update_status" 
                                class="btn btn-secondary" onclick="return confirmBulkAction('update status')">
                            Update Status
                        </button>
                        
                        <button type="submit" name="action" value="bulk_delete" 
                                class="btn btn-danger" onclick="return confirmBulkAction('delete')">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Products List -->
        <div class="data-table-card">
            <div class="table-header">
                <h3>Products (<?php echo $total_products; ?> total)</h3>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="bulk-checkbox-header" style="display: none;">
                                <input type="checkbox" id="table-select-all">
                            </th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Category</th>
                            <?php if ($vendors_table_exists): ?>
                            <th>Vendor</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="<?php echo $vendors_table_exists ? '9' : '8'; ?>" class="no-data">
                                    <div class="no-data-content">
                                        <i class="fas fa-box-open"></i>
                                        <h4>No products found</h4>
                                        <p>Get started by adding your first product.</p>
                                        <a href="?action=create" class="btn btn-primary">Add Product</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td class="bulk-checkbox-cell" style="display: none;">
                                        <input type="checkbox" class="product-checkbox" 
                                               name="product_ids[]" value="<?php echo $prod['id']; ?>">
                                    </td>
                                    <td class="product-cell">
                                        <div class="product-info">
                                            <div class="product-image">
                                                <?php if (!empty($prod['image_url'])): ?>
                                                    <img src="<?php echo e($prod['image_url']); ?>" 
                                                         alt="<?php echo e($prod['name'] ?? 'Product'); ?>">
                                                <?php else: ?>
                                                    <div class="no-image">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <h4><?php echo e($prod['name'] ?? 'Unnamed'); ?></h4>
                                                <?php if (($prod['short_description'] ?? '') !== ''): ?>
                                                    <p class="product-description">
                                                        <?php 
                                                        $description = $prod['short_description'] ?? '';
                                                        $snippet = mb_substr($description, 0, 100, 'UTF-8');
                                                        echo e($snippet) . (mb_strlen($description, 'UTF-8') > 100 ? '...' : '');
                                                        ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="sku-cell">
                                        <code><?php echo e($prod['sku'] ?? ''); ?></code>
                                    </td>
                                    <td class="price-cell">
                                        <div class="price-info">
                                            <span class="current-price">$<?php echo number_format((float)($prod['price'] ?? 0), 2); ?></span>
                                            <?php if (!empty($prod['compare_price']) && $prod['compare_price'] > $prod['price']): ?>
                                                <span class="compare-price">$<?php echo number_format($prod['compare_price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="stock-cell">
                                        <?php if (!empty($prod['track_inventory'])): ?>
                                            <div class="stock-info">
                                                <span class="stock-quantity 
                                                    <?php echo ($prod['stock_quantity'] ?? 0) <= ($prod['low_stock_threshold'] ?? 10) ? 'low-stock' : ''; ?>">
                                                    <?php echo number_format((int)($prod['stock_quantity'] ?? 0)); ?>
                                                </span>
                                                <?php if (($prod['stock_quantity'] ?? 0) <= ($prod['low_stock_threshold'] ?? 10)): ?>
                                                    <span class="stock-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-tracking">Not tracked</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($prod['category_name'] ?? 'No Category'); ?></td>
                                    <?php if ($vendors_table_exists): ?>
                                    <td><?php echo e($prod['vendor_name'] ?? 'No Vendor'); ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="status-badge status-<?php echo e($prod['status'] ?? 'unknown'); ?>">
                                            <?php echo e(ucfirst($prod['status'] ?? 'unknown')); ?>
                                        </span>
                                        <?php if (!empty($prod['is_featured'])): ?>
                                            <span class="featured-badge">
                                                <i class="fas fa-star"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <a href="?action=edit&id=<?php echo $prod['id']; ?>" 
                                               class="btn btn-sm btn-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=view&id=<?php echo $prod['id']; ?>" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete(<?php echo $prod['id']; ?>, '<?php echo e($prod['name'] ?? 'Unnamed'); ?>')" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-wrapper">
                    <?php echo generatePagination($page, $total_pages, $_GET); ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<script>
// Bulk actions functionality
function toggleBulkActions() {
    const bulkActions = document.getElementById('bulk-actions');
    const checkboxHeaders = document.querySelectorAll('.bulk-checkbox-header');
    const checkboxCells = document.querySelectorAll('.bulk-checkbox-cell');
    
    if (bulkActions.style.display === 'none') {
        bulkActions.style.display = 'block';
        checkboxHeaders.forEach(header => header.style.display = 'table-cell');
        checkboxCells.forEach(cell => cell.style.display = 'table-cell');
    } else {
        bulkActions.style.display = 'none';
        checkboxHeaders.forEach(header => header.style.display = 'none');
        checkboxCells.forEach(cell => cell.style.display = 'none');
    }
}

// Select all functionality
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckboxes = document.querySelectorAll('#select-all, #table-select-all');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const selectedCount = document.querySelector('.selected-count');
    
    selectAllCheckboxes.forEach(selectAll => {
        selectAll.addEventListener('change', function() {
            productCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSelectedCount();
            
            // Sync other select all checkboxes
            selectAllCheckboxes.forEach(other => {
                if (other !== this) other.checked = this.checked;
            });
        });
    });
    
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
        selectedCount.textContent = `${checkedCount} selected`;
        
        const allChecked = checkedCount === productCheckboxes.length;
        const someChecked = checkedCount > 0;
        
        selectAllCheckboxes.forEach(selectAll => {
            selectAll.checked = allChecked;
            selectAll.indeterminate = someChecked && !allChecked;
        });
    }
});

function confirmBulkAction(action) {
    const checkedCount = document.querySelectorAll('.product-checkbox:checked').length;
    if (checkedCount === 0) {
        alert('Please select at least one product.');
        return false;
    }
    return confirm(`Are you sure you want to ${action} ${checkedCount} product(s)?`);
}

function confirmDelete(productId, productName) {
    if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../../includes/admin_footer.php'; ?>