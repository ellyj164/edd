<?php
/**
 * Vendor Profile Management
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/auth.php'; // Seller authentication guard

$vendor = new Vendor();
$user = new User();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo) {
    redirect('/sell.php');
}

$userInfo = $user->find(Session::getUserId());
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $business_name = sanitizeInput($_POST['business_name'] ?? '');
        $business_type = sanitizeInput($_POST['business_type'] ?? '');
        $business_phone = sanitizeInput($_POST['business_phone'] ?? '');
        $business_email = sanitizeInput($_POST['business_email'] ?? '');
        $website = sanitizeInput($_POST['website'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $business_address = sanitizeInput($_POST['business_address'] ?? '');

        // Validation
        if (empty($business_name)) {
            $error = 'Business name is required';
        } elseif (empty($business_type)) {
            $error = 'Business type is required';
        } else {
            try {
                $vendorData = [
                    'business_name' => $business_name,
                    'business_type' => $business_type,
                    'business_phone' => $business_phone ?: null,
                    'business_email' => $business_email ?: null,
                    'website' => $website ?: null,
                    'description' => $description ?: null,
                    'business_address' => $business_address ?: null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                if ($vendor->update($vendorInfo['id'], $vendorData)) {
                    $success = 'Profile updated successfully!';
                    $vendorInfo = array_merge($vendorInfo, $vendorData);
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            } catch (Exception $e) {
                Logger::error("Vendor profile update error: " . $e->getMessage());
                $error = 'An error occurred while updating the profile.';
            }
        }
    }
}

$page_title = 'Vendor Profile - Seller Center';
includeHeader($page_title);
?>

<div class="container">
    <div class="vendor-header">
        <nav class="vendor-nav">
            <a href="/seller-center.php">← Back to Seller Center</a>
        </nav>
        <h1>Vendor Profile</h1>
    </div>

    <div class="profile-content">
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="profile-sections">
            <!-- Vendor Status -->
            <div class="section-card">
                <h2>Account Status</h2>
                <div class="status-info">
                    <div class="status-badge status-<?php echo strtolower($vendorInfo['status']); ?>">
                        <?php echo ucfirst($vendorInfo['status']); ?>
                    </div>
                    <div class="status-details">
                        <?php if ($vendorInfo['status'] === 'pending'): ?>
                            <p>Your vendor account is under review. We'll notify you once it's approved.</p>
                        <?php elseif ($vendorInfo['status'] === 'approved'): ?>
                            <p>Your vendor account is approved and active. You can sell products on our platform.</p>
                            <?php if ($vendorInfo['approved_at']): ?>
                                <small>Approved on <?php echo formatDate($vendorInfo['approved_at']); ?></small>
                            <?php endif; ?>
                        <?php elseif ($vendorInfo['status'] === 'suspended'): ?>
                            <p>Your vendor account has been suspended. Please contact support for assistance.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Business Information -->
            <form method="post" class="section-card">
                <?php echo csrfTokenInput(); ?>
                <h2>Business Information</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="business_name">Business Name *</label>
                        <input type="text" id="business_name" name="business_name" required 
                               value="<?php echo htmlspecialchars($vendorInfo['business_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="business_type">Business Type *</label>
                        <select id="business_type" name="business_type" required>
                            <option value="individual" <?php echo $vendorInfo['business_type'] === 'individual' ? 'selected' : ''; ?>>
                                Individual
                            </option>
                            <option value="business" <?php echo $vendorInfo['business_type'] === 'business' ? 'selected' : ''; ?>>
                                Business
                            </option>
                            <option value="corporation" <?php echo $vendorInfo['business_type'] === 'corporation' ? 'selected' : ''; ?>>
                                Corporation
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="business_phone">Business Phone</label>
                        <input type="tel" id="business_phone" name="business_phone" 
                               value="<?php echo htmlspecialchars($vendorInfo['business_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="business_email">Business Email</label>
                        <input type="email" id="business_email" name="business_email" 
                               value="<?php echo htmlspecialchars($vendorInfo['business_email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" 
                           value="<?php echo htmlspecialchars($vendorInfo['website'] ?? ''); ?>"
                           placeholder="https://your-website.com">
                </div>
                
                <div class="form-group">
                    <label for="business_address">Business Address</label>
                    <textarea id="business_address" name="business_address" rows="3"><?php echo htmlspecialchars($vendorInfo['business_address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="description">Business Description</label>
                    <textarea id="description" name="description" rows="4" 
                              placeholder="Tell customers about your business..."><?php echo htmlspecialchars($vendorInfo['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>

            <!-- Account Details -->
            <div class="section-card">
                <h2>Account Details</h2>
                <div class="account-details">
                    <div class="detail-row">
                        <span class="label">User Email:</span>
                        <span class="value"><?php echo htmlspecialchars($userInfo['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Member Since:</span>
                        <span class="value"><?php echo formatDate($vendorInfo['created_at']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Commission Rate:</span>
                        <span class="value"><?php echo number_format($vendorInfo['commission_rate'], 2); ?>%</span>
                    </div>
                </div>
                
                <div class="account-actions">
                    <a href="/account.php" class="btn btn-outline">Update Personal Details</a>
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

.profile-sections {
    display: flex;
    flex-direction: column;
    gap: 25px;
    max-width: 800px;
    margin: 0 auto;
}

.section-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-card h2 {
    color: #1f2937;
    font-size: 18px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e5e7eb;
}

.status-info {
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
}

.status-active { background: #dcfce7; color: #166534; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-suspended { background: #fee2e2; color: #991b1b; }

.status-details p {
    margin-bottom: 5px;
    color: #374151;
}

.status-details small {
    color: #6b7280;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0654ba;
    box-shadow: 0 0 0 3px rgba(6, 84, 186, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.account-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-row .label {
    font-weight: 600;
    color: #374151;
}

.detail-row .value {
    color: #6b7280;
}

.account-actions {
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

@media (max-width: 768px) {
    .vendor-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .status-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .detail-row {
        flex-direction: column;
        gap: 5px;
    }
    
    .section-card {
        padding: 20px;
    }
}
</style>

<?php includeFooter(); ?>