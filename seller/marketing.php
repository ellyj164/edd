<?php
/**
 * Seller Marketing and Promotions
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

// Initialize database connection
$db = db();

// Require vendor login
Session::requireLogin();

$vendor = new Vendor();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Handle marketing actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'create_coupon':
                $couponData = [
                    'code' => strtoupper(trim($_POST['code'])),
                    'type' => $_POST['type'],
                    'value' => (float)$_POST['value'],
                    'minimum_amount' => !empty($_POST['minimum_amount']) ? (float)$_POST['minimum_amount'] : null,
                    'maximum_discount' => !empty($_POST['maximum_discount']) ? (float)$_POST['maximum_discount'] : null,
                    'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
                    'user_usage_limit' => !empty($_POST['user_usage_limit']) ? (int)$_POST['user_usage_limit'] : null,
                    'valid_from' => !empty($_POST['valid_from']) ? $_POST['valid_from'] : null,
                    'valid_to' => !empty($_POST['valid_to']) ? $_POST['valid_to'] : null,
                    'applies_to' => $_POST['applies_to'],
                    'applicable_items' => json_encode($_POST['applicable_items'] ?? []),
                    'description' => $_POST['description'] ?? '',
                    'created_by' => Session::getUserId(),
                    'status' => 'active'
                ];
                
                // Check if coupon code already exists
                $codeQuery = "SELECT id FROM coupons WHERE code = ?";
                $codeStmt = $db->prepare($codeQuery);
                $codeStmt->execute([$couponData['code']]);
                if ($codeStmt->fetch()) {
                    throw new Exception('Coupon code already exists. Please choose a different code.');
                }
                
                // Create coupon
                $couponQuery = "
                    INSERT INTO coupons (
                        code, type, value, minimum_amount, maximum_discount, usage_limit, 
                        user_usage_limit, valid_from, valid_to, applies_to, applicable_items, 
                        description, created_by, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ";
                $couponStmt = $db->prepare($couponQuery);
                $couponStmt->execute([
                    $couponData['code'], $couponData['type'], $couponData['value'],
                    $couponData['minimum_amount'], $couponData['maximum_discount'],
                    $couponData['usage_limit'], $couponData['user_usage_limit'],
                    $couponData['valid_from'], $couponData['valid_to'],
                    $couponData['applies_to'], $couponData['applicable_items'],
                    $couponData['description'], $couponData['created_by'], $couponData['status']
                ]);
                
                Session::setFlash('success', 'Coupon created successfully!');
                break;
                
            case 'toggle_coupon':
                $couponId = (int)$_POST['coupon_id'];
                $newStatus = $_POST['status'];
                
                $updateQuery = "UPDATE coupons SET status = ? WHERE id = ? AND created_by = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$newStatus, $couponId, Session::getUserId()]);
                
                Session::setFlash('success', 'Coupon status updated successfully!');
                break;
                
            case 'join_campaign':
                $campaignId = (int)$_POST['campaign_id'];
                $productIds = $_POST['product_ids'] ?? [];
                
                if (empty($productIds)) {
                    throw new Exception('Please select at least one product to participate.');
                }
                
                // Check if campaign exists and is active
                $campaignQuery = "SELECT * FROM marketing_campaigns WHERE id = ? AND status = 'active'";
                $campaignStmt = $db->prepare($campaignQuery);
                $campaignStmt->execute([$campaignId]);
                $campaign = $campaignStmt->fetch();
                
                if (!$campaign) {
                    throw new Exception('Campaign not found or not active.');
                }
                
                // Add products to campaign
                foreach ($productIds as $productId) {
                    $participationQuery = "
                        INSERT IGNORE INTO campaign_products (campaign_id, product_id, vendor_id, joined_at) 
                        VALUES (?, ?, ?, NOW())
                    ";
                    $participationStmt = $db->prepare($participationQuery);
                    $participationStmt->execute([$campaignId, $productId, $vendorId]);
                }
                
                Session::setFlash('success', 'Successfully joined the campaign with selected products!');
                break;
                
            case 'create_sponsored_ad':
                $productIds = $_POST['product_ids'] ?? [];
                
                if (empty($productIds)) {
                    throw new Exception('Please select at least one product to sponsor.');
                }
                
                // Get pricing from settings
                $settingsQuery = "SELECT setting_value FROM sponsored_product_settings WHERE setting_key = 'price_per_7_days'";
                $settingsStmt = $db->prepare($settingsQuery);
                $settingsStmt->execute();
                $pricePerProduct = (float)($settingsStmt->fetchColumn() ?: 50.00);
                
                $totalCost = count($productIds) * $pricePerProduct;
                
                // Check seller's wallet balance
                $walletQuery = "SELECT balance FROM wallets WHERE user_id = ? AND currency = 'USD'";
                $walletStmt = $db->prepare($walletQuery);
                $walletStmt->execute([Session::getUserId()]);
                $wallet = $walletStmt->fetch();
                
                if (!$wallet || $wallet['balance'] < $totalCost) {
                    throw new Exception('Insufficient wallet balance. You need $' . number_format($totalCost, 2) . ' to sponsor ' . count($productIds) . ' product(s).');
                }
                
                // Deduct from wallet
                $deductQuery = "UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency = 'USD'";
                $deductStmt = $db->prepare($deductQuery);
                $deductStmt->execute([$totalCost, Session::getUserId()]);
                
                // Record wallet transaction
                $transactionQuery = "
                    INSERT INTO wallet_transactions 
                    (wallet_id, amount, type, description, status, created_at)
                    SELECT id, ?, 'debit', ?, 'completed', NOW()
                    FROM wallets WHERE user_id = ? AND currency = 'USD'
                ";
                $transactionStmt = $db->prepare($transactionQuery);
                $transactionDescription = 'Sponsored product ad for ' . count($productIds) . ' product(s) - 7 days';
                $transactionStmt->execute([$totalCost, $transactionDescription, Session::getUserId()]);
                
                // Create sponsored ads
                $sponsoredFrom = date('Y-m-d H:i:s');
                $sponsoredUntil = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                foreach ($productIds as $productId) {
                    $insertQuery = "
                        INSERT INTO sponsored_products 
                        (product_id, vendor_id, seller_id, cost, payment_method, payment_status, 
                         status, sponsored_from, sponsored_until, created_at)
                        VALUES (?, ?, ?, ?, 'wallet', 'paid', 'pending', ?, ?, NOW())
                    ";
                    $insertStmt = $db->prepare($insertQuery);
                    $insertStmt->execute([
                        $productId, $vendorId, Session::getUserId(), 
                        $pricePerProduct, $sponsoredFrom, $sponsoredUntil
                    ]);
                }
                
                Session::setFlash('success', 'Successfully created sponsored ads for ' . count($productIds) . ' product(s)! Total cost: $' . number_format($totalCost, 2) . '. Pending admin approval.');
                break;
        }
        
    } catch (Exception $e) {
        Session::setFlash('error', 'Error: ' . $e->getMessage());
    }
    
    redirect('/seller/marketing.php');
}

// Get seller's coupons
$couponsQuery = "
    SELECT c.*, 
           COUNT(cu.id) as usage_count_actual
    FROM coupons c
    LEFT JOIN coupon_usage cu ON c.id = cu.coupon_id
    WHERE c.created_by = ?
    GROUP BY c.id
    ORDER BY c.created_at DESC
";
$couponsStmt = $db->prepare($couponsQuery);
$couponsStmt->execute([Session::getUserId()]);
$coupons = $couponsStmt->fetchAll();

// Get available campaigns to join
$campaignsQuery = "
    SELECT mc.*, 
           COUNT(cp.id) as participating_vendors,
           CASE WHEN cp_user.vendor_id IS NOT NULL THEN 1 ELSE 0 END as is_participating
    FROM marketing_campaigns mc
    LEFT JOIN campaign_products cp ON mc.id = cp.campaign_id
    LEFT JOIN campaign_products cp_user ON mc.id = cp_user.campaign_id AND cp_user.vendor_id = ?
    WHERE mc.status IN ('active', 'scheduled')
    AND (mc.end_date IS NULL OR mc.end_date > NOW())
    GROUP BY mc.id
    ORDER BY mc.start_date ASC
";
$campaignsStmt = $db->prepare($campaignsQuery);
$campaignsStmt->execute([$vendorId]);
$campaigns = $campaignsStmt->fetchAll();

// Get seller's products for campaign participation
$productsQuery = "
    SELECT id, name, price, stock_quantity, status 
    FROM products 
    WHERE vendor_id = ? AND status = 'active'
    ORDER BY name ASC
";
$productsStmt = $db->prepare($productsQuery);
$productsStmt->execute([$vendorId]);
$products = $productsStmt->fetchAll();

// Get seller's sponsored ads
$sponsoredAdsQuery = "
    SELECT sp.*, p.name as product_name, p.image_url as product_image
    FROM sponsored_products sp
    INNER JOIN products p ON sp.product_id = p.id
    WHERE sp.seller_id = ?
    ORDER BY sp.created_at DESC
";
$sponsoredAdsStmt = $db->prepare($sponsoredAdsQuery);
$sponsoredAdsStmt->execute([Session::getUserId()]);
$sponsoredAds = $sponsoredAdsStmt->fetchAll();

// Get active and expired counts
$activeAds = array_filter($sponsoredAds, function($ad) {
    return $ad['status'] === 'active' && strtotime($ad['sponsored_until']) > time();
});
$expiredAds = array_filter($sponsoredAds, function($ad) {
    return $ad['status'] === 'expired' || ($ad['status'] === 'active' && strtotime($ad['sponsored_until']) <= time());
});

// Get sponsored ad pricing
$pricingQuery = "SELECT setting_value FROM sponsored_product_settings WHERE setting_key = 'price_per_7_days'";
$pricingStmt = $db->prepare($pricingQuery);
$pricingStmt->execute();
$adPricePerProduct = (float)($pricingStmt->fetchColumn() ?: 50.00);

// Get marketing statistics
$statsQuery = "
    SELECT 
        COUNT(c.id) as total_coupons,
        SUM(c.usage_count) as total_coupon_usage,
        SUM(CASE WHEN c.status = 'active' THEN 1 ELSE 0 END) as active_coupons,
        COUNT(DISTINCT cp.campaign_id) as active_campaigns
    FROM coupons c
    LEFT JOIN campaign_products cp ON cp.vendor_id = ?
    WHERE c.created_by = ?
";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute([$vendorId, Session::getUserId()]);
$stats = $statsStmt->fetch();

$page_title = 'Marketing & Promotions - Seller Center';
includeHeader($page_title);
?>

<div class="seller-marketing-page">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-info">
                <nav class="breadcrumb">
                    <a href="/seller/dashboard.php">Dashboard</a>
                    <span>/</span>
                    <span>Marketing</span>
                </nav>
                <h1>Marketing & Promotions</h1>
                <p class="subtitle">Create coupons and join marketing campaigns</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline" onclick="showAnalyticsModal()">
                    📊 View Analytics
                </button>
                <button class="btn btn-primary" onclick="showCouponModal()">
                    🎟️ Create Coupon
                </button>
            </div>
        </div>
    </div>

    <!-- Marketing Stats -->
    <div class="stats-section">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🎟️</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_coupons']; ?></div>
                    <div class="stat-label">Total Coupons</div>
                    <div class="stat-meta"><?php echo $stats['active_coupons']; ?> active</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['total_coupon_usage']; ?></div>
                    <div class="stat-label">Coupon Usage</div>
                    <div class="stat-meta">Total redemptions</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🎯</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['active_campaigns']; ?></div>
                    <div class="stat-label">Active Campaigns</div>
                    <div class="stat-meta">Participating in</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💡</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($campaigns); ?></div>
                    <div class="stat-label">Available Campaigns</div>
                    <div class="stat-meta">Join to boost sales</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="marketing-grid">
        <!-- Coupons Section -->
        <div class="marketing-widget coupons-widget">
            <div class="widget-header">
                <h3>My Coupons</h3>
                <button class="btn btn-sm btn-primary" onclick="showCouponModal()">
                    + Create Coupon
                </button>
            </div>
            <div class="widget-content">
                <?php if (!empty($coupons)): ?>
                    <div class="coupons-list">
                        <?php foreach ($coupons as $coupon): ?>
                            <div class="coupon-card">
                                <div class="coupon-header">
                                    <div class="coupon-code"><?php echo $coupon['code']; ?></div>
                                    <div class="coupon-status">
                                        <span class="status-badge status-<?php echo strtolower($coupon['status']); ?>">
                                            <?php echo ucfirst($coupon['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="coupon-details">
                                    <div class="coupon-value">
                                        <?php if ($coupon['type'] === 'percentage'): ?>
                                            <?php echo $coupon['value']; ?>% OFF
                                        <?php else: ?>
                                            $<?php echo number_format($coupon['value'], 2); ?> OFF
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($coupon['description']): ?>
                                        <div class="coupon-description">
                                            <?php echo htmlspecialchars($coupon['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="coupon-conditions">
                                        <?php if ($coupon['minimum_amount']): ?>
                                            <span class="condition">Min: $<?php echo number_format($coupon['minimum_amount'], 2); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($coupon['maximum_discount']): ?>
                                            <span class="condition">Max: $<?php echo number_format($coupon['maximum_discount'], 2); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($coupon['valid_to']): ?>
                                            <span class="condition">Expires: <?php echo formatDate($coupon['valid_to']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="coupon-usage">
                                    <div class="usage-stats">
                                        <span class="usage-count">
                                            <?php echo $coupon['usage_count_actual']; ?> used
                                        </span>
                                        <?php if ($coupon['usage_limit']): ?>
                                            <span class="usage-limit">
                                                / <?php echo $coupon['usage_limit']; ?> limit
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="coupon-actions">
                                        <?php if ($coupon['status'] === 'active'): ?>
                                            <button onclick="toggleCoupon(<?php echo $coupon['id']; ?>, 'inactive')" 
                                                    class="btn btn-sm btn-ghost">Deactivate</button>
                                        <?php else: ?>
                                            <button onclick="toggleCoupon(<?php echo $coupon['id']; ?>, 'active')" 
                                                    class="btn btn-sm btn-outline">Activate</button>
                                        <?php endif; ?>
                                        <button onclick="duplicateCoupon(<?php echo $coupon['id']; ?>)" 
                                                class="btn btn-sm btn-ghost">Duplicate</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎟️</div>
                        <h3>No coupons yet</h3>
                        <p>Create your first coupon to attract customers and boost sales.</p>
                        <button class="btn btn-primary" onclick="showCouponModal()">
                            Create Your First Coupon
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sponsored Ads Section -->
        <div class="marketing-widget sponsored-ads-widget">
            <div class="widget-header">
                <h3>Sponsored Product Ads</h3>
                <button class="btn btn-sm btn-primary" onclick="showSponsoredAdModal()">
                    📢 Create Ad
                </button>
            </div>
            <div class="widget-content">
                <div class="ad-pricing-info" style="background: #f0f9ff; padding: 12px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                    <strong>💰 Pricing:</strong> $<?php echo number_format($adPricePerProduct, 2); ?> per product for 7 days
                    <span style="color: #64748b; font-size: 0.9em; display: block; margin-top: 4px;">
                        Your sponsored products will be displayed prominently to increase visibility and sales.
                    </span>
                </div>
                
                <?php if (!empty($sponsoredAds)): ?>
                    <div class="sponsored-tabs">
                        <button class="tab-btn active" onclick="switchSponsoredTab('all')">
                            All (<?php echo count($sponsoredAds); ?>)
                        </button>
                        <button class="tab-btn" onclick="switchSponsoredTab('active')">
                            Active (<?php echo count($activeAds); ?>)
                        </button>
                        <button class="tab-btn" onclick="switchSponsoredTab('expired')">
                            Expired (<?php echo count($expiredAds); ?>)
                        </button>
                    </div>
                    
                    <div class="sponsored-ads-list" id="allAds">
                        <?php foreach ($sponsoredAds as $ad): ?>
                            <?php 
                                $isActive = $ad['status'] === 'active' && strtotime($ad['sponsored_until']) > time();
                                $isExpired = $ad['status'] === 'expired' || ($ad['status'] === 'active' && strtotime($ad['sponsored_until']) <= time());
                                $daysRemaining = $isActive ? ceil((strtotime($ad['sponsored_until']) - time()) / 86400) : 0;
                            ?>
                            <div class="sponsored-ad-card" data-status="<?php echo $isActive ? 'active' : ($isExpired ? 'expired' : $ad['status']); ?>">
                                <div class="ad-product-info">
                                    <?php if ($ad['product_image']): ?>
                                        <img src="<?php echo htmlspecialchars($ad['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($ad['product_name']); ?>" 
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                    <?php endif; ?>
                                    <div>
                                        <div class="ad-product-name"><?php echo htmlspecialchars($ad['product_name']); ?></div>
                                        <div class="ad-cost">Cost: $<?php echo number_format($ad['cost'], 2); ?></div>
                                    </div>
                                </div>
                                
                                <div class="ad-status-info">
                                    <span class="status-badge status-<?php echo strtolower($ad['status']); ?>">
                                        <?php echo ucfirst($ad['status']); ?>
                                    </span>
                                    <?php if ($ad['payment_status'] !== 'paid'): ?>
                                        <span class="payment-badge payment-<?php echo $ad['payment_status']; ?>">
                                            Payment: <?php echo ucfirst($ad['payment_status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ad-dates">
                                    <div class="ad-date-item">
                                        <span class="label">Started:</span>
                                        <span class="value"><?php echo formatDate($ad['sponsored_from']); ?></span>
                                    </div>
                                    <div class="ad-date-item">
                                        <span class="label">Expires:</span>
                                        <span class="value"><?php echo formatDate($ad['sponsored_until']); ?></span>
                                    </div>
                                    <?php if ($isActive && $daysRemaining > 0): ?>
                                        <div class="ad-remaining">
                                            <span class="days-remaining"><?php echo $daysRemaining; ?> day(s) remaining</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ad-performance">
                                    <div class="performance-metric">
                                        <span class="metric-label">Impressions</span>
                                        <span class="metric-value"><?php echo number_format($ad['impressions']); ?></span>
                                    </div>
                                    <div class="performance-metric">
                                        <span class="metric-label">Clicks</span>
                                        <span class="metric-value"><?php echo number_format($ad['clicks']); ?></span>
                                    </div>
                                    <?php if ($ad['impressions'] > 0): ?>
                                        <div class="performance-metric">
                                            <span class="metric-label">CTR</span>
                                            <span class="metric-value"><?php echo number_format(($ad['clicks'] / $ad['impressions']) * 100, 2); ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📢</div>
                        <h3>No sponsored ads yet</h3>
                        <p>Boost your product visibility with sponsored ads. Get featured placement for 7 days!</p>
                        <button class="btn btn-primary" onclick="showSponsoredAdModal()">
                            Create Your First Ad
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Campaigns Section -->
        <div class="marketing-widget campaigns-widget">
            <div class="widget-header">
                <h3>Marketing Campaigns</h3>
                <a href="/seller/marketing/campaigns.php" class="view-all">View All</a>
            </div>
            <div class="widget-content">
                <?php if (!empty($campaigns)): ?>
                    <div class="campaigns-list">
                        <?php foreach ($campaigns as $campaign): ?>
                            <div class="campaign-card">
                                <div class="campaign-header">
                                    <div class="campaign-name"><?php echo htmlspecialchars($campaign['name']); ?></div>
                                    <div class="campaign-type">
                                        <span class="type-badge type-<?php echo strtolower($campaign['campaign_type']); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $campaign['campaign_type'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($campaign['description']): ?>
                                    <div class="campaign-description">
                                        <?php echo htmlspecialchars($campaign['description']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="campaign-details">
                                    <div class="campaign-dates">
                                        <span class="date-range">
                                            <?php echo formatDate($campaign['start_date']); ?> - 
                                            <?php echo $campaign['end_date'] ? formatDate($campaign['end_date']) : 'Ongoing'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="campaign-participants">
                                        <?php echo $campaign['participating_vendors']; ?> vendors participating
                                    </div>
                                    
                                    <?php if ($campaign['discount_type']): ?>
                                        <div class="campaign-discount">
                                            Discount: 
                                            <?php if ($campaign['discount_type'] === 'percentage'): ?>
                                                <?php echo $campaign['discount_value']; ?>% OFF
                                            <?php else: ?>
                                                $<?php echo number_format($campaign['discount_value'], 2); ?> OFF
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="campaign-actions">
                                    <?php if ($campaign['is_participating']): ?>
                                        <span class="participating-badge">✅ Participating</span>
                                        <button onclick="viewCampaignDetails(<?php echo $campaign['id']; ?>)" 
                                                class="btn btn-sm btn-outline">View Details</button>
                                    <?php else: ?>
                                        <button onclick="showJoinCampaignModal(<?php echo $campaign['id']; ?>, '<?php echo htmlspecialchars($campaign['name']); ?>')" 
                                                class="btn btn-sm btn-primary">Join Campaign</button>
                                        <button onclick="viewCampaignDetails(<?php echo $campaign['id']; ?>)" 
                                                class="btn btn-sm btn-ghost">Learn More</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">🎯</div>
                        <h3>No campaigns available</h3>
                        <p>Check back later for new marketing campaigns to join.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Coupon Modal -->
<div class="modal-overlay" id="couponModal" style="display: none;">
    <div class="modal large">
        <div class="modal-header">
            <h3>Create New Coupon</h3>
            <button class="modal-close" onclick="closeCouponModal()">✕</button>
        </div>
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="create_coupon">
            <?php echo csrfTokenInput(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="couponCode">Coupon Code *</label>
                    <input type="text" id="couponCode" name="code" required 
                           placeholder="e.g., SAVE20" maxlength="50" style="text-transform: uppercase;">
                    <div class="form-help">Use letters and numbers only. Will be converted to uppercase.</div>
                </div>
                
                <div class="form-group">
                    <label for="couponType">Discount Type *</label>
                    <select id="couponType" name="type" required onchange="updateValueLabel()">
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount ($)</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="couponValue">
                        <span id="valueLabel">Discount Percentage *</span>
                    </label>
                    <input type="number" id="couponValue" name="value" required 
                           min="0" step="0.01" placeholder="20">
                </div>
                
                <div class="form-group">
                    <label for="minimumAmount">Minimum Order Amount</label>
                    <input type="number" id="minimumAmount" name="minimum_amount" 
                           min="0" step="0.01" placeholder="50.00">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="maximumDiscount">Maximum Discount Amount</label>
                    <input type="number" id="maximumDiscount" name="maximum_discount" 
                           min="0" step="0.01" placeholder="100.00">
                    <div class="form-help">For percentage coupons only</div>
                </div>
                
                <div class="form-group">
                    <label for="usageLimit">Total Usage Limit</label>
                    <input type="number" id="usageLimit" name="usage_limit" 
                           min="1" placeholder="100">
                    <div class="form-help">Leave empty for unlimited usage</div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="userUsageLimit">Usage Limit Per Customer</label>
                    <input type="number" id="userUsageLimit" name="user_usage_limit" 
                           min="1" value="1">
                </div>
                
                <div class="form-group">
                    <label for="appliesTo">Applies To</label>
                    <select id="appliesTo" name="applies_to" onchange="toggleApplicableItems()">
                        <option value="all">All Products</option>
                        <option value="products">Specific Products</option>
                        <option value="categories">Specific Categories</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="validFrom">Valid From</label>
                    <input type="datetime-local" id="validFrom" name="valid_from" 
                           value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="validTo">Valid Until</label>
                    <input type="datetime-local" id="validTo" name="valid_to">
                    <div class="form-help">Leave empty for no expiration</div>
                </div>
            </div>
            
            <div class="form-group" id="applicableItemsGroup" style="display: none;">
                <label for="applicableItems">Select Items</label>
                <div class="items-selection">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" 
                          placeholder="Optional description for internal use"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" onclick="closeCouponModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Coupon</button>
            </div>
        </form>
    </div>
</div>

<!-- Join Campaign Modal -->
<div class="modal-overlay" id="joinCampaignModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Join Campaign</h3>
            <button class="modal-close" onclick="closeJoinCampaignModal()">✕</button>
        </div>
        <form method="POST" class="modal-content">
            <input type="hidden" name="action" value="join_campaign">
            <input type="hidden" id="campaignId" name="campaign_id">
            <?php echo csrfTokenInput(); ?>
            
            <div class="campaign-info">
                <h4 id="campaignName"></h4>
                <p>Select the products you want to include in this campaign:</p>
            </div>
            
            <div class="products-selection">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <label class="product-checkbox">
                            <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>">
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-details">
                                    Price: $<?php echo number_format($product['price'], 2); ?> | 
                                    Stock: <?php echo $product['stock_quantity']; ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>You need active products to join campaigns.</p>
                        <a href="/seller/products/add.php" class="btn btn-primary">Add Products</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($products)): ?>
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeJoinCampaignModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Join Campaign</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Create Sponsored Ad Modal -->
<div class="modal-overlay" id="sponsoredAdModal" style="display: none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Create Sponsored Ad</h3>
            <button class="modal-close" onclick="closeSponsoredAdModal()">✕</button>
        </div>
        <form method="POST" class="modal-content" id="sponsoredAdForm">
            <input type="hidden" name="action" value="create_sponsored_ad">
            <?php echo csrfTokenInput(); ?>
            
            <div class="ad-info-box" style="background: #f0f9ff; padding: 16px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                <h4 style="margin: 0 0 8px 0; color: #1e40af;">📢 Sponsored Ad Benefits</h4>
                <ul style="margin: 0; padding-left: 20px; color: #1e3a8a;">
                    <li>Featured placement above "Similar Items" on product pages</li>
                    <li>7-day sponsorship period per product</li>
                    <li>Increased visibility and click-through rates</li>
                    <li>Price: <strong>$<?php echo number_format($adPricePerProduct, 2); ?> per product</strong></li>
                </ul>
            </div>
            
            <div class="campaign-info">
                <p><strong>Select products to sponsor:</strong></p>
                <div class="selected-count" id="selectedCount" style="margin-bottom: 10px; color: #64748b;">
                    0 products selected | Total: $0.00
                </div>
            </div>
            
            <div class="products-selection" style="max-height: 400px; overflow-y: auto;">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <label class="product-checkbox">
                            <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>" 
                                   data-price="<?php echo $adPricePerProduct; ?>"
                                   onchange="updateSponsoredAdTotal()">
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-details">
                                    Price: $<?php echo number_format($product['price'], 2); ?> | 
                                    Stock: <?php echo $product['stock_quantity']; ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>You need active products to create sponsored ads.</p>
                        <a href="/seller/products/add.php" class="btn btn-primary">Add Products</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($products)): ?>
                <div class="wallet-info" style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-top: 16px; border-left: 4px solid #f59e0b;">
                    <strong>💳 Payment:</strong> Cost will be deducted from your wallet balance.
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeSponsoredAdModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="createAdBtn" disabled>Create Sponsored Ads</button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<style>
.seller-marketing-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    margin-bottom: 30px;
    padding: 30px;
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    border-radius: 12px;
    color: white;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
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
    gap: 12px;
}

.stats-section {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
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
    margin-bottom: 4px;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 2px;
}

.stat-meta {
    font-size: 12px;
    color: #9ca3af;
}

.marketing-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.marketing-widget {
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
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.view-all {
    color: #8b5cf6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.widget-content {
    padding: 24px;
    max-height: 600px;
    overflow-y: auto;
}

.coupons-list, .campaigns-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.coupon-card, .campaign-card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    transition: all 0.2s ease;
}

.coupon-card:hover, .campaign-card:hover {
    border-color: #d1d5db;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.coupon-header, .campaign-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.coupon-code {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    font-size: 16px;
    color: #1f2937;
    background: #f3f4f6;
    padding: 4px 8px;
    border-radius: 4px;
}

.campaign-name {
    font-weight: 600;
    font-size: 16px;
    color: #1f2937;
}

.status-badge, .type-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active { background: #dcfce7; color: #166534; }
.status-inactive { background: #f3f4f6; color: #6b7280; }
.status-expired { background: #fee2e2; color: #dc2626; }

.type-flash_sale { background: #fef3c7; color: #92400e; }
.type-daily_deal { background: #dbeafe; color: #1e40af; }
.type-seasonal { background: #f0fdf4; color: #166534; }
.type-promotion { background: #f3e8ff; color: #7c3aed; }

.coupon-value {
    font-size: 20px;
    font-weight: 700;
    color: #dc2626;
    margin-bottom: 8px;
}

.coupon-description, .campaign-description {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 12px;
}

.coupon-conditions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.condition {
    background: #f3f4f6;
    color: #6b7280;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

.coupon-usage {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #f3f4f6;
}

.usage-stats {
    font-size: 12px;
    color: #6b7280;
}

.usage-count {
    font-weight: 600;
}

.coupon-actions, .campaign-actions {
    display: flex;
    gap: 8px;
}

.campaign-details {
    margin-bottom: 16px;
}

.campaign-dates, .campaign-participants, .campaign-discount {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 4px;
}

.date-range {
    font-weight: 500;
}

.participating-badge {
    background: #dcfce7;
    color: #166534;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

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

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 8px;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
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
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.modal.large {
    max-width: 700px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #6b7280;
    padding: 4px;
}

.modal-content {
    padding: 24px;
    max-height: 60vh;
    overflow-y: auto;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.form-help {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.items-selection {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 12px;
}

.products-selection {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.product-checkbox {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.product-checkbox:hover {
    background: #f9fafb;
}

.product-checkbox input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.product-details {
    font-size: 12px;
    color: #6b7280;
}

.campaign-info {
    margin-bottom: 20px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.campaign-info h4 {
    margin: 0 0 8px 0;
    color: #1f2937;
}

.no-products {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

@media (max-width: 768px) {
    .seller-marketing-page {
        padding: 16px;
    }
    
    .header-content {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .marketing-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .coupon-usage {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .campaign-actions {
        flex-direction: column;
    }
}

/* Sponsored Ads Styles */
.sponsored-ads-widget {
    width: 100%;
}

