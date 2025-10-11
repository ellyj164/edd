<?php
/**
 * Feza AI Assistant API Endpoint
 * Handles AI chat interactions with OpenAI or fallback to FAQ
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/init.php';

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
    
    $message = trim($input['message'] ?? '');
    $chatId = $input['chat_id'] ?? null;
    $sessionId = $input['session_id'] ?? session_id();
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit;
    }
    
    // Rate limiting
    $userId = Session::isLoggedIn() ? Session::getUserId() : null;
    $rateLimitKey = $userId ?: $_SERVER['REMOTE_ADDR'];
    
    $db = Database::getInstance()->getConnection();
    
    // Check rate limit (10 requests per minute)
    $rateLimitStmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM ai_interactions
        WHERE (user_id = ? OR session_id = ?)
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $rateLimitStmt->execute([$userId, $sessionId]);
    $requestCount = $rateLimitStmt->fetch()['count'];
    
    if ($requestCount >= 10) {
        http_response_code(429);
        echo json_encode([
            'error' => 'Rate limit exceeded. Please wait a moment.',
            'fallback' => true,
            'response' => 'I\'m receiving too many requests right now. Please wait a moment and try again.'
        ]);
        exit;
    }
    
    $startTime = microtime(true);
    $response = '';
    $error = null;
    $provider = 'fallback';
    $model = 'faq';
    $tokensUsed = 0;
    
    // Check if API key is configured
    $apiKey = defined('FEZA_AI_API_KEY') ? FEZA_AI_API_KEY : '';
    $aiEnabled = defined('FEZA_AI_ENABLED') ? FEZA_AI_ENABLED : true;
    
    if ($aiEnabled && !empty($apiKey)) {
        // Use OpenAI or configured provider
        $provider = defined('FEZA_AI_PROVIDER') ? FEZA_AI_PROVIDER : 'openai';
        
        try {
            $response = callAIProvider($message, $apiKey, $provider, $model, $tokensUsed);
        } catch (Exception $e) {
            Logger::warning("AI provider error: " . $e->getMessage());
            $error = $e->getMessage();
            // Fall back to FAQ
            $response = getFallbackResponse($message);
        }
    } else {
        // Use fallback FAQ/knowledge base
        $response = getFallbackResponse($message);
    }
    
    $responseTime = round((microtime(true) - $startTime) * 1000);
    
    // Log interaction
    $logStmt = $db->prepare("
        INSERT INTO ai_interactions 
        (user_id, session_id, chat_id, prompt, response, provider, model, tokens_used, response_time_ms, error, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $logStmt->execute([
        $userId,
        $sessionId,
        $chatId,
        $message,
        $response,
        $provider,
        $model,
        $tokensUsed,
        $responseTime,
        $error
    ]);
    
    // If there's a chat_id, save the AI response as a message
    if ($chatId) {
        $msgStmt = $db->prepare("
            INSERT INTO chat_messages (chat_id, sender, message, created_at)
            VALUES (?, 'ai', ?, NOW())
        ");
        $msgStmt->execute([$chatId, $response]);
    }
    
    echo json_encode([
        'success' => true,
        'response' => $response,
        'provider' => $provider,
        'response_time_ms' => $responseTime,
        'session_id' => $sessionId
    ]);
    
} catch (Exception $e) {
    Logger::error("Feza AI error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'AI service temporarily unavailable',
        'fallback' => true,
        'response' => 'I\'m having trouble right now. Please try contacting our support team for immediate assistance.'
    ]);
}

/**
 * Call AI Provider (OpenAI or compatible)
 */
