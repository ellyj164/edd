<?php
/**
 * Inventory Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Stock tracking and adjustments
 * - Warehouse management
 * - Low stock alerts
 * - Inventory reports
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
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

requireAdminAuth();
checkPermission('inventory.view');
    require_once __DIR__ . '/../../includes/init.php';
    // Initialize PDO global variable for this module
    $pdo = db();
    requireAdminAuth();
    checkPermission('inventory.view');

// Handle actions
$action = $_GET['action'] ?? 'list';
$warehouse_id = $_GET['warehouse_id'] ?? '';
$product_id = $_GET['product_id'] ?? '';
$message = '';
$error = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            switch ($action) {
                case 'adjust':
                    checkPermission('inventory.adjust');
                    $product_id = (int)$_POST['product_id'];
                    $warehouse_id = (int)$_POST['warehouse_id'];
                    $adjustment = (int)$_POST['adjustment'];
                    $reason = sanitizeInput($_POST['reason']);
                    
                    if ($adjustment != 0) {
                        $pdo->beginTransaction();
                        
                        // Get current stock
                        $stmt = $pdo->prepare("
                            SELECT qty FROM inventory 
                            WHERE product_id = ? AND warehouse_id = ?
                        ");
                        $stmt->execute([$product_id, $warehouse_id]);
                        $current = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($current) {
                            $new_qty = $current['qty'] + $adjustment;
                            if ($new_qty < 0) {
                                throw new Exception('Adjustment would result in negative stock');
                            }
                            
                            $stmt = $pdo->prepare("
                                UPDATE inventory 
                                SET qty = ?, updated_at = NOW() 
                                WHERE product_id = ? AND warehouse_id = ?
                            ");
                            $stmt->execute([$new_qty, $product_id, $warehouse_id]);
                        } else {
                            if ($adjustment < 0) {
                                throw new Exception('Cannot adjust non-existent inventory to negative');
                            }
                            $stmt = $pdo->prepare("
                                INSERT INTO inventory (product_id, warehouse_id, qty) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$product_id, $warehouse_id, $adjustment]);
                        }
                        
                        // Log adjustment
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory_adjustments 
                            (product_id, warehouse_id, adjustment, reason, adjusted_by, adjusted_at)
                            VALUES (?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([$product_id, $warehouse_id, $adjustment, $reason, $_SESSION['admin_id']]);
                        
                        $pdo->commit();
                        logAuditEvent('inventory_adjustment', $product_id, 'adjust', [
                            'warehouse_id' => $warehouse_id,
                            'adjustment' => $adjustment,
                            'reason' => $reason
                        ]);
                        
                        $message = 'Inventory adjusted successfully.';
                    }
                    break;
                    
                case 'create_warehouse':
                    checkPermission('warehouses.manage');
                    $name = sanitizeInput($_POST['name']);
                    $code = sanitizeInput($_POST['code']);
                    $address = sanitizeInput($_POST['address']);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO warehouses (name, code, address, created_at) 
                        VALUES (?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $code, $address]);
                    
                    logAuditEvent('warehouse', $pdo->lastInsertId(), 'create', [
                        'name' => $name,
                        'code' => $code
                    ]);
                    
                    $message = 'Warehouse created successfully.';
                    break;
                    
                case 'update_safety_stock':
                    checkPermission('inventory.adjust');
                    $product_id = (int)$_POST['product_id'];
                    $warehouse_id = (int)$_POST['warehouse_id'];
                    $safety_stock = (int)$_POST['safety_stock'];
                    
                    $stmt = $pdo->prepare("
                        UPDATE inventory 
                        SET safety_stock = ? 
                        WHERE product_id = ? AND warehouse_id = ?
                    ");
                    $stmt->execute([$safety_stock, $product_id, $warehouse_id]);
                    
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory (product_id, warehouse_id, qty, safety_stock) 
                            VALUES (?, ?, 0, ?)
                        ");
                        $stmt->execute([$product_id, $warehouse_id, $safety_stock]);
                    }
                    
                    $message = 'Safety stock updated successfully.';
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

// Get data for display
$warehouses = [];
$inventory_data = [];
$low_stock_items = [];

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
checkPermission('inventory.view');
    // Get warehouses
    $stmt = $pdo->query("SELECT * FROM warehouses ORDER BY name");
    $warehouses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get inventory data with product names
    $warehouse_filter = $warehouse_id ? "AND i.warehouse_id = " . (int)$warehouse_id : "";
    $stmt = $pdo->query("
        SELECT i.*, p.name as product_name, w.name as warehouse_name, w.code as warehouse_code
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        JOIN warehouses w ON i.warehouse_id = w.id
        WHERE 1=1 $warehouse_filter
        ORDER BY p.name, w.name
    ");
    $inventory_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get low stock items
    $stmt = $pdo->query("
        SELECT i.*, p.name as product_name, w.name as warehouse_name
        FROM inventory i
        JOIN products p ON i.product_id = p.id
        JOIN warehouses w ON i.warehouse_id = w.id
        WHERE i.qty <= i.min_stock_level AND i.min_stock_level > 0
        ORDER BY (i.qty - i.min_stock_level) ASC
    ");
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

// Get all products for dropdowns
$products = [];
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
checkPermission('inventory.view');
    $stmt = $pdo->query("SELECT id, name FROM products WHERE status = 'published' ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background-color: #2c3e50; }
        .sidebar a { color: #bdc3c7; text-decoration: none; }
        .sidebar a:hover { color: #fff; background-color: #34495e; }
        .low-stock { background-color: #fff3cd; }
        .out-of-stock { background-color: #f8d7da; }
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
                            <i class="fas fa-boxes"></i> Inventory
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
                    <h2><i class="fas fa-boxes text-primary"></i> Inventory Management</h2>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustModal">
                            <i class="fas fa-plus-minus"></i> Adjust Stock
                        </button>
                        <?php if (hasPermission('warehouses.manage')): ?>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#warehouseModal">
                            <i class="fas fa-warehouse"></i> New Warehouse
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

                <!-- Low Stock Alerts -->
                <?php if (!empty($low_stock_items)): ?>
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Warehouse</th>
                                        <th>Current Stock</th>
                                        <th>Safety Stock</th>
                                        <th>Shortage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['warehouse_name']) ?></td>
                                        <td><?= number_format($item['qty']) ?></td>
                                        <td><?= number_format($item['safety_stock']) ?></td>
                                        <td class="text-danger"><?= number_format($item['safety_stock'] - $item['qty']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Warehouse Filter</label>
                                <select name="warehouse_id" class="form-select">
                                    <option value="">All Warehouses</option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                    <option value="<?= $warehouse['id'] ?>" <?= $warehouse_id == $warehouse['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($warehouse['name']) ?> (<?= htmlspecialchars($warehouse['code']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Inventory Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Warehouse</th>
                                        <th>Current Stock</th>
                                        <th>Safety Stock</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_data as $item): 
                                        $status_class = '';
                                        $status_text = 'In Stock';
                                        if ($item['qty'] == 0) {
                                            $status_class = 'out-of-stock';
                                            $status_text = 'Out of Stock';
                                        } elseif ($item['qty'] <= $item['safety_stock'] && $item['safety_stock'] > 0) {
                                            $status_class = 'low-stock';
                                            $status_text = 'Low Stock';
                                        }
                                    ?>
                                    <tr class="<?= $status_class ?>">
                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= htmlspecialchars($item['warehouse_name']) ?></td>
                                        <td><?= number_format($item['qty']) ?></td>
                                        <td><?= number_format($item['safety_stock']) ?></td>
                                        <td><span class="badge bg-<?= $item['qty'] > 0 ? 'success' : 'danger' ?>"><?= $status_text ?></span></td>
                                        <td><?= $item['updated_at'] ? date('M j, Y g:i A', strtotime($item['updated_at'])) : 'N/A' ?></td>
                                        <td>
                                            <?php if (hasPermission('inventory.adjust')): ?>
                                            <button class="btn btn-sm btn-outline-primary" onclick="adjustStock(<?= $item['product_id'] ?>, <?= $item['warehouse_id'] ?>, '<?= htmlspecialchars($item['product_name']) ?>', '<?= htmlspecialchars($item['warehouse_name']) ?>')">
                                                <i class="fas fa-edit"></i> Adjust
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="setSafetyStock(<?= $item['product_id'] ?>, <?= $item['warehouse_id'] ?>, <?= $item['safety_stock'] ?>, '<?= htmlspecialchars($item['product_name']) ?>', '<?= htmlspecialchars($item['warehouse_name']) ?>')">
                                                <i class="fas fa-shield-alt"></i> Safety
                                            </button>
                                            <?php endif; ?>
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

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="adjustModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=adjust">
                    <div class="modal-header">
                        <h5 class="modal-title">Adjust Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="product_id" id="adjust_product_id">
                        <input type="hidden" name="warehouse_id" id="adjust_warehouse_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select name="product_id_select" id="adjust_product_select" class="form-select">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>"><?= htmlspecialchars($product['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id_select" id="adjust_warehouse_select" class="form-select">
                                <option value="">Select Warehouse</option>
                                <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?= $warehouse['id'] ?>"><?= htmlspecialchars($warehouse['name']) ?> (<?= htmlspecialchars($warehouse['code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Adjustment (+ to add, - to remove)</label>
                            <input type="number" name="adjustment" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <select name="reason" class="form-select" required>
                                <option value="">Select Reason</option>
                                <option value="Stock Count">Stock Count</option>
                                <option value="Damage">Damage/Loss</option>
                                <option value="Return">Return</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Manual Correction">Manual Correction</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Adjust Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Safety Stock Modal -->
    <div class="modal fade" id="safetyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=update_safety_stock">
                    <div class="modal-header">
                        <h5 class="modal-title">Set Safety Stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="product_id" id="safety_product_id">
                        <input type="hidden" name="warehouse_id" id="safety_warehouse_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <p id="safety_product_name" class="form-control-plaintext"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Warehouse</label>
                            <p id="safety_warehouse_name" class="form-control-plaintext"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Safety Stock Level</label>
                            <input type="number" name="safety_stock" id="safety_stock_input" class="form-control" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Safety Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Warehouse Modal -->
    <div class="modal fade" id="warehouseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="?action=create_warehouse">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Warehouse</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Warehouse Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Warehouse Code</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Warehouse</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function adjustStock(productId, warehouseId, productName, warehouseName) {
            document.getElementById('adjust_product_id').value = productId;
            document.getElementById('adjust_warehouse_id').value = warehouseId;
            document.getElementById('adjust_product_select').value = productId;
            document.getElementById('adjust_warehouse_select').value = warehouseId;
            
            // Update form to use hidden fields when pre-populated
            document.getElementById('adjust_product_select').style.display = 'none';
            document.getElementById('adjust_warehouse_select').style.display = 'none';
            
            var modal = new bootstrap.Modal(document.getElementById('adjustModal'));
            modal.show();
        }
        
        function setSafetyStock(productId, warehouseId, currentSafety, productName, warehouseName) {
            document.getElementById('safety_product_id').value = productId;
            document.getElementById('safety_warehouse_id').value = warehouseId;
            document.getElementById('safety_product_name').textContent = productName;
            document.getElementById('safety_warehouse_name').textContent = warehouseName;
            document.getElementById('safety_stock_input').value = currentSafety;
            
            var modal = new bootstrap.Modal(document.getElementById('safetyModal'));
            modal.show();
        }
        
        // Handle product/warehouse selection change
        document.getElementById('adjust_product_select').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('adjust_product_id').value = this.value;
            }
        });
        
        document.getElementById('adjust_warehouse_select').addEventListener('change', function() {
            if (this.value) {
                document.getElementById('adjust_warehouse_id').value = this.value;
            }
        });
    </script>
</body>
</html>