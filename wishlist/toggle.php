<?php
/**
 * Toggle Product in Wishlist
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

// Require login for wishlist operations
Session::requireLogin();

$userId = Session::getUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /products.php');
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

// Validate inputs
if ($productId <= 0) {
    Session::setFlash('error', 'Invalid product selected.');
    header('Location: /products.php');
    exit;
}

// Validate product exists
$productModel = new Product();
$product = $productModel->find($productId);

if (!$product) {
    Session::setFlash('error', 'Product not found.');
    header('Location: /products.php');
    exit;
}

try {
    // Check if wishlist model exists
    if (class_exists('Wishlist')) {
        $wishlist = new Wishlist();
        
        // Check if product is already in wishlist
        $isInWishlist = $wishlist->isInWishlist($userId, $productId);
        
        if ($isInWishlist) {
            $wishlist->removeFromWishlist($userId, $productId);
            Session::setFlash('success', 'Product removed from wishlist.');
        } else {
            $wishlist->addToWishlist($userId, $productId);
            Session::setFlash('success', 'Product added to wishlist.');
        }
    } else {
        // Simple database implementation if Wishlist model doesn't exist
        $db = Database::getInstance()->getConnection();
        
        // Check if wishlist table exists
        $stmt = $db->prepare("SHOW TABLES LIKE 'wishlist'");
        $stmt->execute();
        
        if (!$stmt->fetch()) {
            // Create wishlist table if it doesn't exist
            $db->exec("
                CREATE TABLE wishlist (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_wishlist (user_id, product_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB
            ");
        }
        
        // Check if product is already in wishlist
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $isInWishlist = $stmt->fetch();
        
        if ($isInWishlist) {
            // Remove from wishlist
            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            Session::setFlash('success', 'Product removed from wishlist.');
        } else {
            // Add to wishlist
            $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            Session::setFlash('success', 'Product added to wishlist.');
        }
    }
    
} catch (Exception $e) {
    Session::setFlash('error', 'Failed to update wishlist. Please try again.');
    error_log('Wishlist toggle error: ' . $e->getMessage());
}

// Redirect back to product
header('Location: /product.php?id=' . $productId);
exit;