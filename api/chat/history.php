<?php
/**
 * Chat History API
 * Get chat message history for a conversation
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    $conversationId = $_GET['conversation_id'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    if (empty($conversationId)) {
        throw new Exception('Conversation ID is required');
    }
    
    $db = db();
    
    // Get messages
    $stmt = $db->prepare("
        SELECT 
            m.*,
            u.username as sender_name,
            u.email as sender_email
        FROM live_chat_messages m
        LEFT JOIN users u ON m.sender_id = u.id
        WHERE m.conversation_id = ?
        ORDER BY m.created_at ASC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$conversationId, $limit, $offset]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Mark messages as read if user is admin
    $userRole = Session::get('user_role') ?? 'user';
    if ($userRole === 'admin') {
        $userId = Session::getUserId();
        $stmt = $db->prepare("
            UPDATE live_chat_messages 
            SET is_read = 1 
            WHERE conversation_id = ? 
            AND receiver_id = ? 
            AND is_read = 0
        ");
        $stmt->execute([$conversationId, $userId]);
    }
    
    echo json_encode([
        'conversation_id' => $conversationId,
        'messages' => $messages,
        'count' => count($messages),
        'has_more' => count($messages) === $limit
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
