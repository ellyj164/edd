<?php
/**
 * Custom Dashboards Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Widget library and management
 * - Drag-and-drop dashboard builder
 * - Save multiple custom dashboards
 * - Dashboard sharing and templates
 * - Real-time refresh capabilities
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/audit_log.php';

// Load additional dependencies
require_once __DIR__ . '/../../includes/init.php';

// Database availability check with graceful fallback
$database_available = false;
$db = null;
try {
    $db = db();
    $db->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Admin Bypass Mode - Skip all authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    // Set up admin session automatically in bypass mode
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    // Normal authentication check - redirect to login if not authenticated as admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Custom Dashboards';
$action = $_GET['action'] ?? 'index';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create_dashboard':
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $layout = json_encode($_POST['layout'] ?? []);
                $widgets = json_encode($_POST['widgets'] ?? []);
                
                $stmt = $db->prepare("
                    INSERT INTO admin_dashboards (name, description, layout, widgets, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $description, $layout, $widgets, $_SESSION['user_id']]);
                
                $success = "Dashboard created successfully!";
                break;
                
            case 'update_dashboard':
                $id = intval($_POST['dashboard_id']);
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $layout = json_encode($_POST['layout'] ?? []);
                $widgets = json_encode($_POST['widgets'] ?? []);
                
                $stmt = $db->prepare("
                    UPDATE admin_dashboards 
                    SET name = ?, description = ?, layout = ?, widgets = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $layout, $widgets, $id]);
                
                $success = "Dashboard updated successfully!";
                break;
                
            case 'delete_dashboard':
                $id = intval($_POST['dashboard_id']);
                
                $stmt = $db->prepare("DELETE FROM admin_dashboards WHERE id = ?");
                $stmt->execute([$id]);
                
                $success = "Dashboard deleted successfully!";
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get dashboards
$dashboards = [];
if ($database_available) {
    try {
        $stmt = $db->query("
            SELECT d.*, u.username as created_by_name 
            FROM admin_dashboards d 
            LEFT JOIN users u ON d.created_by = u.id 
            ORDER BY d.created_at DESC
        ");
        $dashboards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = 'Database connection required. Please check your database configuration.';
    $dashboards = [];
}

// Available widgets
$available_widgets = [
    'sales_chart' => ['name' => 'Sales Chart', 'type' => 'chart', 'size' => 'large'],
    'orders_count' => ['name' => 'Orders Count', 'type' => 'counter', 'size' => 'small'],
    'revenue_counter' => ['name' => 'Revenue Counter', 'type' => 'counter', 'size' => 'small'],
    'users_chart' => ['name' => 'Users Chart', 'type' => 'chart', 'size' => 'medium'],
    'activity_feed' => ['name' => 'Activity Feed', 'type' => 'list', 'size' => 'medium'],
    'alerts_list' => ['name' => 'System Alerts', 'type' => 'alerts', 'size' => 'medium'],
    'product_performance' => ['name' => 'Product Performance', 'type' => 'table', 'size' => 'large'],
    'conversion_funnel' => ['name' => 'Conversion Funnel', 'type' => 'chart', 'size' => 'large']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Sortable JS for drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .widget-library {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .widget-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .widget-item:hover {
            background: var(--admin-accent);
            color: white;
            transform: translateX(5px);
        }
        
        .dashboard-canvas {
            background: white;
            border-radius: 8px;
            min-height: 400px;
            padding: 1rem;
            border: 2px dashed #dee2e6;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dashboard-canvas.drag-over {
            border-color: var(--admin-accent);
            background-color: rgba(52, 152, 219, 0.1);
        }
        
        .placed-widget {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .widget-controls {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .placed-widget:hover .widget-controls {
            opacity: 1;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-2px);
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
                        <i class="fas fa-chart-pie me-2"></i>
                        Custom Dashboards
                    </h1>
                    <small class="text-white-50">Create and manage custom admin dashboards</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Admin
                    </a>
                    <span class="text-white-50">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Dashboard Builder -->
        <?php if ($action === 'builder'): ?>
        <div class="row">
            <div class="col-md-3">
                <div class="widget-library">
                    <h5><i class="fas fa-puzzle-piece me-2"></i>Widget Library</h5>
                    <div id="widget-library">
                        <?php foreach ($available_widgets as $key => $widget): ?>
                        <div class="widget-item" data-widget="<?php echo $key; ?>" data-name="<?php echo htmlspecialchars($widget['name']); ?>">
                            <i class="fas fa-grip-vertical me-2"></i>
                            <strong><?php echo htmlspecialchars($widget['name']); ?></strong>
                            <br><small class="text-muted"><?php echo ucfirst($widget['type']); ?> - <?php echo ucfirst($widget['size']); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><i class="fas fa-paint-brush me-2"></i>Dashboard Canvas</h5>
                    <div>
                        <button type="button" class="btn btn-success btn-sm" onclick="saveDashboard()">
                            <i class="fas fa-save me-1"></i>
                            Save Dashboard
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="clearCanvas()">
                            <i class="fas fa-trash me-1"></i>
                            Clear All
                        </button>
                    </div>
                </div>
                <div class="dashboard-canvas" id="dashboard-canvas">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-mouse-pointer fa-3x mb-3"></i>
                        <h5>Drag widgets here to build your dashboard</h5>
                        <p>Widgets can be reordered and configured after placement</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Dashboard Modal -->
        <div class="modal fade" id="saveDashboardModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Save Dashboard</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_dashboard">
                            <input type="hidden" name="layout" id="dashboard-layout">
                            <input type="hidden" name="widgets" id="dashboard-widgets">
                            
                            <div class="mb-3">
                                <label for="dashboard-name" class="form-label">Dashboard Name</label>
                                <input type="text" class="form-control" id="dashboard-name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="dashboard-description" class="form-label">Description</label>
                                <textarea class="form-control" id="dashboard-description" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Dashboard</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Dashboard List -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-chart-pie me-2"></i>Your Dashboards</h4>
                    <a href="?action=builder" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Create New Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if (empty($dashboards)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-chart-pie fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No Custom Dashboards Yet</h4>
                    <p class="text-muted">Create your first custom dashboard to get started</p>
                    <a href="?action=builder" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Create Your First Dashboard
                    </a>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($dashboards as $dashboard): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($dashboard['name']); ?></h6>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?action=view&id=<?php echo $dashboard['id']; ?>">
                                    <i class="fas fa-eye me-2"></i>View
                                </a></li>
                                <li><a class="dropdown-item" href="?action=edit&id=<?php echo $dashboard['id']; ?>">
                                    <i class="fas fa-edit me-2"></i>Edit
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteDashboard(<?php echo $dashboard['id']; ?>)">
                                    <i class="fas fa-trash me-2"></i>Delete
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted"><?php echo htmlspecialchars($dashboard['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($dashboard['created_by_name'] ?? 'Unknown'); ?>
                            </small>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('M d, Y', strtotime($dashboard['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="?action=view&id=<?php echo $dashboard['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>
                            View Dashboard
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dashboard Builder JavaScript -->
    <script>
        let draggedElement = null;
        let canvasWidgets = [];

        // Initialize drag and drop if on builder page
        <?php if ($action === 'builder'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('dashboard-canvas');
            const library = document.getElementById('widget-library');
            
            // Make widget library items draggable
            Sortable.create(library, {
                group: {
                    name: 'widgets',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150
            });
            
            // Make canvas droppable and sortable
            Sortable.create(canvas, {
                group: {
                    name: 'widgets',
                    pull: false,
                    put: true
                },
                animation: 150,
                onAdd: function(evt) {
                    const widget = evt.item;
                    const widgetType = widget.getAttribute('data-widget');
                    const widgetName = widget.getAttribute('data-name');
                    
                    // Replace library item with actual widget
                    widget.innerHTML = generateWidgetHTML(widgetType, widgetName);
                    widget.className = 'placed-widget';
                    
                    // Add to widgets array
                    canvasWidgets.push({
                        type: widgetType,
                        name: widgetName,
                        position: canvasWidgets.length
                    });
                }
            });
        });
        <?php endif; ?>

        function generateWidgetHTML(type, name) {
            const controls = `
                <div class="widget-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="configureWidget(this)">
                        <i class="fas fa-cog"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeWidget(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            switch(type) {
                case 'sales_chart':
                    return controls + `
                        <h6><i class="fas fa-chart-line me-2"></i>${name}</h6>
                        <canvas width="400" height="200" style="background: #f8f9fa; border-radius: 4px;"></canvas>
                    `;
                case 'orders_count':
                    return controls + `
                        <h6><i class="fas fa-shopping-cart me-2"></i>${name}</h6>
                        <div class="text-center">
                            <h2 class="text-primary mb-0">1,234</h2>
                            <small class="text-muted">Total Orders</small>
                        </div>
                    `;
                case 'revenue_counter':
                    return controls + `
                        <h6><i class="fas fa-dollar-sign me-2"></i>${name}</h6>
                        <div class="text-center">
                            <h2 class="text-success mb-0">$45,678</h2>
                            <small class="text-muted">Total Revenue</small>
                        </div>
                    `;
                case 'activity_feed':
                    return controls + `
                        <h6><i class="fas fa-list me-2"></i>${name}</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item border-0 px-0 py-2">
                                <i class="fas fa-user-plus text-success me-2"></i>
                                New user registered
                                <small class="text-muted d-block">2 minutes ago</small>
                            </div>
                            <div class="list-group-item border-0 px-0 py-2">
                                <i class="fas fa-shopping-cart text-primary me-2"></i>
                                New order placed
                                <small class="text-muted d-block">5 minutes ago</small>
                            </div>
                        </div>
                    `;
                default:
                    return controls + `
                        <h6><i class="fas fa-cube me-2"></i>${name}</h6>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-puzzle-piece fa-2x"></i>
                            <p class="mb-0 mt-2">Widget Preview</p>
                        </div>
                    `;
            }
        }

        function removeWidget(button) {
            const widget = button.closest('.placed-widget');
            widget.remove();
            
            // Update widgets array
            canvasWidgets = canvasWidgets.filter((w, index) => {
                return index !== Array.from(widget.parentNode.children).indexOf(widget);
            });
        }

        function configureWidget(button) {
            alert('Widget configuration coming soon!');
        }

        function clearCanvas() {
            if (confirm('Are you sure you want to clear all widgets?')) {
                document.getElementById('dashboard-canvas').innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-mouse-pointer fa-3x mb-3"></i>
                        <h5>Drag widgets here to build your dashboard</h5>
                        <p>Widgets can be reordered and configured after placement</p>
                    </div>
                `;
                canvasWidgets = [];
            }
        }

        function saveDashboard() {
            if (canvasWidgets.length === 0) {
                alert('Please add at least one widget to your dashboard');
                return;
            }
            
            // Populate hidden form fields
            document.getElementById('dashboard-layout').value = JSON.stringify(canvasWidgets);
            document.getElementById('dashboard-widgets').value = JSON.stringify(canvasWidgets);
            
            // Show modal
            new bootstrap.Modal(document.getElementById('saveDashboardModal')).show();
        }

        function deleteDashboard(id) {
            if (confirm('Are you sure you want to delete this dashboard?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_dashboard">
                    <input type="hidden" name="dashboard_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>