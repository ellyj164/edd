<?php
/**
 * Financial Management - Admin Module
 * Transaction tracking, vendor payouts, and financial reporting
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
requireAdminPermission('FINANCE_VIEW');

$page_title = 'Financial Management';
$action = $_GET['action'] ?? 'dashboard';

// (PHP logic for handling POST requests remains the same)
// ...

// Get financial statistics
try {
    $stats = [
        'total_revenue' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'payment' AND status = 'completed'")->fetchColumn(),
        'monthly_revenue' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'payment' AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
        'pending_payouts' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM vendor_payouts WHERE status = 'pending'")->fetchColumn(),
        'processed_payouts' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM vendor_payouts WHERE status = 'completed'")->fetchColumn(),
        'total_transactions' => Database::query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
        'failed_transactions' => Database::query("SELECT COUNT(*) FROM transactions WHERE status = 'failed'")->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = [
        'total_revenue' => 0, 'monthly_revenue' => 0, 'pending_payouts' => 0, 
        'processed_payouts' => 0, 'total_transactions' => 0, 'failed_transactions' => 0
    ];
}

// (PHP logic for fetching data based on action remains the same)
// ...

// Include admin header - We will add our own head content for this page specifically
// require_once __DIR__ . '/../../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .stats-card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .stats-card .card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stats-card .stats-value {
            font-size: 1.75rem;
            font-weight: 600;
        }
        .stats-card .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .stats-card .stats-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <h1 class="h2"><i class="fas fa-dollar-sign me-2"></i>Financial Management</h1>
                <p class="text-muted">Monitor transactions, process payouts, and generate financial reports</p>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>" href="?action=dashboard">
                        <i class="fas fa-chart-pie me-1"></i> Financial Dashboard
                    </a>
                </li>
                <?php if (hasAdminPermission('FINANCE_TRANSACTIONS')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'transactions' ? 'active' : ''; ?>" href="?action=transactions">
                        <i class="fas fa-exchange-alt me-1"></i> Transactions
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasAdminPermission('FINANCE_PAYOUTS')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'payouts' ? 'active' : ''; ?>" href="?action=payouts">
                        <i class="fas fa-money-check-alt me-1"></i> Vendor Payouts
                    </a>
                </li>
                <?php endif; ?>
                <?php if (hasAdminPermission('FINANCE_REPORTS')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $action === 'reports' ? 'active' : ''; ?>" href="?action=reports">
                        <i class="fas fa-chart-line me-1"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <?php if ($action === 'dashboard'): ?>
    <!-- Financial Dashboard -->
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4 col-xl-2">
            <div class="card stats-card">
                <div class="card-body">
                    <div>
                        <div class="stats-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                        <div class="stats-label">Total Revenue</div>
                    </div>
                    <i class="fas fa-sack-dollar stats-icon text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card stats-card">
                <div class="card-body">
                    <div>
                        <div class="stats-value">$<?php echo number_format($stats['monthly_revenue'], 2); ?></div>
                        <div class="stats-label">30-Day Revenue</div>
                    </div>
                    <i class="fas fa-calendar-day stats-icon text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card stats-card">
                <div class="card-body">
                    <div>
                        <div class="stats-value">$<?php echo number_format($stats['pending_payouts'], 2); ?></div>
                        <div class="stats-label">Pending Payouts</div>
                    </div>
                    <i class="fas fa-hourglass-half stats-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card stats-card">
                <div class="card-body">
                    <div>
                        <div class="stats-value">$<?php echo number_format($stats['processed_payouts'], 2); ?></div>
                        <div class="stats-label">Processed Payouts</div>
                    </div>
                    <i class="fas fa-check-double stats-icon text-info"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card stats-card">
                <div class="card-body">
                    <div>
                        <div class="stats-value"><?php echo number_format($stats['total_transactions']); ?></div>
                        <div class="stats-label">Total Transactions</div>
                    </div>
                    <i class="fas fa-receipt stats-icon text-secondary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-2">
            <div class="card stats-card">
                <div class="card-body">
                    <div>
                        <div class="stats-value"><?php echo number_format($stats['failed_transactions']); ?></div>
                        <div class="stats-label">Failed Transactions</div>
                    </div>
                    <i class="fas fa-exclamation-triangle stats-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-chart-area me-2"></i>Revenue Trends</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (hasAdminPermission('FINANCE_TRANSACTIONS')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualTransactionModal">
                            <i class="fas fa-plus me-1"></i> Create Manual Transaction
                        </button>
                        <?php endif; ?>
                        <?php if (hasAdminPermission('FINANCE_REPORTS')): ?>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                            <i class="fas fa-chart-line me-1"></i> Generate Report
                        </button>
                        <?php endif; ?>
                        <a href="?action=transactions" class="btn btn-info">
                            <i class="fas fa-list me-1"></i> View All Transactions
                        </a>
                        <a href="?action=payouts" class="btn btn-warning">
                            <i class="fas fa-money-check-alt me-1"></i> Manage Payouts
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // The rest of the page content for 'transactions', 'payouts', 'reports' actions
    // would go here, similarly refactored to use Bootstrap 5 cards and grids.
    // For brevity, I'm showing just the dashboard fix.
    ?>

</div>

<!-- Modals would go here -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
// The original PHP for additional scripts remains the same
$additional_scripts = '
<script>
// (All the original JavaScript functions like filterTransactions, processPayout, etc.)
// ...

// Revenue chart
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("revenueChart");
    if (ctx) {
        new Chart(ctx, {
            type: "line",
            data: {
                labels: ["7 days ago", "6 days ago", "5 days ago", "4 days ago", "3 days ago", "2 days ago", "Yesterday", "Today"],
                datasets: [{
                    label: "Revenue",
                    data: [1200, 1900, 800, 2500, 1500, 2200, 1800, 2100],
                    borderColor: "rgb(75, 192, 192)",
                    backgroundColor: "rgba(75, 192, 192, 0.1)",
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return "$" + value.toLocaleString(); }
                        }
                    }
                }
            }
        });
    }
});
</script>';

// We'll echo the scripts here directly
echo $additional_scripts;

// The original footer include is likely not needed as we've included scripts manually
// require_once __DIR__ . '/../../includes/footer.php';
?>
</body>
</html>