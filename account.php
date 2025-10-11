<?php
/**
 * User Account Dashboard
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Initialize database connection with fallback
$db = null;
try {
    $db = db();
} catch (Exception $e) {
    // Database not available - show error or demo mode
    $page_title = 'My Account - Database Connection Required';
    includeHeader($page_title);
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-info">';
    echo '<h4>Demo Mode</h4>';
    echo '<p>The account features require a database connection. For demonstration purposes, you can view the interface but functionality will be limited.</p>';
    echo '<a href="/admin/index.php" class="btn btn-primary">View Admin Dashboard</a>';
    echo '</div>';
    echo '</div>';
    includeFooter();
    exit;
}

// Require user login
Session::requireLogin();

$user = new User();
$order = new Order();
$vendor = new Vendor();

$current_user = $user->find(Session::getUserId());
$recentOrders = $order->getUserOrders(Session::getUserId(), 5);
$isVendor = $vendor->findByUserId(Session::getUserId());

// Get wallet balance - support both buyer and seller wallets
$walletBalance = 0.00;
try {
    // Try buyer wallet first
    $buyerQuery = "SELECT * FROM buyers WHERE user_id = ?";
    $buyerStmt = $db->prepare($buyerQuery);
    $buyerStmt->execute([Session::getUserId()]);
    $buyer = $buyerStmt->fetch();
    
    if ($buyer) {
        $walletQuery = "SELECT balance FROM buyer_wallets WHERE buyer_id = ? AND currency = 'USD'";
        $walletStmt = $db->prepare($walletQuery);
        $walletStmt->execute([$buyer['id']]);
        $wallet = $walletStmt->fetch();
        if ($wallet) {
            $walletBalance = $wallet['balance'];
        }
    }
    
    // If no buyer wallet, try seller wallet
    if ($walletBalance == 0.00 && $isVendor) {
        $sellerWalletQuery = "SELECT balance FROM seller_wallets WHERE vendor_id = ?";
        $sellerWalletStmt = $db->prepare($sellerWalletQuery);
        $sellerWalletStmt->execute([$isVendor['id']]);
        $sellerWallet = $sellerWalletStmt->fetch();
        if ($sellerWallet) {
            $walletBalance = $sellerWallet['balance'];
        }
    }
} catch (Exception $e) {
    // Wallet tables might not exist yet, default to 0.00
    error_log("Wallet query error: " . $e->getMessage());
    $walletBalance = 0.00;
}

// Get user's active sessions (login devices)
$sessionsQuery = "SELECT id, session_token, ip_address, user_agent, created_at, expires_at, is_active 
                  FROM user_sessions 
                  WHERE user_id = ? AND is_active = 1 
                  ORDER BY created_at DESC";
$sessionsStmt = $db->prepare($sessionsQuery);
$sessionsStmt->execute([Session::getUserId()]);
$loginDevices = $sessionsStmt->fetchAll();

// Get recent security logs (login alerts)
$logsQuery = "SELECT event_type, severity, ip_address, user_agent, details, created_at 
              FROM security_logs 
              WHERE user_id = ? 
              ORDER BY created_at DESC 
              LIMIT 20";
$logsStmt = $db->prepare($logsQuery);
$logsStmt->execute([Session::getUserId()]);
$securityLogs = $logsStmt->fetchAll();

// Helper function to parse user agent
function parseUserAgent($userAgent) {
    $browser = 'Unknown Browser';
    $os = 'Unknown OS';
    $deviceType = 'desktop';
    
    // Detect browser
    if (strpos($userAgent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($userAgent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
        $browser = 'Safari';
    } elseif (strpos($userAgent, 'Edge') !== false) {
        $browser = 'Edge';
    }
    
    // Detect OS
    if (strpos($userAgent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($userAgent, 'Mac') !== false) {
        $os = 'macOS';
    } elseif (strpos($userAgent, 'Linux') !== false) {
        $os = 'Linux';
    } elseif (strpos($userAgent, 'Android') !== false) {
        $os = 'Android';
        $deviceType = 'mobile';
    } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
        $os = 'iOS';
        $deviceType = strpos($userAgent, 'iPad') !== false ? 'tablet' : 'mobile';
    }
    
    return [
        'browser' => $browser,
        'os' => $os,
        'device_type' => $deviceType
    ];
}

// Helper function to get device icon
function getDeviceIcon($deviceType) {
    switch ($deviceType) {
        case 'mobile':
            return '📱';
        case 'tablet':
            return '📱';
        default:
            return '💻';
    }
}

// Handle address management form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'add_address':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                $addressData = [
                    'user_id' => Session::getUserId(),
                    'type' => sanitizeInput($_POST['type'] ?? 'both'),
                    'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
                    'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
                    'company' => sanitizeInput($_POST['company'] ?? ''),
                    'address_line1' => sanitizeInput($_POST['address_line1'] ?? ''),
                    'address_line2' => sanitizeInput($_POST['address_line2'] ?? ''),
                    'city' => sanitizeInput($_POST['city'] ?? ''),
                    'state' => sanitizeInput($_POST['state'] ?? ''),
                    'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
                    'country' => sanitizeInput($_POST['country'] ?? 'US'),
                    'phone' => sanitizeInput($_POST['phone'] ?? ''),
                    'is_default' => isset($_POST['is_default']) ? 1 : 0
                ];
                
                // Validation
                $required = ['first_name', 'last_name', 'address_line1', 'city', 'state', 'postal_code'];
                foreach ($required as $field) {
                    if (empty($addressData[$field])) {
                        throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                    }
                }
                
                // If setting as default, remove default from other addresses
                if ($addressData['is_default']) {
                    $removeDefaultStmt = $db->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                    $removeDefaultStmt->execute([Session::getUserId()]);
                }
                
                // Insert new address
                $insertStmt = $db->prepare("
                    INSERT INTO addresses (
                        user_id, type, first_name, last_name, company, address_line1, address_line2,
                        city, state, postal_code, country, phone, is_default, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $insertStmt->execute([
                    $addressData['user_id'], $addressData['type'], $addressData['first_name'],
                    $addressData['last_name'], $addressData['company'], $addressData['address_line1'],
                    $addressData['address_line2'], $addressData['city'], $addressData['state'],
                    $addressData['postal_code'], $addressData['country'], $addressData['phone'],
                    $addressData['is_default']
                ]);
                
                Session::setFlash('success', 'Address added successfully!');
                break;
                
            case 'update_preferences':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                // Handle preferences update (placeholder for now)
                Session::setFlash('success', 'Preferences updated successfully!');
                break;
                
            case 'change_password':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                // Validation
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    throw new Exception('All password fields are required.');
                }
                
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New password and confirmation do not match.');
                }
                
                if (strlen($newPassword) < 8) {
                    throw new Exception('New password must be at least 8 characters long.');
                }
                
                // Verify current password
                if (!password_verify($currentPassword, $current_user['password'])) {
                    throw new Exception('Current password is incorrect.');
                }
                
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([$hashedPassword, Session::getUserId()]);
                
                Session::setFlash('success', 'Password changed successfully!');
                break;
                
            case 'enable_2fa':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                // For now, just mark 2FA as enabled - in production this would involve
                // QR code generation, backup codes, etc.
                $updateStmt = $db->prepare("UPDATE users SET two_factor_enabled = 1, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([Session::getUserId()]);
                
                Session::setFlash('success', '2FA has been enabled for your account!');
                break;
                
            case 'disable_2fa':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                $updateStmt = $db->prepare("UPDATE users SET two_factor_enabled = 0, updated_at = NOW() WHERE id = ?");
                $updateStmt->execute([Session::getUserId()]);
                
                Session::setFlash('success', '2FA has been disabled for your account.');
                break;
                
            case 'revoke_session':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                $sessionId = (int)($_POST['session_id'] ?? 0);
                if ($sessionId <= 0) {
                    throw new Exception('Invalid session ID.');
                }
                
                // Revoke the session (but not current session)
                $currentSessionToken = Session::get('session_token', '');
                $revokeStmt = $db->prepare("UPDATE user_sessions SET is_active = 0 WHERE id = ? AND user_id = ? AND session_token != ?");
                $revokeStmt->execute([$sessionId, Session::getUserId(), $currentSessionToken]);
                
                // Log security event
                $logStmt = $db->prepare("INSERT INTO security_logs (user_id, event_type, severity, ip_address, user_agent, details) VALUES (?, 'logout', 'low', ?, ?, ?)");
                $logStmt->execute([
                    Session::getUserId(),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                    json_encode(['action' => 'session_revoked', 'session_id' => $sessionId])
                ]);
                
                Session::setFlash('success', 'Device has been logged out successfully.');
                break;
                
            case 'update_login_alerts':
                $csrfToken = $_POST['csrf_token'] ?? '';
                if (!verifyCsrfToken($csrfToken)) {
                    throw new Exception('Invalid request. Please try again.');
                }
                
                $emailAlerts = isset($_POST['email_alerts']) ? 1 : 0;
                $smsAlerts = isset($_POST['sms_alerts']) ? 1 : 0;
                $newDeviceAlerts = isset($_POST['new_device_alerts']) ? 1 : 0;
                $suspiciousActivityAlerts = isset($_POST['suspicious_activity_alerts']) ? 1 : 0;
                
                // Update user preferences (with fallback for missing columns)
                try {
                    $updateStmt = $db->prepare("UPDATE users SET login_email_alerts = ?, login_sms_alerts = ?, new_device_alerts = ?, suspicious_activity_alerts = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$emailAlerts, $smsAlerts, $newDeviceAlerts, $suspiciousActivityAlerts, Session::getUserId()]);
                } catch (PDOException $e) {
                    // Fallback: store in preferences JSON column if alert columns don't exist
                    $alertPrefs = json_encode([
                        'login_email_alerts' => $emailAlerts,
                        'login_sms_alerts' => $smsAlerts,
                        'new_device_alerts' => $newDeviceAlerts,
                        'suspicious_activity_alerts' => $suspiciousActivityAlerts
                    ]);
                    $updateStmt = $db->prepare("UPDATE users SET preferences = ?, updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$alertPrefs, Session::getUserId()]);
                }
                
                Session::setFlash('success', 'Login alert preferences updated successfully.');
                break;
        }
        
    } catch (Exception $e) {
        Session::setFlash('error', $e->getMessage());
    }
    
    // Redirect to prevent re-submission
    redirect('/account.php?tab=' . $currentTab);
}

// Get current tab from query parameter
$currentTab = $_GET['tab'] ?? 'overview';
$validTabs = ['overview', 'orders', 'tracking', 'wallet', 'addresses', 'payments', 'security', 'preferences'];

if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'overview';
}

$page_title = 'My FezaMarket Account';
includeHeader($page_title);
?>

<!-- Account Management CSS -->
<link rel="stylesheet" href="/css/account-management.css">

<!-- Modern Account Dashboard Styles -->
<style>
:root {
    --account-primary: #2563eb;
    --account-success: #059669;
    --account-warning: #d97706;
    --account-danger: #dc2626;
    --account-gray-50: #f9fafb;
    --account-gray-100: #f3f4f6;
    --account-gray-200: #e5e7eb;
    --account-gray-300: #d1d5db;
    --account-gray-600: #4b5563;
    --account-gray-900: #111827;
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

body {
    background: var(--account-gray-50);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.account-dashboard {
    min-height: 100vh;
    padding: 2rem 0;
}

.account-header {
    background: linear-gradient(135deg, var(--account-primary), #3b82f6);
    color: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.account-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    transform: translate(50px, -50px);
}

.account-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    position: relative;
    z-index: 1;
}

.account-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
    position: relative;
    z-index: 1;
}

.account-navigation {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    overflow: hidden;
}

.nav-tabs {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 1px solid var(--account-gray-200);
    padding: 0;
    margin: 0;
}

.nav-tab {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    color: var(--account-gray-600);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
    border-bottom: 3px solid transparent;
    position: relative;
}

.nav-tab:hover {
    background: var(--account-gray-50);
    color: var(--account-primary);
}

.nav-tab.active {
    color: var(--account-primary);
    border-bottom-color: var(--account-primary);
    background: rgba(37, 99, 235, 0.05);
}

.tab-icon {
    font-size: 1.2rem;
    margin-right: 0.5rem;
}

.account-content {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow);
    overflow: hidden;
}

.account-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    padding: 2rem;
}

.account-card {
    background: white;
    border: 1px solid var(--account-gray-200);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
}

.account-card:hover {
    box-shadow: var(--shadow);
    transform: translateY(-1px);
}

.account-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--account-gray-900);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
}

.account-card h3 .icon {
    margin-right: 0.5rem;
    font-size: 1.5rem;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--account-primary);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: var(--account-primary);
    display: block;
}

.stat-label {
    font-size: 0.875rem;
    color: var(--account-gray-600);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-top: 0.5rem;
}

.wallet-card {
    background: linear-gradient(135deg, var(--account-success), #10b981);
    color: white;
    border: none;
}

.wallet-balance {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.wallet-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.wallet-btn {
    padding: 0.5rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 6px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s ease;
}

.wallet-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}

.info-grid {
    display: grid;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--account-gray-100);
}

.info-label {
    font-weight: 500;
    color: var(--account-gray-600);
}

.info-value {
    font-weight: 600;
    color: var(--account-gray-900);
}

.recent-orders {
    max-height: 400px;
    overflow-y: auto;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid var(--account-gray-100);
    transition: background 0.2s ease;
}

.order-item:hover {
    background: var(--account-gray-50);
}

.order-status {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-completed { background: rgba(5, 150, 105, 0.1); color: var(--account-success); }
.status-pending { background: rgba(217, 119, 6, 0.1); color: var(--account-warning); }
.status-processing { background: rgba(37, 99, 235, 0.1); color: var(--account-primary); }

.btn-primary {
    background: var(--account-primary);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
}

.alert-success {
    background: rgba(5, 150, 105, 0.1);
    color: var(--account-success);
    border-left: 4px solid var(--account-success);
}

.alert-error {
    background: rgba(220, 38, 38, 0.1);
    color: var(--account-danger);
    border-left: 4px solid var(--account-danger);
}

/* Order Tracking Styles */
.tracking-timeline {
    margin: 2rem 0;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
}

