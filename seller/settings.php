<?php
/**
 * Vendor Settings
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php'; // Seller authentication guard

$vendor = new Vendor();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) {
    redirect('/sell.php');
}

$page_title = 'Settings - Seller Center';
includeHeader($page_title);
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav">
            <a href="/seller-center.php">← Back to Seller Center</a>
        </nav>
        <h1>Seller Settings</h1>
    </div>

    <div class="settings-content">
        <div class="settings-sections">
            <!-- Account Settings -->
            <div class="settings-section">
                <h2>Account Settings</h2>
                <div class="settings-options">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Profile Information</h3>
                            <p>Update your business profile and contact information</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/profile.php" class="btn btn-outline">Edit Profile</a>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Personal Account</h3>
                            <p>Manage your personal account details and password</p>
                        </div>
                        <div class="setting-action">
                            <a href="/account.php" class="btn btn-outline">Manage Account</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Business Settings -->
            <div class="settings-section">
                <h2>Business Settings</h2>
                <div class="settings-options">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Payment Information</h3>
                            <p>Configure how you receive payments from sales</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/payment-settings.php" class="btn btn-outline">Configure</a>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Shipping Settings</h3>
                            <p>Set up shipping rates and policies for your products</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/shipping-settings.php" class="btn btn-outline">Configure</a>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Tax Settings</h3>
                            <p>Configure tax collection for your sales</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/tax-settings.php" class="btn btn-outline">Configure</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store Settings -->
            <div class="settings-section">
                <h2>Store Settings</h2>
                <div class="settings-options">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Store Appearance</h3>
                            <p>Customize your store's look with logos and banners</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/store-appearance.php" class="btn btn-outline">Customize</a>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Store Policies</h3>
                            <p>Set return, exchange, and customer service policies</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/store-policies.php" class="btn btn-outline">Edit Policies</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="settings-section">
                <h2>Notifications</h2>
                <div class="settings-options">
                    <div class="setting-item">
                        <div class="setting-info">
                            <h3>Notification Settings</h3>
                            <p>Manage email and SMS notifications for orders and alerts</p>
                        </div>
                        <div class="setting-action">
                            <a href="/seller/notification-settings.php" class="btn btn-outline">Configure</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Status -->
            <div class="settings-section status-section">
                <h2>Account Status</h2>
                <div class="status-info">
                    <div class="status-badge status-<?php echo strtolower($vendorInfo['status']); ?>">
                        <?php echo ucfirst($vendorInfo['status']); ?>
                    </div>
                    <div class="status-details">
                        <?php if ($vendorInfo['status'] === 'pending'): ?>
                            <p>Your seller account is under review. Most features will be available once approved.</p>
                        <?php elseif ($vendorInfo['status'] === 'approved'): ?>
                            <p>Your seller account is active and in good standing.</p>
                            <small>Commission Rate: <?php echo number_format($vendorInfo['commission_rate'], 2); ?>%</small>
                        <?php elseif ($vendorInfo['status'] === 'suspended'): ?>
                            <p>Your account has been suspended. Please contact support for assistance.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.vendor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.vendor-nav a {
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
}

.vendor-nav a:hover {
    color: #374151;
}

.settings-sections {
    max-width: 800px;
    margin: 0 auto;
}

.settings-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 25px;
    overflow: hidden;
}

.settings-section h2 {
    background: #f9fafb;
    color: #1f2937;
    font-size: 18px;
    margin: 0;
    padding: 20px 30px;
    border-bottom: 1px solid #e5e7eb;
}

.settings-options {
    padding: 0;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    border-bottom: 1px solid #f3f4f6;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-info h3 {
    color: #1f2937;
    font-size: 16px;
    margin-bottom: 5px;
}

.setting-info p {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}

.setting-action {
    flex-shrink: 0;
}

.coming-soon-badge {
    background: #fef3c7;
    color: #92400e;
    padding: 6px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-section {
    border: 2px solid #e5e7eb;
}

.status-info {
    padding: 25px 30px;
    display: flex;
    align-items: center;
    gap: 20px;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    flex-shrink: 0;
}

.status-active { background: #dcfce7; color: #166534; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-suspended { background: #fee2e2; color: #991b1b; }

.status-details p {
    margin: 0 0 5px 0;
    color: #374151;
}

.status-details small {
    color: #6b7280;
}

@media (max-width: 768px) {
    .vendor-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .settings-section h2 {
        padding: 15px 20px;
    }
    
    .setting-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
    }
    
    .status-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
    }
}
</style>

<?php includeFooter(); ?>