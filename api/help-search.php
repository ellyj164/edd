<?php
/**
 * Help Search API
 * Returns matching help articles based on search query
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

try {
    $query = sanitizeInput($_GET['q'] ?? '');
    
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Search query is required']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Search in help articles (if table exists), otherwise use hardcoded articles
    $results = [];
    
    // Define help articles (this can be moved to database later)
    $help_articles = [
        [
            'id' => 1,
            'title' => 'How to Place an Order',
            'category' => 'Orders',
            'content' => 'To place an order, browse our products, add items to cart, and proceed to checkout.',
            'url' => '/help/orders.php#place-order'
        ],
        [
            'id' => 2,
            'title' => 'Shipping Information',
            'category' => 'Shipping',
            'content' => 'We offer various shipping options including standard, express, and international shipping.',
            'url' => '/help/shipping.php'
        ],
        [
            'id' => 3,
            'title' => 'Return Policy',
            'category' => 'Returns',
            'content' => 'Returns are accepted within 30 days of purchase. Items must be in original condition.',
            'url' => '/help/returns.php'
        ],
        [
            'id' => 4,
            'title' => 'Payment Methods',
            'category' => 'Payments',
            'content' => 'We accept credit cards and debit cards securely processed through Stripe.',
            'url' => '/help/payments.php'
        ],
        [
            'id' => 5,
            'title' => 'Account Security',
            'category' => 'Account',
            'content' => 'Learn how to secure your account with two-factor authentication and strong passwords.',
            'url' => '/help/account.php#security'
        ],
        [
            'id' => 6,
            'title' => 'Track Your Order',
            'category' => 'Orders',
            'content' => 'Track your order status and shipping information from your account dashboard.',
            'url' => '/help/orders.php#tracking'
        ],
        [
            'id' => 7,
            'title' => 'Seller Registration',
            'category' => 'Selling',
            'content' => 'Learn how to become a seller on FezaMarket and start selling your products.',
            'url' => '/help/selling.php'
        ],
        [
            'id' => 8,
            'title' => 'Wishlist Guide',
            'category' => 'Features',
            'content' => 'Save products to your wishlist for later purchase and get notifications on price drops.',
            'url' => '/help/features.php#wishlist'
        ],
        [
            'id' => 9,
            'title' => 'Contact Support',
            'category' => 'Support',
            'content' => 'Contact our support team via email, live chat, or phone for assistance.',
            'url' => '/contact.php'
        ],
        [
            'id' => 10,
            'title' => 'Refund Process',
            'category' => 'Returns',
            'content' => 'Refunds are processed within 5-7 business days after we receive your return.',
            'url' => '/help/returns.php#refunds'
        ]
    ];
    
    // Search through articles
    $search_terms = explode(' ', strtolower($query));
    
    foreach ($help_articles as $article) {
        $match_score = 0;
        $title_lower = strtolower($article['title']);
        $content_lower = strtolower($article['content']);
        $category_lower = strtolower($article['category']);
        
        foreach ($search_terms as $term) {
            if (empty($term)) continue;
            
            // Higher weight for title matches
            if (strpos($title_lower, $term) !== false) {
                $match_score += 3;
            }
            
            // Medium weight for category matches
            if (strpos($category_lower, $term) !== false) {
                $match_score += 2;
            }
            
            // Lower weight for content matches
            if (strpos($content_lower, $term) !== false) {
                $match_score += 1;
            }
        }
        
        if ($match_score > 0) {
            $results[] = [
                'title' => $article['title'],
                'category' => $article['category'],
                'content' => $article['content'],
                'url' => $article['url'],
                'score' => $match_score
            ];
        }
    }
    
    // Sort by relevance score
    usort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    
    // Remove score from final results
    $results = array_map(function($item) {
        unset($item['score']);
        return $item;
    }, $results);
    
    // Limit to top 10 results
    $results = array_slice($results, 0, 10);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    Logger::error('Help search error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching'
    ]);
}
