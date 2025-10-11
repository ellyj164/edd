<?php
/**
 * Send Chat Message API Endpoint
 * Sends a message in an existing chat
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $chatId = $input['chat_id'] ?? null;
    $message = trim($input['message'] ?? '');
    $sender = $input['sender'] ?? 'user'; // user, agent, ai
    
    // Validation
    if (!$chatId) {
        http_response_code(400);
        echo json_encode(['error' => 'Chat ID is required']);
        exit;
    }
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }
    
    if (strlen($message) > 5000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message too long (max 5000 characters)']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Verify chat exists and is active
    $chatStmt = $db->prepare("SELECT id, status, type FROM chats WHERE id = ?");
    $chatStmt->execute([$chatId]);
    $chat = $chatStmt->fetch();
    
    if (!$chat) {
        http_response_code(404);
        echo json_encode(['error' => 'Chat not found']);
        exit;
    }
    
    if ($chat['status'] === 'closed') {
        http_response_code(400);
        echo json_encode(['error' => 'Chat is closed']);
        exit;
    }
    
    // Get sender ID if logged in
    $senderId = Session::isLoggedIn() ? Session::getUserId() : null;
    
    // Insert message
    $stmt = $db->prepare("
        INSERT INTO chat_messages (chat_id, sender, sender_id, message, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$chatId, $sender, $senderId, $message]);
    $messageId = $db->lastInsertId();
    
    // Update chat timestamp
    $updateStmt = $db->prepare("UPDATE chats SET updated_at = NOW() WHERE id = ?");
    $updateStmt->execute([$chatId]);
    
    // If this is an AI chat, trigger AI response
    if ($chat['type'] === 'ai' && $sender === 'user') {
        // Queue AI response (will be handled by feza_ai.php endpoint)
        $aiStmt = $db->prepare("
            INSERT INTO ai_interactions (chat_id, session_id, prompt, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $sessionId = session_id() ?: 'guest_' . substr(md5($chatId . time()), 0, 16);
        $aiStmt->execute([$chatId, $sessionId, $message]);
    }
    
    Logger::info("Message sent in chat {$chatId} by {$sender}");
    
    echo json_encode([
        'success' => true,
        'message_id' => $messageId,
        'chat_id' => $chatId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    Logger::error("Send message error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message']);
}
