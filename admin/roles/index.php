<?php
/**
 * Role & Permission Management - Admin Module
 * RBAC Management System
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';

// Initialize with graceful fallback
require_once __DIR__ . '/../../includes/init.php';

requireAdminAuth();
checkPermission('roles.view');

$page_title = 'Roles & Permissions';
$action = $_GET['action'] ?? 'list';
$role_id = $_GET['id'] ?? null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
    } else {
        try {
            switch ($_POST['action']) {
                case 'create_role':
                    checkPermission('roles.create');
                    
                    $roleData = [
                        'name' => sanitizeInput($_POST['name']),
                        'display_name' => sanitizeInput($_POST['display_name']),
                        'description' => sanitizeInput($_POST['description']),
                        'level' => intval($_POST['level'])
                    ];
                    
                    // Get database connection
                    $pdo = db();
                    $stmt = $pdo->prepare(
                        "INSERT INTO roles (name, display_name, description, level, is_active, created_at) 
                         VALUES (?, ?, ?, ?, 1, NOW())"
                    );
                    $result = $stmt->execute(array_values($roleData));
                    
                    if ($result) {
                        $_SESSION['success_message'] = 'Role created successfully.';
                    } else {
                        $_SESSION['error_message'] = 'Failed to create role.';
                    }
                    break;
                    
                case 'update_role':
                    checkPermission('roles.edit');
                    
                    $roleId = intval($_POST['role_id']);
                    $roleData = [
                        'display_name' => sanitizeInput($_POST['display_name']),
                        'description' => sanitizeInput($_POST['description']),
                        'level' => intval($_POST['level']),
                        'is_active' => isset($_POST['is_active']) ? 1 : 0
                    ];
                    
                    $pdo = db();
                    $stmt = $pdo->prepare(
                        "UPDATE roles SET display_name = ?, description = ?, level = ?, is_active = ?, updated_at = NOW() 
                         WHERE id = ?"
                    );
                    $result = $stmt->execute(array_merge(array_values($roleData), [$roleId]));
                    
                    if ($result) {
                        $_SESSION['success_message'] = 'Role updated successfully.';
                    } else {
                        $_SESSION['error_message'] = 'Failed to update role.';
                    }
                    break;
                    
                case 'update_role_permissions':
                    checkPermission('roles.manage');
                    
                    $roleId = intval($_POST['role_id']);
                    $permissions = $_POST['permissions'] ?? [];
                    
                    $pdo = db();
                    $pdo->beginTransaction();
                    
                    try {
                        // Remove existing permissions
                        $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
                        $stmt->execute([$roleId]);
                        
                        // Add new permissions
                        foreach ($permissions as $permissionId) {
                            $stmt = $pdo->prepare(
                                "INSERT INTO role_permissions (role_id, permission_id, created_at) 
                                 VALUES (?, ?, NOW())"
                            );
                            $stmt->execute([$roleId, intval($permissionId)]);
                        }
                        
                        $pdo->commit();
                        $_SESSION['success_message'] = 'Role permissions updated successfully.';
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Role management error: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred while processing your request.';
        }
    }
    
    header('Location: /admin/roles/');
    exit;
}

// Get data from database
$pdo = db();
$roles = [];
$permissions = [];
$permissionsByModule = [];

try {
    // Get roles
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY level DESC, name");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get permissions
    $stmt = $pdo->query("SELECT * FROM permissions ORDER BY module, name");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($permissions as $permission) {
        $permissionsByModule[$permission['module']][] = $permission;
    }
} catch (Exception $e) {
    error_log("Error fetching roles/permissions: " . $e->getMessage());
    $_SESSION['error_message'] = 'Error loading data from database.';
}

// Get current role for editing
$currentRole = null;
$currentRolePermissions = [];
if ($action === 'edit' && $role_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        $currentRole = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentRole) {
            $stmt = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$role_id]);
            $currentRolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {
        error_log("Error fetching role details: " . $e->getMessage());
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
        }
        
        body { 
            background-color: #f8f9fa; 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .roles-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            align-items: start;
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
        
        .role-stats {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2563eb;
            display: block;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .permission-module {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: #f9fafb;
        }
        
        .permission-module h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3b82f6;
        }
        
        @media (max-width: 768px) {
            .roles-grid {
                grid-template-columns: 1fr;
            }
            
            .roles-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="roles-container">
        <div class="page-header">
            <h1><i class="fas fa-user-shield me-3"></i>Role & Permission Management</h1>
            <p>Manage user roles and permissions across the platform</p>
        </div>

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

        <?php if ($action === 'list'): ?>
        <!-- Enhanced Roles List -->
        <div class="roles-grid">
            <div class="dashboard-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5><i class="fas fa-users-cog me-2"></i>System Roles</h5>
                    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                        <i class="fas fa-plus me-2"></i>Create Role
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Role Details</th>
                                <th>Level</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($roles)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                                    <div class="h5 text-muted">No roles found</div>
                                    <p class="text-muted">Get started by creating your first role.</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($roles as $role): ?>
                                <?php
                                // Get actual user count for this role
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE role_id = ?");
                                    $stmt->execute([$role['id']]);
                                    $userCount = $stmt->fetchColumn();
                                } catch (Exception $e) {
                                    $userCount = 0;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="text-dark"><?php echo htmlspecialchars($role['display_name'] ?? 'Unknown'); ?></strong>
                                            <?php if (!empty($role['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($role['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-info fs-6"><?php echo $role['level'] ?? 1; ?></span></td>
                                    <td><span class="fw-bold text-primary"><?php echo number_format($userCount); ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($role['is_active'] ?? 1) ? 'success' : 'secondary'; ?>">
                                            <?php echo ($role['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?action=edit&id=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-key me-1"></i>Permissions
                                            </a>
                                            <?php if (($role['name'] ?? '') !== 'admin'): ?>
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Role Statistics Sidebar -->
            <div class="role-stats">
                <h5><i class="fas fa-chart-bar me-2"></i>Role Statistics</h5>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($roles); ?></span>
                    <span class="stat-label">Total Roles</span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo count(array_filter($roles, fn($r) => ($r['is_active'] ?? 1))); ?></span>
                    <span class="stat-label">Active Roles</span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($permissions); ?></span>
                    <span class="stat-label">Total Permissions</span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo count($permissionsByModule); ?></span>
                    <span class="stat-label">Permission Modules</span>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                        <i class="fas fa-plus me-2"></i>Create New Role
                    </button>
                </div>
            </div>
        </div>

        <?php elseif ($action === 'edit' && $currentRole): ?>
        <!-- Edit Role Permissions -->
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5>Edit Permissions for: <?php echo htmlspecialchars($currentRole['display_name'] ?? 'Unknown'); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($currentRole['description'] ?? ''); ?></p>
                </div>
                <a href="/admin/roles/" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Roles
                </a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="update_role_permissions">
                <input type="hidden" name="role_id" value="<?php echo $currentRole['id']; ?>">
                
                <?php if (empty($permissionsByModule)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Permissions Available</h5>
                        <p class="text-muted">No permissions have been configured in the system yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
                    <div class="permission-module">
                        <h6 class="text-primary text-uppercase fw-bold">
                            <?php echo ucfirst($module); ?> Module
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-2 select-all-btn" 
                                    data-module="<?php echo $module; ?>">
                                Select All
                            </button>
                        </h6>
                        <div class="row">
                            <?php foreach ($modulePermissions as $permission): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input permission-checkbox" 
                                           type="checkbox" 
                                           name="permissions[]" 
                                           value="<?php echo $permission['id']; ?>"
                                           id="perm_<?php echo $permission['id']; ?>"
                                           data-module="<?php echo $module; ?>"
                                           <?php echo in_array($permission['id'], $currentRolePermissions) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="perm_<?php echo $permission['id']; ?>">
                                        <strong><?php echo htmlspecialchars($permission['display_name'] ?? $permission['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($permission['description'] ?? 'No description'); ?></small>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Permissions
                    </button>
                    <a href="/admin/roles/" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="createRoleForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="create_role">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="createRoleModalLabel">Create New Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="role_name" class="form-label">Role Name (Internal) *</label>
                            <input type="text" class="form-control" id="role_name" name="name" required
                                   pattern="[a-z_]+" title="Lowercase letters and underscores only"
                                   placeholder="e.g. content_manager">
                        </div>
                        
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name *</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" required
                                   placeholder="e.g. Content Manager">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="Describe what this role can do..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="level" class="form-label">Level (1-10) *</label>
                            <input type="number" class="form-control" id="level" name="level" min="1" max="10" value="1" required>
                            <small class="form-text text-muted">Higher levels inherit permissions from lower levels</small>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="editRoleForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_display_name" class="form-label">Display Name *</label>
                            <input type="text" class="form-control" id="edit_display_name" name="display_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_level" class="form-label">Level (1-10) *</label>
                            <input type="number" class="form-control" id="edit_level" name="level" min="1" max="10" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                                <label class="form-check-label" for="edit_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function editRole(role) {
        document.getElementById("edit_role_id").value = role.id;
        document.getElementById("edit_display_name").value = role.display_name || "";
        document.getElementById("edit_description").value = role.description || "";
        document.getElementById("edit_level").value = role.level || 1;
        document.getElementById("edit_is_active").checked = (role.is_active || 1) == 1;
        
        const modal = new bootstrap.Modal(document.getElementById("editRoleModal"));
        modal.show();
    }

    // Handle select all buttons
    document.addEventListener("DOMContentLoaded", function() {
        // Select all functionality for permissions
        document.querySelectorAll('.select-all-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const module = this.dataset.module;
                const checkboxes = document.querySelectorAll(`input[data-module="${module}"]`);
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                checkboxes.forEach(cb => cb.checked = !allChecked);
                this.textContent = allChecked ? 'Select All' : 'Deselect All';
            });
        });
        
        // Form validation
        const createForm = document.getElementById('createRoleForm');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                const name = document.getElementById('role_name').value;
                const displayName = document.getElementById('display_name').value;
                
                if (!name || !displayName) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                // Auto-generate role name from display name if needed
                if (!name.match(/^[a-z_]+$/)) {
                    document.getElementById('role_name').value = displayName.toLowerCase().replace(/[^a-z0-9]/g, '_');
                }
            });
        }
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            });
        }, 5000);
    });
    </script>
</body>
</html>