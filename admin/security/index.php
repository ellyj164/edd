<?php
/**
 * Security & Audit Management - Admin Module
 * Security monitoring and audit log management
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

// Require proper permissions
requireAdminPermission(AdminPermissions::SECURITY_VIEW);

$page_title = 'Security & Audit';
$action = $_GET['action'] ?? 'dashboard';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        switch ($_POST['action']) {
            case 'clear_old_logs':
                requireAdminPermission(AdminPermissions::SECURITY_LOGS);
                
                $days = intval($_POST['retention_days']);
                if ($days < 30) {
                    $_SESSION['error_message'] = 'Minimum retention period is 30 days.';
                    break;
                }
                
                $deleted = cleanOldAuditLogs($days);
                
                logAdminAction('audit_logs_cleaned', 'system', null, null, 
                    ['retention_days' => $days, 'deleted_count' => $deleted], 
                    "Cleaned old audit logs (retention: {$days} days)"
                );
                
                $_SESSION['success_message'] = "Old audit logs cleaned. {$deleted} records removed.";
                break;
                
            case 'export_logs':
                requireAdminPermission(AdminPermissions::SECURITY_LOGS);
                
                $filters = [
                    'date_from' => $_POST['date_from'] ?? '',
                    'date_to' => $_POST['date_to'] ?? '',
                    'category' => $_POST['category'] ?? '',
                    'user_id' => $_POST['user_id'] ?? ''
                ];
                
                $csvData = exportAuditLogs($filters, 'csv');
                
                if ($csvData) {
                    $filename = 'audit_logs_' . date('Y-m-d') . '.csv';
                    
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header('Content-Length: ' . strlen($csvData));
                    
                    echo $csvData;
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Failed to export audit logs.';
                }
                break;
                
            case 'ban_ip':
                requireAdminPermission(AdminPermissions::SECURITY_USERS);
                
                $ipAddress = sanitizeInput($_POST['ip_address']);
                $reason = sanitizeInput($_POST['reason']);
                $duration = intval($_POST['duration']); // hours
                
                // Add to IP ban table (create if needed)
                try {
                    Database::query("
                        CREATE TABLE IF NOT EXISTS ip_bans (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            ip_address VARCHAR(45) NOT NULL,
                            reason TEXT,
                            banned_by INT,
                            banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            expires_at TIMESTAMP NULL,
                            is_active TINYINT(1) DEFAULT 1,
                            UNIQUE KEY unique_ip (ip_address),
                            KEY banned_by (banned_by)
                        ) ENGINE=InnoDB
                    ");
                    
                    $expiresAt = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} hours")) : null;
                    
                    Database::query("
                        INSERT INTO ip_bans (ip_address, reason, banned_by, expires_at) 
                        VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE 
                        reason = ?, banned_by = ?, banned_at = NOW(), expires_at = ?, is_active = 1
                    ", [$ipAddress, $reason, getCurrentUserId(), $expiresAt, $reason, getCurrentUserId(), $expiresAt]);
                    
                    logAdminAction('ip_banned', 'security', null, null, 
                        ['ip_address' => $ipAddress, 'reason' => $reason, 'duration_hours' => $duration], 
                        "IP address banned: {$ipAddress}"
                    );
                    
                    $_SESSION['success_message'] = "IP address {$ipAddress} has been banned.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Failed to ban IP address: ' . $e->getMessage();
                }
                break;
        }
        
        header('Location: /admin/security/?action=' . $action);
        exit;
    } catch (Exception $e) {
        error_log("Security management error: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while processing your request.';
        header('Location: /admin/security/');
        exit;
    }
}

// Get security dashboard data
$dashboardData = getSecurityDashboard(7); // Last 7 days

// Get recent security events for different actions
$recentFailedLogins = [];
$suspiciousActivity = [];
$adminActions = [];

try {
    // Recent failed login attempts
    $recentFailedLogins = Database::query("
        SELECT al.*, u.username, u.email
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.event LIKE '%login_failed%'
        AND al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY al.created_at DESC
        LIMIT 10
    ")->fetchAll();
    
    // Suspicious activity (multiple failed attempts from same IP)
    $suspiciousActivity = Database::query("
        SELECT ip_address, COUNT(*) as attempt_count, MAX(created_at) as last_attempt
        FROM audit_logs
        WHERE event LIKE '%login_failed%'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY ip_address
        HAVING attempt_count >= 5
        ORDER BY attempt_count DESC, last_attempt DESC
        LIMIT 10
    ")->fetchAll();
    
    // Recent admin actions
    $adminActions = Database::query("
        SELECT al.*, u.username
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.category = 'admin_action'
        AND al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY al.created_at DESC
        LIMIT 15
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching security data: " . $e->getMessage());
}

// Get audit logs with pagination for logs view
if ($action === 'logs') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    
    $filters = [
        'date_from' => $_GET['date_from'] ?? '',
        'date_to' => $_GET['date_to'] ?? '',
        'category' => $_GET['category'] ?? '',
        'event' => $_GET['event'] ?? '',
        'user_id' => $_GET['user_id'] ?? '',
        'ip_address' => $_GET['ip_address'] ?? ''
    ];
    
    $auditLogs = getAuditLogs($filters, $page, $limit);
}

// Include admin header
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Security Management Content -->
<div class="row">
    <div class="col-12">
        <div class="page-header">
            <h1><i class="fas fa-shield-alt me-2"></i>Security & Audit Management</h1>
            <p class="text-muted">Monitor security events and manage audit logs</p>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="row mb-4">
    <div class="col-12">
        <nav class="nav nav-pills">
            <a class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>" href="?action=dashboard">
                <i class="fas fa-tachometer-alt me-1"></i>Security Dashboard
            </a>
            <?php if (hasAdminPermission(AdminPermissions::SECURITY_LOGS)): ?>
            <a class="nav-link <?php echo $action === 'logs' ? 'active' : ''; ?>" href="?action=logs">
                <i class="fas fa-list me-1"></i>Audit Logs
            </a>
            <?php endif; ?>
            <a class="nav-link <?php echo $action === 'alerts' ? 'active' : ''; ?>" href="?action=alerts">
                <i class="fas fa-exclamation-triangle me-1"></i>Security Alerts
            </a>
            <a class="nav-link <?php echo $action === 'settings' ? 'active' : ''; ?>" href="?action=settings">
                <i class="fas fa-cog me-1"></i>Security Settings
            </a>
        </nav>
    </div>
</div>

<?php if ($action === 'dashboard'): ?>
<!-- Security Dashboard -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="stats-value"><?php echo number_format($dashboardData['failed_logins']); ?></div>
            <div class="stats-label">Failed Logins (7 days)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="stats-value"><?php echo number_format($dashboardData['permission_denials']); ?></div>
            <div class="stats-label">Permission Denials</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($dashboardData['unique_ips']); ?></div>
            <div class="stats-label">Unique IP Addresses</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="stats-value"><?php echo number_format($dashboardData['admin_actions']); ?></div>
            <div class="stats-label">Admin Actions</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Failed Login Attempts -->
        <div class="dashboard-card">
            <h5><i class="fas fa-exclamation-circle text-danger me-2"></i>Recent Failed Logins</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User/Email</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentFailedLogins, 0, 8) as $attempt): ?>
                        <tr>
                            <td><?php echo date('H:i:s', strtotime($attempt['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($attempt['username'] ?? $attempt['email'] ?? 'Unknown'); ?></td>
                            <td>
                                <?php echo htmlspecialchars($attempt['ip_address'] ?? 'Unknown'); ?>
                                <?php if (hasAdminPermission(AdminPermissions::SECURITY_USERS)): ?>
                                <button class="btn btn-sm btn-outline-danger ms-1" 
                                        onclick="banIP('<?php echo htmlspecialchars($attempt['ip_address']); ?>')">
                                    <i class="fas fa-ban"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Suspicious Activity -->
        <div class="dashboard-card">
            <h5><i class="fas fa-eye text-warning me-2"></i>Suspicious Activity</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Failed Attempts</th>
                            <th>Last Attempt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suspiciousActivity as $activity): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                            <td><span class="badge bg-danger"><?php echo $activity['attempt_count']; ?></span></td>
                            <td><?php echo date('H:i:s', strtotime($activity['last_attempt'])); ?></td>
                            <td>
                                <?php if (hasAdminPermission(AdminPermissions::SECURITY_USERS)): ?>
                                <button class="btn btn-sm btn-outline-danger" 
                                        onclick="banIP('<?php echo htmlspecialchars($activity['ip_address']); ?>')">
                                    <i class="fas fa-ban"></i> Ban
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
    
    <div class="col-md-6">
        <!-- Recent Admin Actions -->
        <div class="dashboard-card">
            <h5><i class="fas fa-user-shield text-info me-2"></i>Recent Admin Actions</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($adminActions, 0, 10) as $action_log): ?>
                        <tr>
                            <td><?php echo date('H:i:s', strtotime($action_log['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($action_log['username'] ?? 'System'); ?></td>
                            <td>
                                <small><?php echo htmlspecialchars($action_log['event']); ?></small>
                                <?php if ($action_log['details']): ?>
                                <?php $details = json_decode($action_log['details'], true); ?>
                                <?php if (isset($details['action'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($details['action']); ?></small>
                                <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Security Chart -->
        <div class="dashboard-card">
            <h5><i class="fas fa-chart-line me-2"></i>Security Trends</h5>
            <div class="chart-container">
                <canvas id="securityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action === 'logs' && hasAdminPermission(AdminPermissions::SECURITY_LOGS)): ?>
<!-- Audit Logs -->
<div class="dashboard-card mb-4">
    <form method="GET" class="row align-items-end">
        <input type="hidden" name="action" value="logs">
        
        <div class="col-md-2">
            <label class="form-label">Date From</label>
            <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Date To</label>
            <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Category</label>
            <select class="form-select" name="category">
                <option value="">All Categories</option>
                <option value="security" <?php echo $filters['category'] === 'security' ? 'selected' : ''; ?>>Security</option>
                <option value="admin_action" <?php echo $filters['category'] === 'admin_action' ? 'selected' : ''; ?>>Admin Actions</option>
                <option value="user_action" <?php echo $filters['category'] === 'user_action' ? 'selected' : ''; ?>>User Actions</option>
                <option value="system" <?php echo $filters['category'] === 'system' ? 'selected' : ''; ?>>System</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">IP Address</label>
            <input type="text" class="form-control" name="ip_address" value="<?php echo htmlspecialchars($filters['ip_address']); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-success w-100" onclick="exportLogs()">Export</button>
        </div>
    </form>
</div>

<div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Audit Logs</h5>
        <span class="text-muted">Total: <?php echo number_format($auditLogs['total']); ?> records</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Category</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLogs['logs'] as $log): ?>
                <tr>
                    <td>
                        <?php echo date('M j, Y', strtotime($log['created_at'])); ?><br>
                        <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $log['category'] === 'security' ? 'danger' : ($log['category'] === 'admin_action' ? 'warning' : 'info'); ?>">
                            <?php echo htmlspecialchars($log['event']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($log['category']); ?></td>
                    <td><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td>
                    <td>
                        <?php if ($log['details']): ?>
                        <?php $details = json_decode($log['details'], true); ?>
                        <?php if ($details): ?>
                        <small class="text-muted">
                            <?php echo htmlspecialchars(substr(json_encode($details, JSON_PRETTY_PRINT), 0, 100)); ?>
                            <?php if (strlen(json_encode($details)) > 100): ?>...<?php endif; ?>
                        </small>
                        <?php endif; ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($auditLogs['pages'] > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= min($auditLogs['pages'], 10); $i++): ?>
            <li class="page-item <?php echo $auditLogs['page'] === $i ? 'active' : ''; ?>">
                <a class="page-link" href="?action=logs&page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php elseif ($action === 'settings'): ?>
<!-- Security Settings -->
<div class="row">
    <div class="col-md-6">
        <div class="dashboard-card">
            <h5><i class="fas fa-database me-2"></i>Log Management</h5>
            
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="clear_old_logs">
                
                <div class="mb-3">
                    <label class="form-label">Log Retention (Days)</label>
                    <input type="number" class="form-control" name="retention_days" min="30" max="3650" value="365" required>
                    <small class="form-text text-muted">Logs older than this will be permanently deleted. Minimum: 30 days.</small>
                </div>
                
                <button type="submit" class="btn btn-warning confirm-action" 
                        data-confirm-message="Are you sure you want to delete old audit logs? This action cannot be undone.">
                    <i class="fas fa-trash me-1"></i>Clean Old Logs
                </button>
            </form>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="dashboard-card">
            <h5><i class="fas fa-ban me-2"></i>IP Management</h5>
            
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="ban_ip">
                
                <div class="mb-3">
                    <label class="form-label">IP Address</label>
                    <input type="text" class="form-control" name="ip_address" required 
                           pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" title="Please enter a valid IP address">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control" name="reason" rows="3" required 
                              placeholder="Why is this IP being banned?"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Duration (Hours)</label>
                    <select class="form-select" name="duration">
                        <option value="0">Permanent</option>
                        <option value="1">1 Hour</option>
                        <option value="24">24 Hours</option>
                        <option value="168">1 Week</option>
                        <option value="720">30 Days</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-ban me-1"></i>Ban IP Address
                </button>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Ban IP Modal -->
<div class="modal fade" id="banIPModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="ban_ip">
                <input type="hidden" name="ip_address" id="ban_ip_address">
                
                <div class="modal-header">
                    <h5 class="modal-title">Ban IP Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong>Warning:</strong> You are about to ban IP address <span id="ban_ip_display"></span>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea class="form-control" name="reason" rows="3" required 
                                  placeholder="Why is this IP being banned?"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Duration</label>
                        <select class="form-select" name="duration">
                            <option value="24">24 Hours</option>
                            <option value="168">1 Week</option>
                            <option value="720">30 Days</option>
                            <option value="0">Permanent</option>
                        </select>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Ban IP Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Additional scripts for security management
$additional_scripts = '
<script>
function banIP(ipAddress) {
    document.getElementById("ban_ip_address").value = ipAddress;
    document.getElementById("ban_ip_display").textContent = ipAddress;
    new bootstrap.Modal(document.getElementById("banIPModal")).show();
}

function exportLogs() {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/admin/security/";
    
    const params = new URLSearchParams(window.location.search);
    form.innerHTML = `
        ' . csrfTokenInput() . '
        <input type="hidden" name="action" value="export_logs">
        <input type="hidden" name="date_from" value="${params.get("date_from") || ""}">
        <input type="hidden" name="date_to" value="${params.get("date_to") || ""}">
        <input type="hidden" name="category" value="${params.get("category") || ""}">
        <input type="hidden" name="user_id" value="${params.get("user_id") || ""}">
        <input type="hidden" name="ip_address" value="${params.get("ip_address") || ""}">
    `;
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// Security trends chart
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("securityChart");
    if (ctx) {
        new Chart(ctx, {
            type: "line",
            data: {
                labels: ["6 days ago", "5 days ago", "4 days ago", "3 days ago", "2 days ago", "Yesterday", "Today"],
                datasets: [{
                    label: "Failed Logins",
                    data: [12, 19, 8, 25, 15, 22, 18],
                    borderColor: "rgb(220, 53, 69)",
                    backgroundColor: "rgba(220, 53, 69, 0.1)"
                }, {
                    label: "Permission Denials",
                    data: [3, 5, 2, 8, 4, 6, 5],
                    borderColor: "rgb(255, 193, 7)",
                    backgroundColor: "rgba(255, 193, 7, 0.1)"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>';

// Include admin footer
require_once __DIR__ . '/../../includes/footer.php';
?>