.ad-pricing-info {
    font-size: 14px;
}

.sponsored-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.tab-btn {
    padding: 12px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: #6b7280;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.tab-btn:hover {
    color: #3b82f6;
}

.sponsored-ads-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.sponsored-ad-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.ad-product-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.ad-product-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.ad-cost {
    font-size: 14px;
    color: #6b7280;
}

.ad-status-info {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.payment-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.payment-pending {
    background: #fef3c7;
    color: #92400e;
}

.payment-paid {
    background: #d1fae5;
    color: #065f46;
}

.payment-failed {
    background: #fee2e2;
    color: #991b1b;
}

.ad-dates {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.ad-date-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ad-date-item .label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
}

.ad-date-item .value {
    font-size: 14px;
    color: #1f2937;
}

.ad-remaining {
    display: flex;
    align-items: center;
}

.days-remaining {
    background: #dbeafe;
    color: #1e40af;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.ad-performance {
    display: flex;
    gap: 24px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 6px;
}

.performance-metric {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.metric-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
}

.metric-value {
    font-size: 16px;
    color: #1f2937;
    font-weight: 700;
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

@media (max-width: 768px) {
    .ad-dates,
    .ad-performance {
        grid-template-columns: 1fr;
        flex-direction: column;
    }
}
</style>

<script>
// Coupon modal functions
function showCouponModal() {
    document.getElementById('couponModal').style.display = 'flex';
}

function closeCouponModal() {
    document.getElementById('couponModal').style.display = 'none';
    document.getElementById('couponModal').querySelector('form').reset();
}

function updateValueLabel() {
    const type = document.getElementById('couponType').value;
    const label = document.getElementById('valueLabel');
    const valueInput = document.getElementById('couponValue');
    
    if (type === 'percentage') {
        label.textContent = 'Discount Percentage *';
        valueInput.placeholder = '20';
        valueInput.max = '100';
    } else {
        label.textContent = 'Discount Amount ($) *';
        valueInput.placeholder = '10.00';
        valueInput.removeAttribute('max');
    }
}

function toggleApplicableItems() {
    const appliesTo = document.getElementById('appliesTo').value;
    const group = document.getElementById('applicableItemsGroup');
    
    if (appliesTo === 'all') {
        group.style.display = 'none';
    } else {
        group.style.display = 'block';
        // In production, load products or categories via AJAX
        const selection = group.querySelector('.items-selection');
        selection.innerHTML = '<p>Loading...</p>';
    }
}

// Campaign modal functions
function showJoinCampaignModal(campaignId, campaignName) {
    document.getElementById('campaignId').value = campaignId;
    document.getElementById('campaignName').textContent = campaignName;
    document.getElementById('joinCampaignModal').style.display = 'flex';
}

function closeJoinCampaignModal() {
    document.getElementById('joinCampaignModal').style.display = 'none';
    // Uncheck all products
    document.querySelectorAll('input[name="product_ids[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Coupon actions
function toggleCoupon(couponId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_coupon">
        <input type="hidden" name="coupon_id" value="${couponId}">
        <input type="hidden" name="status" value="${status}">
        <?php echo csrfTokenInput(); ?>
    `;
    document.body.appendChild(form);
    form.submit();
}

function duplicateCoupon(couponId) {
    // In production, this would copy coupon data to the create form
    alert('Duplicate functionality would copy coupon settings to create form');
}

function viewCampaignDetails(campaignId) {
    window.open(`/seller/marketing/campaigns/view.php?id=${campaignId}`, '_blank');
}

function showAnalyticsModal() {
    // In production, show marketing analytics modal
    alert('Analytics modal would show coupon performance metrics');
}

// Auto-generate coupon code
document.getElementById('couponCode').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
});

// Generate random coupon code
function generateCouponCode() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 8; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('couponCode').value = result;
}

// Add generate button to coupon code field
document.addEventListener('DOMContentLoaded', function() {
    const codeGroup = document.getElementById('couponCode').parentNode;
    const generateBtn = document.createElement('button');
    generateBtn.type = 'button';
    generateBtn.className = 'btn btn-sm btn-ghost';
    generateBtn.textContent = 'Generate';
    generateBtn.onclick = generateCouponCode;
    generateBtn.style.marginTop = '8px';
    codeGroup.appendChild(generateBtn);
});

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});

// Sponsored Ad modal functions
function showSponsoredAdModal() {
    document.getElementById('sponsoredAdModal').style.display = 'flex';
}

function closeSponsoredAdModal() {
    document.getElementById('sponsoredAdModal').style.display = 'none';
    document.getElementById('sponsoredAdForm').reset();
    updateSponsoredAdTotal();
}

function updateSponsoredAdTotal() {
    const checkboxes = document.querySelectorAll('#sponsoredAdForm input[name="product_ids[]"]:checked');
    const count = checkboxes.length;
    const pricePerProduct = <?php echo $adPricePerProduct; ?>;
    const total = count * pricePerProduct;
    
    const selectedCountEl = document.getElementById('selectedCount');
    if (selectedCountEl) {
        selectedCountEl.textContent = `${count} product${count !== 1 ? 's' : ''} selected | Total: $${total.toFixed(2)}`;
    }
    
    const createBtn = document.getElementById('createAdBtn');
    if (createBtn) {
        createBtn.disabled = count === 0;
        if (count > 0) {
            createBtn.textContent = `Create Sponsored Ads ($${total.toFixed(2)})`;
        } else {
            createBtn.textContent = 'Create Sponsored Ads';
        }
    }
}

// Sponsored tab switching
function switchSponsoredTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.sponsored-tabs .tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Filter ads
    const ads = document.querySelectorAll('.sponsored-ad-card');
    ads.forEach(ad => {
        const status = ad.getAttribute('data-status');
        if (tab === 'all') {
            ad.style.display = 'flex';
        } else if (tab === 'active' && status === 'active') {
            ad.style.display = 'flex';
        } else if (tab === 'expired' && status === 'expired') {
            ad.style.display = 'flex';
        } else {
            ad.style.display = 'none';
        }
    });
}

// Close modals with escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});
</script>

<?php includeFooter(); ?>