<?php
/**
 * Communications - Message Logs & Analytics
 * Admin Panel - Delivery tracking and analytics
 */

require_once __DIR__ . '/../../includes/init.php';

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

$page_title = 'Message Logs & Analytics';
$page_subtitle = 'Delivery tracking and communication analytics';

// Fetch live data from database
$recent_messages = [];
$delivery_stats = [
    'total_sent' => 0,
    'delivered' => 0,
    'opened' => 0,
    'clicked' => 0,
    'bounced' => 0,
    'delivery_rate' => 0,
    'open_rate' => 0,
    'click_rate' => 0,
    'bounce_rate' => 0
];

try {
    $pdo = db();
    
    // Get recent messages from email_queue
    $stmt = $pdo->query("
        SELECT 
            id,
            recipient_email as recipient,
            'Email' as type,
            subject,
            CASE 
                WHEN status = 'sent' THEN 'Delivered'
                WHEN status = 'failed' THEN 'Bounced'
                WHEN status = 'pending' THEN 'Pending'
                ELSE status
            END as status,
            created_at as sent_at,
            sent_at as delivered_at,
            NULL as opened_at,
            NULL as clicked_at,
            template_type as campaign,
            error_message as bounce_reason
        FROM email_queue
        ORDER BY created_at DESC
        LIMIT 100
    ");
    $recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get delivery statistics
    $stmt = $pdo->query("SELECT COUNT(*) as total_sent FROM email_queue");
    $delivery_stats['total_sent'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as delivered FROM email_queue WHERE status = 'sent'");
    $delivery_stats['delivered'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) as bounced FROM email_queue WHERE status = 'failed'");
    $delivery_stats['bounced'] = $stmt->fetchColumn();
    
    // Calculate rates
    if ($delivery_stats['total_sent'] > 0) {
        $delivery_stats['delivery_rate'] = round(($delivery_stats['delivered'] / $delivery_stats['total_sent']) * 100, 1);
        $delivery_stats['bounce_rate'] = round(($delivery_stats['bounced'] / $delivery_stats['total_sent']) * 100, 1);
    }
    
} catch (Exception $e) {
    error_log("Error fetching message logs: " . $e->getMessage());
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
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--admin-accent);
            text-align: center;
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
        
        .timeline-item {
            padding: 1rem;
            border-left: 3px solid #dee2e6;
            margin-left: 1rem;
            position: relative;
        }
        
        .timeline-item::before {
            content: '';
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #dee2e6;
            position: absolute;
            left: -7px;
            top: 1.5rem;
        }
        
        .timeline-item.success::before {
            background: var(--admin-success);
        }
        
        .timeline-item.danger::before {
            background: var(--admin-danger);
        }
        
        .timeline-item.warning::before {
            background: var(--admin-warning);
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
                        <i class="fas fa-chart-line me-2"></i>
                        Message Logs & Analytics
                    </h1>
                    <small class="text-white-50">Delivery tracking and communication analytics</small>
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
                        <li class="breadcrumb-item">
                            <a href="/admin/communications/" class="text-decoration-none">Communications</a>
                        </li>
                        <li class="breadcrumb-item active">Message Logs</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Delivery Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-primary">
                        <?php echo number_format($delivery_stats['total_sent']); ?>
                    </div>
                    <div class="stats-label">Total Sent</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card success">
                    <div class="stats-value text-success">
                        <?php echo $delivery_stats['delivery_rate']; ?>%
                    </div>
                    <div class="stats-label">Delivery Rate</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card warning">
                    <div class="stats-value text-warning">
                        <?php echo $delivery_stats['open_rate']; ?>%
                    </div>
                    <div class="stats-label">Open Rate</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-info">
                        <?php echo $delivery_stats['click_rate']; ?>%
                    </div>
                    <div class="stats-label">Click Rate</div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <h6>Filter Messages</h6>
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <select class="form-select form-select-sm">
                        <option>Today</option>
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>Custom Range</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select form-select-sm">
                        <option>All Types</option>
                        <option>Email</option>
                        <option>SMS</option>
                        <option>Push</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select form-select-sm">
                        <option>All Status</option>
                        <option>Delivered</option>
                        <option>Opened</option>
                        <option>Clicked</option>
                        <option>Bounced</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Campaign</label>
                    <select class="form-select form-select-sm">
                        <option>All Campaigns</option>
                        <option>Welcome Series</option>
                        <option>Order Updates</option>
                        <option>Cart Recovery</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Analytics Charts -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Delivery Performance Trends</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="deliveryChart" height="100"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Message Types Distribution</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="typeChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Messages</h6>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-download me-1"></i>Export
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Export as CSV</a></li>
                                    <li><a class="dropdown-item" href="#">Export as Excel</a></li>
                                    <li><a class="dropdown-item" href="#">Delivery Report</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Message ID</th>
                                        <th>Recipient</th>
                                        <th>Type</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Campaign</th>
                                        <th>Timeline</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_messages as $message): ?>
                                    <tr>
                                        <td>
                                            <code><?php echo $message['id']; ?></code>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($message['recipient']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline message-type-<?php echo strtolower($message['type']); ?>">
                                                <i class="fas fa-<?php echo $message['type'] === 'Email' ? 'envelope' : ($message['type'] === 'SMS' ? 'sms' : 'bell'); ?> me-1"></i>
                                                <?php echo $message['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($message['subject']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $message['status'] === 'Delivered' ? 'success' : 
                                                    ($message['status'] === 'Clicked' ? 'primary' : 
                                                    ($message['status'] === 'Bounced' ? 'danger' : 'warning')); 
                                            ?>">
                                                <?php echo $message['status']; ?>
                                            </span>
                                            <?php if ($message['bounce_reason']): ?>
                                                <br><small class="text-danger"><?php echo htmlspecialchars($message['bounce_reason']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $message['campaign']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.8rem;">
                                                <div><strong>Sent:</strong> <?php echo date('H:i', strtotime($message['sent_at'])); ?></div>
                                                <?php if ($message['delivered_at']): ?>
                                                    <div><strong>Delivered:</strong> <?php echo date('H:i', strtotime($message['delivered_at'])); ?></div>
                                                <?php endif; ?>
                                                <?php if ($message['opened_at']): ?>
                                                    <div><strong>Opened:</strong> <?php echo date('H:i', strtotime($message['opened_at'])); ?></div>
                                                <?php endif; ?>
                                                <?php if ($message['clicked_at']): ?>
                                                    <div><strong>Clicked:</strong> <?php echo date('H:i', strtotime($message['clicked_at'])); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-info" title="View Timeline">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Resend">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <nav aria-label="Message pagination">
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item disabled">
                                    <span class="page-link">Previous</span>
                                </li>
                                <li class="page-item active">
                                    <span class="page-link">1</span>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">2</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">3</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Next</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bounce Analysis -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Bounce Analysis</h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline-item danger">
                            <strong>Hard Bounce</strong> - Invalid domain<br>
                            <small class="text-muted">invalid@domain.invalid • 2 hours ago</small>
                        </div>
                        <div class="timeline-item warning">
                            <strong>Soft Bounce</strong> - Mailbox full<br>
                            <small class="text-muted">user@example.com • 4 hours ago</small>
                        </div>
                        <div class="timeline-item danger">
                            <strong>Hard Bounce</strong> - User unknown<br>
                            <small class="text-muted">unknown@company.com • 6 hours ago</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Channel Performance</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Email Delivery Rate</span>
                            <span class="text-success">96.2%</span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-success" style="width: 96.2%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>SMS Delivery Rate</span>
                            <span class="text-success">99.8%</span>
                        </div>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-success" style="width: 99.8%"></div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Push Delivery Rate</span>
                            <span class="text-warning">87.4%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" style="width: 87.4%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Delivery Performance Chart
        const deliveryCtx = document.getElementById('deliveryChart').getContext('2d');
        const deliveryChart = new Chart(deliveryCtx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Sent',
                    data: [2400, 1800, 2200, 2600, 2100, 1200, 800],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Delivered',
                    data: [2320, 1750, 2150, 2520, 2050, 1180, 790],
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Opened',
                    data: [1400, 1050, 1300, 1520, 1230, 710, 470],
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243, 156, 18, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Message Types Distribution Chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: ['Email', 'SMS', 'Push', 'In-App'],
                datasets: [{
                    data: [65, 20, 12, 3],
                    backgroundColor: [
                        '#2c3e50',
                        '#27ae60', 
                        '#f39c12',
                        '#9b59b6'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>