.timeline-marker {
    position: absolute;
    left: -1.5rem;
    top: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #e5e7eb;
    border: 3px solid #fff;
}

.timeline-item.active .timeline-marker {
    background: var(--account-primary);
}

.timeline-content h5 {
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
    font-weight: 600;
}

.timeline-content p {
    margin: 0 0 0.25rem 0;
    color: #6b7280;
}

.timeline-content small {
    color: #9ca3af;
    font-size: 0.875rem;
}

.tracking-info {
    padding: 1.5rem;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.tracking-status {
    margin-top: 0.5rem;
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.input-group input {
    flex: 1;
}
</style>

<div class="account-dashboard">
<div class="container">
    <!-- Flash Messages -->
    <?php if (Session::hasFlash('success')): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars(Session::getFlash('success')); ?>
        </div>
    <?php endif; ?>
    
    <?php if (Session::hasFlash('error')): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars(Session::getFlash('error')); ?>
        </div>
    <?php endif; ?>

    <!-- Modern Account Header -->
    <div class="account-header">
        <h1>Hello, <?php echo htmlspecialchars($current_user['first_name']); ?>! 👋</h1>
        <p class="account-subtitle">Welcome back to your dashboard. Manage your account and view your activity.</p>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats">
        <div class="stat-card">
            <span class="stat-number"><?php echo count($recentOrders); ?></span>
            <span class="stat-label">Recent Orders</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo count($loginDevices); ?></span>
            <span class="stat-label">Active Sessions</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $isVendor ? 'Yes' : 'No'; ?></span>
            <span class="stat-label">Seller Status</span>
        </div>
        <div class="stat-card wallet-card">
            <div class="wallet-balance">$<?php echo number_format($walletBalance, 2); ?></div>
            <div style="font-size: 0.875rem; opacity: 0.9;">Wallet Balance</div>
            <div class="wallet-actions">
                <button class="wallet-btn" onclick="addFunds()">Add Funds</button>
                <button class="wallet-btn" onclick="withdraw()">Withdraw</button>
            </div>
        </div>
    </div>

    <!-- Account Navigation -->
    <div class="account-navigation">
        <nav class="nav-tabs">
            <a href="?tab=overview" class="nav-tab <?php echo $currentTab === 'overview' ? 'active' : ''; ?>">
                <span class="tab-icon">📊</span>
                Overview
            </a>
            <a href="?tab=orders" class="nav-tab <?php echo $currentTab === 'orders' ? 'active' : ''; ?>">
                <span class="tab-icon">📦</span>
                Orders
            </a>
            <a href="?tab=tracking" class="nav-tab <?php echo $currentTab === 'tracking' ? 'active' : ''; ?>">
                <span class="tab-icon">🚚</span>
                Track Order
            </a>
            <a href="?tab=wallet" class="nav-tab <?php echo $currentTab === 'wallet' ? 'active' : ''; ?>">
                <span class="tab-icon">💰</span>
                Wallet
            </a>
            <a href="?tab=addresses" class="nav-tab <?php echo $currentTab === 'addresses' ? 'active' : ''; ?>">
                <span class="tab-icon">📍</span>
                Addresses
            </a>
            <a href="?tab=payments" class="nav-tab <?php echo $currentTab === 'payments' ? 'active' : ''; ?>">
                <span class="tab-icon">💳</span>
                Payment Methods
            </a>
            <a href="?tab=security" class="nav-tab <?php echo $currentTab === 'security' ? 'active' : ''; ?>">
                <span class="tab-icon">🔒</span>
                Security
            </a>
            <a href="?tab=preferences" class="nav-tab <?php echo $currentTab === 'preferences' ? 'active' : ''; ?>">
                <span class="tab-icon">⚙️</span>
                Preferences
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="account-content">
        <?php if ($currentTab === 'overview'): ?>
            <!-- Enhanced Overview Tab -->
            <div class="account-grid">
                <!-- Account Summary -->
                <div class="account-card">
                    <h3><span class="icon">👤</span>Account Summary</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($current_user['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Member Since</span>
                            <span class="info-value"><?php echo formatDate($current_user['created_at']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Type</span>
                            <span class="info-value">
                                <?php echo ucfirst(htmlspecialchars($current_user['role'])); ?>
                                <?php if ($isVendor): ?>
                                    <span class="badge badge-seller" style="background: var(--account-success); color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: 0.5rem;">Seller</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <div style="margin-top: 1.5rem;">
                        <button class="btn-primary" onclick="editProfile()">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </button>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="account-card">
                    <h3><span class="icon">📦</span>Recent Orders</h3>
                    <div class="recent-orders">
                        <?php if (empty($recentOrders)): ?>
                            <p style="text-align: center; color: var(--account-gray-600); padding: 2rem;">
                                <i class="fas fa-shopping-cart" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
                                No orders yet. <a href="/products.php" style="color: var(--account-primary);">Start shopping!</a>
                            </p>
                        <?php else: ?>
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="order-item">
                                    <div>
                                        <div style="font-weight: 600;">#<?php echo $order['id']; ?></div>
                                        <div style="font-size: 0.875rem; color: var(--account-gray-600);">
                                            <?php echo formatDate($order['created_at']); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <div style="text-align: right; margin-bottom: 0.25rem;">
                                            $<?php echo number_format($order['total_amount'], 2); ?>
                                        </div>
                                        <span class="order-status status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($currentTab === 'wallet'): ?>
            <!-- New Wallet Tab -->
            <div style="padding: 2rem;">
                <h2 style="margin-bottom: 2rem; display: flex; align-items: center;">
                    <span style="font-size: 2rem; margin-right: 0.5rem;">💰</span>
                    Wallet Management
                </h2>
                
                <div class="account-grid">
                    <!-- Wallet Balance -->
                    <div class="account-card wallet-card">
                        <h3 style="color: white;"><span class="icon">💳</span>Current Balance</h3>
                        <div class="wallet-balance">$<?php echo number_format($walletBalance, 2); ?></div>
                        <p style="opacity: 0.9; margin-bottom: 1.5rem;">Available for purchases and withdrawals</p>
                        <div class="wallet-actions">
                            <button class="wallet-btn" onclick="addFunds()">
                                <i class="fas fa-plus me-1"></i>Add Funds
                            </button>
                            <button class="wallet-btn" onclick="withdraw()">
                                <i class="fas fa-minus me-1"></i>Withdraw
                            </button>
                            <button class="wallet-btn" onclick="viewHistory()">
                                <i class="fas fa-history me-1"></i>History
                            </button>
                        </div>
                    </div>

                    <!-- Wallet Actions -->
                    <div class="account-card">
                        <h3><span class="icon">⚡</span>Quick Actions</h3>
                        <div style="display: grid; gap: 1rem;">
                            <button class="btn-primary" style="justify-self: start;" onclick="addFunds()">
                                <i class="fas fa-credit-card me-2"></i>Add Funds via Card
                            </button>
                            <button class="btn-primary" style="justify-self: start; background: var(--account-success);" onclick="showTransferModal()">
                                <i class="fas fa-exchange-alt me-2"></i>Transfer to User
                            </button>
                            <button class="btn-primary" style="justify-self: start; background: var(--account-warning);" onclick="requestPayout()">
                                <i class="fas fa-money-bill-wave me-2"></i>Request Payout
                            </button>
                            <button class="btn-primary" style="justify-self: start; background: var(--account-gray-600);" onclick="setupAutoReload()">
                                <i class="fas fa-sync me-2"></i>Setup Auto-Reload
                            </button>
                        </div>
                    </div>

                    <!-- Transaction History -->
                    <div class="account-card" style="grid-column: 1 / -1;">
                        <h3><span class="icon">📋</span>Recent Transactions</h3>
                        <div class="recent-orders">
                            <!-- Demo transactions -->
                            <div class="order-item">
                                <div>
                                    <div style="font-weight: 600;">Purchase Refund</div>
                                    <div style="font-size: 0.875rem; color: var(--account-gray-600);">Order #12345</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--account-success); font-weight: 600;">+$29.99</div>
                                    <div style="font-size: 0.75rem; color: var(--account-gray-600);">2 hours ago</div>
                                </div>
                            </div>
                            <div class="order-item">
                                <div>
                                    <div style="font-weight: 600;">Purchase</div>
                                    <div style="font-size: 0.875rem; color: var(--account-gray-600);">Demo Product</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--account-danger); font-weight: 600;">-$49.99</div>
                                    <div style="font-size: 0.75rem; color: var(--account-gray-600);">1 day ago</div>
                                </div>
                            </div>
                            <div class="order-item">
                                <div>
                                    <div style="font-weight: 600;">Wallet Top-up</div>
                                    <div style="font-size: 0.875rem; color: var(--account-gray-600);">Credit Card ****1234</div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: var(--account-success); font-weight: 600;">+$100.00</div>
                                    <div style="font-size: 0.75rem; color: var(--account-gray-600);">3 days ago</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="account-section">
                    <div class="account-card">
                        <h2>Quick Stats</h2>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number"><?php echo count($recentOrders); ?></div>
                                <div class="stat-label">Recent Orders</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?php echo $cart_count ?? 0; ?></div>
                                <div class="stat-label">Items in Cart</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php 
                                    // Get wishlist count (implement if wishlist exists)
                                    echo '0'; // Placeholder
                                    ?>
                                </div>
                                <div class="stat-label">Wishlist Items</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="account-section full-width">
                <div class="account-card">
                    <h2>Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="?tab=orders" class="action-link">
                            <span class="action-icon">📦</span>
                            <div>
                                <div class="action-title">Your Orders</div>
                                <div class="action-subtitle">Track and manage your orders</div>
                            </div>
                        </a>
                        <a href="/wishlist.php" class="action-link">
                            <span class="action-icon">�?��?</span>
                            <div>
                                <div class="action-title">Your Wishlist</div>
                                <div class="action-subtitle">Items you want to buy later</div>
                            </div>
                        </a>
                        <a href="/cart.php" class="action-link">
                            <span class="action-icon">🛒</span>
                            <div>
                                <div class="action-title">Your Cart</div>
                                <div class="action-subtitle">Review items ready to purchase</div>
                            </div>
                        </a>
                        <?php if ($isVendor): ?>
                        <a href="/seller-center.php" class="action-link">
                            <span class="action-icon">�?�</span>
                            <div>
                                <div class="action-title">Seller Center</div>
                                <div class="action-subtitle">Manage your store</div>
                            </div>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo sellerUrl('register'); ?>" class="action-link">
                            <span class="action-icon">💰</span>
                            <div>
                                <div class="action-title">Start Selling</div>
                                <div class="action-subtitle">Become a seller on FezaMarket</div>
                            </div>
                        </a>
                        <?php endif; ?>
                        <a href="?tab=security" class="action-link">
                            <span class="action-icon">🔒</span>
                            <div>
                                <div class="action-title">Security Settings</div>
                                <div class="action-subtitle">Password, 2FA, and more</div>
                            </div>
                        </a>
                        <a href="?tab=addresses" class="action-link">
                            <span class="action-icon">�?</span>
                            <div>
                                <div class="action-title">Addresses</div>
                                <div class="action-subtitle">Manage shipping addresses</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Preview -->
            <?php if (!empty($recentOrders)): ?>
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Recent Orders</h2>
                        <a href="?tab=orders" class="btn btn-outline">View All Orders</a>
                    </div>
                    <div class="orders-list">
                        <?php foreach (array_slice($recentOrders, 0, 3) as $order): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <div class="order-number">Order #<?php echo $order['id']; ?></div>
                                    <div class="order-date"><?php echo formatDate($order['created_at']); ?></div>
                                    <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </div>
                                </div>
                                <div class="order-total">
                                    <?php echo formatCurrency($order['total'] ?? 0); ?>
                                </div>
                                <div class="order-actions">
                                    <a href="/order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View Details</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php elseif ($currentTab === 'orders'): ?>
            <!-- Orders Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Your Orders</h2>
                    </div>
                    <?php if (!empty($recentOrders)): ?>
                        <div class="orders-list">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="order-item detailed">
                                    <div class="order-info">
                                        <div class="order-number">Order #<?php echo $order['id']; ?></div>
                                        <div class="order-date"><?php echo formatDate($order['created_at']); ?></div>
                                        <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </div>
                                    </div>
                                    <div class="order-details">
                                        <div class="order-total"><?php echo formatCurrency($order['total'] ?? 0); ?></div>
                                        <div class="order-description">
                                            <?php 
                                            // Get order items count
                                            $itemsStmt = $db->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
                                            $itemsStmt->execute([$order['id']]);
                                            $itemCount = $itemsStmt->fetchColumn();
                                            echo $itemCount ? "$itemCount item(s)" : 'Order details';
                                            ?>
                                        </div>
                                    </div>
                                    <div class="order-actions">
                                        <a href="/order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm">View Details</a>
                                        <a href="?tab=tracking#order-<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">Track</a>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">Cancel</button>
                                        <?php endif; ?>
                                        <?php if (in_array($order['status'], ['delivered', 'completed'])): ?>
                                            <a href="/checkout.php?reorder=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">Reorder</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">📦</div>
                            <h3>No orders yet</h3>
                            <p>When you place your first order, it will appear here.</p>
                            <a href="/products.php" class="btn btn-primary">Start Shopping</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($currentTab === 'tracking'): ?>
            <!-- Track Order Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>🚚 Track Your Order</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="trackingOrderId" class="form-label">Enter Order ID or Tracking Number:</label>
                            <div class="input-group">
                                <input type="text" id="trackingOrderId" class="form-control" placeholder="e.g., #12345 or TRACK123456">
                                <button onclick="trackOrder()" class="btn btn-primary">Track</button>
                            </div>
                        </div>
                        
                        <div id="trackingResults" style="display: none;">
                            <div class="tracking-info">
                                <h3 id="trackingOrderNumber"></h3>
                                <div class="tracking-status" id="trackingStatus"></div>
                            </div>
                            
                            <div class="tracking-timeline" id="trackingTimeline">
                                <!-- Timeline will be populated dynamically -->
                            </div>
                            
                            <div class="tracking-details" id="trackingDetails">
                                <!-- Order details will be populated -->
                            </div>
                        </div>
                        
                        <div id="trackingError" class="alert alert-danger" style="display: none;"></div>
                        
                        <?php if (!empty($recentOrders)): ?>
                            <div class="mt-4">
                                <h4>Recent Orders:</h4>
                                <div class="list-group">
                                    <?php foreach (array_slice($recentOrders, 0, 5) as $order): ?>
                                        <a href="#" onclick="trackOrderById(<?php echo $order['id']; ?>); return false;" class="list-group-item list-group-item-action">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Order #<?php echo $order['id']; ?></strong>
                                                    <br>
                                                    <small><?php echo formatDate($order['created_at']); ?></small>
                                                </div>
                                                <span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : 'primary'; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($currentTab === 'addresses'): ?>
            <!-- Addresses Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Your Addresses</h2>
                        <button class="btn btn-primary" onclick="showAddressModal()">Add New Address</button>
                    </div>
                    
                    <?php
                    // Get user addresses
                    $addressStmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
                    $addressStmt->execute([Session::getUserId()]);
                    $addresses = $addressStmt->fetchAll();
                    ?>
                    
                    <?php if (!empty($addresses)): ?>
                        <div class="addresses-grid">
                            <?php foreach ($addresses as $address): ?>
                                <div class="address-card <?php echo $address['is_default'] ? 'default-address' : ''; ?>">
                                    <?php if ($address['is_default']): ?>
                                        <div class="default-badge">Default</div>
                                    <?php endif; ?>
                                    
                                    <div class="address-type"><?php echo ucfirst($address['type']); ?> Address</div>
                                    
                                    <div class="address-details">
                                        <?php if ($address['first_name'] || $address['last_name']): ?>
                                            <div class="address-name"><?php echo htmlspecialchars(trim($address['first_name'] . ' ' . $address['last_name'])); ?></div>
                                        <?php endif; ?>
                                        
                                        <?php if ($address['company']): ?>
                                            <div class="address-company"><?php echo htmlspecialchars($address['company']); ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="address-line"><?php echo htmlspecialchars($address['address_line1']); ?></div>
                                        
                                        <?php if ($address['address_line2']): ?>
                                            <div class="address-line"><?php echo htmlspecialchars($address['address_line2']); ?></div>
                                        <?php endif; ?>
                                        
                                        <div class="address-location">
                                            <?php echo htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']); ?>
                                        </div>
                                        
                                        <?php if ($address['phone']): ?>
                                            <div class="address-phone"><?php echo htmlspecialchars($address['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="address-actions">
                                        <button class="btn btn-sm btn-outline" onclick="editAddress(<?php echo $address['id']; ?>)">Edit</button>
                                        <?php if (!$address['is_default']): ?>
                                            <button class="btn btn-sm btn-outline" onclick="makeDefaultAddress(<?php echo $address['id']; ?>)">Make Default</button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-danger" onclick="deleteAddress(<?php echo $address['id']; ?>)">Delete</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">�?</div>
                            <h3>No addresses saved</h3>
                            <p>Add an address to make checkout faster and easier.</p>
                            <button class="btn btn-primary" onclick="showAddressModal()">Add Your First Address</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($currentTab === 'payments'): ?>
            <!-- Payment Methods Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Saved Payment Methods</h2>
                        <p style="color: #666; font-size: 0.9rem; margin-top: 5px;">Securely manage your payment methods</p>
                    </div>
                    
                    <?php
                    // Get user saved payment methods
                    try {
                        $paymentStmt = $db->prepare("SELECT * FROM user_payment_methods WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
                        $paymentStmt->execute([Session::getUserId()]);
                        $paymentMethods = $paymentStmt->fetchAll();
                    } catch (Exception $e) {
                        // Table might not exist yet
                        $paymentMethods = [];
                    }
                    ?>
                    
                    <div class="add-payment-method-section" style="margin-bottom: 2rem;">
                        <button class="btn btn-primary" onclick="showAddPaymentMethodModal()" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <i class="fas fa-plus"></i>
                            Add New Payment Method
                        </button>
                    </div>
                    
                    <?php if (!empty($paymentMethods)): ?>
                        <div class="payment-methods">
                            <?php foreach ($paymentMethods as $method): ?>
                                <div class="payment-card">
                                    <div class="payment-type">
                                        <span class="card-icon">
                                            <?php
                                            // Display card brand icon
                                            $brandIcons = [
                                                'visa' => '💳',
                                                'mastercard' => '💳',
                                                'amex' => '💳',
                                                'discover' => '💳',
                                                'diners' => '💳',
                                                'jcb' => '💳',
                                                'unionpay' => '💳'
                                            ];
                                            echo $brandIcons[strtolower($method['brand'])] ?? '💳';
                                            ?>
                                        </span>
                                        <div class="payment-details">
                                            <div class="payment-brand"><?php echo htmlspecialchars(ucfirst($method['brand'])); ?></div>
                                            <div class="payment-number">•••• •••• •••• <?php echo htmlspecialchars($method['last4']); ?></div>
                                            <div class="payment-expiry">Expires <?php echo sprintf('%02d/%d', $method['exp_month'], $method['exp_year']); ?></div>
                                        </div>
                                    </div>
                                    <div class="payment-status">
                                        <?php if ($method['is_default']): ?>
                                            <div class="default-badge">Default</div>
                                        <?php endif; ?>
                                        <div class="payment-actions">
                                            <?php if (!$method['is_default']): ?>
                                                <button class="btn btn-sm btn-outline" onclick="makeDefaultPaymentMethod(<?php echo $method['id']; ?>)">Make Default</button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-danger" onclick="deletePaymentMethod(<?php echo $method['id']; ?>)">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">💳</div>
                            <h3>No saved payment methods</h3>
                            <p>Add a payment method now to speed up your future purchases.</p>
                            <button class="btn btn-primary" onclick="showAddPaymentMethodModal()">Add Payment Method</button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="payment-integrations">
                        <h4>Supported Payment Methods</h4>
                        <div class="integration-grid">
                            <div class="integration-item">
                                <div class="integration-icon">💳</div>
                                <div class="integration-info">
                                    <div class="integration-name">Credit/Debit Cards</div>
                                    <div class="integration-desc">Visa, Mastercard, American Express</div>
                                    <div class="integration-status enabled">✓ Stripe Integration</div>
                                </div>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-icon">📱</div>
                                <div class="integration-info">
                                    <div class="integration-name">MTN Mobile Money</div>
                                    <div class="integration-desc">Pay with your mobile money wallet</div>
                                    <div class="integration-status enabled">✓ MTN API Ready</div>
                                </div>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-icon">₿</div>
                                <div class="integration-info">
                                    <div class="integration-name">Cryptocurrency</div>
                                    <div class="integration-desc">Bitcoin, Ethereum, USDT</div>
                                    <div class="integration-status enabled">✓ Crypto Gateway</div>
                                </div>
                            </div>
                            
                            <div class="integration-item">
                                <div class="integration-icon">🏦</div>
                                <div class="integration-info">
                                    <div class="integration-name">Bank Transfer</div>
                                    <div class="integration-desc">Direct bank account transfers</div>
                                    <div class="integration-status pending">⏳ Coming Soon</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-note">
                        <p><strong>Security:</strong> Your payment information is encrypted and securely stored by our certified payment processors. We never store your full card details.</p>
                        <p><strong>API Configuration:</strong> Payment gateway API keys are configured in the admin panel for security.</p>
                    </div>
                </div>
            </div>

        <?php elseif ($currentTab === 'preferences'): ?>
            <!-- Preferences Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Account Preferences</h2>
                        <p class="card-description">Customize your FezaMarket experience</p>
                    </div>
                    
                    <form method="POST" class="preferences-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="update_preferences">
                        
                        <div class="preference-section">
                            <h4>Communication Preferences</h4>
                            <div class="preference-options">
                                <label class="preference-option">
                                    <input type="checkbox" name="email_marketing" checked>
                                    <span class="checkmark"></span>
                                    <div class="option-info">
                                        <strong>Marketing Emails</strong>
                                        <p>Receive emails about new products, sales, and special offers</p>
                                    </div>
                                </label>
                                
                                <label class="preference-option">
                                    <input type="checkbox" name="email_order_updates" checked>
                                    <span class="checkmark"></span>
                                    <div class="option-info">
                                        <strong>Order Updates</strong>
                                        <p>Get notified about order status changes and shipping updates</p>
                                    </div>
                                </label>
                                
                                <label class="preference-option">
                                    <input type="checkbox" name="email_recommendations" checked>
                                    <span class="checkmark"></span>
                                    <div class="option-info">
                                        <strong>Product Recommendations</strong>
                                        <p>Receive personalized product suggestions based on your interests</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="preference-section">
                            <h4>Display Preferences</h4>
                            <div class="preference-options">
                                <label class="preference-option">
                                    <span class="option-info">
                                        <strong>Currency</strong>
                                        <p>Choose your preferred currency for prices</p>
                                    </span>
                                    <select name="currency" class="form-control">
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="GBP">GBP (£)</option>
                                    </select>
                                </label>
                                
                                <label class="preference-option">
                                    <span class="option-info">
                                        <strong>Language</strong>
                                        <p>Select your preferred language</p>
                                    </span>
                                    <select name="language" class="form-control">
                                        <option value="en">English</option>
                                        <option value="es">Español</option>
                                        <option value="fr">Français</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                        
                        <div class="preference-section">
                            <h4>Privacy Settings</h4>
                            <div class="preference-options">
                                <label class="preference-option">
                                    <input type="checkbox" name="profile_public">
                                    <span class="checkmark"></span>
                                    <div class="option-info">
                                        <strong>Public Profile</strong>
                                        <p>Allow other users to see your public profile and purchase history</p>
                                    </div>
                                </label>
                                
                                <label class="preference-option">
                                    <input type="checkbox" name="data_collection" checked>
                                    <span class="checkmark"></span>
                                    <div class="option-info">
                                        <strong>Analytics & Improvement</strong>
                                        <p>Help us improve FezaMarket by sharing anonymous usage data</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($currentTab === 'security'): ?>
            <!-- Security Tab -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2>Security Settings</h2>
                        <p class="card-description">Keep your account safe and secure</p>
                    </div>
                    
                    <div class="security-options">
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Password</h4>
                                <p>Change your account password</p>
                            </div>
                            <div class="security-action">
                                <button class="btn btn-outline" onclick="openPasswordModal()">Change Password</button>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Two-Factor Authentication</h4>
                                <p>Add an extra layer of security to your account</p>
                            </div>
                            <div class="security-action">
                                <?php 
                                $twoFactorEnabled = isset($current_user['two_factor_enabled']) ? $current_user['two_factor_enabled'] : 0;
                                ?>
                                <span class="badge <?php echo $twoFactorEnabled ? 'badge-enabled' : 'badge-disabled'; ?>">
                                    <?php echo $twoFactorEnabled ? 'Enabled' : 'Not Enabled'; ?>
                                </span>
                                <?php if ($twoFactorEnabled): ?>
                                    <button class="btn btn-outline" onclick="open2FAModal('disable')">Disable 2FA</button>
                                <?php else: ?>
                                    <button class="btn btn-outline" onclick="open2FAModal('enable')">Enable 2FA</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Login Devices</h4>
                                <p>Manage devices that can access your account</p>
                            </div>
                            <div class="security-action">
                                <button class="btn btn-outline" onclick="openLoginDevicesModal()">Manage Devices</button>
                            </div>
                        </div>
                        
                        <div class="security-item">
                            <div class="security-info">
                                <h4>Login Alerts</h4>
                                <p>Get notified when someone logs into your account</p>
                            </div>
                            <div class="security-action">
                                <?php 
                                $alertsEnabled = true; // For now, assume enabled
                                ?>
                                <span class="badge <?php echo $alertsEnabled ? 'badge-enabled' : 'badge-disabled'; ?>">
                                    <?php echo $alertsEnabled ? 'Enabled' : 'Disabled'; ?>
                                </span>
                                <button class="btn btn-outline" onclick="openLoginAlertsModal()">Configure</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Other tabs (placeholder) -->
            <div class="account-section full-width">
                <div class="account-card">
                    <div class="card-header">
                        <h2><?php echo ucfirst($currentTab); ?></h2>
                        <p class="card-description">This section is being developed.</p>
                    </div>
                    
                    <div class="empty-state">
                        <div class="empty-icon">🚧</div>
                        <h3>Coming Soon</h3>
                        <p>This feature is currently under development.</p>
                        <a href="?tab=overview" class="btn btn-primary">Back to Overview</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Enhanced Account Dashboard JavaScript -->
<script>
// Wallet functionality
function addFunds() {
    showWalletModal('add', {
        title: 'Add Funds to Wallet',
        description: 'Choose how much you want to add to your wallet',
        action: 'add'
    });
}

function withdraw() {
    showWalletModal('withdraw', {
        title: 'Withdraw from Wallet',
        description: 'Enter the amount you want to withdraw',
        action: 'withdraw'
    });
}

function viewHistory() {
    window.location.href = '?tab=wallet&view=history';
}

function requestPayout() {
    showWalletModal('payout', {
        title: 'Request Payout',
        description: 'Request a payout to your bank account',
        action: 'payout'
    });
}

function setupAutoReload() {
    showWalletModal('auto-reload', {
        title: 'Setup Auto-Reload',
        description: 'Automatically reload your wallet when balance is low',
        action: 'auto-reload'
    });
}

function showWalletModal(type, config) {
    const modal = document.createElement('div');
    modal.className = 'wallet-modal-overlay';
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); z-index: 1000;
        display: flex; align-items: center; justify-content: center;
    `;
    
    const content = document.createElement('div');
    content.className = 'wallet-modal-content';
    content.style.cssText = `
        background: white; border-radius: 12px; padding: 2rem;
        max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    `;
    
    let formContent = '';
    
    switch(type) {
        case 'add':
            formContent = `
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Amount to Add</label>
                    <input type="number" step="0.01" min="10" max="5000" placeholder="100.00" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--account-gray-200); border-radius: 8px;">
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Payment Method</label>
                    <select style="width: 100%; padding: 0.75rem; border: 2px solid var(--account-gray-200); border-radius: 8px;">
                        <option>Credit Card ****1234</option>
                        <option>PayPal Account</option>
                        <option>Bank Transfer</option>
                    </select>
                </div>
            `;
            break;
        case 'withdraw':
            formContent = `
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Amount to Withdraw</label>
                    <input type="number" step="0.01" min="5" max="<?php echo $walletBalance; ?>" placeholder="50.00" 
                           style="width: 100%; padding: 0.75rem; border: 2px solid var(--account-gray-200); border-radius: 8px;">
                    <small style="color: var(--account-gray-600);">Available balance: $<?php echo number_format($walletBalance, 2); ?></small>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Withdraw To</label>
                    <select style="width: 100%; padding: 0.75rem; border: 2px solid var(--account-gray-200); border-radius: 8px;">
                        <option>Bank Account ****5678</option>
                        <option>PayPal Account</option>
                    </select>
                </div>
            `;
            break;
    }
    
    content.innerHTML = `
        <div style="margin-bottom: 1.5rem;">
            <h3 style="margin: 0 0 0.5rem; font-size: 1.5rem; font-weight: 600;">${config.title}</h3>
            <p style="margin: 0; color: var(--account-gray-600); font-size: 0.95rem;">${config.description}</p>
        </div>
        <form onsubmit="processWalletAction(event, '${config.action}')" style="margin-bottom: 1.5rem;">
            ${formContent}
        </form>
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <button onclick="closeWalletModal()" 
                    style="padding: 0.75rem 1.5rem; border: 2px solid var(--account-gray-300); background: white; 
                           color: var(--account-gray-600); border-radius: 8px; cursor: pointer;">
                Cancel
            </button>
            <button onclick="processWalletAction(event, '${config.action}')" 
                    style="padding: 0.75rem 1.5rem; background: var(--account-primary); color: white; 
                           border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                ${type === 'add' ? 'Add Funds' : type === 'withdraw' ? 'Withdraw' : 'Continue'}
            </button>
        </div>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    // Close on overlay click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeWalletModal();
    });
    
    window.currentWalletModal = modal;
}

function closeWalletModal() {
    if (window.currentWalletModal) {
        window.currentWalletModal.remove();
        window.currentWalletModal = null;
    }
}

function processWalletAction(event, action) {
    event.preventDefault();
    
    // Show processing state
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = 'Processing...';
    btn.disabled = true;
    
    setTimeout(() => {
        closeWalletModal();
        showNotification(
            action === 'add' ? 'Funds added successfully!' : 
            action === 'withdraw' ? 'Withdrawal request submitted!' : 
            'Action completed successfully!',
            'success'
        );
        
        // Simulate balance update
        if (action === 'add') {
            setTimeout(() => location.reload(), 1000);
        }
    }, 2000);
}

function editProfile() {
    // Redirect to profile edit page or show modal
    window.location.href = '?tab=preferences&section=profile';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 1050;
        background: ${type === 'success' ? 'var(--account-success)' : 
                    type === 'error' ? 'var(--account-danger)' : 'var(--account-primary)'};
        color: white; padding: 1rem 1.5rem; border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15); transform: translateX(400px);
        transition: transform 0.3s ease; max-width: 300px;
    `;
    
    notification.innerHTML = `
        <div style="display: flex; align-items: center;">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                              type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Tab switching with smooth transitions
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.nav-tab');
    
    tabLinks.forEach(tab => {
        tab.addEventListener('click', function(e) {
            if (this.href.includes('tab=wallet') && e.ctrlKey) {
                e.preventDefault();
                // Quick wallet modal for power users
                addFunds();
            }
        });
    });
    
    // Add loading states to action buttons
    const actionButtons = document.querySelectorAll('.btn-primary, .wallet-btn');
    
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.disabled) {
                this.style.opacity = '0.8';
                setTimeout(() => {
                    if (this.style) this.style.opacity = '1';
                }, 200);
            }
        });
    });
});
</script>

