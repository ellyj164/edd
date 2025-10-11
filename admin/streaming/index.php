<?php
/**
 * Live Streaming Management Admin Module
 * E-Commerce Platform Admin Panel
 */

require_once __DIR__ . '/../../includes/init.php';

// Database availability check with graceful fallback
$database_available = false;
$pdo = null;
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

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
    // Normal authentication check
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Live Streaming Management';

// Live streaming data from database
$streams = [];
$stats = [
    'total_streams' => 0,
    'live_streams' => 0,
    'scheduled_streams' => 0,
    'completed_streams' => 0,
    'cancelled_streams' => 0,
    'total_viewers' => 0,
    'total_revenue' => 0,
    'avg_revenue' => 0
];
$vendors = [];

if ($database_available) {
    try {
        // Get all streams
        $streamStmt = $pdo->query("
            SELECT ls.*, v.business_name as vendor_name, v.id as vendor_id,
                   COUNT(DISTINCT sv.id) as current_viewers
            FROM live_streams ls
            JOIN vendors v ON ls.vendor_id = v.id
            LEFT JOIN stream_viewers sv ON ls.id = sv.stream_id AND sv.is_active = 1
            GROUP BY ls.id
            ORDER BY 
                CASE ls.status 
                    WHEN 'live' THEN 1 
                    WHEN 'scheduled' THEN 2 
                    WHEN 'ended' THEN 3 
                    WHEN 'cancelled' THEN 4 
                END,
                ls.scheduled_at DESC
        ");
        $streams = $streamStmt->fetchAll();
        
        // Get revenue for each stream
        foreach ($streams as &$stream) {
            $revenueStmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount), 0) as revenue
                FROM stream_orders
                WHERE stream_id = ?
            ");
            $revenueStmt->execute([$stream['id']]);
            $revenueResult = $revenueStmt->fetch();
            $stream['revenue'] = $revenueResult['revenue'];
        }
        
        // Get statistics
        $stats['total_streams'] = count($streams);
        $stats['live_streams'] = count(array_filter($streams, fn($s) => $s['status'] === 'live'));
        $stats['scheduled_streams'] = count(array_filter($streams, fn($s) => $s['status'] === 'scheduled'));
        $stats['completed_streams'] = count(array_filter($streams, fn($s) => $s['status'] === 'ended'));
        $stats['cancelled_streams'] = count(array_filter($streams, fn($s) => $s['status'] === 'cancelled'));
        $stats['total_viewers'] = array_sum(array_column($streams, 'current_viewers'));
        $stats['total_revenue'] = array_sum(array_column($streams, 'revenue'));
        $stats['avg_revenue'] = $stats['total_streams'] > 0 ? $stats['total_revenue'] / $stats['total_streams'] : 0;
        
        // Get vendors for filter dropdown
        $vendorsStmt = $pdo->query("SELECT id, business_name FROM vendors ORDER BY business_name");
        $vendors = $vendorsStmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Error loading stream data: " . $e->getMessage());
    }
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
            padding: 1.5rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .status-live { background-color: #dc3545; }
        .status-scheduled { background-color: #17a2b8; }
        .status-ended { background-color: #6c757d; }
        .status-cancelled { background-color: #ffc107; color: #000; }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-align: center;
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success { border-left-color: var(--admin-success); }
        .stats-card.warning { border-left-color: var(--admin-warning); }
        .stats-card.danger { border-left-color: var(--admin-danger); }
        .stats-card.info { border-left-color: #17a2b8; }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #dc3545;
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 5px;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .stream-thumbnail {
            width: 60px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
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
                        <i class="fas fa-video me-2"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <small class="text-white-50">Manage live streams, chat, and streaming analytics</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Streaming Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <i class="fas fa-video fa-2x text-primary mb-2"></i>
                    <div class="stats-value text-primary"><?php echo number_format($stats['total_streams']); ?></div>
                    <div class="stats-label">Total Streams</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card danger">
                    <i class="fas fa-broadcast-tower fa-2x text-danger mb-2"></i>
                    <div class="stats-value text-danger"><?php echo number_format($stats['live_streams']); ?></div>
                    <div class="stats-label">Live Now</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card info">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <div class="stats-value text-info"><?php echo number_format($stats['total_viewers']); ?></div>
                    <div class="stats-label">Current Viewers</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card success">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                    <div class="stats-value text-success">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="stats-label">Stream Revenue</div>
                </div>
            </div>
        </div>

        <!-- Live Streaming Control Panel -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Streaming Control Panel</h5>
                        <div>
                            <button class="btn btn-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#scheduleStreamModal">
                                <i class="fas fa-plus me-1"></i> Schedule Stream
                            </button>
                            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#streamSettingsModal">
                                <i class="fas fa-cog me-1"></i> Stream Settings
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" id="exportDataBtn">
                                <i class="fas fa-download me-1"></i> Export Data
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Controls -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Search streams...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="live">Live</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="ended">Ended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select" id="vendorFilter">
                                    <option value="">All Vendors</option>
                                    <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['business_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" id="dateFilter">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary me-2" id="applyFilters">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <button class="btn btn-outline-secondary" id="refreshBtn">
                                    <i class="fas fa-sync me-1"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <!-- Streams Table -->
                        <div class="table-responsive">
                            <table class="table table-hover" id="streamsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Preview</th>
                                        <th>Stream Details</th>
                                        <th>Vendor</th>
                                        <th>Status</th>
                                        <th>Viewers</th>
                                        <th>Revenue</th>
                                        <th>Scheduled Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($streams)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                            <div class="h5 text-muted">No streams found</div>
                                            <p class="text-muted">Start by scheduling a new stream</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($streams as $stream): ?>
                                    <tr>
                                        <td>
                                            <div class="stream-thumbnail">
                                                <?php if ($stream['status'] === 'live'): ?>
                                                <i class="fas fa-play text-danger"></i>
                                                <?php else: ?>
                                                <i class="fas fa-video text-muted"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($stream['title']); ?></strong>
                                                <?php if ($stream['status'] === 'live'): ?>
                                                <br><small class="text-danger">
                                                    <span class="live-indicator"></span>LIVE
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($stream['vendor_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-<?php echo htmlspecialchars($stream['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($stream['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo number_format($stream['current_viewers'] ?? 0); ?></strong>
                                                <?php if ($stream['max_viewers'] > 0): ?>
                                                <br><small class="text-muted">Peak: <?php echo number_format($stream['max_viewers']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>$<?php echo number_format($stream['revenue'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y H:i', strtotime($stream['scheduled_at'])); ?>
                                            <?php if ($stream['started_at']): ?>
                                            <br><small class="text-success">Started: <?php echo date('H:i', strtotime($stream['started_at'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($stream['status'] === 'live'): ?>
                                                <button type="button" class="btn btn-outline-danger stream-action" 
                                                        data-action="stop" data-stream-id="<?php echo $stream['id']; ?>" 
                                                        title="End Stream">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-warning stream-action" 
                                                        data-action="pause" data-stream-id="<?php echo $stream['id']; ?>" 
                                                        title="Pause Stream">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <?php elseif ($stream['status'] === 'scheduled'): ?>
                                                <button type="button" class="btn btn-outline-success stream-action" 
                                                        data-action="start" data-stream-id="<?php echo $stream['id']; ?>" 
                                                        title="Start Stream">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" title="Edit Schedule">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" 
                                                        data-bs-toggle="modal" data-bs-target="#moderationModal" 
                                                        data-stream-id="<?php echo $stream['id']; ?>" 
                                                        title="Chat Moderation">
                                                    <i class="fas fa-comments"></i>
                                                </button>
                                                <?php if ($stream['status'] !== 'live'): ?>
                                                <button type="button" class="btn btn-outline-danger stream-action" 
                                                        data-action="delete" data-stream-id="<?php echo $stream['id']; ?>" 
                                                        title="Delete Stream">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Stream Analytics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Stream Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border rounded p-3 mb-3">
                                    <div class="h4 text-primary mb-1"><?php echo $stats['scheduled_streams']; ?></div>
                                    <small class="text-muted">Scheduled</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 mb-3">
                                    <div class="h4 text-success mb-1"><?php echo $stats['completed_streams']; ?></div>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 mb-3">
                                    <div class="h4 text-warning mb-1"><?php echo $stats['cancelled_streams']; ?></div>
                                    <small class="text-muted">Cancelled</small>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h6>Average Revenue Per Stream</h6>
                            <div class="h3 text-success">$<?php echo number_format($stats['avg_revenue'], 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Performing Streams</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        if (!empty($streams)) {
                            $sorted_streams = $streams;
                            usort($sorted_streams, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
                            $top_streams = array_slice($sorted_streams, 0, 3);
                            
                            if (!empty($top_streams)):
                                foreach ($top_streams as $index => $stream):
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($stream['title']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($stream['vendor_name']); ?> • <?php echo number_format($stream['max_viewers']); ?> viewers</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-success">$<?php echo number_format($stream['revenue'], 2); ?></strong>
                            </div>
                        </div>
                        <?php if ($index < count($top_streams) - 1): ?>
                        <hr>
                        <?php endif; ?>
                        <?php 
                                endforeach;
                            else:
                        ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <p class="mb-0">No stream revenue data available yet</p>
                        </div>
                        <?php 
                            endif;
                        } else {
                        ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-video fa-2x mb-2"></i>
                            <p class="mb-0">No streams found</p>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#generateKeyModal">
                                    <i class="fas fa-key me-2"></i>
                                    Generate Stream Key
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#analyticsModal">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    View Analytics
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-warning w-100 mb-2" data-bs-toggle="modal" data-bs-target="#moderationModal">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Moderation Tools
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-info w-100 mb-2" data-bs-toggle="modal" data-bs-target="#streamSettingsModal">
                                    <i class="fas fa-cogs me-2"></i>
                                    RTMP Settings
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Stream Configuration:</strong> RTMP endpoint is configured and ready. 
                                    Vendors can use their assigned stream keys to broadcast live content.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    
    <!-- Schedule Stream Modal -->
    <div class="modal fade" id="scheduleStreamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Schedule New Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="scheduleStreamForm">
                        <div class="mb-3">
                            <label class="form-label">Vendor</label>
                            <select class="form-select" name="vendor_id" required>
                                <option value="">Select Vendor</option>
                                <?php foreach ($vendors as $vendor): ?>
                                <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['business_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stream Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Scheduled Date & Time</label>
                            <input type="datetime-local" class="form-control" name="scheduled_at" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveScheduleBtn">
                        <i class="fas fa-save me-1"></i> Schedule Stream
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Stream Key Modal -->
    <div class="modal fade" id="generateKeyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Generate Stream Key</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Vendor</label>
                        <select class="form-select" id="streamKeyVendor">
                            <option value="">Select Vendor</option>
                            <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['business_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="streamKeyResult" class="alert alert-success d-none">
                        <p class="mb-2"><strong>Stream Key Generated:</strong></p>
                        <div class="input-group">
                            <input type="text" class="form-control" id="generatedKey" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyKeyBtn">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="generateKeyBtn">
                        <i class="fas fa-key me-1"></i> Generate Key
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stream Settings Modal -->
    <div class="modal fade" id="streamSettingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog me-2"></i>RTMP & Stream Settings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="streamSettingsForm">
                        <h6 class="mb-3">RTMP Server Configuration</h6>
                        <div class="mb-3">
                            <label class="form-label">RTMP Server URL</label>
                            <input type="text" class="form-control" name="rtmp_server_url" 
                                   value="rtmp://localhost/live" placeholder="rtmp://your-server.com/live">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Server Key (Optional)</label>
                            <input type="text" class="form-control" name="rtmp_server_key">
                        </div>
                        
                        <hr>
                        <h6 class="mb-3">Stream Quality Settings</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Bitrate (kbps)</label>
                                <input type="number" class="form-control" name="stream_max_bitrate" 
                                       value="4000" min="500" max="10000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Resolution</label>
                                <select class="form-select" name="stream_max_resolution">
                                    <option value="1920x1080">1920x1080 (Full HD)</option>
                                    <option value="1280x720">1280x720 (HD)</option>
                                    <option value="854x480">854x480 (SD)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Max Stream Duration (seconds)</label>
                            <input type="number" class="form-control" name="stream_max_duration" 
                                   value="14400" min="600">
                            <small class="text-muted">Default: 14400 seconds (4 hours)</small>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="stream_enable_recording" 
                                   id="enableRecording" checked>
                            <label class="form-check-label" for="enableRecording">
                                Enable automatic stream recording
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveSettingsBtn">
                        <i class="fas fa-save me-1"></i> Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Moderation Modal -->
    <div class="modal fade" id="moderationModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shield-alt me-2"></i>Chat Moderation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Stream</label>
                        <select class="form-select" id="moderationStreamSelect">
                            <option value="">Select a stream</option>
                            <?php foreach ($streams as $stream): ?>
                            <?php if ($stream['status'] === 'live' || $stream['status'] === 'ended'): ?>
                            <option value="<?php echo $stream['id']; ?>">
                                <?php echo htmlspecialchars($stream['title']); ?> - <?php echo ucfirst($stream['status']); ?>
                            </option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="moderationContent">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Select a stream to view and moderate comments</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Modal -->
    <div class="modal fade" id="analyticsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-chart-bar me-2"></i>Stream Analytics</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Analytics Dashboard Coming Soon</strong>
                        <p class="mb-0 mt-2">Detailed analytics including viewer demographics, engagement metrics, 
                        revenue breakdowns, and performance trends will be available here.</p>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo number_format($stats['total_streams']); ?></h3>
                                    <p class="text-muted mb-0">Total Streams</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-success">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                                    <p class="text-muted mb-0">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-info">$<?php echo number_format($stats['avg_revenue'], 2); ?></h3>
                                    <p class="text-muted mb-0">Avg Revenue Per Stream</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Admin Streaming Dashboard JavaScript
        
        // Apply filters
        document.getElementById('applyFilters').addEventListener('click', function() {
            loadStreams();
        });
        
        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-sync fa-spin me-1"></i> Refreshing...';
            
            loadStreams().finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync me-1"></i> Refresh';
            });
            
            // Also refresh stats
            loadStats();
        });
        
        // Export data
        document.getElementById('exportDataBtn').addEventListener('click', function() {
            window.location.href = '/api/admin/streams/export.php';
        });
        
        // Stream actions (start, stop, pause, delete)
        document.querySelectorAll('.stream-action').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
                const streamId = this.dataset.streamId;
                
                let confirmMessage = '';
                switch(action) {
                    case 'start':
                        confirmMessage = 'Are you sure you want to start this stream?';
                        break;
                    case 'stop':
                        confirmMessage = 'Are you sure you want to end this stream? This cannot be undone.';
                        break;
                    case 'pause':
                        confirmMessage = 'Are you sure you want to pause this stream?';
                        break;
                    case 'delete':
                        confirmMessage = 'Are you sure you want to delete this stream? This cannot be undone.';
                        break;
                }
                
                if (!confirm(confirmMessage)) return;
                
                performStreamAction(action, streamId);
            });
        });
        
        // Schedule stream
        document.getElementById('saveScheduleBtn').addEventListener('click', async function() {
            const form = document.getElementById('scheduleStreamForm');
            const formData = new FormData(form);
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Scheduling...';
            
            const data = {
                vendor_id: formData.get('vendor_id'),
                title: formData.get('title'),
                description: formData.get('description'),
                scheduled_at: formData.get('scheduled_at')
            };
            
            try {
                const response = await fetch('/api/admin/streams/schedule.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Stream scheduled successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('scheduleStreamModal')).hide();
                    form.reset();
                    loadStreams();
                    loadStats();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to schedule stream. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> Schedule Stream';
            }
        });
        
        // Generate stream key
        document.getElementById('generateKeyBtn').addEventListener('click', async function() {
            const vendorSelect = document.getElementById('streamKeyVendor');
            const vendorId = vendorSelect.value;
            
            if (!vendorId) {
                alert('Please select a vendor');
                return;
            }
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
            
            try {
                const response = await fetch('/api/admin/streams/stream-key.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ vendor_id: parseInt(vendorId) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('generatedKey').value = data.stream_key;
                    document.getElementById('streamKeyResult').classList.remove('d-none');
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to generate stream key. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-key me-1"></i> Generate Key';
            }
        });
        
        // Copy stream key
        document.getElementById('copyKeyBtn').addEventListener('click', function() {
            const keyInput = document.getElementById('generatedKey');
            keyInput.select();
            document.execCommand('copy');
            
            const btn = this;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                btn.innerHTML = originalHTML;
            }, 2000);
        });
        
        // Save stream settings
        document.getElementById('saveSettingsBtn').addEventListener('click', async function() {
            const form = document.getElementById('streamSettingsForm');
            const formData = new FormData(form);
            
            const settings = {};
            for (let [key, value] of formData.entries()) {
                settings[key] = value;
            }
            
            // Handle checkbox
            settings['stream_enable_recording'] = form.querySelector('[name="stream_enable_recording"]').checked ? '1' : '0';
            
            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Saving...';
            
            try {
                const response = await fetch('/api/admin/streams/settings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'update', settings: settings })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Settings saved successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('streamSettingsModal')).hide();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save settings. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save me-1"></i> Save Settings';
            }
        });
        
        // Load settings when modal opens
        document.getElementById('streamSettingsModal').addEventListener('show.bs.modal', async function() {
            try {
                const response = await fetch('/api/admin/streams/settings.php?action=get');
                const data = await response.json();
                
                if (data.success) {
                    const form = document.getElementById('streamSettingsForm');
                    for (let [key, value] of Object.entries(data.settings)) {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            if (input.type === 'checkbox') {
                                input.checked = value === '1';
                            } else {
                                input.value = value;
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        });
        
        // Moderation - load comments when stream selected
        document.getElementById('moderationStreamSelect').addEventListener('change', async function() {
            const streamId = this.value;
            const contentDiv = document.getElementById('moderationContent');
            
            if (!streamId) {
                contentDiv.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <p>Select a stream to view and moderate comments</p>
                    </div>
                `;
                return;
            }
            
            contentDiv.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            
            try {
                const response = await fetch(`/api/admin/streams/moderation.php?action=get_comments&stream_id=${streamId}`);
                const data = await response.json();
                
                if (data.success && data.comments.length > 0) {
                    let html = '<div class="list-group">';
                    data.comments.forEach(comment => {
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>${escapeHtml(comment.username || 'Anonymous')}</strong>
                                        <small class="text-muted ms-2">${new Date(comment.created_at).toLocaleString()}</small>
                                        <p class="mb-0 mt-1">${escapeHtml(comment.comment_text)}</p>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger delete-comment" data-comment-id="${comment.id}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    contentDiv.innerHTML = html;
                    
                    // Add delete handlers
                    contentDiv.querySelectorAll('.delete-comment').forEach(btn => {
                        btn.addEventListener('click', function() {
                            deleteComment(this.dataset.commentId, streamId);
                        });
                    });
                } else {
                    contentDiv.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-comment-slash fa-3x mb-3"></i>
                            <p>No comments found for this stream</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load comments</div>';
            }
        });
        
        // Helper functions
        async function loadStreams() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const vendorId = document.getElementById('vendorFilter').value;
            const date = document.getElementById('dateFilter').value;
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (vendorId) params.append('vendor_id', vendorId);
            if (date) params.append('date', date);
            
            try {
                const response = await fetch(`/api/admin/streams/list.php?${params}`);
                const data = await response.json();
                
                if (data.success) {
                    updateStreamsTable(data.streams);
                }
            } catch (error) {
                console.error('Error loading streams:', error);
            }
        }
        
        async function loadStats() {
            try {
                const response = await fetch('/api/admin/streams/stats.php');
                const data = await response.json();
                
                if (data.success) {
                    // Update stats display
                    location.reload(); // Simple reload for now
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }
        
        async function performStreamAction(action, streamId) {
            try {
                const response = await fetch('/api/admin/streams/control.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action, stream_id: parseInt(streamId) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    loadStreams();
                    loadStats();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to perform action. Please try again.');
            }
        }
        
        async function deleteComment(commentId, streamId) {
            if (!confirm('Are you sure you want to delete this comment?')) return;
            
            try {
                const response = await fetch('/api/admin/streams/moderation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ action: 'delete_comment', comment_id: parseInt(commentId) })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Reload comments
                    document.getElementById('moderationStreamSelect').dispatchEvent(new Event('change'));
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete comment');
            }
        }
        
        function updateStreamsTable(streams) {
            // This would update the table dynamically
            // For now, just reload the page
            location.reload();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                loadStats();
            }
        }, 30000);
    </script>
</body>
</html>