#!/usr/bin/env php
<?php
/**
 * Test script for FEZA AI Chatbot
 * Tests various questions and keyword matching
 */

// Mock the required functions and classes for testing
if (!function_exists('db')) {
    function db() {
        return null; // Mock database for testing
    }
}

if (!class_exists('Session')) {
    class Session {
        public static function isLoggedIn() { return false; }
        public static function getUserId() { return null; }
    }
}

// Load the knowledge base
$replies = require __DIR__ . '/api/feza_ai_replies.php';

echo "=== FEZA AI Chatbot Test ===\n\n";
echo "Knowledge Base Size: " . count($replies) . " entries\n\n";

// Test cases
$testQuestions = [
    // Exact matches
    "hi" => "greeting",
    "hello" => "greeting",
    "how to track my order" => "order tracking",
    "return policy" => "returns",
    "payment methods" => "payment",
    
    // Keyword matching tests
    "I need to track my package" => "should match 'track' keyword",
    "How can I return an item?" => "should match 'return' keyword",
    "What payment options are available?" => "should match 'payment' keyword",
    "Can I ship to Kigali?" => "should match 'kigali' or 'ship' keyword",
    "Is my order shipped yet?" => "should match 'order' or 'shipped' keyword",
    "How do I reset my password?" => "should match 'password' keyword",
    "What is the refund process?" => "should match 'refund' keyword",
    "Do you have mobile money?" => "should match 'mobile money' keyword",
    "I want to become a seller" => "should match 'seller' keyword",
    "Are there any discounts?" => "should match 'discount' keyword",
    
    // Complex questions
    "How long does shipping to Rwanda take?" => "should match shipping/rwanda",
    "Can I use MTN Mobile Money to pay?" => "should match mtn/payment",
    "What if my package is damaged?" => "should match damaged/package",
];

// Stopwords for keyword extraction
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

echo "Running " . count($testQuestions) . " test cases...\n\n";

$passed = 0;
$failed = 0;

foreach ($testQuestions as $question => $expectedMatch) {
    echo "Q: \"$question\"\n";
    echo "Expected: $expectedMatch\n";
    
    $userMessage = strtolower(trim($question));
    $response = null;
    $matchType = 'none';
    
    // Try exact match
    if (isset($replies[$userMessage])) {
        $response = $replies[$userMessage];
        $matchType = 'exact';
    }
    
    // Try keyword matching
    if (!$response) {
        $words = preg_split('/\s+/', $userMessage);
        $keywords = array_diff($words, $stopwords);
        $keywords = array_filter($keywords, function($word) {
            return strlen($word) > 2;
        });
        
        $maxMatches = 0;
        foreach ($replies as $key => $reply) {
            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (strpos($key, $keyword) !== false) {
                    $matchCount++;
                }
            }
            
            if ($matchCount > 0 && $matchCount > $maxMatches) {
                $maxMatches = $matchCount;
                $response = $reply;
                $matchType = 'keyword';
            }
        }
    }
    
    if ($response) {
        echo "✓ Match Type: $matchType\n";
        echo "Response: " . substr($response, 0, 100) . (strlen($response) > 100 ? "..." : "") . "\n";
        $passed++;
    } else {
        echo "✗ No match found\n";
        $failed++;
    }
    echo "\n";
}

echo "=== Test Results ===\n";
echo "Passed: $passed/" . count($testQuestions) . "\n";
echo "Failed: $failed/" . count($testQuestions) . "\n";
echo "\nSuccess Rate: " . round(($passed / count($testQuestions)) * 100, 1) . "%\n";