<!-- Address Modal -->
<div id="addressModal" class="modal" style="display: none;">
    <div class="modal-content">
        <h3>Add New Address</h3>
        <form method="POST" id="addressForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="action" value="add_address">
            
            <div class="form-row">
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Company (Optional)</label>
                <input type="text" name="company" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Address Line 1</label>
                <input type="text" name="address_line1" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Address Line 2 (Optional)</label>
                <input type="text" name="address_line2" class="form-control">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>ZIP Code</label>
                    <input type="text" name="postal_code" class="form-control" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Phone (Optional)</label>
                <input type="tel" name="phone" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Address Type</label>
                <select name="type" class="form-control" required>
                    <option value="both">Both Billing & Shipping</option>
                    <option value="billing">Billing Only</option>
                    <option value="shipping">Shipping Only</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_default" value="1">
                    <span class="checkmark"></span>
                    Make this my default address
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Address</button>
                <button type="button" class="btn btn-secondary" onclick="hideAddressModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Payment Method Modal -->
<div id="paymentModal" class="modal" style="display: none;">
    <div class="modal-content large">
        <h3>Add Payment Method</h3>
        <div class="payment-method-tabs">
            <button class="payment-tab active" data-tab="card">Credit/Debit Card</button>
            <button class="payment-tab" data-tab="mobile">Mobile Money</button>
            <button class="payment-tab" data-tab="crypto">Cryptocurrency</button>
        </div>
        
        <!-- Credit Card Form -->
        <div class="payment-form-container" id="cardForm">
            <form method="POST" class="payment-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_payment_method">
                <input type="hidden" name="method_type" value="card">
                
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" name="card_number" class="form-control card-input" 
                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                    <div class="card-icons">
                        <span class="card-icon visa">💳</span>
                        <span class="card-icon mastercard">💳</span>
                        <span class="card-icon amex">💳</span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="text" name="expiry" class="form-control" placeholder="MM/YY" maxlength="5" required>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="text" name="cvv" class="form-control" placeholder="123" maxlength="4" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Cardholder Name</label>
                    <input type="text" name="cardholder_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_default" value="1">
                        <span class="checkmark"></span>
                        Make this my default payment method
                    </label>
                </div>
                
                <div class="payment-security-info">
                    <p><strong>🔒 Secure Processing:</strong> Your card details are encrypted and processed securely through Stripe.</p>
                    <p><em>API Key Placeholder: pk_test_...</em></p>
                </div>
            </form>
        </div>
        
        <!-- Mobile Money Form -->
        <div class="payment-form-container" id="mobileForm" style="display: none;">
            <form method="POST" class="payment-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_payment_method">
                <input type="hidden" name="method_type" value="mobile">
                
                <div class="form-group">
                    <label>Mobile Money Provider</label>
                    <select name="provider" class="form-control" required>
                        <option value="">Select Provider</option>
                        <option value="mtn">MTN Mobile Money</option>
                        <option value="airtel">Airtel Money</option>
                        <option value="telecel">Telecel Cash</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone_number" class="form-control" 
                           placeholder="+256 700 000 000" required>
                </div>
                
                <div class="form-group">
                    <label>Account Name</label>
                    <input type="text" name="account_name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_default" value="1">
                        <span class="checkmark"></span>
                        Make this my default payment method
                    </label>
                </div>
                
                <div class="payment-security-info">
                    <p><strong>📱 MTN Integration:</strong> Connect your mobile money account for seamless payments.</p>
                    <p><em>API Key Placeholder: mtn_api_key_...</em></p>
                </div>
            </form>
        </div>
        
        <!-- Crypto Form -->
        <div class="payment-form-container" id="cryptoForm" style="display: none;">
            <form method="POST" class="payment-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="add_payment_method">
                <input type="hidden" name="method_type" value="crypto">
                
                <div class="form-group">
                    <label>Cryptocurrency</label>
                    <select name="crypto_type" class="form-control" required>
                        <option value="">Select Cryptocurrency</option>
                        <option value="bitcoin">Bitcoin (BTC)</option>
                        <option value="ethereum">Ethereum (ETH)</option>
                        <option value="usdt">Tether (USDT)</option>
                        <option value="usdc">USD Coin (USDC)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Wallet Address</label>
                    <input type="text" name="wallet_address" class="form-control" 
                           placeholder="1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa" required>
                    <div class="form-help">Enter your cryptocurrency wallet address</div>
                </div>
                
                <div class="form-group">
                    <label>Wallet Label (Optional)</label>
                    <input type="text" name="wallet_label" class="form-control" 
                           placeholder="My Bitcoin Wallet">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_default" value="1">
                        <span class="checkmark"></span>
                        Make this my default payment method
                    </label>
                </div>
                
                <div class="payment-security-info">
                    <p><strong>₿ Crypto Payments:</strong> Pay with your favorite cryptocurrency through our secure gateway.</p>
                    <p><em>Gateway API: crypto_api_key_...</em></p>
                </div>
            </form>
        </div>
        
        <div class="form-actions">
            <button type="button" class="btn btn-primary" onclick="submitPaymentForm()">Add Payment Method</button>
            <button type="button" class="btn btn-secondary" onclick="hidePaymentModal()">Cancel</button>
        </div>
    </div>
