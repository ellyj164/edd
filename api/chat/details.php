<?php
/**
 * Chat Details API
 * Returns details for a specific chat
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// Require authentication  
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $chatId = $_GET['chat_id'] ?? null;
    
    if (!$chatId) {
        http_response_code(400);
        echo json_encode(['error' => 'Chat ID is required']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get chat details
    $stmt = $db->prepare("
        SELECT 
            c.*,
            u.first_name,
            u.last_name,
            u.email as user_email
        FROM chats c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    
    $stmt->execute([$chatId]);
    $chat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$chat) {
        http_response_code(404);
        echo json_encode(['error' => 'Chat not found']);
        exit;
    }
    
    // Get message count
    $countStmt = $db->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE chat_id = ?");
    $countStmt->execute([$chatId]);
    $chat['message_count'] = $countStmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'chat' => $chat
    ]);
    
} catch (Exception $e) {
    Logger::error("Chat details error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load chat details']);
}
