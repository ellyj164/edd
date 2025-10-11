<?php
/**
 * AI-Powered Product Recommendations API
 * Returns personalized product recommendations based on user behavior
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

try {
    $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
    $limit = isset($_GET['limit']) ? min(20, max(1, (int)$_GET['limit'])) : 8;
    
    if ($productId <= 0) {
        errorResponse('Invalid product ID');
    }
    
    $userId = Session::isLoggedIn() ? Session::getUserId() : null;
    $db = db();
    $product = new Product();
    
    // Get current product details
    $currentProduct = $product->find($productId);
    if (!$currentProduct) {
        errorResponse('Product not found');
    }
    
    $categoryId = $currentProduct['category_id'];
    $currentPrice = (float)$currentProduct['price'];
    
    $recommendations = [];
    
    if ($userId) {
        // Personalized recommendations for logged-in users
        // Based on: 1) User's viewing history, 2) Same category, 3) Similar price range
        
        $stmt = $db->prepare("
            SELECT DISTINCT p.*, 
                   COUNT(upv.id) as view_count,
                   v.shop_name as vendor_name,
                   CASE 
                       WHEN upv.user_id = ? THEN 1 
                       ELSE 0 
                   END as viewed_by_user
            FROM products p
            LEFT JOIN user_product_views upv ON p.id = upv.product_id
            LEFT JOIN vendors v ON p.vendor_id = v.id
            WHERE p.id != ? 
                AND p.status = 'active'
                AND p.stock_quantity > 0
                AND (
                    p.category_id = ?
                    OR p.id IN (
                        SELECT upv2.product_id 
                        FROM user_product_views upv2 
                        WHERE upv2.user_id = ? 
                        ORDER BY upv2.created_at DESC 
                        LIMIT 20
                    )
                )
            GROUP BY p.id
            ORDER BY 
                viewed_by_user DESC,
                view_count DESC,
                ABS(p.price - ?) ASC,
                p.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$userId, $productId, $categoryId, $userId, $currentPrice, $limit]);
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Anonymous user recommendations
        // Based on: 1) Same category, 2) Popular products, 3) Similar price
        
        $stmt = $db->prepare("
            SELECT p.*, 
                   v.shop_name as vendor_name,
                   COUNT(upv.id) as view_count
            FROM products p
            LEFT JOIN user_product_views upv ON p.id = upv.product_id
            LEFT JOIN vendors v ON p.vendor_id = v.id
            WHERE p.id != ? 
                AND p.status = 'active'
                AND p.stock_quantity > 0
                AND p.category_id = ?
            GROUP BY p.id
            ORDER BY 
                view_count DESC,
                ABS(p.price - ?) ASC,
                p.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$productId, $categoryId, $currentPrice, $limit]);
        $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Format recommendations
    $formattedRecommendations = array_map(function($item) {
        return [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'slug' => $item['slug'],
            'price' => (float)$item['price'],
            'sale_price' => isset($item['sale_price']) ? (float)$item['sale_price'] : null,
            'image_url' => $item['image_url'] ?? '/images/placeholder-product.jpg',
            'vendor_name' => $item['vendor_name'] ?? 'FezaMarket',
            'stock_quantity' => (int)$item['stock_quantity'],
            'rating' => isset($item['rating']) ? (float)$item['rating'] : 0,
            'is_featured' => (bool)($item['is_featured'] ?? false)
        ];
    }, $recommendations);
    
    successResponse([
        'recommendations' => $formattedRecommendations,
        'algorithm' => $userId ? 'personalized' : 'category-based',
        'count' => count($formattedRecommendations)
    ]);
    
} catch (Exception $e) {
    Logger::error('AI Recommendations error: ' . $e->getMessage());
    errorResponse('An error occurred', 500);
}
