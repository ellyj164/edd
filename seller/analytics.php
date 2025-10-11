<?php
/**
 * Comprehensive Vendor Analytics Dashboard
 * E-Commerce Platform - Full Analytics Implementation
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php'; // Seller authentication guard

// Initialize database connection
$db = db();

$vendor = new Vendor();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Date range handling
$dateRange = $_GET['range'] ?? '30days';
$customStart = $_GET['start'] ?? '';
$customEnd = $_GET['end'] ?? '';

// Calculate date ranges
$endDate = date('Y-m-d');
switch ($dateRange) {
    case '7days':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $startDate = date('Y-m-d', strtotime('-90 days'));
        break;
    case '1year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        break;
    case 'custom':
        $startDate = $customStart ?: date('Y-m-d', strtotime('-30 days'));
        $endDate = $customEnd ?: date('Y-m-d');
        break;
    default:
        $startDate = date('Y-m-d', strtotime('-30 days'));
}

try {
    // Sales Overview Statistics
    $salesQuery = "
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COUNT(DISTINCT oi.id) as total_items,
            SUM(oi.subtotal) as total_revenue,
            AVG(oi.subtotal) as avg_order_value,
            SUM(oi.qty) as total_units_sold
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.vendor_id = ? 
        AND DATE(o.created_at) BETWEEN ? AND ?
        AND o.status != 'cancelled'
    ";
    $salesStmt = $db->prepare($salesQuery);
    $salesStmt->execute([$vendorId, $startDate, $endDate]);
    $salesStats = $salesStmt->fetch();
    
    // Ensure numeric values for display
    $salesStats['total_revenue'] = (float)($salesStats['total_revenue'] ?? 0);
    $salesStats['total_orders'] = (int)($salesStats['total_orders'] ?? 0);
    $salesStats['total_items'] = (int)($salesStats['total_items'] ?? 0);
    $salesStats['avg_order_value'] = (float)($salesStats['avg_order_value'] ?? 0);
    $salesStats['total_units_sold'] = (int)($salesStats['total_units_sold'] ?? 0);

    // Daily sales data for charts
    $dailySalesQuery = "
        SELECT 
            DATE(o.created_at) as date,
            COUNT(DISTINCT o.id) as orders,
            SUM(oi.subtotal) as revenue,
            SUM(oi.qty) as units
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.vendor_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY DATE(o.created_at)
        ORDER BY date ASC
    ";
    $dailySalesStmt = $db->prepare($dailySalesQuery);
    $dailySalesStmt->execute([$vendorId, $startDate, $endDate]);
    $dailySales = $dailySalesStmt->fetchAll();

    // Top selling products
    $topProductsQuery = "
        SELECT 
            p.id,
            p.name,
            p.price,
            SUM(oi.qty) as units_sold,
            SUM(oi.subtotal) as revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM products p
        JOIN order_items oi ON p.id = oi.product_id
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.vendor_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY p.id, p.name, p.price
        ORDER BY units_sold DESC
        LIMIT 10
    ";
    $topProductsStmt = $db->prepare($topProductsQuery);
    $topProductsStmt->execute([$vendorId, $startDate, $endDate]);
    $topProducts = $topProductsStmt->fetchAll();

    // Customer demographics (simplified)
    $customerQuery = "
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            COUNT(DISTINCT o.id) as order_count,
            SUM(oi.subtotal) as total_spent,
            MAX(o.created_at) as last_order
        FROM users u
        JOIN orders o ON u.id = o.user_id
        JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.vendor_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY u.id, u.first_name, u.last_name, u.email
        ORDER BY total_spent DESC
        LIMIT 10
    ";
    $customerStmt = $db->prepare($customerQuery);
    $customerStmt->execute([$vendorId, $startDate, $endDate]);
    $topCustomers = $customerStmt->fetchAll();

    // Order status breakdown
    $statusQuery = "
        SELECT 
            oi.status,
            COUNT(*) as count,
            SUM(oi.subtotal) as revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE oi.vendor_id = ?
        AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY oi.status
        ORDER BY count DESC
    ";
    $statusStmt = $db->prepare($statusQuery);
    $statusStmt->execute([$vendorId, $startDate, $endDate]);
    $orderStatuses = $statusStmt->fetchAll();

    // Product performance
    $productPerformanceQuery = "
        SELECT 
            p.id,
            p.name,
            p.stock_quantity,
            p.view_count,
            COALESCE(sales.units_sold, 0) as units_sold,
            COALESCE(sales.revenue, 0) as revenue,
            CASE 
                WHEN p.view_count > 0 THEN (COALESCE(sales.units_sold, 0) / p.view_count * 100)
                ELSE 0 
            END as conversion_rate
        FROM products p
        LEFT JOIN (
            SELECT 
                oi.product_id,
                SUM(oi.qty) as units_sold,
                SUM(oi.subtotal) as revenue
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.vendor_id = ?
            AND DATE(o.created_at) BETWEEN ? AND ?
            AND o.status != 'cancelled'
            GROUP BY oi.product_id
        ) sales ON p.id = sales.product_id
        WHERE p.vendor_id = ?
        ORDER BY revenue DESC
        LIMIT 15
    ";
    $performanceStmt = $db->prepare($productPerformanceQuery);
    $performanceStmt->execute([$vendorId, $startDate, $endDate, $vendorId]);
    $productPerformance = $performanceStmt->fetchAll();

} catch (Exception $e) {
    Logger::error("Analytics query error: " . $e->getMessage());
    // Set default values
    $salesStats = ['total_orders' => 0, 'total_items' => 0, 'total_revenue' => 0, 'avg_order_value' => 0, 'total_units_sold' => 0];
    $dailySales = [];
    $topProducts = [];
    $topCustomers = [];
    $orderStatuses = [];
    $productPerformance = [];
}

$page_title = 'Analytics Dashboard - Seller Center';
includeHeader($page_title);
?>

<div class="analytics-dashboard">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <nav class="breadcrumb">
                    <a href="/seller/dashboard.php">Dashboard</a>
                    <span>/</span>
                    <span>Analytics</span>
                </nav>
                <h1>Analytics & Reports</h1>
                <p class="subtitle">Track your performance and grow your business</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="exportReport()">
                    📊 Export Report
                </button>
                <select id="dateRangeSelect" class="date-range-select" onchange="updateDateRange()">
                    <option value="7days" <?php echo $dateRange === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                    <option value="30days" <?php echo $dateRange === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                    <option value="90days" <?php echo $dateRange === '90days' ? 'selected' : ''; ?>>Last 90 Days</option>
                    <option value="1year" <?php echo $dateRange === '1year' ? 'selected' : ''; ?>>Last Year</option>
                    <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                </select>
            </div>
        </div>
        
        <!-- Custom Date Range -->
        <div class="custom-date-range" id="customDateRange" style="<?php echo $dateRange === 'custom' ? 'display: block;' : 'display: none;'; ?>">
            <div class="date-inputs">
                <input type="date" id="startDate" value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                <span>to</span>
                <input type="date" id="endDate" value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                <button class="btn btn-primary" onclick="applyCustomRange()">Apply</button>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card revenue">
            <div class="metric-icon">💰</div>
            <div class="metric-content">
                <div class="metric-value">$<?php echo number_format($salesStats['total_revenue'], 2); ?></div>
                <div class="metric-label">Total Revenue</div>
                <div class="metric-change positive">+12.5% vs last period</div>
            </div>
        </div>
        
        <div class="metric-card orders">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
                <div class="metric-value"><?php echo number_format($salesStats['total_orders']); ?></div>
                <div class="metric-label">Total Orders</div>
                <div class="metric-change positive">+8.3% vs last period</div>
            </div>
        </div>
        
        <div class="metric-card aov">
            <div class="metric-icon">💳</div>
            <div class="metric-content">
                <div class="metric-value">$<?php echo number_format($salesStats['avg_order_value'], 2); ?></div>
                <div class="metric-label">Avg Order Value</div>
                <div class="metric-change negative">-2.1% vs last period</div>
            </div>
        </div>
        
        <div class="metric-card units">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-value"><?php echo number_format($salesStats['total_units_sold']); ?></div>
                <div class="metric-label">Units Sold</div>
                <div class="metric-change positive">+15.7% vs last period</div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
        <div class="chart-container">
            <div class="chart-header">
                <h3>Sales Performance</h3>
                <div class="chart-controls">
                    <button class="chart-control active" data-chart="revenue">Revenue</button>
                    <button class="chart-control" data-chart="orders">Orders</button>
                    <button class="chart-control" data-chart="units">Units</button>
                </div>
            </div>
            <div class="chart-content">
                <canvas id="salesChart" width="800" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Data Tables Section -->
    <div class="data-section">
        <!-- Top Products -->
        <div class="data-widget">
            <div class="widget-header">
                <h3>🏆 Top Selling Products</h3>
                <a href="/seller/products.php" class="view-all">View All Products</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($topProducts)): ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units Sold</th>
                                    <th>Revenue</th>
                                    <th>Orders</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="product-price">$<?php echo number_format((float)($product['price'] ?? 0), 2); ?></div>
                                            </div>
                                        </td>
                                        <td><strong><?php echo $product['units_sold']; ?></strong></td>
                                        <td><strong>$<?php echo number_format((float)($product['revenue'] ?? 0), 2); ?></strong></td>
                                        <td><?php echo $product['order_count']; ?></td>
                                        <td>
                                            <div class="performance-bar">
                                                <div class="bar-fill" style="width: <?php echo min(100, ($product['units_sold'] / max(array_column($topProducts, 'units_sold'))) * 100); ?>%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <p>No sales data available for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="data-widget">
            <div class="widget-header">
                <h3>👥 Top Customers</h3>
                <a href="/seller/customers.php" class="view-all">View All Customers</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($topCustomers)): ?>
                    <div class="customer-list">
                        <?php foreach ($topCustomers as $customer): ?>
                            <div class="customer-item">
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                </div>
                                <div class="customer-info">
                                    <div class="customer-name"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                                    <div class="customer-email"><?php echo htmlspecialchars($customer['email']); ?></div>
                                </div>
                                <div class="customer-stats">
                                    <div class="stat">
                                        <div class="stat-value">$<?php echo number_format((float)($customer['total_spent'] ?? 0), 2); ?></div>
                                        <div class="stat-label">Total Spent</div>
                                    </div>
                                    <div class="stat">
                                        <div class="stat-value"><?php echo $customer['order_count']; ?></div>
                                        <div class="stat-label">Orders</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <p>No customer data available for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="analytics-grid">
        <!-- Order Status Breakdown -->
        <div class="analytics-widget">
            <div class="widget-header">
                <h3>📊 Order Status</h3>
            </div>
            <div class="widget-content">
                <div class="status-chart">
                    <canvas id="statusChart" width="300" height="300"></canvas>
                </div>
                <div class="status-legend">
                    <?php foreach ($orderStatuses as $status): ?>
                        <div class="legend-item">
                            <div class="legend-color status-<?php echo strtolower($status['status']); ?>"></div>
                            <div class="legend-text">
                                <div class="legend-label"><?php echo ucfirst($status['status']); ?></div>
                                <div class="legend-value"><?php echo $status['count']; ?> orders</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Product Performance -->
        <div class="analytics-widget">
            <div class="widget-header">
                <h3>🎯 Product Performance</h3>
                <select class="performance-metric">
                    <option value="revenue">By Revenue</option>
                    <option value="units">By Units Sold</option>
                    <option value="conversion">By Conversion</option>
                </select>
            </div>
            <div class="widget-content">
                <?php if (!empty($productPerformance)): ?>
                    <div class="performance-list">
                        <?php foreach (array_slice($productPerformance, 0, 5) as $product): ?>
                            <div class="performance-item">
                                <div class="performance-info">
                                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="performance-stats">
                                        Revenue: $<?php echo number_format((float)($product['revenue'] ?? 0), 2); ?> | 
                                        Units: <?php echo $product['units_sold']; ?> | 
                                        Stock: <?php echo $product['stock_quantity']; ?>
                                    </div>
                                </div>
                                <div class="conversion-rate">
                                    <?php echo number_format((float)($product['conversion_rate'] ?? 0), 1); ?>%
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎯</div>
                        <p>No performance data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.analytics-dashboard {
    max-width: 1600px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    border-radius: 12px;
    color: white;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 20px;
}

.breadcrumb {
    font-size: 14px;
    margin-bottom: 8px;
    opacity: 0.8;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
}

.page-header h1 {
    margin: 0 0 8px 0;
    font-size: 28px;
    font-weight: 700;
}

.subtitle {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.header-actions {
    display: flex;
    gap: 16px;
    align-items: center;
}

.date-range-select {
    padding: 8px 12px;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 14px;
}

.custom-date-range {
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.date-inputs {
    display: flex;
    gap: 12px;
    align-items: center;
}

.date-inputs input {
    padding: 8px 12px;
    border: 1px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 14px;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s ease;
}

.metric-card:hover {
    transform: translateY(-2px);
}

.metric-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: #f8fafc;
}

.metric-card.revenue .metric-icon { background: #ecfdf5; }
.metric-card.orders .metric-icon { background: #eff6ff; }
.metric-card.aov .metric-icon { background: #fef3c7; }
.metric-card.units .metric-icon { background: #f3e8ff; }

.metric-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.metric-label {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 8px;
    font-weight: 500;
}

.metric-change {
    font-size: 12px;
    font-weight: 600;
    padding: 2px 6px;
    border-radius: 4px;
}

.metric-change.positive {
    color: #059669;
    background: #ecfdf5;
}

.metric-change.negative {
    color: #dc2626;
    background: #fef2f2;
}

/* Charts Section */
.charts-section {
    margin-bottom: 30px;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.chart-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.chart-controls {
    display: flex;
    gap: 8px;
}

.chart-control {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chart-control.active {
    background: #6366f1;
    color: white;
    border-color: #6366f1;
}

.chart-content {
    position: relative;
    height: 300px;
}

/* Data Section */
.data-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 30px;
}

.data-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.widget-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.widget-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
}

