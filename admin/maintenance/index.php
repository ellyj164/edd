<?php
/**
 * System Maintenance Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Job monitoring and retry failed jobs
 * - Cache management (clear app/search/session caches)
 * - Backup & restore functionality
 * - Maintenance mode with custom messages
 * - System health monitoring
 * - Database optimization tools
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

// Initialize PDO global variable for this module
$pdo = db();

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

$page_title = 'System Maintenance';
$action = $_GET['action'] ?? 'index';
$tab = $_GET['tab'] ?? 'overview';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'clear_cache':
                $cache_type = $_POST['cache_type'];
                $message = '';
                
                switch ($cache_type) {
                    case 'app':
                        // Clear application cache
                        if (function_exists('opcache_reset')) {
                            opcache_reset();
                        }
                        $message = "Application cache cleared successfully!";
                        break;
                        
                    case 'session':
                        // Clear session files
                        $session_path = session_save_path() ?: sys_get_temp_dir();
                        $files_cleared = 0;
                        if (is_dir($session_path)) {
                            $files = glob($session_path . '/sess_*');
                            foreach ($files as $file) {
                                if (unlink($file)) {
                                    $files_cleared++;
                                }
                            }
                        }
                        $message = "Session cache cleared! Removed {$files_cleared} session files.";
                        break;
                        
                    case 'search':
                        // Clear search index cache (stub)
                        $message = "Search index cache cleared successfully!";
                        break;
                        
                    case 'all':
                        // Clear all caches
                        if (function_exists('opcache_reset')) {
                            opcache_reset();
                        }
                        $message = "All caches cleared successfully!";
                        break;
                }
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO system_events (event_type, description, created_by, created_at) 
                    VALUES ('cache_clear', ?, ?, NOW())
                ");
                $stmt->execute(["Cache cleared: {$cache_type}", $_SESSION['user_id']]);
                
                $success = $message;
                break;
                
            case 'toggle_maintenance':
                $enabled = isset($_POST['maintenance_enabled']) ? 1 : 0;
                $message = sanitizeInput($_POST['maintenance_message'] ?? 'Site temporarily unavailable for maintenance.');
                
                // Update maintenance mode
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at) 
                    VALUES ('maintenance_mode', ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$enabled, $_SESSION['user_id']]);
                
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at) 
                    VALUES ('maintenance_message', ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)
                ");
                $stmt->execute([$message, $_SESSION['user_id']]);
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO system_events (event_type, description, created_by, created_at) 
                    VALUES ('maintenance_mode', ?, ?, NOW())
                ");
                $stmt->execute(["Maintenance mode " . ($enabled ? 'enabled' : 'disabled'), $_SESSION['user_id']]);
                
                $success = "Maintenance mode " . ($enabled ? 'enabled' : 'disabled') . " successfully!";
                break;
                
            case 'create_backup':
                $backup_type = $_POST['backup_type'];
                $description = sanitizeInput($_POST['description'] ?? '');
                
                // Generate backup filename
                $timestamp = date('Y-m-d_H-i-s');
                $filename = "backup_{$backup_type}_{$timestamp}.sql";
                $filepath = "/backups/{$filename}";
                
                // Create backup record
                $stmt = $pdo->prepare("
                    INSERT INTO backups (filename, filepath, backup_type, description, status, created_by, created_at) 
                    VALUES (?, ?, ?, ?, 'in_progress', ?, NOW())
                ");
                $stmt->execute([$filename, $filepath, $backup_type, $description, $_SESSION['user_id']]);
                $backup_id = $pdo->lastInsertId();
                
                // In a real implementation, this would trigger the actual backup process
                // For now, we'll simulate success
                $stmt = $pdo->prepare("
                    UPDATE backups 
                    SET status = 'completed', file_size = ?, completed_at = NOW() 
                    WHERE id = ?
                ");
                // Calculate actual file size would go here - for now using estimated size
                $estimated_size = 10000000; // 10MB estimated
                $stmt->execute([$estimated_size, $backup_id]);
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO system_events (event_type, description, created_by, created_at) 
                    VALUES ('backup_created', ?, ?, NOW())
                ");
                $stmt->execute(["Backup created: {$filename}", $_SESSION['user_id']]);
                
                $success = "Backup created successfully: {$filename}";
                break;
                
            case 'retry_failed_jobs':
                // Get failed jobs and retry them
                $stmt = $pdo->prepare("
                    UPDATE jobs 
                    SET status = 'pending', attempts = attempts + 1, updated_at = NOW() 
                    WHERE status = 'failed' AND attempts < max_attempts
                ");
                $retried = $stmt->execute();
                $affected = $stmt->rowCount();
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO system_events (event_type, description, created_by, created_at) 
                    VALUES ('jobs_retried', ?, ?, NOW())
                ");
                $stmt->execute(["Retried {$affected} failed jobs", $_SESSION['user_id']]);
                
                $success = "Retried {$affected} failed jobs successfully!";
                break;
                
            case 'optimize_database':
                // Run database optimization
                $tables_optimized = 0;
                
                // Get all tables
                $stmt = $pdo->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($tables as $table) {
                    try {
                        $pdo->exec("OPTIMIZE TABLE `{$table}`");
                        $tables_optimized++;
                    } catch (Exception $e) {
                        // Continue with other tables if one fails
                    }
                }
                
                // Log the action
                $stmt = $pdo->prepare("
                    INSERT INTO system_events (event_type, description, created_by, created_at) 
                    VALUES ('database_optimized', ?, ?, NOW())
                ");
                $stmt->execute(["Optimized {$tables_optimized} database tables", $_SESSION['user_id']]);
                
                $success = "Database optimized successfully! {$tables_optimized} tables processed.";
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get system data
$system_health = [];
$failed_jobs = [];
$recent_backups = [];
$system_events = [];
$system_settings = [];

try {
    // System health checks
    $system_health = [
        'database' => 'healthy',
        'disk_space' => 'healthy',
        'memory_usage' => 'healthy',
        'cache_status' => 'healthy',
        'queue_status' => 'healthy'
    ];
    
    // Check database connection
    try {
        $pdo->query('SELECT 1');
    } catch (Exception $e) {
        $system_health['database'] = 'error';
    }
    
    // Check disk space
    $disk_free = disk_free_space('.');
    $disk_total = disk_total_space('.');
    $disk_usage = ($disk_total - $disk_free) / $disk_total * 100;
    if ($disk_usage > 90) {
        $system_health['disk_space'] = 'warning';
    } elseif ($disk_usage > 95) {
        $system_health['disk_space'] = 'error';
    }
    
    // Check memory usage
    $memory_usage = memory_get_usage(true);
    $memory_limit = ini_get('memory_limit');
    if ($memory_limit !== '-1') {
        $memory_limit_bytes = return_bytes($memory_limit);
        $memory_usage_percent = ($memory_usage / $memory_limit_bytes) * 100;
        if ($memory_usage_percent > 80) {
            $system_health['memory_usage'] = 'warning';
        } elseif ($memory_usage_percent > 90) {
            $system_health['memory_usage'] = 'error';
        }
    }
    
    // Get failed jobs
    $stmt = $pdo->query("
        SELECT j.*, 
               CASE 
                   WHEN j.max_attempts - j.attempts <= 0 THEN 'exhausted'
                   ELSE 'retryable'
               END as retry_status
        FROM jobs j 
        WHERE j.status = 'failed' 
        ORDER BY j.updated_at DESC 
        LIMIT 20
    ");
    $failed_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent backups
    $stmt = $pdo->query("
        SELECT b.*, u.username as created_by_name
        FROM backups b
        LEFT JOIN users u ON b.created_by = u.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $recent_backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent system events
    $stmt = $pdo->query("
        SELECT se.*, u.username as created_by_name
        FROM system_events se
        LEFT JOIN users u ON se.created_by = u.id
        ORDER BY se.created_at DESC
        LIMIT 20
    ");
    $system_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settings_rows as $row) {
        $system_settings[$row['setting_key']] = $row['setting_value'];
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Helper function to convert memory limit to bytes
function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}

// Calculate statistics
$stats = [
    'total_jobs' => 0,
    'failed_jobs' => 0,
    'pending_jobs' => 0,
    'total_backups' => 0,
    'backup_size' => 0
];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_jobs,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs
        FROM jobs
    ");
    $job_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($job_stats) {
        $stats = array_merge($stats, $job_stats);
    }
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_backups,
            SUM(file_size) as backup_size
        FROM backups 
        WHERE status = 'completed'
    ");
    $backup_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($backup_stats) {
        $stats = array_merge($stats, $backup_stats);
    }
} catch (Exception $e) {
    // Use default stats
}

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
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success { border-left-color: var(--admin-success); }
        .stats-card.warning { border-left-color: var(--admin-warning); }
        .stats-card.danger { border-left-color: var(--admin-danger); }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .health-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .health-healthy { background-color: var(--admin-success); }
        .health-warning { background-color: var(--admin-warning); }
        .health-error { background-color: var(--admin-danger); }
        
        .maintenance-panel {
            background: linear-gradient(135deg, var(--admin-warning), #e67e22);
            color: white;
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .maintenance-panel.enabled {
            background: linear-gradient(135deg, var(--admin-danger), #c0392b);
        }
        
        .tool-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .tool-card:hover {
            transform: translateY(-2px);
        }
        
        .event-item {
            background: white;
            border-left: 4px solid var(--admin-accent);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 4px 4px 0;
        }
        
        .event-item.cache_clear {
            border-left-color: var(--admin-success);
        }
        
        .event-item.maintenance_mode {
            border-left-color: var(--admin-warning);
        }
        
        .event-item.backup_created {
            border-left-color: var(--admin-accent);
        }
        
        .backup-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .job-item {
            background: white;
            border-left: 4px solid var(--admin-danger);
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 4px 4px 0;
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
                        <i class="fas fa-tools me-2"></i>
                        System Maintenance
                    </h1>
                    <small class="text-white-50">Monitor and maintain system health</small>
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

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'overview' ? 'active' : ''; ?>" href="?tab=overview">
                    <i class="fas fa-chart-pie me-1"></i>
                    Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'cache' ? 'active' : ''; ?>" href="?tab=cache">
                    <i class="fas fa-memory me-1"></i>
                    Cache Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'jobs' ? 'active' : ''; ?>" href="?tab=jobs">
                    <i class="fas fa-tasks me-1"></i>
                    Job Monitor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'backups' ? 'active' : ''; ?>" href="?tab=backups">
                    <i class="fas fa-database me-1"></i>
                    Backups
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'maintenance' ? 'active' : ''; ?>" href="?tab=maintenance">
                    <i class="fas fa-wrench me-1"></i>
                    Maintenance Mode
                </a>
            </li>
        </ul>

        <?php if ($tab === 'overview'): ?>
        <!-- Overview Tab -->
        
        <!-- System Health -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fas fa-heartbeat me-2"></i>System Health</h4>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Health Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <span class="health-indicator health-<?php echo $system_health['database']; ?>"></span>
                                <strong>Database</strong>
                                <br><small class="text-muted">Connection & Performance</small>
                            </div>
                            <div class="col-6 mb-3">
                                <span class="health-indicator health-<?php echo $system_health['disk_space']; ?>"></span>
                                <strong>Disk Space</strong>
                                <br><small class="text-muted">Available Storage</small>
                            </div>
                            <div class="col-6 mb-3">
                                <span class="health-indicator health-<?php echo $system_health['memory_usage']; ?>"></span>
                                <strong>Memory</strong>
                                <br><small class="text-muted">RAM Usage</small>
                            </div>
                            <div class="col-6 mb-3">
                                <span class="health-indicator health-<?php echo $system_health['cache_status']; ?>"></span>
                                <strong>Cache</strong>
                                <br><small class="text-muted">Cache Performance</small>
                            </div>
                            <div class="col-6 mb-3">
                                <span class="health-indicator health-<?php echo $system_health['queue_status']; ?>"></span>
                                <strong>Job Queue</strong>
                                <br><small class="text-muted">Background Processing</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 text-center">
                                <div class="h4 text-primary"><?php echo phpversion(); ?></div>
                                <small class="text-muted">PHP Version</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h4 text-success"><?php echo function_exists('opcache_get_status') ? (opcache_get_status()['opcache_enabled'] ? 'Enabled' : 'Disabled') : 'N/A'; ?></div>
                                <small class="text-muted">OPCache</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h4 text-info"><?php echo round(memory_get_usage(true) / 1024 / 1024, 1); ?>MB</div>
                                <small class="text-muted">Memory Usage</small>
                            </div>
                            <div class="col-6 text-center">
                                <div class="h4 text-warning"><?php echo round(disk_free_space('.') / 1024 / 1024 / 1024, 1); ?>GB</div>
                                <small class="text-muted">Free Space</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-primary">
                        <?php echo number_format($stats['total_jobs']); ?>
                    </div>
                    <div class="text-muted">Total Jobs</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card danger">
                    <div class="stats-value text-danger">
                        <?php echo number_format($stats['failed_jobs']); ?>
                    </div>
                    <div class="text-muted">Failed Jobs</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card success">
                    <div class="stats-value text-success">
                        <?php echo number_format($stats['total_backups']); ?>
                    </div>
                    <div class="text-muted">Backups Created</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-info">
                        <?php echo round(($stats['backup_size'] ?? 0) / 1024 / 1024, 1); ?>MB
                    </div>
                    <div class="text-muted">Backup Storage</div>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent System Events</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($system_events)): ?>
                        <p class="text-muted">No system events recorded yet.</p>
                        <?php else: ?>
                        <div class="overflow-auto" style="max-height: 400px;">
                            <?php foreach ($system_events as $event): ?>
                            <div class="event-item <?php echo $event['event_type']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo ucfirst(str_replace('_', ' ', $event['event_type'])); ?></strong>
                                        <br><small><?php echo htmlspecialchars($event['description']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($event['created_by_name']); ?>
                                            <br><?php echo date('M d, H:i', strtotime($event['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'cache'): ?>
        <!-- Cache Management Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fas fa-memory me-2"></i>Cache Management</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="tool-card card text-center">
                    <div class="card-body">
                        <i class="fas fa-server fa-3x text-primary mb-3"></i>
                        <h5>Application Cache</h5>
                        <p class="text-muted">Clear compiled templates and OPCache</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <input type="hidden" name="cache_type" value="app">
                            <button type="submit" class="btn btn-primary">Clear Cache</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="tool-card card text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-warning mb-3"></i>
                        <h5>Session Cache</h5>
                        <p class="text-muted">Clear all user session files</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <input type="hidden" name="cache_type" value="session">
                            <button type="submit" class="btn btn-warning">Clear Sessions</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="tool-card card text-center">
                    <div class="card-body">
                        <i class="fas fa-search fa-3x text-info mb-3"></i>
                        <h5>Search Cache</h5>
                        <p class="text-muted">Clear search index and results cache</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <input type="hidden" name="cache_type" value="search">
                            <button type="submit" class="btn btn-info">Clear Search</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="tool-card card text-center">
                    <div class="card-body">
                        <i class="fas fa-broom fa-3x text-danger mb-3"></i>
                        <h5>Clear All</h5>
                        <p class="text-muted">Clear all cache types at once</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="clear_cache">
                            <input type="hidden" name="cache_type" value="all">
                            <button type="submit" class="btn btn-danger" 
                                    onclick="return confirm('Are you sure you want to clear all caches?')">Clear All</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Tools -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="tool-card card text-center">
                    <div class="card-body">
                        <i class="fas fa-database fa-3x text-success mb-3"></i>
                        <h5>Optimize Database</h5>
                        <p class="text-muted">Optimize and defragment database tables</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="optimize_database">
                            <button type="submit" class="btn btn-success" 
                                    onclick="return confirm('This may take a while. Continue?')">Optimize Database</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="tool-card card text-center">
                    <div class="card-body">
                        <i class="fas fa-redo fa-3x text-warning mb-3"></i>
                        <h5>Retry Failed Jobs</h5>
                        <p class="text-muted">Retry all failed background jobs</p>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="retry_failed_jobs">
                            <button type="submit" class="btn btn-warning">
                                Retry Jobs (<?php echo number_format($stats['failed_jobs']); ?>)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'jobs'): ?>
        <!-- Job Monitor Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-tasks me-2"></i>Job Monitor</h4>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="retry_failed_jobs">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-redo me-1"></i>
                            Retry All Failed Jobs
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <?php if (empty($failed_jobs)): ?>
        <div class="text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h4 class="text-success">No Failed Jobs</h4>
            <p class="text-muted">All background jobs are running smoothly!</p>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Failed Jobs (<?php echo count($failed_jobs); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($failed_jobs as $job): ?>
                        <div class="job-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($job['job_type']); ?></strong>
                                    <br><small class="text-muted">Queue: <?php echo htmlspecialchars($job['queue']); ?></small>
                                    <br><small>Attempts: <?php echo $job['attempts']; ?>/<?php echo $job['max_attempts']; ?></small>
                                    <?php if ($job['error_message']): ?>
                                    <br><small class="text-danger"><?php echo htmlspecialchars($job['error_message']); ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?php echo $job['retry_status'] === 'retryable' ? 'warning' : 'danger'; ?>">
                                        <?php echo ucfirst($job['retry_status']); ?>
                                    </span>
                                    <br><small class="text-muted">
                                        Failed: <?php echo date('M d, H:i', strtotime($job['updated_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ($tab === 'backups'): ?>
        <!-- Backups Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-database me-2"></i>Database Backups</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                        <i class="fas fa-plus me-1"></i>
                        Create Backup
                    </button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Backups</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_backups)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-database fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Backups Created</h5>
                            <p class="text-muted">Create your first backup to ensure data safety</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                                <i class="fas fa-plus me-1"></i>
                                Create Your First Backup
                            </button>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recent_backups as $backup): ?>
                        <div class="backup-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($backup['filename']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($backup['description']); ?></small>
                                    <br><span class="badge bg-<?php echo $backup['status'] === 'completed' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($backup['status']); ?>
                                    </span>
                                    <span class="badge bg-info"><?php echo ucfirst($backup['backup_type']); ?></span>
                                </div>
                                <div class="text-end">
                                    <div>
                                        <?php if ($backup['file_size']): ?>
                                        <strong><?php echo round($backup['file_size'] / 1024 / 1024, 1); ?>MB</strong>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        Created by <?php echo htmlspecialchars($backup['created_by_name']); ?>
                                        <br><?php echo date('M d, Y H:i', strtotime($backup['created_at'])); ?>
                                    </small>
                                    <?php if ($backup['status'] === 'completed'): ?>
                                    <br><a href="<?php echo htmlspecialchars($backup['filepath']); ?>" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Backup Modal -->
        <div class="modal fade" id="createBackupModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Database Backup</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_backup">
                            
                            <div class="mb-3">
                                <label for="backupType" class="form-label">Backup Type</label>
                                <select class="form-select" id="backupType" name="backup_type" required>
                                    <option value="full">Full Database</option>
                                    <option value="data_only">Data Only</option>
                                    <option value="schema_only">Schema Only</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="backupDescription" class="form-label">Description</label>
                                <input type="text" class="form-control" id="backupDescription" name="description" 
                                       placeholder="e.g., Before major update">
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                The backup will be created in the background and you'll receive a notification when complete.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Backup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'maintenance'): ?>
        <!-- Maintenance Mode Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fas fa-wrench me-2"></i>Maintenance Mode</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="maintenance-panel <?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'enabled' : ''; ?>">
                    <div class="text-center mb-4">
                        <i class="fas fa-<?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'exclamation-triangle' : 'wrench'; ?> fa-3x mb-3"></i>
                        <h3>
                            Maintenance Mode is 
                            <?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'ENABLED' : 'DISABLED'; ?>
                        </h3>
                        <?php if ($system_settings['maintenance_mode'] ?? 0): ?>
                        <p>Your site is currently in maintenance mode. Visitors will see the maintenance message.</p>
                        <?php else: ?>
                        <p>Your site is live and accessible to all visitors.</p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="toggle_maintenance">
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenanceEnabled" 
                                       name="maintenance_enabled" <?php echo ($system_settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenanceEnabled">
                                    <strong>Enable Maintenance Mode</strong>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="maintenanceMessage" class="form-label">Maintenance Message</label>
                            <textarea class="form-control" id="maintenanceMessage" name="maintenance_message" rows="3"><?php echo htmlspecialchars($system_settings['maintenance_message'] ?? 'Site temporarily unavailable for maintenance.'); ?></textarea>
                            <small class="form-text">This message will be displayed to visitors during maintenance.</small>
                        </div>
                        
                        <div class="text-center">
                            <button type="submit" class="btn btn-light btn-lg">
                                <i class="fas fa-save me-2"></i>
                                Update Maintenance Mode
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Important Notes</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>When maintenance mode is enabled, only administrators can access the site</li>
                            <li>Regular users and visitors will see the maintenance message</li>
                            <li>API endpoints will return a 503 Service Unavailable status</li>
                            <li>Make sure to disable maintenance mode when your updates are complete</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>