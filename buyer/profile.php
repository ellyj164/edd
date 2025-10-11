<?php
/**
 * Buyer Profile & Account Management
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';
Session::requireLogin();

$db = db();
$userId = Session::getUserId();

// Get user and buyer information
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $db->prepare($userQuery);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Get or create buyer record
$buyerQuery = "SELECT * FROM buyers WHERE user_id = ?";
$buyerStmt = $db->prepare($buyerQuery);
$buyerStmt->execute([$userId]);
$buyer = $buyerStmt->fetch();

if (!$buyer) {
    $createBuyerQuery = "INSERT INTO buyers (user_id) VALUES (?)";
    $createBuyerStmt = $db->prepare($createBuyerQuery);
    $createBuyerStmt->execute([$userId]);
    $buyerId = $db->lastInsertId();
    
    $buyerStmt->execute([$userId]);
    $buyer = $buyerStmt->fetch();
} else {
    $buyerId = $buyer['id'];
}

// Handle form submissions
if ($_POST && Session::validateCSRF($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if ($firstName && $lastName && $email) {
            // Update user table
            $updateUserQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
            $updateUserStmt = $db->prepare($updateUserQuery);
            $updateUserStmt->execute([$firstName, $lastName, $email, $phone, $userId]);
            
            $success = "Profile updated successfully!";
            
            // Refresh user data
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
        } else {
            $error = "Please fill in all required fields.";
        }
    }
}

$page_title = 'Profile & Account';
includeHeader($page_title);
?>

<div class="buyer-dashboard">
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Profile & Account</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-8">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Personal Information</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="first_name" class="form-label">First Name *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="last_name" class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <input type="hidden" name="action" value="update_profile">
                                    <input type="hidden" name="csrf_token" value="<?php echo Session::generateCSRF(); ?>">
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Change Password</h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3">For security, we'll email you a password reset link.</p>
                                <a href="/forgot-password.php?email=<?php echo urlencode($user['email']); ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-key"></i> Send Password Reset Link
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Account Status & Settings -->
                    <div class="col-lg-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Account Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <strong>Status:</strong><br>
                                    <span class="badge badge-success">Active</span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Member Since:</strong><br>
                                    <span class="text-muted"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Loyalty Tier:</strong><br>
                                    <span class="badge badge-<?php echo getBadgeColor($buyer['tier'] ?? 'bronze'); ?>">
                                        <?php echo ucfirst($buyer['tier'] ?? 'Bronze'); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Email Verified:</strong><br>
                                    <?php if ($user['email_verified_at']): ?>
                                        <span class="text-success"><i class="fas fa-check-circle"></i> Verified</span>
                                    <?php else: ?>
                                        <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Pending</span>
                                        <br><a href="/resend-verification.php" class="btn btn-sm btn-outline-primary mt-2">Resend Email</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Account Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="/buyer/addresses.php" class="btn btn-outline-primary">
                                        <i class="fas fa-map-marker-alt"></i> Manage Addresses
                                    </a>
                                    <a href="/buyer/wallet.php" class="btn btn-outline-primary">
                                        <i class="fas fa-credit-card"></i> Payment Methods
                                    </a>
                                    <a href="/buyer/privacy.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-shield-alt"></i> Privacy Settings
                                    </a>
                                    <a href="/buyer/notifications.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-bell"></i> Notifications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.buyer-dashboard {
    background-color: #f8f9fc;
    min-height: 100vh;
}

.main-content {
    padding: 0 1.5rem;
}

.badge-bronze { background-color: #cd7f32; color: white; }
.badge-silver { background-color: #c0c0c0; color: white; }
.badge-gold { background-color: #ffd700; color: black; }
.badge-platinum { background-color: #e5e4e2; color: black; }
.badge-diamond { background-color: #b9f2ff; color: black; }
</style>

<?php
function getBadgeColor($tier) {
    switch (strtolower($tier)) {
        case 'bronze': return 'secondary';
        case 'silver': return 'light';
        case 'gold': return 'warning';
        case 'platinum': return 'info';
        case 'diamond': return 'primary';
        default: return 'secondary';
    }
}

includeFooter();
?>