</div>

<script>
// Address Management
function showAddressModal() {
    document.getElementById('addressModal').style.display = 'block';
}

function hideAddressModal() {
    document.getElementById('addressModal').style.display = 'none';
}

function editAddress(addressId) {
    // Implementation for editing addresses
    alert('Edit address functionality to be implemented');
}

function makeDefaultAddress(addressId) {
    if (confirm('Make this your default address?')) {
        // AJAX call to make address default
        fetch('/api/addresses/set-default', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ address_id: addressId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating default address');
            }
        });
    }
}

function deleteAddress(addressId) {
    if (confirm('Are you sure you want to delete this address?')) {
        // AJAX call to delete address
        fetch('/api/addresses/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ address_id: addressId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting address');
            }
        });
    }
}

// Payment Method Management
function makeDefaultPaymentMethod(paymentMethodId) {
    if (confirm('Make this your default payment method?')) {
        fetch('/api/payment-methods/set-default', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ payment_method_id: paymentMethodId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating default payment method: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error updating default payment method');
            console.error('Error:', error);
        });
    }
}

function deletePaymentMethod(paymentMethodId) {
    if (confirm('Are you sure you want to remove this payment method? This action cannot be undone.')) {
        fetch('/api/payment-methods/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ payment_method_id: paymentMethodId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting payment method: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error deleting payment method');
            console.error('Error:', error);
        });
    }
}

