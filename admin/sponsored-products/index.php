<?php
/**
 * Admin - Sponsored Products Management
 * E-Commerce Platform
 */

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rbac.php';

// Check admin authentication
requireAdminAuth();
checkPermission('marketing.view');

$db = db();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'update_pricing':
                checkPermission('marketing.manage');
                $newPrice = (float)$_POST['price'];
                
                if ($newPrice < 0) {
                    throw new Exception('Price must be a positive number.');
                }
                
                $updateQuery = "
                    UPDATE sponsored_product_settings 
                    SET setting_value = ?, updated_at = NOW(), updated_by = ?
                    WHERE setting_key = 'price_per_7_days'
                ";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$newPrice, Session::getUserId()]);
                
                Session::setFlash('success', 'Ad pricing updated successfully!');
                break;
                
            case 'approve_ad':
                checkPermission('marketing.manage');
                $adId = (int)$_POST['ad_id'];
                
                $updateQuery = "
                    UPDATE sponsored_products 
                    SET status = 'active', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                    WHERE id = ?
                ";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([Session::getUserId(), $adId]);
                
                Session::setFlash('success', 'Sponsored ad approved successfully!');
                break;
                
            case 'reject_ad':
                checkPermission('marketing.manage');
                $adId = (int)$_POST['ad_id'];
                $reason = trim($_POST['reason'] ?? '');
                
                $updateQuery = "
                    UPDATE sponsored_products 
                    SET status = 'rejected', rejected_reason = ?, updated_at = NOW()
                    WHERE id = ?
                ";
                $stmt = $db->prepare($updateQuery);
                $stmt->execute([$reason, $adId]);
                
                Session::setFlash('success', 'Sponsored ad rejected.');
                break;
                
            case 'manual_sponsor':
                checkPermission('marketing.manage');
                $productId = (int)$_POST['product_id'];
                $duration = (int)$_POST['duration'];
                
                if ($duration <= 0) {
                    throw new Exception('Duration must be greater than 0.');
                }
                
                // Get product info
                $productQuery = "SELECT vendor_id FROM products WHERE id = ?";
                $productStmt = $db->prepare($productQuery);
                $productStmt->execute([$productId]);
                $product = $productStmt->fetch();
                
                if (!$product) {
                    throw new Exception('Product not found.');
                }
                
                $sponsoredFrom = date('Y-m-d H:i:s');
                $sponsoredUntil = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
                
                $insertQuery = "
                    INSERT INTO sponsored_products 
                    (product_id, vendor_id, seller_id, cost, payment_method, payment_status, 
                     status, sponsored_from, sponsored_until, approved_by, approved_at, created_at)
                    VALUES (?, ?, ?, 0, 'admin', 'paid', 'active', ?, ?, ?, NOW(), NOW())
                ";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    $productId, $product['vendor_id'], $product['vendor_id'],
                    $sponsoredFrom, $sponsoredUntil, Session::getUserId()
                ]);
                
                Session::setFlash('success', 'Product manually sponsored for ' . $duration . ' days!');
                break;
        }
        
    } catch (Exception $e) {
        Session::setFlash('error', 'Error: ' . $e->getMessage());
    }
    
    redirect('/admin/sponsored-products/');
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "sp.status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(p.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get sponsored ads
$adsQuery = "
    SELECT sp.*, 
           p.name as product_name, p.image_url as product_image, p.price as product_price,
           u.email as seller_email,
           v.shop_name as vendor_name,
           admin.email as approved_by_email
    FROM sponsored_products sp
    INNER JOIN products p ON sp.product_id = p.id
    INNER JOIN users u ON sp.seller_id = u.id
    LEFT JOIN vendors v ON sp.vendor_id = v.id
    LEFT JOIN users admin ON sp.approved_by = admin.id
    {$whereClause}
    ORDER BY sp.created_at DESC
";
$adsStmt = $db->prepare($adsQuery);
$adsStmt->execute($params);
$ads = $adsStmt->fetchAll();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_ads,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_ads,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ads,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_ads,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_ads,
        SUM(impressions) as total_impressions,
        SUM(clicks) as total_clicks,
        SUM(cost) as total_revenue
    FROM sponsored_products
";
$statsStmt = $db->query($statsQuery);
$stats = $statsStmt->fetch();

