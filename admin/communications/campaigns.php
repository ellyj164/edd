<?php
/**
 * Communications - Campaign Management
 * Admin Panel - Communication Campaign Builder
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

$page_title = 'Communication Campaigns';
$page_subtitle = 'Manage bulk messaging campaigns';

// Sample campaign data
$campaigns = [
    [
        'id' => 1,
        'name' => 'Welcome Email Series',
        'type' => 'Email',
        'status' => 'Active',
        'target_audience' => 'New Customers',
        'sent' => 2350,
        'delivered' => 2301,
        'opened' => 1840,
        'clicked' => 423,
        'open_rate' => 79.9,
        'click_rate' => 18.4,
        'created' => '2024-09-10 14:30:00',
        'scheduled' => '2024-09-10 15:00:00'
    ],
    [
        'id' => 2,
        'name' => 'Flash Sale Alert',
        'type' => 'Push',
        'status' => 'Completed',
        'target_audience' => 'All Subscribers',
        'sent' => 8920,
        'delivered' => 8435,
        'opened' => 3240,
        'clicked' => 892,
        'open_rate' => 38.4,
        'click_rate' => 10.6,
        'created' => '2024-09-05 16:45:00',
        'scheduled' => '2024-09-05 17:00:00'
    ],
    [
        'id' => 3,
        'name' => 'Order Status Updates',
        'type' => 'SMS',
        'status' => 'Active',
        'target_audience' => 'Customers with Orders',
        'sent' => 1560,
        'delivered' => 1556,
        'opened' => null,
        'clicked' => null,
        'open_rate' => null,
        'click_rate' => null,
        'created' => '2024-09-08 09:15:00',
        'scheduled' => '2024-09-08 09:30:00'
    ],
    [
        'id' => 4,
        'name' => 'Abandoned Cart Recovery',
        'type' => 'Email',
        'status' => 'Scheduled',
        'target_audience' => 'Cart Abandoners',
        'sent' => 0,
        'delivered' => 0,
        'opened' => 0,
        'clicked' => 0,
        'open_rate' => 0,
        'click_rate' => 0,
        'created' => '2024-09-14 11:00:00',
        'scheduled' => '2024-09-15 10:00:00'
    ]
];

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
        
        .performance-metric {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: white;
            margin-bottom: 1rem;
        }
        
        .performance-metric h3 {
            color: var(--admin-accent);
            margin-bottom: 0.5rem;
        }
        
        .performance-metric small {
            color: #666;
        }
        
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(var(--admin-success) 0deg 280deg, #e9ecef 280deg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
        }
        
        .progress-circle::before {
            content: '';
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
        }
        
        .campaign-builder {
            background: white;
            border-radius: 8px;
            padding: 2rem;
        }
        
        .step-indicator {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        
        .step::after {
            content: '';
            position: absolute;
            top: 20px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        
        .step:last-child::after {
            display: none;
        }
        
        .step.active .step-number {
            background: var(--admin-accent);
            color: white;
        }
        
        .step.completed .step-number {
            background: var(--admin-success);
            color: white;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            position: relative;
            z-index: 2;
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
                        <i class="fas fa-bullhorn me-2"></i>
                        Communication Campaigns
                    </h1>
                    <small class="text-white-50">Manage bulk messaging campaigns</small>
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
                        <li class="breadcrumb-item active">Campaigns</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Campaign Management</h5>
                <p class="text-muted">Create and manage bulk communication campaigns</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#campaignBuilderModal">
                    <i class="fas fa-plus me-1"></i>
                    Create Campaign
                </button>
                <div class="btn-group">
                    <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-1"></i>Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Export as CSV</a></li>
                        <li><a class="dropdown-item" href="#">Export as Excel</a></li>
                        <li><a class="dropdown-item" href="#">Performance Report</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Campaign Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="performance-metric">
                    <h3>4</h3>
                    <small>Total Campaigns</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="performance-metric">
                    <h3>12,830</h3>
                    <small>Total Messages Sent</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="performance-metric">
                    <h3>84.2%</h3>
                    <small>Average Delivery Rate</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="performance-metric">
                    <h3>45.6%</h3>
                    <small>Average Open Rate</small>
                </div>
            </div>
        </div>

        <!-- Campaigns Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Active Campaigns</h6>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Filter
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">All Campaigns</a></li>
                                    <li><a class="dropdown-item" href="#">Active Only</a></li>
                                    <li><a class="dropdown-item" href="#">Scheduled</a></li>
                                    <li><a class="dropdown-item" href="#">Completed</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Audience</th>
                                        <th>Performance</th>
                                        <th>Engagement</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($campaigns as $campaign): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($campaign['name']); ?></strong><br>
                                                <small class="text-muted">ID: <?php echo $campaign['id']; ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline message-type-<?php echo strtolower($campaign['type']); ?>">
                                                <i class="fas fa-<?php echo $campaign['type'] === 'Email' ? 'envelope' : ($campaign['type'] === 'SMS' ? 'sms' : 'bell'); ?> me-1"></i>
                                                <?php echo $campaign['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $campaign['status'] === 'Active' ? 'success' : 
                                                    ($campaign['status'] === 'Scheduled' ? 'warning' : 
                                                    ($campaign['status'] === 'Completed' ? 'info' : 'secondary')); 
                                            ?>">
                                                <?php echo $campaign['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $campaign['target_audience']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>Sent:</strong> <?php echo number_format($campaign['sent']); ?><br>
                                                <strong>Delivered:</strong> <?php echo number_format($campaign['delivered']); ?>
                                                <?php if ($campaign['sent'] > 0): ?>
                                                    <small class="text-success">
                                                        (<?php echo round(($campaign['delivered'] / $campaign['sent']) * 100, 1); ?>%)
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($campaign['type'] !== 'SMS'): ?>
                                                <div>
                                                    <strong>Opens:</strong> <?php echo number_format($campaign['opened']); ?>
                                                    <?php if ($campaign['open_rate']): ?>
                                                        <small class="text-info">(<?php echo $campaign['open_rate']; ?>%)</small>
                                                    <?php endif; ?><br>
                                                    <strong>Clicks:</strong> <?php echo number_format($campaign['clicked']); ?>
                                                    <?php if ($campaign['click_rate']): ?>
                                                        <small class="text-primary">(<?php echo $campaign['click_rate']; ?>%)</small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">SMS - No tracking</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo date('M d, Y H:i', strtotime($campaign['created'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" title="Duplicate">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <?php if ($campaign['status'] === 'Active'): ?>
                                                <button class="btn btn-outline-warning" title="Pause">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <?php endif; ?>
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
        </div>

        <!-- Campaign Performance Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Campaign Performance Trends</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="campaignChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Builder Modal -->
    <div class="modal fade" id="campaignBuilderModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-magic me-2"></i>Campaign Builder
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="campaign-builder">
                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step active">
                                <div class="step-number">1</div>
                                <div>Setup</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div>Audience</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div>Content</div>
                            </div>
                            <div class="step">
                                <div class="step-number">4</div>
                                <div>Schedule</div>
                            </div>
                            <div class="step">
                                <div class="step-number">5</div>
                                <div>Review</div>
                            </div>
                        </div>

                        <!-- Step 1: Campaign Setup -->
                        <div id="step1" class="step-content">
                            <h6>Campaign Setup</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="campaignName" class="form-label">Campaign Name</label>
                                    <input type="text" class="form-control" id="campaignName" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="campaignType" class="form-label">Communication Type</label>
                                    <select class="form-select" id="campaignType" required>
                                        <option value="">Select Type</option>
                                        <option value="email">Email Campaign</option>
                                        <option value="sms">SMS Campaign</option>
                                        <option value="push">Push Notification</option>
                                        <option value="in_app">In-App Message</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label for="campaignCategory" class="form-label">Category</label>
                                    <select class="form-select" id="campaignCategory">
                                        <option value="marketing">Marketing</option>
                                        <option value="transactional">Transactional</option>
                                        <option value="announcement">Announcement</option>
                                        <option value="promotional">Promotional</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="campaignPriority" class="form-label">Priority</label>
                                    <select class="form-select" id="campaignPriority">
                                        <option value="normal">Normal</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label for="campaignDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="campaignDescription" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-secondary" id="prevStep" style="display:none;">Previous</button>
                    <button type="button" class="btn btn-primary" id="nextStep">Next</button>
                    <button type="button" class="btn btn-success" id="launchCampaign" style="display:none;">Launch Campaign</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Campaign Performance Chart
        const ctx = document.getElementById('campaignChart').getContext('2d');
        const campaignChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Messages Sent',
                    data: [2100, 2800, 3200, 2900],
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Delivered',
                    data: [2050, 2750, 3150, 2820],
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Opened',
                    data: [1640, 2200, 2520, 2256],
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

        // Campaign Builder Steps
        let currentStep = 1;
        const totalSteps = 5;

        document.getElementById('nextStep').addEventListener('click', function() {
            if (currentStep < totalSteps) {
                currentStep++;
                updateStepIndicator();
                // In a real implementation, you would show/hide step content here
                console.log('Moving to step:', currentStep);
            }
        });

        document.getElementById('prevStep').addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateStepIndicator();
                console.log('Moving to step:', currentStep);
            }
        });

        function updateStepIndicator() {
            // Update step indicator visual state
            document.querySelectorAll('.step').forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 < currentStep) {
                    step.classList.add('completed');
                } else if (index + 1 === currentStep) {
                    step.classList.add('active');
                }
            });

            // Update button visibility
            const prevBtn = document.getElementById('prevStep');
            const nextBtn = document.getElementById('nextStep');
            const launchBtn = document.getElementById('launchCampaign');

            prevBtn.style.display = currentStep > 1 ? 'inline-block' : 'none';
            nextBtn.style.display = currentStep < totalSteps ? 'inline-block' : 'none';
            launchBtn.style.display = currentStep === totalSteps ? 'inline-block' : 'none';
        }
    </script>
</body>
</html>