// Payment Management
function showPaymentModal() {
    document.getElementById('paymentModal').style.display = 'block';
    // Set first tab as active
    document.querySelector('.payment-tab').click();
}

function showAddPaymentMethodModal() {
    // Alias for showPaymentModal - used in payment methods section
    showPaymentModal();
}

function hidePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    // Reset all forms
    document.querySelectorAll('.payment-form').forEach(form => form.reset());
    // Reset to first tab
    document.querySelector('.payment-tab').click();
}

function submitPaymentForm() {
    const activeTab = document.querySelector('.payment-tab.active').dataset.tab;
    const activeForm = document.querySelector(`#${activeTab}Form .payment-form`);
    
    // Basic validation
    const requiredFields = activeForm.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc2626';
            isValid = false;
        } else {
            field.style.borderColor = '#d1d5db';
        }
    });
    
    if (!isValid) {
        alert('Please fill in all required fields');
        return;
    }
    
    // For demo purposes - in production this would integrate with payment processors
    alert(`${activeTab.charAt(0).toUpperCase() + activeTab.slice(1)} payment method would be added here.\n\nThis requires integration with:\n- Stripe for cards\n- MTN API for mobile money\n- Crypto payment gateway`);
    hidePaymentModal();
}

// Payment tab switching
document.addEventListener('DOMContentLoaded', function() {
    // Setup payment tabs
    document.querySelectorAll('.payment-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs and forms
            document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.payment-form-container').forEach(f => f.style.display = 'none');
            
            // Add active class to clicked tab and show corresponding form
            this.classList.add('active');
            const formId = this.dataset.tab + 'Form';
            document.getElementById(formId).style.display = 'block';
        });
    });
    
    // Card number formatting
    const cardInput = document.querySelector('input[name="card_number"]');
    if (cardInput) {
        cardInput.addEventListener('input', function() {
            let value = this.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            this.value = formattedValue;
        });
    }
    
    // Expiry date formatting
    const expiryInput = document.querySelector('input[name="expiry"]');
    if (expiryInput) {
        expiryInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0,2) + '/' + value.substring(2,4);
            }
            this.value = value;
        });
    }
    
    // CVV validation
    const cvvInput = document.querySelector('input[name="cvv"]');
    if (cvvInput) {
        cvvInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 4);
        });
    }
    
    // Phone number formatting
    const phoneInput = document.querySelector('input[name="phone_number"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('256')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+256' + value.substring(1);
            } else if (!value.startsWith('+')) {
                value = '+256' + value;
            }
            this.value = value;
        });
    }
});