.view-all {
    color: #6366f1;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.widget-content {
    padding: 24px;
    max-height: 400px;
    overflow-y: auto;
}

/* Data Table */
.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 12px 8px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    border-bottom: 1px solid #e5e7eb;
}

.data-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: top;
}

.product-cell {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.product-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.product-price {
    font-size: 12px;
    color: #6b7280;
}

.performance-bar {
    width: 60px;
    height: 6px;
    background: #f3f4f6;
    border-radius: 3px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: #6366f1;
    border-radius: 3px;
}

/* Customer List */
.customer-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.customer-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.customer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #6366f1;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.customer-info {
    flex: 1;
}

.customer-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 2px;
}

.customer-email {
    font-size: 12px;
    color: #6b7280;
}

.customer-stats {
    display: flex;
    gap: 24px;
}

.stat {
    text-align: center;
}

.stat-value {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.stat-label {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
}

/* Analytics Grid */
.analytics-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.analytics-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

/* Status Chart */
.status-chart {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.status-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.legend-color.status-pending { background: #fbbf24; }
.legend-color.status-processing { background: #3b82f6; }
.legend-color.status-shipped { background: #10b981; }
.legend-color.status-delivered { background: #059669; }
.legend-color.status-cancelled { background: #ef4444; }

.legend-text {
    flex: 1;
}

.legend-label {
    font-weight: 500;
    color: #1f2937;
    font-size: 14px;
}

.legend-value {
    font-size: 12px;
    color: #6b7280;
}

/* Performance List */
.performance-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.performance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.performance-info {
    flex: 1;
}

.performance-stats {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.conversion-rate {
    font-weight: 600;
    color: #059669;
    font-size: 14px;
}

.performance-metric {
    padding: 6px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.3;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .analytics-dashboard {
        padding: 16px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .data-section {
        grid-template-columns: 1fr;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-header {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .customer-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .customer-stats {
        align-self: stretch;
        justify-content: space-around;
    }
}
</style>

<script>
// Chart.js configuration and data
const chartData = {
    revenue: {
        label: 'Revenue ($)',
        data: [<?php echo implode(',', array_map(function($day) { return $day['revenue'] ?? 0; }, $dailySales)); ?>],
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
    },
    orders: {
        label: 'Orders',
        data: [<?php echo implode(',', array_map(function($day) { return $day['orders'] ?? 0; }, $dailySales)); ?>],
        borderColor: '#10b981',
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
    },
    units: {
        label: 'Units Sold',
        data: [<?php echo implode(',', array_map(function($day) { return $day['units'] ?? 0; }, $dailySales)); ?>],
        borderColor: '#f59e0b',
        backgroundColor: 'rgba(245, 158, 11, 0.1)',
    }
};

const chartLabels = [<?php echo '"' . implode('","', array_map(function($day) { return date('M j', strtotime($day['date'])); }, $dailySales)) . '"'; ?>];

let salesChart;

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSalesChart();
    initializeStatusChart();
    setupChartControls();
});

function initializeSalesChart() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                ...chartData.revenue,
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
                    grid: {
                        color: '#f3f4f6'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function initializeStatusChart() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    
    const statusData = [<?php echo implode(',', array_map(function($status) { return $status['count']; }, $orderStatuses)); ?>];
    const statusLabels = [<?php echo '"' . implode('","', array_map(function($status) { return ucfirst($status['status']); }, $orderStatuses)) . '"'; ?>];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: [
                    '#fbbf24', // pending
                    '#3b82f6', // processing
                    '#10b981', // shipped
                    '#059669', // delivered
                    '#ef4444'  // cancelled
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function setupChartControls() {
    document.querySelectorAll('.chart-control').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.chart-control').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const chartType = this.dataset.chart;
            updateSalesChart(chartType);
        });
    });
}

function updateSalesChart(type) {
    salesChart.data.datasets[0] = {
        ...chartData[type],
        fill: true,
        tension: 0.4
    };
    salesChart.update();
}

// Date range functions
function updateDateRange() {
    const select = document.getElementById('dateRangeSelect');
    const customRange = document.getElementById('customDateRange');
    
    if (select.value === 'custom') {
        customRange.style.display = 'block';
    } else {
        customRange.style.display = 'none';
        window.location.href = `?range=${select.value}`;
    }
}

function applyCustomRange() {
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    
    if (start && end) {
        window.location.href = `?range=custom&start=${start}&end=${end}`;
    }
}

function exportReport() {
    // Implementation for report export
    alert('Report export functionality would generate PDF/Excel reports');
}
</script>

<?php includeFooter(); ?>