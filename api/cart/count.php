<?php
/**
 * Cart Count API Endpoint
 * E-Commerce Platform
 * 
 * Returns the number of items in user's cart
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Allow both authenticated and unauthenticated access
// Unauthenticated users get count = 0

if (!Session::isLoggedIn()) {
    successResponse(['count' => 0]);
}

$userId = Session::getUserId();

try {
    $cart = new Cart();
    $count = $cart->getCartCount($userId);
    
    successResponse(['count' => $count]);
    
} catch (Exception $e) {
    Logger::error('Cart count API error: ' . $e->getMessage());
    errorResponse('An error occurred while getting cart count', 500);
}
?>