function callAIProvider($message, $apiKey, $provider, &$model, &$tokensUsed) {
    $model = 'gpt-3.5-turbo';
    
    if ($provider === 'openai') {
        $endpoint = 'https://api.openai.com/v1/chat/completions';
        
        $systemPrompt = "You are Feza AI, a helpful virtual assistant for FezaMarket, an e-commerce platform. " .
                       "Help users with product inquiries, order tracking, account issues, and general questions. " .
                       "Be friendly, concise, and professional. If you can't help with something, suggest contacting support.";
        
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ];
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("OpenAI API returned status {$httpCode}");
        }
        
        $decoded = json_decode($result, true);
        
        if (isset($decoded['error'])) {
            throw new Exception($decoded['error']['message'] ?? 'OpenAI API error');
        }
        
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response');
        }
        
        $tokensUsed = $decoded['usage']['total_tokens'] ?? 0;
        return $decoded['choices'][0]['message']['content'];
    }
    
    throw new Exception("Unsupported AI provider: {$provider}");
}

/**
 * Fallback FAQ/Knowledge Base Response
 * Now uses comprehensive 1000+ entry knowledge base with keyword matching
 */
function getFallbackResponse($message) {
    $message = strtolower(trim($message));
    
    // Load comprehensive knowledge base
    $replies = require __DIR__ . '/feza_ai_replies.php';
    
    // STEP 1: Try exact match
    if (isset($replies[$message])) {
        return $replies[$message];
    }
    
    // STEP 2: Keyword-based fuzzy matching
    $stopwords = [
        'the', 'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'what', 'how', 'why', 'when', 'where', 'who', 'which',
        'a', 'an', 'and', 'or', 'but', 'if', 'of', 'at', 'by', 'for',
        'with', 'about', 'as', 'into', 'through', 'during', 'before',
        'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in',
        'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then',
        'once', 'here', 'there', 'all', 'both', 'each', 'few', 'more',
        'most', 'other', 'some', 'such', 'only', 'own', 'same', 'so',
        'than', 'too', 'very', 'can', 'will', 'just', 'should', 'now',
        'me', 'my', 'myself', 'we', 'our', 'ours', 'ourselves', 'you',
        'your', 'yours', 'yourself', 'yourselves', 'he', 'him', 'his',
        'himself', 'she', 'her', 'hers', 'herself', 'it', 'its', 'itself',
        'they', 'them', 'their', 'theirs', 'themselves', 'i', 'please',
        'do', 'does', 'did', 'doing', 'would', 'could', 'ought'
    ];
    
    // Extract keywords from user message
    $words = preg_split('/\s+/', $message);
    $keywords = array_diff($words, $stopwords);
    $keywords = array_filter($keywords, function($word) {
        return strlen($word) > 2; // Remove very short words
    });
    
    // Find best matching response based on keywords
    $maxMatches = 0;
    $bestMatch = null;
    
    foreach ($replies as $key => $reply) {
        $matchCount = 0;
        
        // Count how many keywords match in this response key
        foreach ($keywords as $keyword) {
            if (strpos($key, $keyword) !== false) {
                $matchCount++;
            }
        }
        
        // If this key matches more keywords than previous best, use it
        if ($matchCount > 0 && $matchCount > $maxMatches) {
            $maxMatches = $matchCount;
            $bestMatch = $reply;
        }
    }
    
    if ($bestMatch) {
        return $bestMatch;
    }
    
    // STEP 3: Fallback response if no match found
    $fallbacks = [
        "I'm not sure I understand that yet. Could you try asking differently? 🤔",
        "That's an interesting question! Can you rephrase it or be more specific?",
        "Hmm... I'm still learning about that. Try asking about orders, shipping, returns, or payments!",
        "I may not know that yet, but I'm here to help! Try asking about:\n• Order tracking\n• Returns & refunds\n• Shipping info\n• Payment methods\n• Account issues",
        "I'm not quite sure about that, but I can help with shopping, orders, shipping, and account questions!",
        "Let me think... Could you ask that another way? I'm great at helping with orders, products, and account issues!"
    ];
    
    // Log unanswered question for learning
    try {
        $db = db();
        if ($db) {
            $stmt = $db->prepare("INSERT INTO unanswered_questions (question, created_at) VALUES (?, NOW())");
            $stmt->execute([$message]);
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist yet
    }
    
    return $fallbacks[array_rand($fallbacks)];
}