// Get pricing
$pricingQuery = "SELECT setting_value FROM sponsored_product_settings WHERE setting_key = 'price_per_7_days'";
$pricingStmt = $db->query($pricingQuery);
$currentPrice = (float)($pricingStmt->fetchColumn() ?: 50.00);

$page_title = 'Sponsored Products Management';
include __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-sponsored-products">
    <div class="page-header">
        <h1>📢 Sponsored Products Management</h1>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="showPricingModal()">
                💰 Update Pricing
            </button>
            <button class="btn btn-primary" onclick="showManualSponsorModal()">
                + Manual Sponsor
            </button>
        </div>
    </div>

    <!-- Stats Dashboard -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_ads']); ?></div>
                <div class="stat-label">Total Ads</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['pending_ads']); ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['active_ads']); ?></div>
                <div class="stat-label">Active Ads</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">👁️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_impressions']); ?></div>
                <div class="stat-label">Total Impressions</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">🖱️</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo number_format($stats['total_clicks']); ?></div>
                <div class="stat-label">Total Clicks</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
    </div>

    <!-- Current Pricing Info -->
    <div class="pricing-info">
        <strong>Current Pricing:</strong> $<?php echo number_format($currentPrice, 2); ?> per product for 7 days
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       placeholder="Product name or seller email...">
            </div>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($statusFilter !== 'all' || !empty($searchQuery)): ?>
                <a href="/admin/sponsored-products/" class="btn btn-ghost">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Sponsored Ads List -->
    <div class="ads-table-container">
        <?php if (!empty($ads)): ?>
            <table class="ads-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Seller</th>
                        <th>Cost</th>
                        <th>Status</th>
                        <th>Period</th>
                        <th>Performance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ads as $ad): ?>
                        <?php 
                            $isActive = $ad['status'] === 'active' && strtotime($ad['sponsored_until']) > time();
                            $isExpired = $ad['status'] === 'expired' || ($ad['status'] === 'active' && strtotime($ad['sponsored_until']) <= time());
                        ?>
                        <tr class="ad-row">
                            <td>
                                <div class="product-cell">
                                    <?php if ($ad['product_image']): ?>
                                        <img src="<?php echo htmlspecialchars($ad['product_image']); ?>" 
                                             alt="Product" class="product-thumbnail">
                                    <?php endif; ?>
                                    <div>
                                        <div class="product-name"><?php echo htmlspecialchars($ad['product_name']); ?></div>
                                        <div class="product-price">$<?php echo number_format($ad['product_price'], 2); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="seller-cell">
                                    <div><?php echo htmlspecialchars($ad['vendor_name'] ?? 'N/A'); ?></div>
                                    <div class="text-muted"><?php echo htmlspecialchars($ad['seller_email']); ?></div>
                                </div>
                            </td>
                            <td>
                                <strong>$<?php echo number_format($ad['cost'], 2); ?></strong>
                                <div class="text-muted"><?php echo ucfirst($ad['payment_method']); ?></div>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($ad['status']); ?>">
                                    <?php echo ucfirst($ad['status']); ?>
                                </span>
                                <?php if ($ad['payment_status'] !== 'paid'): ?>
                                    <div class="text-muted">Pay: <?php echo ucfirst($ad['payment_status']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="date-cell">
                                    <div><strong>From:</strong> <?php echo date('M j, Y', strtotime($ad['sponsored_from'])); ?></div>
                                    <div><strong>Until:</strong> <?php echo date('M j, Y', strtotime($ad['sponsored_until'])); ?></div>
                                    <?php if ($isActive): ?>
                                        <div class="text-success">
                                            <?php echo ceil((strtotime($ad['sponsored_until']) - time()) / 86400); ?> days left
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="performance-cell">
                                    <div>👁️ <?php echo number_format($ad['impressions']); ?></div>
                                    <div>🖱️ <?php echo number_format($ad['clicks']); ?></div>
                                    <?php if ($ad['impressions'] > 0): ?>
                                        <div class="text-muted">
                                            CTR: <?php echo number_format(($ad['clicks'] / $ad['impressions']) * 100, 2); ?>%
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($ad['status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="approveAd(<?php echo $ad['id']; ?>)">
                                            Approve
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="showRejectModal(<?php echo $ad['id']; ?>)">
                                            Reject
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-ghost" 
                                                onclick="viewAdDetails(<?php echo $ad['id']; ?>)">
                                            View Details
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📢</div>
                <h3>No sponsored ads found</h3>
                <p>No sponsored ads match your filters.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Update Pricing Modal -->
<div class="modal-overlay" id="pricingModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Update Ad Pricing</h3>
            <button class="modal-close" onclick="closePricingModal()">✕</button>
        </div>
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="update_pricing">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="price">Price per Product (7 days) *</label>
                <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input type="number" id="price" name="price" 
                           value="<?php echo $currentPrice; ?>" 
                           min="0" step="0.01" required>
                </div>
                <div class="form-help">This price will be charged to sellers for each product they sponsor.</div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closePricingModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Pricing</button>
            </div>
        </form>
    </div>
</div>

<!-- Manual Sponsor Modal -->
<div class="modal-overlay" id="manualSponsorModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Manually Sponsor Product</h3>
            <button class="modal-close" onclick="closeManualSponsorModal()">✕</button>
        </div>
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="manual_sponsor">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="product_id">Product ID *</label>
                <input type="number" id="product_id" name="product_id" required min="1">
                <div class="form-help">Enter the product ID you want to sponsor.</div>
            </div>
            
            <div class="form-group">
                <label for="duration">Sponsorship Duration (days) *</label>
                <input type="number" id="duration" name="duration" value="7" required min="1">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closeManualSponsorModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Sponsor Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Reject Sponsored Ad</h3>
            <button class="modal-close" onclick="closeRejectModal()">✕</button>
        </div>
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="reject_ad">
            <input type="hidden" id="reject_ad_id" name="ad_id">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-group">
                <label for="reason">Rejection Reason</label>
                <textarea id="reason" name="reason" rows="4" 
                          placeholder="Provide a reason for rejection..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Ad</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-sponsored-products {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
    font-size: 28px;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    font-size: 24px;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: #f8fafc;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
}

.pricing-info {
    background: #f0f9ff;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #3b82f6;
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.filters-form {
    display: flex;
    gap: 16px;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    font-size: 14px;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    min-width: 200px;
}

.ads-table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    overflow-x: auto;
}

.ads-table {
    width: 100%;
    border-collapse: collapse;
}

.ads-table th {
    background: #f9fafb;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #6b7280;
    font-size: 12px;
    text-transform: uppercase;
    border-bottom: 2px solid #e5e7eb;
}

.ads-table td {
    padding: 16px 12px;
    border-bottom: 1px solid #e5e7eb;
}

.product-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-thumbnail {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
}

.product-name {
    font-weight: 600;
    color: #1f2937;
}

.product-price {
    color: #6b7280;
    font-size: 14px;
}

.seller-cell,
.date-cell,
.performance-cell {
    font-size: 14px;
}

.text-muted {
    color: #6b7280;
    font-size: 12px;
}

.text-success {
    color: #059669;
    font-size: 12px;
    font-weight: 600;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-expired {
    background: #f3f4f6;
    color: #6b7280;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal {
    background: white;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6b7280;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

.modal-close:hover {
    background: #f3f4f6;
}

.modal-content {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1f2937;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.input-group {
    position: relative;
    display: flex;
    align-items: center;
}

.input-prefix {
    position: absolute;
    left: 12px;
    color: #6b7280;
    font-weight: 600;
}

.input-group input {
    padding-left: 32px;
}

.form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.btn {
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 14px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #1f2937;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-ghost {
    background: none;
    color: #6b7280;
}

.btn-ghost:hover {
    background: #f3f4f6;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    margin: 0 0 8px 0;
    color: #1f2937;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group input,
    .filter-group select {
        min-width: 100%;
    }
}
</style>

<script>
function showPricingModal() {
    document.getElementById('pricingModal').style.display = 'flex';
}

function closePricingModal() {
    document.getElementById('pricingModal').style.display = 'none';
}

function showManualSponsorModal() {
    document.getElementById('manualSponsorModal').style.display = 'flex';
}

function closeManualSponsorModal() {
    document.getElementById('manualSponsorModal').style.display = 'none';
}

function showRejectModal(adId) {
    document.getElementById('reject_ad_id').value = adId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function approveAd(adId) {
    if (confirm('Approve this sponsored ad?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="approve_ad">
            <input type="hidden" name="ad_id" value="${adId}">
            <?php echo csrfTokenInput(); ?>
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewAdDetails(adId) {
    alert('View details for ad ID: ' + adId + '\n\nThis would open a detailed view modal.');
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../../includes/admin_footer.php'; ?>
