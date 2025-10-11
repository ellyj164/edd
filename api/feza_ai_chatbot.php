<?php
/**
 * FEZA AI Chatbot - Self-contained Keyword-based Chatbot
 * No external APIs required - uses local knowledge base
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
    $sessionId = $input['session_id'] ?? session_id();
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['error' => 'Message is required']);
        exit;
    }
    
    // Load knowledge base
    $replies = require __DIR__ . '/feza_ai_replies.php';
    
    $startTime = microtime(true);
    $userMessage = strtolower($message);
    $bestMatch = null;
    $matchType = 'none';
    
    // STEP 1: Try exact match
    if (isset($replies[$userMessage])) {
        $bestMatch = $replies[$userMessage];
        $matchType = 'exact';
    }
    
    // STEP 2: Keyword detection if no exact match
    if (!$bestMatch) {
        // Define stopwords to remove (common words that don't help matching)
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
            'do', 'does', 'did', 'doing', 'would', 'could', 'ought', 'im',
            'youre', 'hes', 'shes', 'its', 'were', 'theyre', 'ive', 'youve',
            'weve', 'theyve', 'id', 'youd', 'hed', 'shed', 'wed', 'theyd',
            'ill', 'youll', 'hell', 'shell', 'well', 'theyll', 'isnt', 'arent',
            'wasnt', 'werent', 'hasnt', 'havent', 'hadnt', 'doesnt', 'dont',
            'didnt', 'wont', 'wouldnt', 'shouldnt', 'cant', 'cannot', 'couldnt',
            'mightnt', 'mustnt', 'lets', 'thats', 'whos', 'whats', 'heres',
            'theres', 'whens', 'wheres', 'whys', 'hows'
        ];
        
        // Extract keywords from user message
        $words = preg_split('/\s+/', $userMessage);
        $keywords = array_diff($words, $stopwords);
        $keywords = array_filter($keywords, function($word) {
            return strlen($word) > 2; // Remove very short words
        });
        
        // Find best matching response based on keywords
        $maxMatches = 0;
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
                $matchType = 'keyword';
            }
        }
    }
    
    // STEP 3: Fallback response if no match found
    if (!$bestMatch) {
        $fallbacks = [
            "I'm not sure I understand that yet. Could you try asking differently? 🤔",
            "That's an interesting question! Can you rephrase it or be more specific?",
            "Hmm... I'm still learning about that. Try asking about orders, shipping, returns, or payments!",
            "I may not know that yet, but I'm here to help! Try asking about:\n• Order tracking\n• Returns & refunds\n• Shipping info\n• Payment methods\n• Account issues",
            "I'm not quite sure about that, but I can help with shopping, orders, shipping, and account questions!",
            "Let me think... Could you ask that another way? I'm great at helping with orders, products, and account issues!",
            "I don't have that information yet, but I'm learning every day! 😊 Ask me about orders, products, or returns!",
            "Hmm, that's outside my knowledge base right now. How about I help you with order tracking or product questions?"
        ];
        $bestMatch = $fallbacks[array_rand($fallbacks)];
        $matchType = 'fallback';
        
        // Log unanswered question for improvement
        try {
            $db = db();
            if ($db) {
                $stmt = $db->prepare("INSERT INTO unanswered_questions (question, created_at) VALUES (?, NOW())");
                $stmt->execute([$message]);
            }
        } catch (Exception $e) {
            // Silently fail if table doesn't exist yet
            error_log("Could not log unanswered question: " . $e->getMessage());
        }
    }
    
    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
    
    // Log interaction for analytics
    try {
        $db = db();
        if ($db) {
            $userId = Session::isLoggedIn() ? Session::getUserId() : null;
            $stmt = $db->prepare("
                INSERT INTO ai_interactions 
                (user_id, session_id, prompt, response, provider, model, response_time_ms, created_at)
                VALUES (?, ?, ?, ?, 'self-contained', 'keyword-match', ?, NOW())
            ");
            $stmt->execute([$userId, $sessionId, $message, $bestMatch, $responseTime]);
        }
    } catch (Exception $e) {
        // Silently fail if table doesn't exist yet
        error_log("Could not log AI interaction: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'response' => $bestMatch,
        'provider' => 'self-contained',
        'match_type' => $matchType,
        'response_time_ms' => $responseTime,
        'session_id' => $sessionId
    ]);
    
} catch (Exception $e) {
    error_log("Feza AI Chatbot error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Service temporarily unavailable',
        'response' => 'I\'m having trouble right now. Please try again in a moment or contact support@fezamarket.com for immediate help.'
    ]);
}
