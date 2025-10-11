<?php
/**
 * Live Chat Management - Admin Communications
 * Manage customer support chat sessions
 */

require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO
$pdo = db();

// Admin authentication
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
    }
} else {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php');
        exit;
    }
}

$page_title = 'Live Chat Management';

// Get active chat sessions
$active_chats = [];
$pending_chats = [];
$closed_chats = [];

try {
    // Active chats (assigned to agents)
    $stmt = $pdo->query("
        SELECT c.*, 
               u.username, u.email,
               (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id AND sender = 'user') as message_count,
               (SELECT message FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM chats c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.status = 'active'
        ORDER BY c.updated_at DESC
        LIMIT 20
    ");
    $active_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pending chats (waiting for agent)
    $stmt = $pdo->query("
        SELECT c.*, 
               u.username, u.email,
               (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id) as message_count,
               (SELECT message FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM chats c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.status = 'pending'
        ORDER BY c.created_at ASC
        LIMIT 20
    ");
    $pending_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recently closed
    $stmt = $pdo->query("
        SELECT c.*, 
               u.username, u.email,
               (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id) as message_count
        FROM chats c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.status = 'closed'
        ORDER BY c.updated_at DESC
        LIMIT 10
    ");
    $closed_chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching chat data: " . $e->getMessage());
}

// Get stats
$stats = [
    'active' => count($active_chats),
    'pending' => count($pending_chats),
    'total_today' => 0,
    'avg_response_time' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM chats WHERE DATE(created_at) = CURDATE()");
    $stats['total_today'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Error fetching chat stats: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - FezaMarket Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 0;
        }
        .chat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
            transition: all 0.3s;
            cursor: pointer;
        }
        .chat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(5px);
        }
        .chat-card.pending {
            border-left-color: #f39c12;
            background: #fff9f0;
        }
        .chat-card.closed {
            border-left-color: #95a5a6;
            opacity: 0.7;
        }
        .stats-box {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-box h3 {
            margin: 0;
            font-size: 2rem;
            color: #3498db;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-comments me-2"></i><?php echo htmlspecialchars($page_title); ?>
                </h1>
                <a href="/admin/communications/" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>
    
    <div class="container mt-4">
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-box">
                    <h3><?php echo $stats['active']; ?></h3>
                    <p class="mb-0">Active Chats</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box" style="border-top: 3px solid #f39c12;">
                    <h3 style="color: #f39c12;"><?php echo $stats['pending']; ?></h3>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box" style="border-top: 3px solid #27ae60;">
                    <h3 style="color: #27ae60;"><?php echo $stats['total_today']; ?></h3>
                    <p class="mb-0">Today's Chats</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-box" style="border-top: 3px solid #9b59b6;">
                    <h3 style="color: #9b59b6;"><?php echo $stats['avg_response_time']; ?>m</h3>
                    <p class="mb-0">Avg Response</p>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#pending">
                    <i class="fas fa-clock me-1"></i>Pending (<?php echo count($pending_chats); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#active">
                    <i class="fas fa-comments me-1"></i>Active (<?php echo count($active_chats); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#closed">
                    <i class="fas fa-check me-1"></i>Closed
                </a>
            </li>
        </ul>
        
        <div class="tab-content">
            <!-- Pending Chats -->
            <div class="tab-pane fade show active" id="pending">
                <?php if (empty($pending_chats)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No pending chats
                </div>
                <?php else: ?>
                <?php foreach ($pending_chats as $chat): ?>
                <div class="chat-card pending" onclick="openChat(<?php echo $chat['id']; ?>)">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <?php echo htmlspecialchars($chat['name'] ?? $chat['username'] ?? 'Guest'); ?>
                                <?php if ($chat['email']): ?>
                                <small class="text-muted">(<?php echo htmlspecialchars($chat['email']); ?>)</small>
                                <?php endif; ?>
                            </h6>
                            <p class="mb-2 text-muted">
                                <?php echo htmlspecialchars(substr($chat['last_message'] ?? 'No messages yet', 0, 100)); ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('M d, Y H:i', strtotime($chat['created_at'])); ?> •
                                <?php echo $chat['message_count']; ?> messages
                            </small>
                        </div>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Active Chats -->
            <div class="tab-pane fade" id="active">
                <?php if (empty($active_chats)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No active chats
                </div>
                <?php else: ?>
                <?php foreach ($active_chats as $chat): ?>
                <div class="chat-card" onclick="openChat(<?php echo $chat['id']; ?>)">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <?php echo htmlspecialchars($chat['name'] ?? $chat['username'] ?? 'Guest'); ?>
                            </h6>
                            <p class="mb-2 text-muted">
                                <?php echo htmlspecialchars(substr($chat['last_message'] ?? 'No messages', 0, 100)); ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Last: <?php echo date('M d, H:i', strtotime($chat['last_message_time'] ?? $chat['updated_at'])); ?>
                            </small>
                        </div>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Closed Chats -->
            <div class="tab-pane fade" id="closed">
                <?php if (empty($closed_chats)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No closed chats
                </div>
                <?php else: ?>
                <?php foreach ($closed_chats as $chat): ?>
                <div class="chat-card closed" onclick="openChat(<?php echo $chat['id']; ?>)">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">
                                <?php echo htmlspecialchars($chat['name'] ?? $chat['username'] ?? 'Guest'); ?>
                            </h6>
                            <small class="text-muted">
                                Closed: <?php echo date('M d, Y H:i', strtotime($chat['updated_at'])); ?> •
                                <?php echo $chat['message_count']; ?> messages
                            </small>
                        </div>
                        <span class="badge bg-secondary">Closed</span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function openChat(chatId) {
        // Open chat details page
        window.location.href = `/api/chat/details.php?id=${chatId}`;
    }
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        location.reload();
    }, 30000);
    </script>
</body>
</html>
