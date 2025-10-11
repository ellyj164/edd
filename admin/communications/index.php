<?php
/**
 * Communications Management
 * Admin Panel - Message and Communication Center
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();

// Admin Bypass Mode - Skip all authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Communications Management';
$page_subtitle = 'Message templates, campaigns, and delivery tracking';

// Fetch live data from database
$message_stats = [
    'total_sent' => 0,
    'delivered' => 0,
    'bounced' => 0,
    'pending' => 0,
    'delivery_rate' => 0
];

$recent_campaigns = [];
$message_templates = [];

try {
    // Get message statistics from email_queue table
    $stmt = $pdo->query("SELECT COUNT(*) as total_sent FROM email_queue");
    $message_stats['total_sent'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as delivered FROM email_queue WHERE status = 'sent'");
    $message_stats['delivered'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as bounced FROM email_queue WHERE status = 'failed'");
    $message_stats['bounced'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM email_queue WHERE status = 'pending'");
    $message_stats['pending'] = $stmt->fetchColumn();
    
    // Calculate delivery rate
    if ($message_stats['total_sent'] > 0) {
        $message_stats['delivery_rate'] = round(($message_stats['delivered'] / $message_stats['total_sent']) * 100, 1);
    }
    
    // Get recent campaigns (if campaigns table exists)
    $tables = $pdo->query("SHOW TABLES LIKE 'campaigns'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $pdo->query("
            SELECT id, name, type, status, 
                   (SELECT COUNT(*) FROM email_queue WHERE campaign_id = campaigns.id) as sent,
                   (SELECT COUNT(*) FROM email_queue WHERE campaign_id = campaigns.id AND status = 'sent') as delivered,
                   created_at as created
            FROM campaigns 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $recent_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get message templates (if email_templates table exists)
    $tables = $pdo->query("SHOW TABLES LIKE 'email_templates'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $pdo->query("
            SELECT id, name, type, category, language, status,
                   (SELECT COUNT(*) FROM email_queue WHERE template_id = email_templates.id) as usage_count,
                   updated_at as last_used
            FROM email_templates 
            ORDER BY updated_at DESC
            LIMIT 10
        ");
        $message_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching communications data: " . $e->getMessage());
    // Data remains empty if database query fails
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Admin CSS -->
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
            --admin-dark: #2c3e50;
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
        
        .stats-card.success {
            border-left-color: var(--admin-success);
        }
        
        .stats-card.warning {
            border-left-color: var(--admin-warning);
        }
        
        .stats-card.danger {
            border-left-color: var(--admin-danger);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .action-btn {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            text-decoration: none;
            color: var(--admin-dark);
            display: block;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: var(--admin-accent);
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .badge-outline {
            border: 1px solid;
            background: transparent;
        }
        
        .message-type-email {
            color: var(--admin-primary);
            border-color: var(--admin-primary);
        }
        
        .message-type-sms {
            color: var(--admin-success);
            border-color: var(--admin-success);
        }
        
        .message-type-push {
            color: var(--admin-warning);
            border-color: var(--admin-warning);
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
                        <i class="fas fa-comments me-2"></i>
                        Communications Management
                    </h1>
                    <small class="text-white-50">Message templates, campaigns, and delivery tracking</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-inline-block">
                        <span class="me-3">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                        </span>
                        <span class="badge bg-light text-dark me-3">
                            <i class="fas fa-circle text-success me-1"></i>
                            Online
                        </span>
                        <span class="text-white-50">
                            <?php echo date('M d, Y H:i'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Navigation Breadcrumb -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="/admin/" class="text-decoration-none">
                                <i class="fas fa-tachometer-alt me-1"></i>Admin Dashboard
                            </a>
                        </li>
                        <li class="breadcrumb-item active">Communications Management</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Key Statistics -->
        <div class="row mb-4">
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-primary">
                        <?php echo number_format($message_stats['total_sent']); ?>
                    </div>
                    <div class="stats-label">Total Sent</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card success">
                    <div class="stats-value text-success">
                        <?php echo number_format($message_stats['delivered']); ?>
                    </div>
                    <div class="stats-label">Delivered</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card danger">
                    <div class="stats-value text-danger">
                        <?php echo number_format($message_stats['bounced']); ?>
                    </div>
                    <div class="stats-label">Bounced</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card warning">
                    <div class="stats-value text-warning">
                        <?php echo number_format($message_stats['pending']); ?>
                    </div>
                    <div class="stats-label">Pending</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card success">
                    <div class="stats-value text-success">
                        <?php echo $message_stats['delivery_rate']; ?>%
                    </div>
                    <div class="stats-label">Delivery Rate</div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stats-label">Performance</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <h5><i class="fas fa-bolt text-primary me-2"></i>Quick Actions</h5>
            </div>
            <div class="col-md-3 mb-3">
                <a href="/admin/communications/compose" class="action-btn">
                    <i class="fas fa-edit fa-2x mb-2 text-primary"></i><br>
                    <strong>Compose Message</strong><br>
                    <small class="text-muted">Send one-time message</small>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="/admin/communications/templates.php" class="action-btn">
                    <i class="fas fa-file-alt fa-2x mb-2 text-success"></i><br>
                    <strong>Manage Templates</strong><br>
                    <small class="text-muted">Create reusable templates</small>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="/admin/communications/campaigns.php" class="action-btn">
                    <i class="fas fa-bullhorn fa-2x mb-2 text-warning"></i><br>
                    <strong>Campaign Builder</strong><br>
                    <small class="text-muted">Launch bulk campaigns</small>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="/admin/communications/logs.php" class="action-btn">
                    <i class="fas fa-chart-bar fa-2x mb-2 text-info"></i><br>
                    <strong>Analytics & Logs</strong><br>
                    <small class="text-muted">Delivery analytics</small>
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="/admin/communications/live-chat.php" class="action-btn">
                    <i class="fas fa-comments fa-2x mb-2 text-danger"></i><br>
                    <strong>Live Chat</strong><br>
                    <small class="text-muted">Manage support chats</small>
                </a>
            </div>
        </div>

        <!-- Recent Campaigns and Templates -->
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-paper-plane me-2"></i>Recent Campaigns
                        </h6>
                        <a href="/admin/communications/campaigns" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Sent</th>
                                        <th>Delivered</th>
                                        <th>Performance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_campaigns as $campaign): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($campaign['name']); ?></strong><br>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($campaign['created'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline message-type-<?php echo strtolower($campaign['type']); ?>">
                                                <?php echo $campaign['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $campaign['status'] === 'Active' ? 'success' : 'secondary'; ?>">
                                                <?php echo $campaign['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($campaign['sent']); ?></td>
                                        <td><?php echo number_format($campaign['delivered']); ?></td>
                                        <td>
                                            <?php if ($campaign['opened'] !== null): ?>
                                                <small>
                                                    Opens: <?php echo number_format($campaign['opened']); ?><br>
                                                    Clicks: <?php echo number_format($campaign['clicked']); ?>
                                                </small>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Duplicate">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" title="Stop">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Message Templates
                        </h6>
                        <a href="/admin/communications/templates" class="btn btn-sm btn-outline-primary">
                            Manage All
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Template</th>
                                        <th>Type</th>
                                        <th>Usage</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($message_templates as $template): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($template['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo $template['category']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline message-type-<?php echo strtolower($template['type']); ?>">
                                                <?php echo $template['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo number_format($template['usage_count']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Use">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Communication Channels Status -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-broadcast-tower me-2"></i>Channel Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>
                                <i class="fas fa-envelope text-primary me-2"></i>Email Service
                            </span>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> Active
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>
                                <i class="fas fa-sms text-success me-2"></i>SMS Gateway
                            </span>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> Active
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span>
                                <i class="fas fa-bell text-warning me-2"></i>Push Notifications
                            </span>
                            <span class="badge bg-warning">
                                <i class="fas fa-pause"></i> Paused
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-megaphone text-info me-2"></i>In-App Messages
                            </span>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>