<?php
/**
 * Recommendations API
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

$recommendation = new Recommendation();
$type = sanitizeInput($_GET['type'] ?? 'trending');
$productId = (int)($_GET['product_id'] ?? 0);

try {
    $recommendations = [];
    
    switch ($type) {
        case 'viewed_together':
            if ($productId > 0) {
                $recommendations = $recommendation->getViewedTogether($productId);
            }
            break;
            
        case 'purchased_together':
            if ($productId > 0) {
                $recommendations = $recommendation->getPurchasedTogether($productId);
            }
            break;
            
        case 'personalized':
            if (Session::isLoggedIn()) {
                $recommendations = $recommendation->getPersonalizedRecommendations(Session::getUserId());
            } else {
                $recommendations = $recommendation->getTrendingProducts();
            }
            break;
            
        case 'cart':
            // Get recommendations based on cart items
            if (Session::isLoggedIn()) {
                $cart = new Cart();
                $cartItems = $cart->getCartItems(Session::getUserId());
                
                if (!empty($cartItems)) {
                    // Get recommendations for the first item in cart
                    $firstProductId = $cartItems[0]['product_id'];
                    $recommendations = $recommendation->getViewedTogether($firstProductId, 3);
                }
            }
            
            if (empty($recommendations)) {
                $recommendations = $recommendation->getTrendingProducts(3);
            }
            break;
            
        case 'trending':
        default:
            $recommendations = $recommendation->getTrendingProducts();
            break;
    }
    
    // Format recommendations for frontend
    $formattedRecs = array_map(function($item) {
        return [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => formatPrice($item['price']),
            'image_url' => getSafeProductImageUrl($item),
            'url' => "/product.php?id={$item['id']}"
        ];
    }, $recommendations);
    
    successResponse($formattedRecs);
    
} catch (Exception $e) {
    Logger::error('Recommendations API error: ' . $e->getMessage());
    errorResponse('Recommendations unavailable', 500);
}
?>