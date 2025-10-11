<?php
/**
 * Search Suggestions API
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

$query = sanitizeInput($_GET['q'] ?? '');

if (strlen($query) < 3) {
    successResponse(['suggestions' => []]);
}

try {
    $product = new Product();
    $category = new Category();
    
    $suggestions = [];
    
    // Search products (limit to 5)
    $products = $product->search($query, 5);
    foreach ($products as $prod) {
        $suggestions[] = [
            'name' => $prod['name'],
            'type' => 'product',
            'url' => "/product.php?id={$prod['id']}"
        ];
    }
    
    // Search categories (limit to 3)
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM categories WHERE name LIKE ? AND status = 'active' LIMIT 3");
    $stmt->execute(["%{$query}%"]);
    $categories = $stmt->fetchAll();
    
    foreach ($categories as $cat) {
        $suggestions[] = [
            'name' => $cat['name'] . ' (Category)',
            'type' => 'category',
            'url' => "/products.php?category={$cat['id']}"
        ];
    }
    
    successResponse(['suggestions' => $suggestions]);
    
} catch (Exception $e) {
    Logger::error('Search suggestions error: ' . $e->getMessage());
    errorResponse('Search unavailable', 500);
}
?>