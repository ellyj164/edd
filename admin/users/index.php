<?php
/**
 * User Management - Admin Module
 * Comprehensive User & Role Management System
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';

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
checkPermission('users.view');

$page_title = 'User Management';
$action = $_GET['action'] ?? 'list';
$user_id = $_GET['id'] ?? null;

// Initialize variables
$users = [];
$total_users = 0;
$error_message = '';
$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'pending_users' => 0,
    'seller_users' => 0,
    'customer_users' => 0
];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
    } else {
        try {
            if (!$database_available) {
                throw new Exception('Database connection required for this operation.');
            }
            
            switch ($_POST['action']) {
                case 'create_user':
                    checkPermission('users.create');
                    $username = trim($_POST['username'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $role = $_POST['role'] ?? 'customer';
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    
                    if (empty($username) || empty($email) || empty($password)) {
                        throw new Exception('Username, email, and password are required.');
                    }
                    
                    // Check if username or email exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        throw new Exception('Username or email already exists.');
                    }
                    
                    // Create user
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, email, password, role, first_name, last_name, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                    ");
                    $stmt->execute([$username, $email, $password_hash, $role, $first_name, $last_name]);
                    $user_id = $pdo->lastInsertId();
                    
                    logAuditEvent('user', $user_id, 'create', ['username' => $username, 'role' => $role]);
                    $message = 'User created successfully';
                    break;
                    
                case 'update_user':
                    checkPermission('users.edit');
                    $user_id = (int)$_POST['user_id'];
                    $email = trim($_POST['email'] ?? '');
                    $role = $_POST['role'] ?? 'customer';
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    $status = $_POST['status'] ?? 'active';
                    
                    // Check if email is taken by another user
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        throw new Exception('Email already exists for another user.');
                    }
                    
                    // Update user
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET email = ?, role = ?, first_name = ?, last_name = ?, status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $role, $first_name, $last_name, $status, $user_id]);
                    
                    logAuditEvent('user', $user_id, 'update', ['email' => $email, 'role' => $role]);
                    $message = 'User updated successfully';
                    break;
                    
                case 'suspend_user':
                    checkPermission('users.edit');
                    $user_id = (int)$_POST['user_id'];
                    
                    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', suspended = 1, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    logAuditEvent('user', $user_id, 'suspend');
                    $_SESSION['success_message'] = 'User suspended successfully';
                    break;
                    
                case 'unsuspend_user':
                case 'activate_user':
                    checkPermission('users.edit');
                    $user_id = (int)$_POST['user_id'];
                    
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active', suspended = 0, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    logAuditEvent('user', $user_id, 'activate');
                    $_SESSION['success_message'] = 'User activated successfully';
                    break;
                    
                case 'delete_user':
                    checkPermission('users.delete');
                    $user_id = (int)$_POST['user_id'];
                    
                    // Prevent deleting yourself
                    if ($user_id == $_SESSION['user_id']) {
                        throw new Exception('Cannot delete your own account.');
                    }
                    
                    // Soft delete by setting deleted_at timestamp
                    $stmt = $pdo->prepare("UPDATE users SET deleted_at = NOW(), status = 'deleted', updated_at = NOW() WHERE id = ?");
                    $stmt = $pdo->prepare("UPDATE users SET status = 'deleted', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    logAuditEvent('user', $user_id, 'delete');
                    $message = 'User deleted successfully';
                    break;
                    
                case 'bulk_import':
                    checkPermission('users.create');
                    
                    // Check if file was uploaded
                    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('Please select a valid CSV file to import.');
                    }
                    
                    $file = $_FILES['import_file']['tmp_name'];
                    $handle = fopen($file, 'r');
                    
                    if (!$handle) {
                        throw new Exception('Failed to open the uploaded file.');
                    }
                    
                    // Read CSV header
                    $header = fgetcsv($handle);
                    $required_columns = ['username', 'email', 'password'];
                    $optional_columns = ['role', 'first_name', 'last_name', 'status'];
                    
                    // Validate header
                    $missing_columns = array_diff($required_columns, $header);
                    if (!empty($missing_columns)) {
                        fclose($handle);
                        throw new Exception('CSV file is missing required columns: ' . implode(', ', $missing_columns));
                    }
                    
                    // Map column names to indexes
                    $column_map = array_flip($header);
                    
                    $imported_count = 0;
                    $skipped_count = 0;
                    $errors = [];
                    
                    // Begin transaction for bulk insert
                    $pdo->beginTransaction();
                    
                    try {
                        while (($row = fgetcsv($handle)) !== false) {
                            // Skip empty rows
                            if (empty(array_filter($row))) {
                                continue;
                            }
                            
                            $username = trim($row[$column_map['username']] ?? '');
                            $email = trim($row[$column_map['email']] ?? '');
                            $password = $row[$column_map['password']] ?? '';
                            $role = isset($column_map['role']) ? ($row[$column_map['role']] ?? 'customer') : 'customer';
                            $first_name = isset($column_map['first_name']) ? trim($row[$column_map['first_name']] ?? '') : '';
                            $last_name = isset($column_map['last_name']) ? trim($row[$column_map['last_name']] ?? '') : '';
                            $status = isset($column_map['status']) ? ($row[$column_map['status']] ?? 'active') : 'active';
                            
                            // Validate required fields
                            if (empty($username) || empty($email) || empty($password)) {
                                $skipped_count++;
                                $errors[] = "Row skipped: Missing required fields for {$username}";
                                continue;
                            }
                            
                            // Validate email format
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $skipped_count++;
                                $errors[] = "Row skipped: Invalid email format for {$email}";
                                continue;
                            }
                            
                            // Check if user already exists
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                            $stmt->execute([$username, $email]);
                            if ($stmt->fetch()) {
                                $skipped_count++;
                                $errors[] = "Row skipped: User {$username} or email {$email} already exists";
                                continue;
                            }
                            
                            // Hash password
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert user
                            $stmt = $pdo->prepare("
                                INSERT INTO users (username, email, password, role, first_name, last_name, status, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->execute([$username, $email, $password_hash, $role, $first_name, $last_name, $status]);
                            
                            $imported_count++;
                        }
                        
                        $pdo->commit();
                        fclose($handle);
                        
                        logAuditEvent('users', null, 'bulk_import', [
                            'imported' => $imported_count,
                            'skipped' => $skipped_count
                        ]);
                        
                        $message = "Import complete: {$imported_count} users imported";
                        if ($skipped_count > 0) {
                            $message .= ", {$skipped_count} rows skipped";
                        }
                        if (!empty($errors) && count($errors) <= 10) {
                            $message .= ". Errors: " . implode("; ", $errors);
                        }
                        
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        fclose($handle);
                        throw new Exception('Import failed: ' . $e->getMessage());
                    }
                    break;
            }
            $_SESSION['success_message'] = $message ?? 'Action completed successfully';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
    
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Handle export action (GET request)
if ($action === 'export' && $database_available) {
    checkPermission('users.view');
    
    try {
        // Get all users
        $stmt = $pdo->query("
            SELECT username, email, role, first_name, last_name, status, 
                   DATE_FORMAT(created_at, '%Y-%m-%d') as created_date,
                   DATE_FORMAT(last_login_at, '%Y-%m-%d %H:%i:%s') as last_login
            FROM users 
            WHERE status != 'deleted'
            ORDER BY created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
        
        // Output CSV
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['Username', 'Email', 'Role', 'First Name', 'Last Name', 'Status', 'Created Date', 'Last Login']);
        
        // Add data rows
        foreach ($users as $user) {
            fputcsv($output, [
                $user['username'],
                $user['email'],
                $user['role'],
                $user['first_name'] ?? '',
                $user['last_name'] ?? '',
                $user['status'],
                $user['created_date'] ?? '',
                $user['last_login'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Export failed: ' . $e->getMessage();
        header('Location: /admin/users/');
        exit;
    }
}

// Get data from database
if (!$database_available) {
    $error_message = "Database connection required. Please check your database configuration.";
    $users = [];
} else {
    try {
        // Get users with proper error handling
        $usersQuery = "
            SELECT u.*, 
                   COALESCE(u.role, 'customer') as role,
                   COALESCE(u.status, 'active') as status,
                   COALESCE(u.created_at, NOW()) as created_at,
                   COALESCE(u.last_login_at, u.created_at) as last_login_at
            FROM users u 
            WHERE u.status != 'deleted'
            ORDER BY u.created_at DESC 
            LIMIT 100
        ";
        
        $usersStmt = $pdo->prepare($usersQuery);
        $usersStmt->execute();
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        $total_users = count($users);
        
        // Calculate stats
        foreach ($users as $user) {
            $stats['total_users']++;
            if (($user['status'] ?? 'active') === 'active') {
                $stats['active_users']++;
            } elseif (($user['status'] ?? 'active') === 'pending') {
                $stats['pending_users']++;
            }
            
            if (($user['role'] ?? 'customer') === 'seller') {
                $stats['seller_users']++;
            } else {
                $stats['customer_users']++;
            }
        }
    } catch (Exception $e) {
        $error_message = "Database query failed: " . $e->getMessage();
        $users = [];
        error_log("User management query error: " . $e->getMessage());
    }
}

// Get current user for edit/view
$currentUser = null;
if (($action === 'edit' || $action === 'view') && $user_id) {
    foreach ($users as $user) {
        if ($user['id'] == $user_id) {
            $currentUser = $user;
            break;
        }
    }
    if (!$currentUser) {
        $_SESSION['error_message'] = 'User not found.';
        header('Location: /admin/users/');
        exit;
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
        
        .page-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .user-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active { background-color: #d4edda; color: #155724; }
        .status-inactive { background-color: #f8d7da; color: #721c24; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-admin { background-color: #e7f3ff; color: #0066cc; }
        .role-seller { background-color: #fff0e6; color: #cc6600; }
        .role-customer { background-color: #f0f0f0; color: #666666; }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            margin-right: 1rem;
            opacity: 0.8;
        }
        
        .stats-content h3 {
            font-size: 2rem;
            margin: 0;
            font-weight: 700;
        }
        
        .stats-content p {
            margin: 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .user-info {
            font-size: 0.85rem;
        }
        
        .avatar-placeholder {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent)) !important;
        }
        
        .table-actions {
            white-space: nowrap;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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
                        <i class="fas fa-users me-2"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <p class="mb-0 opacity-75">Manage users, roles, and permissions across the platform</p>
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

        <?php if ($error_message): ?>
            <div class="alert alert-danger border-0 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <h6 class="mb-1">Database Error</h6>
                        <p class="mb-0"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <!-- Enhanced Stats Dashboard -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card bg-primary text-white">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-success text-white">
                    <div class="stats-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['active_users']); ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-warning text-white">
                    <div class="stats-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['pending_users']); ?></h3>
                        <p>Pending Users</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card bg-info text-white">
                    <div class="stats-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format($stats['seller_users']); ?></h3>
                        <p>Sellers</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Page Actions -->
        <div class="page-actions">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">User Management</h4>
                    <small class="text-muted">Manage all platform users and their permissions</small>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="btn-group">
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Add User
                        </a>
                        <button class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="exportUsers(); return false;"><i class="fas fa-download me-2"></i>Export Users</a></li>
                            <li><a class="dropdown-item" href="#" onclick="showImportModal(); return false;"><i class="fas fa-upload me-2"></i>Import Users</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="showBulkActionsModal(); return false;"><i class="fas fa-shield-alt me-2"></i>Bulk Actions</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Users Grid -->
        <div class="row">
            <?php if (empty($users)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h5>No Users Found</h5>
                            <p>Get started by adding your first user to the system.</p>
                            <a href="?action=create" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Add First User
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <div class="col-lg-6 col-xl-4">
                        <div class="user-card">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="d-flex">
                                    <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" 
                                         style="width: 50px; height: 50px; font-size: 1.2rem; font-weight: 600;">
                                        <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars(($user['first_name'] ?? 'Unknown') . ' ' . ($user['last_name'] ?? 'User')); ?>
                                        </h6>
                                        <p class="text-muted mb-0 small">@<?php echo htmlspecialchars($user['username'] ?? 'unknown'); ?></p>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link btn-sm" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="?action=edit&id=<?php echo $user['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a></li>
                                        <li><a class="dropdown-item" href="?action=view&id=<?php echo $user['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php if (($user['status'] ?? 'active') === 'active'): ?>
                                            <li><a class="dropdown-item text-warning" href="#" onclick="suspendUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-pause me-2"></i>Suspend
                                            </a></li>
                                        <?php else: ?>
                                            <li><a class="dropdown-item text-success" href="#" onclick="activateUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-play me-2"></i>Activate
                                            </a></li>
                                        <?php endif; ?>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash me-2"></i>Delete
                                        </a></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="user-info">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Email:</span>
                                    <span class="small"><?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Role:</span>
                                    <span class="role-badge role-<?php echo $user['role'] ?? 'customer'; ?>">
                                        <?php echo ucfirst($user['role'] ?? 'customer'); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Status:</span>
                                    <span class="status-badge status-<?php echo $user['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small">Joined:</span>
                                    <span class="small"><?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                                </div>
                                <?php if ($user['last_login_at'] ?? null): ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Last Login:</span>
                                        <span class="small"><?php echo date('M j, Y g:i A', strtotime($user['last_login_at'])); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small">Last Login:</span>
                                        <span class="small text-muted">Never</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (($user['status'] ?? 'active') === 'pending'): ?>
                                <div class="mt-3 pt-3 border-top">
                                    <button class="btn btn-success btn-sm me-2" onclick="approveUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-check me-1"></i>Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rejectUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-times me-1"></i>Reject
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <!-- User Form -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action === 'create' ? 'Add New User' : 'Edit User'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="<?php echo $action === 'create' ? 'create_user' : 'update_user'; ?>">
                    <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="user_id" value="<?php echo $currentUser['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Role *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="customer" <?php echo ($currentUser['role'] ?? '') === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="seller" <?php echo ($currentUser['role'] ?? '') === 'seller' ? 'selected' : ''; ?>>Seller</option>
                                    <option value="support" <?php echo ($currentUser['role'] ?? '') === 'support' ? 'selected' : ''; ?>>Support</option>
                                    <option value="admin" <?php echo ($currentUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="active" <?php echo ($currentUser['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo ($currentUser['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="suspended" <?php echo ($currentUser['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    <option value="inactive" <?php echo ($currentUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password <?php echo $action === 'create' ? '*' : '(leave blank to keep current)'; ?>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $action === 'create' ? 'required' : ''; ?>>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?php echo $action === 'create' ? 'Create User' : 'Update User'; ?>
                            </button>
                            <a href="/admin/users/" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php elseif ($action === 'view' && $currentUser): ?>
        <!-- User Details View -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">User Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Username:</strong></td>
                                        <td><?php echo htmlspecialchars($currentUser['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email:</strong></td>
                                        <td><?php echo htmlspecialchars($currentUser['email']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Full Name:</strong></td>
                                        <td><?php echo htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>Role:</strong></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst($currentUser['role']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status:</strong></td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'active' => 'success',
                                                'pending' => 'warning',
                                                'suspended' => 'danger',
                                                'inactive' => 'secondary'
                                            ][$currentUser['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($currentUser['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Joined:</strong></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($currentUser['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="?action=edit&id=<?php echo $currentUser['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> Edit User
                            </a>
                            <a href="/admin/users/" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-info" onclick="alert('Demo: KYC feature would open here')">
                                <i class="fas fa-id-card me-1"></i> View KYC
                            </button>
                            <button class="btn btn-outline-success" onclick="alert('Demo: Orders feature would open here')">
                                <i class="fas fa-shopping-cart me-1"></i> View Orders
                            </button>
                            <button class="btn btn-outline-warning" onclick="alert('Demo: Support tickets would open here')">
                                <i class="fas fa-headset me-1"></i> Support Tickets
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced User Management JavaScript -->
    <script>
    // User management functions
    function editUser(userId) {
        window.location.href = `?action=edit&id=${userId}`;
    }
    
    function viewUser(userId) {
        window.location.href = `?action=view&id=${userId}`;
    }
    
    function suspendUser(userId) {
        if (confirm('Are you sure you want to suspend this user?')) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="suspend_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function activateUser(userId) {
        if (confirm('Are you sure you want to activate this user?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function deleteUser(userId) {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function approveUser(userId) {
        if (confirm('Approve this user account?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function rejectUser(userId) {
        if (confirm('Reject this user account? They will need to re-register.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function exportUsers() {
        // Create a CSV export
        window.location.href = '?action=export';
    }
    
    function showImportModal() {
        // Create and show import modal
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'importModal';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Import Users</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                            <input type="hidden" name="action" value="bulk_import">
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>CSV Format Required:</strong><br>
                                The CSV file must have the following columns:<br>
                                <code>username,email,password,role,first_name,last_name,status</code><br>
                                <small>Only username, email, and password are required. Role defaults to 'customer', status defaults to 'active'.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Select CSV File</label>
                                <input type="file" name="import_file" class="form-control" accept=".csv" required>
                            </div>
                            
                            <div class="mb-3">
                                <a href="#" onclick="downloadTemplate(); return false;" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download Template
                                </a>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i>Import Users
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Clean up modal after it's hidden
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
    }
    
    function downloadTemplate() {
        // Create CSV template
        const csv = 'username,email,password,role,first_name,last_name,status\n' +
                    'john_doe,john@example.com,Password123,customer,John,Doe,active\n' +
                    'jane_seller,jane@example.com,SecurePass456,seller,Jane,Smith,active';
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'user_import_template.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    function showBulkActionsModal() {
        alert('Bulk actions feature coming soon! You can select multiple users and perform actions like activate, suspend, or delete.');
    }
    
    // Add search functionality on page load
    document.addEventListener('DOMContentLoaded', function() {
        console.log('User Management loaded with <?php echo count($users); ?> users');
        
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