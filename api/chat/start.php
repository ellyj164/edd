<?php
/**
 * Start Chat API Endpoint
 * Creates a new chat session
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
    
    // Get user info
    $userId = Session::isLoggedIn() ? Session::getUserId() : null;
    $name = $input['name'] ?? null;
    $email = $input['email'] ?? null;
    $type = $input['type'] ?? 'support'; // support, ai, sales
    $initialMessage = $input['message'] ?? null;
    
    // Validation
    if (!$userId && (!$name || !$email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email required for guests']);
        exit;
    }
    
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        exit;
    }
    
    // Rate limiting check
    $db = Database::getInstance()->getConnection();
    
    // Check recent chats from same IP/user
    $recentCheck = $db->prepare("
        SELECT COUNT(*) as count 
        FROM chats 
        WHERE (user_id = ? OR email = ?) 
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $recentCheck->execute([$userId, $email]);
    $recentCount = $recentCheck->fetch()['count'];
    
    if ($recentCount >= 3) {
        http_response_code(429);
        echo json_encode(['error' => 'Too many chat sessions. Please wait a moment.']);
        exit;
    }
    
    // Create chat session
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO chats (user_id, name, email, status, type, created_at)
            VALUES (?, ?, ?, 'active', ?, NOW())
        ");
        
        $stmt->execute([$userId, $name, $email, $type]);
        $chatId = $db->lastInsertId();
        
        // Add initial message if provided
        if ($initialMessage) {
            $msgStmt = $db->prepare("
                INSERT INTO chat_messages (chat_id, sender, sender_id, message, created_at)
                VALUES (?, 'user', ?, ?, NOW())
            ");
            $msgStmt->execute([$chatId, $userId, $initialMessage]);
        }
        
        // Add welcome system message
        $welcomeMsg = "Welcome to FezaMarket Support! ";
        if ($type === 'ai') {
            $welcomeMsg = "Welcome! I'm Feza AI, your virtual assistant. How can I help you today?";
        } else {
            $welcomeMsg .= "An agent will be with you shortly. How can we help you today?";
        }
        
        $sysStmt = $db->prepare("
            INSERT INTO chat_messages (chat_id, sender, message, created_at)
            VALUES (?, 'system', ?, NOW())
        ");
        $sysStmt->execute([$chatId, $welcomeMsg]);
        
        $db->commit();
        
        // Log successful chat start
        Logger::info("Chat started: ID={$chatId}, Type={$type}, User={$userId}");
        
        // Return chat info
        echo json_encode([
            'success' => true,
            'chat_id' => $chatId,
            'status' => 'active',
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    Logger::error("Chat start error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to start chat session']);
}
