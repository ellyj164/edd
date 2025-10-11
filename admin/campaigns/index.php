<?php
/**
 * Professional Marketing Campaigns Module
 *
 * @package    Admin/Campaigns
 * @version    2.2.0
 */

// Core application requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/init.php';

// --- Page Setup & Security ---
$page_title = 'Marketing Campaigns';
$error_message = null;

// Initialize default stats to prevent errors
$stats = ['total_campaigns' => 0, 'messages_sent' => 0, 'opens' => 0, 'clicks' => 0, 'open_rate' => 0, 'click_rate' => 0];
$campaigns = [];

try {
    requireAdminAuth();
    checkPermission('campaigns.view');
    $pdo = db();

    // --- DATA FETCHING (GET REQUESTS) ---
    $stats_query = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM marketing_campaigns) as total_campaigns,
            (SELECT COUNT(*) FROM campaign_recipients) as messages_sent,
            (SELECT COUNT(DISTINCT id) FROM campaign_recipients WHERE opened_at IS NOT NULL) as opens,
            (SELECT COUNT(DISTINCT id) FROM campaign_recipients WHERE clicked_at IS NOT NULL) as clicks
    ");
    if ($stats_query && $result = $stats_query->fetch(PDO::FETCH_ASSOC)) {
        $stats['total_campaigns'] = (int)$result['total_campaigns'];
        $stats['messages_sent'] = (int)$result['messages_sent'];
        $stats['opens'] = (int)$result['opens'];
        $stats['clicks'] = (int)$result['clicks'];
        $stats['open_rate'] = ($stats['messages_sent'] > 0) ? ($stats['opens'] / $stats['messages_sent']) * 100 : 0;
        $stats['click_rate'] = ($stats['opens'] > 0) ? ($stats['clicks'] / $stats['opens']) * 100 : 0;
    }

    // This query is now safe to run after the ALTER TABLE commands
    $campaigns_query = $pdo->query("
        SELECT mc.id, mc.name, mc.type, mc.status, mc.created_at,
               (SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = mc.id) as recipient_count,
               (SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = mc.id AND opened_at IS NOT NULL) as open_count,
               (SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = mc.id AND clicked_at IS NOT NULL) as click_count
        FROM marketing_campaigns mc ORDER BY mc.created_at DESC
    ");
    if ($campaigns_query) {
        $campaigns = $campaigns_query->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $error_message = "Error loading campaign data: " . $e->getMessage();
}

// --- RENDER PAGE ---
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
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #212529; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); }
        .sidebar .nav-link:hover { color: #fff; }
        .sidebar .nav-link.active { color: #fff; font-weight: bold; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Admin Sidebar -->
    <div class="d-flex flex-column flex-shrink-0 p-3 sidebar" style="width: 250px; min-height: 100vh;">
        <a href="/admin/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-4">Admin Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="/admin/" class="nav-link"><i class="fas fa-tachometer-alt fa-fw me-2"></i>Dashboard</a></li>
            <li><a href="/admin/campaigns/" class="nav-link active"><i class="fas fa-bullhorn fa-fw me-2"></i>Campaigns</a></li>
            <li><a href="/admin/coupons/" class="nav-link"><i class="fas fa-tags fa-fw me-2"></i>Coupons</a></li>
            <li><a href="/admin/analytics/" class="nav-link"><i class="fas fa-chart-line fa-fw me-2"></i>Analytics</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1 p-4">
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS): ?>
            <div class="alert alert-warning alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2"><i class="fas fa-bullhorn"></i> Marketing Campaigns</h1>
            <div>
                <a href="/admin/campaigns/new.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Campaign</a>
                <button class="btn btn-secondary"><i class="fas fa-download me-2"></i>Export</button>
            </div>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2"><?php echo (int)$stats['total_campaigns']; ?></h5><p class="card-text text-muted">Total Campaigns</p></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2"><?php echo (int)$stats['messages_sent']; ?></h5><p class="card-text text-muted">Messages Sent</p></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-success"><?php echo number_format($stats['open_rate'], 2); ?>%</h5><p class="card-text text-muted">Open Rate</p></div></div></div>
            <div class="col-lg-3 col-md-6"><div class="card shadow-sm text-center"><div class="card-body"><h5 class="card-title h2 text-warning"><?php echo number_format($stats['click_rate'], 2); ?>%</h5><p class="card-text text-muted">Click Rate</p></div></div></div>
        </div>

        <!-- Campaign List -->
        <div class="card shadow-sm">
            <div class="card-header"><h5>Campaign List</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Campaign</th><th>Type</th><th>Status</th><th>Recipients</th><th>Performance</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($campaigns)): ?>
                                <tr><td colspan="7" class="text-center text-muted p-5"><i class="fas fa-folder-open fa-2x mb-2"></i><br>No campaigns found. Create one to get started.</td></tr>
                            <?php else: foreach ($campaigns as $campaign): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($campaign['name']); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst($campaign['type']); ?></span></td>
                                    <td>
                                        <?php $status_map = ['draft' => 'secondary', 'sending' => 'info', 'sent' => 'success', 'archived' => 'dark']; ?>
                                        <span class="badge bg-<?php echo $status_map[$campaign['status']] ?? 'primary'; ?>"><?php echo ucfirst($campaign['status']); ?></span>
                                    </td>
                                    <td><?php echo number_format($campaign['recipient_count']); ?></td>
                                    <td>
                                        <?php $open_rate = $campaign['recipient_count'] > 0 ? ($campaign['open_count'] / $campaign['recipient_count']) * 100 : 0; ?>
                                        <small>Opens: <?php echo number_format($campaign['open_count']); ?> (<?php echo number_format($open_rate, 1); ?>%)</small><br>
                                        <small>Clicks: <?php echo number_format($campaign['click_count']); ?></small>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($campaign['created_at'])); ?></td>
                                    <td class="text-end">
                                        <a href="view_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>