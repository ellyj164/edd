<?php
/**
 * Analytics & Reporting - Admin Module
 * Comprehensive Analytics Dashboard with Real-time Insights
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();
RoleMiddleware::requireAdmin();

$page_title = 'Analytics & Reporting';
$view = $_GET['view'] ?? 'overview';
$date_range = $_GET['range'] ?? '30d';

// Calculate date range
$date_ranges = [
    '1d' => ['1 day ago', 'Today'],
    '7d' => ['7 days ago', 'Today'],
    '30d' => ['30 days ago', 'Today'],
    '90d' => ['90 days ago', 'Today'],
    '1y' => ['1 year ago', 'Today']
];

$range_info = $date_ranges[$date_range] ?? $date_ranges['30d'];
$start_date = date('Y-m-d', strtotime($range_info[0]));
$end_date = date('Y-m-d', strtotime($range_info[1]));

// Generate analytics data
$analytics = [];

try {
    // Sales Analytics
    $analytics['sales'] = [
        'total_revenue' => Database::query(
            "SELECT COALESCE(SUM(total_amount), 0) FROM orders 
             WHERE status NOT IN ('cancelled', 'refunded') 
             AND DATE(created_at) BETWEEN ? AND ?",
            [$start_date, $end_date]
        )->fetchColumn(),
        
        'total_orders' => Database::query(
            "SELECT COUNT(*) FROM orders 
             WHERE DATE(created_at) BETWEEN ? AND ?",
            [$start_date, $end_date]
        )->fetchColumn(),
        
        'avg_order_value' => Database::query(
            "SELECT COALESCE(AVG(total_amount), 0) FROM orders 
             WHERE status NOT IN ('cancelled', 'refunded') 
             AND DATE(created_at) BETWEEN ? AND ?",
            [$start_date, $end_date]
        )->fetchColumn(),
        
        'conversion_rate' => 0, // Would need visitor tracking data
        
        'daily_sales' => Database::query(
            "SELECT DATE(created_at) as date, COALESCE(SUM(total_amount), 0) as revenue,
                    COUNT(*) as orders
             FROM orders 
             WHERE status NOT IN ('cancelled', 'refunded') 
             AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date",
            [$start_date, $end_date]
        )->fetchAll()
    ];
    
    // Customer Analytics
    $analytics['customers'] = [
        'new_customers' => Database::query(
            "SELECT COUNT(*) FROM users 
             WHERE role = 'customer' 
             AND DATE(created_at) BETWEEN ? AND ?",
            [$start_date, $end_date]
        )->fetchColumn(),
        
        'repeat_customers' => Database::query(
            "SELECT COUNT(DISTINCT user_id) FROM orders 
             WHERE user_id IN (
                 SELECT user_id FROM orders 
                 GROUP BY user_id HAVING COUNT(*) > 1
             )
             AND DATE(created_at) BETWEEN ? AND ?",
            [$start_date, $end_date]
        )->fetchColumn(),
        
        'customer_lifetime_value' => Database::query(
            "SELECT COALESCE(AVG(customer_total), 0) FROM (
                 SELECT user_id, SUM(total_amount) as customer_total
                 FROM orders 
                 WHERE status NOT IN ('cancelled', 'refunded')
                 GROUP BY user_id
             ) as customer_totals"
        )->fetchColumn(),
        
        'top_customers' => Database::query(
            "SELECT u.username, u.email, COUNT(o.id) as order_count, 
                    COALESCE(SUM(o.total_amount), 0) as total_spent
             FROM users u 
             LEFT JOIN orders o ON u.id = o.user_id 
             WHERE u.role = 'customer'
             AND o.status NOT IN ('cancelled', 'refunded')
             AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY u.id 
             ORDER BY total_spent DESC 
             LIMIT 10",
            [$start_date, $end_date]
        )->fetchAll()
    ];
    
    // Product Analytics
    $analytics['products'] = [
        'top_selling' => Database::query(
            "SELECT p.name, p.sku, SUM(oi.quantity) as total_sold,
                    COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
             FROM products p
             JOIN order_items oi ON p.id = oi.product_id
             JOIN orders o ON oi.order_id = o.id
             WHERE o.status NOT IN ('cancelled', 'refunded')
             AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY p.id
             ORDER BY total_sold DESC
             LIMIT 10",
            [$start_date, $end_date]
        )->fetchAll(),
        
        'low_stock' => Database::query(
            "SELECT name, sku, stock_quantity, low_stock_threshold
             FROM products 
             WHERE stock_quantity <= low_stock_threshold
             AND status = 'active'
             ORDER BY stock_quantity ASC
             LIMIT 10"
        )->fetchAll(),
        
        'category_performance' => Database::query(
            "SELECT c.name, COUNT(DISTINCT p.id) as product_count,
                    COALESCE(SUM(oi.quantity), 0) as total_sold,
                    COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
             FROM categories c
             LEFT JOIN products p ON c.id = p.category_id
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id
             WHERE o.status NOT IN ('cancelled', 'refunded')
             AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY c.id
             ORDER BY revenue DESC",
            [$start_date, $end_date]
        )->fetchAll()
    ];
    
    // Vendor Analytics (if marketplace)
    $analytics['vendors'] = [
        'top_vendors' => Database::query(
            "SELECT u.username, COUNT(DISTINCT p.id) as product_count,
                    COALESCE(SUM(oi.quantity), 0) as items_sold,
                    COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
             FROM users u
             LEFT JOIN products p ON u.id = p.vendor_id
             LEFT JOIN order_items oi ON p.id = oi.product_id
             LEFT JOIN orders o ON oi.order_id = o.id
             WHERE u.role = 'vendor'
             AND o.status NOT IN ('cancelled', 'refunded')
             AND DATE(o.created_at) BETWEEN ? AND ?
             GROUP BY u.id
             ORDER BY revenue DESC
             LIMIT 10",
            [$start_date, $end_date]
        )->fetchAll(),
        
        'pending_approvals' => Database::query(
            "SELECT COUNT(*) FROM vendors WHERE status = 'pending'"
        )->fetchColumn()
    ];
    
} catch (Exception $e) {
    error_log("Analytics error: " . $e->getMessage());
    $analytics = [
        'sales' => ['total_revenue' => 0, 'total_orders' => 0, 'avg_order_value' => 0, 'conversion_rate' => 0, 'daily_sales' => []],
        'customers' => ['new_customers' => 0, 'repeat_customers' => 0, 'customer_lifetime_value' => 0, 'top_customers' => []],
        'products' => ['top_selling' => [], 'low_stock' => [], 'category_performance' => []],
        'vendors' => ['top_vendors' => [], 'pending_approvals' => 0]
    ];
}

// Performance metrics for different time periods
$performance_comparison = [];
try {
    $previous_start = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) . ' seconds'));
    $previous_end = date('Y-m-d', strtotime($start_date . ' -1 day'));
    
    $current_revenue = $analytics['sales']['total_revenue'];
    $previous_revenue = Database::query(
        "SELECT COALESCE(SUM(total_amount), 0) FROM orders 
         WHERE status NOT IN ('cancelled', 'refunded') 
         AND DATE(created_at) BETWEEN ? AND ?",
        [$previous_start, $previous_end]
    )->fetchColumn();
    
    $performance_comparison = [
        'revenue_change' => $previous_revenue > 0 ? (($current_revenue - $previous_revenue) / $previous_revenue) * 100 : 0,
        'current_revenue' => $current_revenue,
        'previous_revenue' => $previous_revenue
    ];
} catch (Exception $e) {
    $performance_comparison = ['revenue_change' => 0, 'current_revenue' => 0, 'previous_revenue' => 0];
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 0;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        .stats-card:hover { transform: translateY(-2px); }
        .stats-card.success { border-left-color: #27ae60; }
        .stats-card.warning { border-left-color: #f39c12; }
        .stats-card.danger { border-left-color: #e74c3c; }
        .stats-card.info { border-left-color: #17a2b8; }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .metric-label {
            color: #666;
            font-size: 0.9rem;
        }
        .metric-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        .metric-change.positive { color: #28a745; }
        .metric-change.negative { color: #dc3545; }
        .chart-container {
            position: relative;
            height: 300px;
        }
        .nav-pills .nav-link {
            border-radius: 0.5rem;
            margin: 0 0.25rem;
        }
        .table-analytics {
            font-size: 0.9rem;
        }
        .progress-thin {
            height: 8px;
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
                        <?php echo $page_title; ?>
                    </h1>
                    <small class="text-white-50">Real-time insights and performance metrics</small>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group me-3">
                        <select class="form-select form-select-sm" onchange="changeRange(this.value)">
                            <option value="1d" <?php echo $date_range === '1d' ? 'selected' : ''; ?>>Last 24 Hours</option>
                            <option value="7d" <?php echo $date_range === '7d' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30d" <?php echo $date_range === '30d' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90d" <?php echo $date_range === '90d' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="1y" <?php echo $date_range === '1y' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    <a href="/admin/" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'overview' ? 'active' : ''; ?>" href="?view=overview&range=<?php echo $date_range; ?>">
                    <i class="fas fa-tachometer-alt me-1"></i> Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'sales' ? 'active' : ''; ?>" href="?view=sales&range=<?php echo $date_range; ?>">
                    <i class="fas fa-dollar-sign me-1"></i> Sales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'customers' ? 'active' : ''; ?>" href="?view=customers&range=<?php echo $date_range; ?>">
                    <i class="fas fa-users me-1"></i> Customers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'products' ? 'active' : ''; ?>" href="?view=products&range=<?php echo $date_range; ?>">
                    <i class="fas fa-box me-1"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $view === 'vendors' ? 'active' : ''; ?>" href="?view=vendors&range=<?php echo $date_range; ?>">
                    <i class="fas fa-store me-1"></i> Vendors
                </a>
            </li>
        </ul>

        <?php if ($view === 'overview'): ?>
        <!-- Overview Dashboard -->
        
        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card success">
                    <div class="metric-value text-success">$<?php echo number_format($analytics['sales']['total_revenue'], 2); ?></div>
                    <div class="metric-label">Total Revenue</div>
                    <?php if ($performance_comparison['revenue_change'] != 0): ?>
                    <div class="metric-change <?php echo $performance_comparison['revenue_change'] > 0 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-arrow-<?php echo $performance_comparison['revenue_change'] > 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs(round($performance_comparison['revenue_change'], 1)); ?>% vs previous period
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card info">
                    <div class="metric-value text-info"><?php echo number_format($analytics['sales']['total_orders']); ?></div>
                    <div class="metric-label">Total Orders</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="metric-value">$<?php echo number_format($analytics['sales']['avg_order_value'], 2); ?></div>
                    <div class="metric-label">Average Order Value</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card warning">
                    <div class="metric-value text-warning"><?php echo number_format($analytics['customers']['new_customers']); ?></div>
                    <div class="metric-label">New Customers</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Sales Trend</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top Products</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="productsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top Customers</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-analytics">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Orders</th>
                                        <th>Total Spent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($analytics['customers']['top_customers'], 0, 5) as $customer): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($customer['username']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                        </td>
                                        <td><?php echo $customer['order_count']; ?></td>
                                        <td><strong>$<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Inventory Alerts</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-analytics">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock</th>
                                        <th>Threshold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($analytics['products']['low_stock'], 0, 5) as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($product['sku']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $product['stock_quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $product['low_stock_threshold']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($view === 'sales'): ?>
        <!-- Sales Analytics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Sales Performance</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="detailedSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Sales by Category</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-analytics">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Products</th>
                                        <th>Items Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['products']['category_performance'] as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo $category['product_count']; ?></td>
                                        <td><?php echo number_format($category['total_sold']); ?></td>
                                        <td><strong>$<?php echo number_format($category['revenue'], 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Top Selling Products</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-analytics">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['products']['top_selling'] as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($product['sku']); ?></small>
                                        </td>
                                        <td><?php echo number_format($product['total_sold']); ?></td>
                                        <td><strong>$<?php echo number_format($product['revenue'], 2); ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($view === 'customers'): ?>
        <!-- Customer Analytics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card warning">
                    <div class="metric-value text-warning"><?php echo number_format($analytics['customers']['new_customers']); ?></div>
                    <div class="metric-label">New Customers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card success">
                    <div class="metric-value text-success"><?php echo number_format($analytics['customers']['repeat_customers']); ?></div>
                    <div class="metric-label">Repeat Customers</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card info">
                    <div class="metric-value text-info">$<?php echo number_format($analytics['customers']['customer_lifetime_value'], 2); ?></div>
                    <div class="metric-label">Avg Customer LTV</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="metric-value"><?php echo $analytics['customers']['repeat_customers'] > 0 ? round(($analytics['customers']['repeat_customers'] / ($analytics['customers']['new_customers'] + $analytics['customers']['repeat_customers'])) * 100, 1) : 0; ?>%</div>
                    <div class="metric-label">Retention Rate</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Customer Details</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-analytics">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Avg Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['customers']['top_customers'] as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['username']); ?></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo $customer['order_count']; ?></td>
                                <td><strong>$<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                <td>$<?php echo $customer['order_count'] > 0 ? number_format($customer['total_spent'] / $customer['order_count'], 2) : '0.00'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeRange(range) {
            const url = new URL(window.location);
            url.searchParams.set('range', range);
            window.location.href = url.toString();
        }

        // Sales Chart
        <?php if ($view === 'overview' || $view === 'sales'): ?>
        const salesData = <?php echo json_encode($analytics['sales']['daily_sales']); ?>;
        const salesLabels = salesData.map(d => new Date(d.date).toLocaleDateString());
        const salesValues = salesData.map(d => parseFloat(d.revenue));

        const salesCtx = document.getElementById('<?php echo $view === 'sales' ? 'detailedSalesChart' : 'salesChart'; ?>').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Revenue ($)',
                    data: salesValues,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Products Chart (Overview only)
        <?php if ($view === 'overview'): ?>
        const topProducts = <?php echo json_encode(array_slice($analytics['products']['top_selling'], 0, 5)); ?>;
        const productLabels = topProducts.map(p => p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name);
        const productValues = topProducts.map(p => parseInt(p.total_sold));

        const productsCtx = document.getElementById('productsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'doughnut',
            data: {
                labels: productLabels,
                datasets: [{
                    data: productValues,
                    backgroundColor: [
                        '#3498db',
                        '#e74c3c',
                        '#f39c12',
                        '#27ae60',
                        '#9b59b6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>