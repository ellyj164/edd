<?php
/**
 * Admin Chat Queue API
 * Returns active chats for admin console
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// Require authentication
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check role
$user = new User();
$userData = $user->find(Session::getUserId());
if (!$userData || !in_array($userData['role'], ['admin', 'support', 'agent'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get active chats with last message
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.user_id,
            c.name,
            c.email,
            c.type,
            c.status,
            c.created_at,
            c.updated_at,
            (SELECT message FROM chat_messages WHERE chat_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM chat_messages WHERE chat_id = c.id AND is_read = FALSE AND sender != 'agent') as unread_count
        FROM chats c
        WHERE c.status = 'active'
        ORDER BY c.updated_at DESC
        LIMIT 50
    ");
    
    $stmt->execute();
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'chats' => $chats,
        'count' => count($chats)
    ]);
    
} catch (Exception $e) {
    Logger::error("Admin chat queue error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load chats']);
}
