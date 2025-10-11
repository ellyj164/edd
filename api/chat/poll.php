<?php
/**
 * Poll Chat Messages API Endpoint
 * Retrieves new messages for a chat (long polling support)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $chatId = $_GET['chat_id'] ?? null;
    $lastMessageId = intval($_GET['last_message_id'] ?? 0);
    $wait = intval($_GET['wait'] ?? 0); // Long polling wait time in seconds (0-30)
    
    if (!$chatId) {
        http_response_code(400);
        echo json_encode(['error' => 'Chat ID is required']);
        exit;
    }
    
    // Limit wait time to prevent hanging
    $wait = min($wait, 30);
    $wait = max($wait, 0);
    
    $db = Database::getInstance()->getConnection();
    
    // Verify chat exists
    $chatStmt = $db->prepare("SELECT id, status, type FROM chats WHERE id = ?");
    $chatStmt->execute([$chatId]);
    $chat = $chatStmt->fetch();
    
    if (!$chat) {
        http_response_code(404);
        echo json_encode(['error' => 'Chat not found']);
        exit;
    }
    
    // Long polling implementation
    $startTime = time();
    $messages = [];
    
    while (true) {
        // Fetch new messages
        $stmt = $db->prepare("
            SELECT 
                id,
                sender,
                sender_id,
                message,
                is_read,
                created_at
            FROM chat_messages
            WHERE chat_id = ? AND id > ?
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([$chatId, $lastMessageId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If we have messages or timeout, break
        if (!empty($messages) || (time() - $startTime) >= $wait) {
            break;
        }
        
        // Sleep briefly before checking again
        usleep(500000); // 0.5 seconds
    }
    
    // Mark messages as read if requested
    if (!empty($messages) && isset($_GET['mark_read'])) {
        $messageIds = array_column($messages, 'id');
        $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
        $readStmt = $db->prepare("
            UPDATE chat_messages 
            SET is_read = TRUE 
            WHERE id IN ($placeholders)
        ");
        $readStmt->execute($messageIds);
    }
    
    // Get chat status
    $statusStmt = $db->prepare("SELECT status FROM chats WHERE id = ?");
    $statusStmt->execute([$chatId]);
    $status = $statusStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'chat_id' => $chatId,
        'status' => $status,
        'messages' => $messages,
        'count' => count($messages),
        'last_message_id' => !empty($messages) ? end($messages)['id'] : $lastMessageId
    ]);
    
} catch (Exception $e) {
    Logger::error("Poll messages error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve messages']);
}
