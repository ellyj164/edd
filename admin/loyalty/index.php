<?php
/**
 * Loyalty & Rewards Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Points earning and redemption rules
 * - Customer tier management
 * - Points expiration and rollover
 * - Rewards catalog
 * - Loyalty program analytics and reporting
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

// Admin Bypass Mode - Skip all authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    // Set up admin session automatically in bypass mode
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    // Normal authentication check - redirect to login if not authenticated as admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Loyalty & Rewards';
$action = $_GET['action'] ?? 'index';
$tab = $_GET['tab'] ?? 'overview';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create_tier':
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $min_points = intval($_POST['min_points']);
                $point_multiplier = floatval($_POST['point_multiplier']);
                $benefits = sanitizeInput($_POST['benefits'] ?? '');
                
                $stmt = $pdo->prepare("
                    INSERT INTO loyalty_tiers (name, description, min_points, point_multiplier, benefits, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $description, $min_points, $point_multiplier, $benefits]);
                
                $success = "Loyalty tier created successfully!";
                break;
                
            case 'update_tier':
                $id = intval($_POST['tier_id']);
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $min_points = intval($_POST['min_points']);
                $point_multiplier = floatval($_POST['point_multiplier']);
                $benefits = sanitizeInput($_POST['benefits'] ?? '');
                $status = $_POST['status'];
                
                $stmt = $pdo->prepare("
                    UPDATE loyalty_tiers 
                    SET name = ?, description = ?, min_points = ?, point_multiplier = ?, benefits = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $min_points, $point_multiplier, $benefits, $status, $id]);
                
                $success = "Loyalty tier updated successfully!";
                break;
                
            case 'adjust_points':
                $user_id = intval($_POST['user_id']);
                $points = intval($_POST['points']);
                $reason = sanitizeInput($_POST['reason']);
                $type = $_POST['type']; // 'earned' or 'redeemed' or 'adjusted'
                
                // Get or create loyalty account
                $stmt = $pdo->prepare("
                    INSERT INTO loyalty_accounts (user_id, current_points, lifetime_points, created_at) 
                    VALUES (?, 0, 0, NOW())
                    ON DUPLICATE KEY UPDATE user_id = user_id
                ");
                $stmt->execute([$user_id]);
                
                // Add transaction
                $stmt = $pdo->prepare("
                    INSERT INTO loyalty_ledger (user_id, transaction_type, points, description, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$user_id, $type, $points, $reason, $_SESSION['user_id']]);
                
                // Update account balance
                if ($type === 'redeemed' || ($type === 'adjusted' && $points < 0)) {
                    $stmt = $pdo->prepare("
                        UPDATE loyalty_accounts 
                        SET current_points = current_points + ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$points, $user_id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE loyalty_accounts 
                        SET current_points = current_points + ?, lifetime_points = lifetime_points + ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$points, max(0, $points), $user_id]);
                }
                
                $success = "Points adjustment applied successfully!";
                break;
                
            case 'create_reward':
                $name = sanitizeInput($_POST['name']);
                $description = sanitizeInput($_POST['description'] ?? '');
                $points_required = intval($_POST['points_required']);
                $reward_type = $_POST['reward_type'];
                $reward_value = sanitizeInput($_POST['reward_value'] ?? '');
                $quantity_available = intval($_POST['quantity_available'] ?? 0);
                
                $stmt = $pdo->prepare("
                    INSERT INTO loyalty_rewards (name, description, points_required, reward_type, reward_value, quantity_available, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $description, $points_required, $reward_type, $reward_value, $quantity_available]);
                
                $success = "Reward created successfully!";
                break;
                
            case 'update_settings':
                $settings = $_POST['settings'] ?? [];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO loyalty_settings (setting_key, setting_value, updated_by, updated_at) 
                        VALUES (?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)
                    ");
                    $stmt->execute([$key, $value, $_SESSION['user_id']]);
                }
                
                $success = "Loyalty settings updated successfully!";
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get data based on tab
$tiers = [];
$rewards = [];
$recent_transactions = [];
$top_customers = [];
$settings = [];

try {
    // Get loyalty tiers
    $stmt = $pdo->query("
        SELECT lt.*, 
               COUNT(la.user_id) as customer_count,
               AVG(la.current_points) as avg_points
        FROM loyalty_tiers lt
        LEFT JOIN loyalty_accounts la ON la.current_points >= lt.min_points 
            AND la.current_points < COALESCE((
                SELECT MIN(min_points) 
                FROM loyalty_tiers lt2 
                WHERE lt2.min_points > lt.min_points
            ), 999999999)
        GROUP BY lt.id
        ORDER BY lt.min_points ASC
    ");
    $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get rewards
    $stmt = $pdo->query("
        SELECT lr.*, 
               COUNT(lrd.id) as redemption_count
        FROM loyalty_rewards lr
        LEFT JOIN loyalty_redemptions lrd ON lr.id = lrd.reward_id
        GROUP BY lr.id
        ORDER BY lr.points_required ASC
    ");
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get recent transactions
    $stmt = $pdo->query("
        SELECT ll.*, u.username, u.email, la.user_id
        FROM loyalty_ledger ll
        LEFT JOIN loyalty_accounts la ON ll.account_id = la.id
        LEFT JOIN users u ON la.user_id = u.id
        ORDER BY ll.created_at DESC
        LIMIT 20
    ");
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get top customers by points
    $stmt = $pdo->query("
        SELECT la.*, u.username, u.email,
               (SELECT name FROM loyalty_tiers WHERE min_points <= la.current_points ORDER BY min_points DESC LIMIT 1) as tier_name
        FROM loyalty_accounts la
        LEFT JOIN users u ON la.user_id = u.id
        ORDER BY la.current_points DESC
        LIMIT 10
    ");
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM loyalty_settings");
    $settings_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($settings_rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Default settings
$default_settings = [
    'points_per_dollar' => '1',
    'welcome_bonus' => '100',
    'referral_bonus' => '500',
    'birthday_bonus' => '250',
    'review_bonus' => '50',
    'points_expiry_months' => '12',
    'min_redemption_points' => '100'
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// Calculate statistics
$stats = [
    'total_members' => 0,
    'active_members' => 0,
    'total_points_issued' => 0,
    'total_points_redeemed' => 0,
    'avg_points_per_member' => 0
];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT user_id) as total_members,
            COUNT(DISTINCT CASE WHEN current_points > 0 THEN user_id END) as active_members,
            SUM(lifetime_points) as total_points_issued,
            AVG(current_points) as avg_points_per_member
        FROM loyalty_accounts
    ");
    $stats_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats_result) {
        $stats = array_merge($stats, $stats_result);
    }
    
    $stmt = $pdo->query("
        SELECT SUM(ABS(points)) as total_points_redeemed
        FROM loyalty_ledger 
        WHERE transaction_type = 'redeemed'
    ");
    $redeemed_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($redeemed_result['total_points_redeemed']) {
        $stats['total_points_redeemed'] = $redeemed_result['total_points_redeemed'];
    }
} catch (Exception $e) {
    // Use default stats
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
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
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success { border-left-color: var(--admin-success); }
        .stats-card.warning { border-left-color: var(--admin-warning); }
        .stats-card.danger { border-left-color: var(--admin-danger); }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .tier-card, .reward-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .tier-card:hover, .reward-card:hover {
            transform: translateY(-2px);
        }
        
        .tier-badge {
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        
        .points-display {
            background: var(--admin-light);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .points-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--admin-accent);
        }
        
        .transaction-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--admin-accent);
        }
        
        .transaction-item.earned {
            border-left-color: var(--admin-success);
        }
        
        .transaction-item.redeemed {
            border-left-color: var(--admin-danger);
        }
        
        .customer-rank {
            background: var(--admin-accent);
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
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
                        <i class="fas fa-gift me-2"></i>
                        Loyalty & Rewards
                    </h1>
                    <small class="text-white-50">Manage customer loyalty program and rewards</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Admin
                    </a>
                    <span class="text-white-50">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'overview' ? 'active' : ''; ?>" href="?tab=overview">
                    <i class="fas fa-chart-pie me-1"></i>
                    Overview
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'tiers' ? 'active' : ''; ?>" href="?tab=tiers">
                    <i class="fas fa-layer-group me-1"></i>
                    Tiers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'rewards' ? 'active' : ''; ?>" href="?tab=rewards">
                    <i class="fas fa-star me-1"></i>
                    Rewards
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'customers' ? 'active' : ''; ?>" href="?tab=customers">
                    <i class="fas fa-users me-1"></i>
                    Customers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'settings' ? 'active' : ''; ?>" href="?tab=settings">
                    <i class="fas fa-cog me-1"></i>
                    Settings
                </a>
            </li>
        </ul>

        <?php if ($tab === 'overview'): ?>
        <!-- Overview Tab -->
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card success">
                    <div class="stats-value text-success">
                        <?php echo number_format((int)($stats['total_members'] ?? 0)); ?>
                    </div>
                    <div class="text-muted">Total Members</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-primary">
                        <?php echo number_format((int)($stats['active_members'] ?? 0)); ?>
                    </div>
                    <div class="text-muted">Active Members</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card warning">
                    <div class="stats-value text-warning">
                        <?php echo number_format((int)($stats['total_points_issued'] ?? 0)); ?>
                    </div>
                    <div class="text-muted">Points Issued</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card danger">
                    <div class="stats-value text-danger">
                        <?php echo number_format((int)($stats['total_points_redeemed'] ?? 0)); ?>
                    </div>
                    <div class="text-muted">Points Redeemed</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Transactions -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_transactions)): ?>
                        <p class="text-muted">No transactions yet.</p>
                        <?php else: ?>
                        <div class="overflow-auto" style="max-height: 400px;">
                            <?php foreach ($recent_transactions as $transaction): ?>
                            <div class="transaction-item <?php echo $transaction['transaction_type']; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo htmlspecialchars($transaction['username']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($transaction['email']); ?></small>
                                        <br><small><?php echo htmlspecialchars($transaction['description']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-<?php echo $transaction['points'] >= 0 ? 'success' : 'danger'; ?> fw-bold">
                                            <?php echo $transaction['points'] >= 0 ? '+' : ''; ?><?php echo number_format($transaction['points']); ?>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($transaction['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Top Customers -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Customers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_customers)): ?>
                        <p class="text-muted">No loyalty members yet.</p>
                        <?php else: ?>
                        <?php foreach ($top_customers as $index => $customer): ?>
                        <div class="d-flex align-items-center mb-3">
                            <div class="customer-rank me-3">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="flex-grow-1">
                                <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                <?php if ($customer['tier_name']): ?>
                                <br><span class="badge bg-primary"><?php echo htmlspecialchars($customer['tier_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <div class="points-value">
                                    <?php echo number_format($customer['current_points']); ?>
                                </div>
                                <small class="text-muted">points</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'tiers'): ?>
        <!-- Tiers Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-layer-group me-2"></i>Loyalty Tiers</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTierModal">
                        <i class="fas fa-plus me-1"></i>
                        Create Tier
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($tiers)): ?>
        <div class="text-center py-5">
            <i class="fas fa-layer-group fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No Loyalty Tiers Configured</h4>
            <p class="text-muted">Create tiers to reward your most loyal customers</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTierModal">
                <i class="fas fa-plus me-1"></i>
                Create Your First Tier
            </button>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($tiers as $tier): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="tier-card card">
                    <div class="card-header text-center">
                        <div class="tier-badge"><?php echo htmlspecialchars($tier['name']); ?></div>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="points-display">
                                <div class="points-value"><?php echo number_format($tier['min_points']); ?>+</div>
                                <small class="text-muted">points required</small>
                            </div>
                        </div>
                        
                        <p class="text-muted"><?php echo htmlspecialchars($tier['description']); ?></p>
                        
                        <div class="mb-3">
                            <small><strong>Point Multiplier:</strong></small>
                            <span class="badge bg-success"><?php echo $tier['point_multiplier']; ?>x</span>
                        </div>
                        
                        <?php if ($tier['benefits']): ?>
                        <div class="mb-3">
                            <small><strong>Benefits:</strong></small>
                            <p class="small"><?php echo nl2br(htmlspecialchars($tier['benefits'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="h6 mb-0"><?php echo number_format($tier['customer_count']); ?></div>
                                <small class="text-muted">Members</small>
                            </div>
                            <div class="col-6">
                                <div class="h6 mb-0"><?php echo number_format($tier['avg_points']); ?></div>
                                <small class="text-muted">Avg Points</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-<?php echo $tier['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($tier['status']); ?>
                            </span>
                            <button class="btn btn-sm btn-outline-primary" onclick="editTier(<?php echo htmlspecialchars(json_encode($tier)); ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Create/Edit Tier Modal -->
        <div class="modal fade" id="createTierModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" id="tierForm">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tierModalTitle">Create Loyalty Tier</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" id="tierAction" value="create_tier">
                            <input type="hidden" name="tier_id" id="tierId">
                            
                            <div class="mb-3">
                                <label for="tierName" class="form-label">Tier Name</label>
                                <input type="text" class="form-control" id="tierName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="tierDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="tierDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="minPoints" class="form-label">Minimum Points Required</label>
                                <input type="number" class="form-control" id="minPoints" name="min_points" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label for="pointMultiplier" class="form-label">Point Earning Multiplier</label>
                                <input type="number" class="form-control" id="pointMultiplier" name="point_multiplier" step="0.1" min="1" value="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="tierBenefits" class="form-label">Benefits</label>
                                <textarea class="form-control" id="tierBenefits" name="benefits" rows="3" 
                                          placeholder="Free shipping, early access to sales, etc."></textarea>
                            </div>
                            <div class="mb-3" id="statusField" style="display: none;">
                                <label for="tierStatus" class="form-label">Status</label>
                                <select class="form-select" id="tierStatus" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="tierSubmitBtn">Create Tier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'rewards'): ?>
        <!-- Rewards Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-star me-2"></i>Rewards Catalog</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRewardModal">
                        <i class="fas fa-plus me-1"></i>
                        Create Reward
                    </button>
                </div>
            </div>
        </div>

        <?php if (empty($rewards)): ?>
        <div class="text-center py-5">
            <i class="fas fa-star fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No Rewards Available</h4>
            <p class="text-muted">Create rewards that customers can redeem with their points</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRewardModal">
                <i class="fas fa-plus me-1"></i>
                Create Your First Reward
            </button>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($rewards as $reward): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="reward-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo htmlspecialchars($reward['name']); ?></h6>
                        <span class="badge bg-<?php echo $reward['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($reward['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="points-display">
                                <div class="points-value"><?php echo number_format($reward['points_required']); ?></div>
                                <small class="text-muted">points</small>
                            </div>
                        </div>
                        
                        <p class="text-muted"><?php echo htmlspecialchars($reward['description']); ?></p>
                        
                        <div class="mb-2">
                            <small><strong>Type:</strong></small>
                            <span class="badge bg-info"><?php echo ucfirst($reward['reward_type']); ?></span>
                        </div>
                        
                        <?php if ($reward['reward_value']): ?>
                        <div class="mb-2">
                            <small><strong>Value:</strong></small>
                            <?php echo htmlspecialchars($reward['reward_value']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($reward['quantity_available']): ?>
                        <div class="mb-3">
                            <small><strong>Available:</strong></small>
                            <?php echo number_format($reward['quantity_available']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Redeemed <?php echo number_format($reward['redemption_count']); ?> times
                            </small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Create Reward Modal -->
        <div class="modal fade" id="createRewardModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Create Reward</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="create_reward">
                            
                            <div class="mb-3">
                                <label for="rewardName" class="form-label">Reward Name</label>
                                <input type="text" class="form-control" id="rewardName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="rewardDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="rewardDescription" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="pointsRequired" class="form-label">Points Required</label>
                                <input type="number" class="form-control" id="pointsRequired" name="points_required" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="rewardType" class="form-label">Reward Type</label>
                                <select class="form-select" id="rewardType" name="reward_type" required>
                                    <option value="">Select type...</option>
                                    <option value="discount">Discount Code</option>
                                    <option value="free_shipping">Free Shipping</option>
                                    <option value="product">Free Product</option>
                                    <option value="credit">Store Credit</option>
                                    <option value="gift_card">Gift Card</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="rewardValue" class="form-label">Reward Value</label>
                                <input type="text" class="form-control" id="rewardValue" name="reward_value" 
                                       placeholder="e.g., $10, 20%, Product SKU">
                            </div>
                            <div class="mb-3">
                                <label for="quantityAvailable" class="form-label">Quantity Available (0 = unlimited)</label>
                                <input type="number" class="form-control" id="quantityAvailable" name="quantity_available" min="0" value="0">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Reward</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'customers'): ?>
        <!-- Customers Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-users me-2"></i>Loyalty Customers</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustPointsModal">
                        <i class="fas fa-edit me-1"></i>
                        Adjust Points
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Customer</th>
                                <th>Current Points</th>
                                <th>Lifetime Points</th>
                                <th>Tier</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_customers)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No loyalty customers yet
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($top_customers as $index => $customer): ?>
                            <tr>
                                <td>
                                    <div class="customer-rank">
                                        <?php echo $index + 1; ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($customer['username']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                                </td>
                                <td>
                                    <span class="points-value">
                                        <?php echo number_format($customer['current_points']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo number_format($customer['lifetime_points']); ?>
                                </td>
                                <td>
                                    <?php if ($customer['tier_name']): ?>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($customer['tier_name']); ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">No tier</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($customer['created_at'])); ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="adjustPoints(<?php echo $customer['user_id']; ?>, '<?php echo htmlspecialchars($customer['username']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Adjust Points Modal -->
        <div class="modal fade" id="adjustPointsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Adjust Customer Points</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="adjust_points">
                            
                            <div class="mb-3">
                                <label for="adjustUserId" class="form-label">Customer</label>
                                <select class="form-select" id="adjustUserId" name="user_id" required>
                                    <option value="">Select customer...</option>
                                    <?php foreach ($top_customers as $customer): ?>
                                    <option value="<?php echo $customer['user_id']; ?>">
                                        <?php echo htmlspecialchars($customer['username'] . ' (' . $customer['email'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="adjustType" class="form-label">Transaction Type</label>
                                <select class="form-select" id="adjustType" name="type" required>
                                    <option value="earned">Points Earned</option>
                                    <option value="redeemed">Points Redeemed</option>
                                    <option value="adjusted">Manual Adjustment</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="adjustPoints" class="form-label">Points</label>
                                <input type="number" class="form-control" id="adjustPoints" name="points" required>
                                <small class="form-text text-muted">Use negative numbers to subtract points</small>
                            </div>
                            <div class="mb-3">
                                <label for="adjustReason" class="form-label">Reason</label>
                                <input type="text" class="form-control" id="adjustReason" name="reason" required 
                                       placeholder="e.g., Customer service bonus, Return processing">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Apply Adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php elseif ($tab === 'settings'): ?>
        <!-- Settings Tab -->
        <div class="row mb-4">
            <div class="col-12">
                <h4><i class="fas fa-cog me-2"></i>Loyalty Program Settings</h4>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Point Earning Rules</h5>
                            <div class="mb-3">
                                <label for="pointsPerDollar" class="form-label">Points per Dollar Spent</label>
                                <input type="number" class="form-control" id="pointsPerDollar" 
                                       name="settings[points_per_dollar]" value="<?php echo htmlspecialchars($settings['points_per_dollar']); ?>" min="0" step="0.1">
                            </div>
                            <div class="mb-3">
                                <label for="welcomeBonus" class="form-label">Welcome Bonus Points</label>
                                <input type="number" class="form-control" id="welcomeBonus" 
                                       name="settings[welcome_bonus]" value="<?php echo htmlspecialchars($settings['welcome_bonus']); ?>" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="referralBonus" class="form-label">Referral Bonus Points</label>
                                <input type="number" class="form-control" id="referralBonus" 
                                       name="settings[referral_bonus]" value="<?php echo htmlspecialchars($settings['referral_bonus']); ?>" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="birthdayBonus" class="form-label">Birthday Bonus Points</label>
                                <input type="number" class="form-control" id="birthdayBonus" 
                                       name="settings[birthday_bonus]" value="<?php echo htmlspecialchars($settings['birthday_bonus']); ?>" min="0">
                            </div>
                            <div class="mb-3">
                                <label for="reviewBonus" class="form-label">Review Bonus Points</label>
                                <input type="number" class="form-control" id="reviewBonus" 
                                       name="settings[review_bonus]" value="<?php echo htmlspecialchars($settings['review_bonus']); ?>" min="0">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5>Redemption Rules</h5>
                            <div class="mb-3">
                                <label for="minRedemption" class="form-label">Minimum Points for Redemption</label>
                                <input type="number" class="form-control" id="minRedemption" 
                                       name="settings[min_redemption_points]" value="<?php echo htmlspecialchars($settings['min_redemption_points']); ?>" min="1">
                            </div>
                            <div class="mb-3">
                                <label for="pointsExpiry" class="form-label">Points Expiry (months)</label>
                                <input type="number" class="form-control" id="pointsExpiry" 
                                       name="settings[points_expiry_months]" value="<?php echo htmlspecialchars($settings['points_expiry_months']); ?>" min="1">
                                <small class="form-text text-muted">Set to 0 for no expiry</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editTier(tier) {
            document.getElementById('tierAction').value = 'update_tier';
            document.getElementById('tierId').value = tier.id;
            document.getElementById('tierName').value = tier.name;
            document.getElementById('tierDescription').value = tier.description;
            document.getElementById('minPoints').value = tier.min_points;
            document.getElementById('pointMultiplier').value = tier.point_multiplier;
            document.getElementById('tierBenefits').value = tier.benefits;
            document.getElementById('tierStatus').value = tier.status;
            
            document.getElementById('tierModalTitle').textContent = 'Edit Loyalty Tier';
            document.getElementById('tierSubmitBtn').textContent = 'Update Tier';
            document.getElementById('statusField').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('createTierModal')).show();
        }
        
        function adjustPoints(userId, username) {
            document.getElementById('adjustUserId').value = userId;
            new bootstrap.Modal(document.getElementById('adjustPointsModal')).show();
        }
        
        // Reset modal when closed
        document.getElementById('createTierModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('tierForm').reset();
            document.getElementById('tierAction').value = 'create_tier';
            document.getElementById('tierModalTitle').textContent = 'Create Loyalty Tier';
            document.getElementById('tierSubmitBtn').textContent = 'Create Tier';
            document.getElementById('statusField').style.display = 'none';
        });
    </script>
</body>
</html>