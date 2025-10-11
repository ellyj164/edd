<?php
/**
 * Watchlist API Endpoint
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Session::isLoggedIn()) {
    errorResponse('Please login to manage your watchlist', 401);
}

$userId = Session::getUserId();
$watchlist = new Watchlist();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $items = $watchlist->getUserWatchlist($userId);
        successResponse(['items' => $items]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                // Check if product exists
                $product = new Product();
                $productData = $product->find($productId);
                
                if (!$productData) {
                    errorResponse('Product not found');
                }
                
                // Check if item is already in watchlist
                if ($watchlist->isInWatchlist($userId, $productId)) {
                    errorResponse('Item already in watchlist');
                }
                
                // Note: Allowing watchlist for inactive products so users can track them
                // Remove this check if watchlist should only allow active products
                
                $result = $watchlist->addToWatchlist($userId, $productId);
                
                if ($result) {
                    successResponse([], 'Item added to watchlist');
                } else {
                    errorResponse('Failed to add item to watchlist');
                }
                break;
                
            case 'remove':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                $result = $watchlist->removeFromWatchlist($userId, $productId);
                
                if ($result) {
                    successResponse([], 'Item removed from watchlist');
                } else {
                    errorResponse('Failed to remove item');
                }
                break;
                
            case 'check':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                $inWatchlist = $watchlist->isInWatchlist($userId, $productId);
                successResponse(['in_watchlist' => $inWatchlist]);
                break;
                
            default:
                errorResponse('Invalid action');
        }
        
    } else {
        errorResponse('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('Watchlist API error: ' . $e->getMessage());
    errorResponse('An error occurred');
}
?>