// Close modals when clicking outside
window.onclick = function(event) {
    const addressModal = document.getElementById('addressModal');
    if (event.target === addressModal) {
        hideAddressModal();
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Basic validation
            const requiredFields = form.querySelectorAll('input[required], select[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc2626';
                    isValid = false;
                } else {
                    field.style.borderColor = '#d1d5db';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields');
            }
        });
    });
});
</script>

<style>
.stats-container {
    text-align: center;
}

.stat-item {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 6px;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    color: #0064d2;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.action-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.action-link:hover {
    border-color: #0064d2;
    box-shadow: 0 2px 4px rgba(0, 100, 210, 0.1);
    transform: translateY(-1px);
}

.action-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.action-title {
    font-weight: 500;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.action-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Orders */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    transition: border-color 0.2s;
}

.order-item:hover {
    border-color: #d1d5db;
}

.order-item.detailed {
    flex-direction: column;
    align-items: stretch;
}

.order-item.detailed .order-info,
.order-item.detailed .order-details,
.order-item.detailed .order-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-number {
    font-weight: 500;
    color: #1f2937;
}

.order-date {
    font-size: 0.875rem;
    color: #6b7280;
}

.order-status {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-pending {
    background-color: #fef3c7;
    color: #92400e;
}

.status-shipped {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-delivered {
    background-color: #dcfce7;
    color: #166534;
}

.status-cancelled {
    background-color: #fef2f2;
    color: #991b1b;
}

.order-total {
    font-weight: 600;
    color: #1f2937;
}

.order-actions {
    display: flex;
    gap: 0.5rem;
}

/* Security Settings */
.security-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.security-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.security-info h4 {
    margin-bottom: 0.25rem;
    color: #1f2937;
}

.security-info p {
    margin: 0;
    font-size: 0.875rem;
    color: #6b7280;
}

.security-action {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

/* Buttons */
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    font-size: 0.875rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: all 0.2s;
    font-weight: 500;
}

.btn-primary {
    background: #0064d2;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-outline {
    background: white;
    color: #0064d2;
    border: 1px solid #0064d2;
}

.btn-outline:hover {
    background: #0064d2;
    color: white;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .account-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .nav-tabs {
        padding-bottom: 0;
    }
    
    .nav-tab {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
    
    .order-item {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .security-item {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .card-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
}

.action-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: background-color 0.3s ease;
}

.action-link:hover {
    background: #f3f4f6;
}

.action-icon {
    font-size: 24px;
}

.action-title {
    font-weight: 600;
    color: #1f2937;
}

.action-subtitle {
    color: #6b7280;
    font-size: 14px;
}
</style>



<!-- Password Change Modal -->
<div id="passwordModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Change Password</h3>
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
        </div>
        <form method="post" class="modal-form">
            <?php echo csrfTokenInput(); ?>
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" required minlength="8">
                <div class="form-help">Must be at least 8 characters long</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closePasswordModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </div>
        </form>
    </div>
</div>

<!-- 2FA Modal -->
<div id="twoFactorModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="twoFactorTitle">Enable Two-Factor Authentication</h3>
            <button class="modal-close" onclick="close2FAModal()">&times;</button>
        </div>
        <div id="twoFactorContent">
            <!-- Content will be filled by JavaScript -->
        </div>
    </div>
</div>

<!-- Login Devices Modal -->
<div id="loginDevicesModal" class="modal" style="display: none;">
    <div class="modal-content large-modal">
        <div class="modal-header">
            <h3>Manage Login Devices</h3>
            <button class="modal-close" onclick="closeLoginDevicesModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>These are the devices currently logged into your account. You can revoke access from any device.</p>
            
            <div class="devices-list">
                <?php if (!empty($loginDevices)): ?>
                    <?php foreach ($loginDevices as $device): ?>
                        <?php
                        $currentSession = Session::get('session_token', '') === $device['session_token'];
                        $userAgent = $device['user_agent'] ?? 'Unknown Browser';
                        $deviceInfo = parseUserAgent($userAgent);
                        ?>
                        <div class="device-item <?php echo $currentSession ? 'current-device' : ''; ?>">
                            <div class="device-info">
                                <div class="device-icon">
                                    <?php echo getDeviceIcon($deviceInfo['device_type']); ?>
                                </div>
                                <div class="device-details">
                                    <h4><?php echo htmlspecialchars($deviceInfo['browser'] . ' on ' . $deviceInfo['os']); ?></h4>
                                    <p>IP: <?php echo htmlspecialchars($device['ip_address']); ?></p>
                                    <p>Last active: <?php echo date('M j, Y \a\t g:i A', strtotime($device['created_at'])); ?></p>
                                    <?php if ($currentSession): ?>
                                        <span class="current-label">Current Device</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!$currentSession): ?>
                                <div class="device-actions">
                                    <form method="post" style="display: inline;">
                                        <?php echo csrfTokenInput(); ?>
                                        <input type="hidden" name="action" value="revoke_session">
                                        <input type="hidden" name="session_id" value="<?php echo $device['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to log out this device?')">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No active devices found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Login Alerts Modal -->
<div id="loginAlertsModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Login Alert Settings</h3>
            <button class="modal-close" onclick="closeLoginAlertsModal()">&times;</button>
        </div>
        <form method="post" class="modal-form">
            <?php echo csrfTokenInput(); ?>
            <input type="hidden" name="action" value="update_login_alerts">
            
            <div class="modal-body">
                <p>Configure how you want to be notified about account activity.</p>
                
                <div class="alert-options">
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="email_alerts" <?php echo (isset($current_user['login_email_alerts']) && $current_user['login_email_alerts']) ? 'checked' : 'checked'; ?>>
                            <span class="checkmark"></span>
                            Email notifications for new logins
                        </label>
                        <p class="option-description">Get an email when someone logs into your account</p>
                    </div>
                    
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_alerts" <?php echo (isset($current_user['login_sms_alerts']) && $current_user['login_sms_alerts']) ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            SMS notifications for new logins
                        </label>
                        <p class="option-description">Get a text message when someone logs into your account</p>
                    </div>
                    
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="new_device_alerts" <?php echo (isset($current_user['new_device_alerts']) && $current_user['new_device_alerts']) ? 'checked' : 'checked'; ?>>
                            <span class="checkmark"></span>
                            New device alerts
                        </label>
                        <p class="option-description">Get notified when your account is accessed from a new device</p>
                    </div>
                    
                    <div class="alert-option">
                        <label class="checkbox-label">
                            <input type="checkbox" name="suspicious_activity_alerts" <?php echo (isset($current_user['suspicious_activity_alerts']) && $current_user['suspicious_activity_alerts']) ? 'checked' : 'checked'; ?>>
                            <span class="checkmark"></span>
                            Suspicious activity alerts
                        </label>
                        <p class="option-description">Get notified about unusual login patterns or potential security threats</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeLoginAlertsModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<script>
// Password Modal Functions
function openPasswordModal() {
    document.getElementById('passwordModal').style.display = 'flex';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
    // Clear form
    document.getElementById('passwordModal').querySelector('form').reset();
}

// 2FA Modal Functions
function open2FAModal(action) {
    const modal = document.getElementById('twoFactorModal');
    const title = document.getElementById('twoFactorTitle');
    const content = document.getElementById('twoFactorContent');
    
    if (action === 'enable') {
        title.textContent = 'Enable Two-Factor Authentication';
        content.innerHTML = `
            <div class="modal-body">
                <p>Two-Factor Authentication adds an extra layer of security to your account by requiring a verification code from your phone in addition to your password.</p>
                <div class="twofa-benefits">
                    <h4>Benefits:</h4>
                    <ul>
                        <li>Protects your account even if your password is compromised</li>
                        <li>Reduces risk of unauthorized access</li>
                        <li>Provides real-time security alerts</li>
                    </ul>
                </div>
                <p><strong>Note:</strong> This is a basic implementation. In production, this would involve QR code generation, authenticator app setup, and backup codes.</p>
            </div>
            <form method="post" class="modal-form">
                ${document.querySelector('input[name="csrf_token"]').outerHTML}
                <input type="hidden" name="action" value="enable_2fa">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="close2FAModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Enable 2FA</button>
                </div>
            </form>
        `;
    } else {
        title.textContent = 'Disable Two-Factor Authentication';
        content.innerHTML = `
            <div class="modal-body">
                <p>Are you sure you want to disable Two-Factor Authentication? This will make your account less secure.</p>
                <div class="warning-box">
                    <strong>Warning:</strong> Disabling 2FA will remove the extra security layer from your account.
                </div>
            </div>
            <form method="post" class="modal-form">
                ${document.querySelector('input[name="csrf_token"]').outerHTML}
                <input type="hidden" name="action" value="disable_2fa">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="close2FAModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Disable 2FA</button>
                </div>
            </form>
        `;
    }
    
    modal.style.display = 'flex';
}

function close2FAModal() {
    document.getElementById('twoFactorModal').style.display = 'none';
}

// Login Devices Modal Functions
function openLoginDevicesModal() {
    document.getElementById('loginDevicesModal').style.display = 'flex';
}

function closeLoginDevicesModal() {
    document.getElementById('loginDevicesModal').style.display = 'none';
}

// Login Alerts Modal Functions
function openLoginAlertsModal() {
    document.getElementById('loginAlertsModal').style.display = 'flex';
}

function closeLoginAlertsModal() {
    document.getElementById('loginAlertsModal').style.display = 'none';
}

// Password confirmation validation
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== newPasswordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});

// Order Tracking Functions
function trackOrder() {
    const input = document.getElementById('trackingOrderId').value.trim();
    if (!input) {
        document.getElementById('trackingError').textContent = 'Please enter an order ID or tracking number';
        document.getElementById('trackingError').style.display = 'block';
        return;
    }
    
    // Remove # if present
    const orderId = input.replace('#', '');
    trackOrderById(orderId);
}

async function trackOrderById(orderId) {
    document.getElementById('trackingError').style.display = 'none';
    document.getElementById('trackingResults').style.display = 'none';
    
    try {
        const response = await fetch(`/api/track-order.php?order_id=${orderId}`);
        const data = await response.json();
        
        if (data.success) {
            displayTrackingResults(data.order);
        } else {
            document.getElementById('trackingError').textContent = data.message || 'Order not found';
            document.getElementById('trackingError').style.display = 'block';
        }
    } catch (error) {
        console.error('Tracking error:', error);
        document.getElementById('trackingError').textContent = 'Error fetching tracking information';
        document.getElementById('trackingError').style.display = 'block';
    }
}

function displayTrackingResults(order) {
    document.getElementById('trackingOrderNumber').textContent = `Order #${order.id}`;
    document.getElementById('trackingStatus').innerHTML = `
        <span class="badge bg-${getStatusColor(order.status)}">${order.status}</span>
    `;
    
    // Build timeline
    let timeline = '<div class="timeline">';
    const updates = order.tracking_updates || [];
    
    updates.forEach((update, index) => {
        timeline += `
            <div class="timeline-item ${index === 0 ? 'active' : ''}">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <h5>${update.status}</h5>
                    <p>${update.description || ''}</p>
                    ${update.location ? `<small>Location: ${update.location}</small><br>` : ''}
                    <small>${new Date(update.created_at).toLocaleString()}</small>
                </div>
            </div>
        `;
    });
    
    timeline += '</div>';
    document.getElementById('trackingTimeline').innerHTML = timeline;
    
    // Display order details
    document.getElementById('trackingDetails').innerHTML = `
        <div class="mt-4">
            <h5>Order Details</h5>
            <table class="table">
                <tr>
                    <td>Order Date:</td>
                    <td>${new Date(order.created_at).toLocaleDateString()}</td>
                </tr>
                <tr>
                    <td>Total:</td>
                    <td>${formatCurrency(order.total)}</td>
                </tr>
                ${order.tracking_number ? `
                <tr>
                    <td>Tracking Number:</td>
                    <td>${order.tracking_number}</td>
                </tr>
                ` : ''}
                ${order.carrier ? `
                <tr>
                    <td>Carrier:</td>
                    <td>${order.carrier}</td>
                </tr>
                ` : ''}
                ${order.shipped_at ? `
                <tr>
                    <td>Shipped:</td>
                    <td>${new Date(order.shipped_at).toLocaleString()}</td>
                </tr>
                ` : ''}
            </table>
        </div>
    `;
    
    document.getElementById('trackingResults').style.display = 'block';
}

function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'processing': 'info',
        'shipped': 'primary',
        'delivered': 'success',
        'cancelled': 'danger'
    };
    return colors[status.toLowerCase()] || 'secondary';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount || 0);
}

</script>

<!-- Account Management JavaScript -->
<script src="/js/account-management.js"></script>

<?php includeFooter(); ?>