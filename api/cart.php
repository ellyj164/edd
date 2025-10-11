<?php
/**
 * Cart API Endpoint
 * E-Commerce Platform
 * 
 * Handles: add, update, remove, clear, get cart items
 */

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

// Require user login for cart operations
if (!Session::isLoggedIn()) {
    errorResponse('Please login to manage your cart', 401);
}

$userId = Session::getUserId();
$cart = new Cart();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get cart items
        $items = $cart->getCartItems($userId);
        $total = $cart->getCartTotal($userId);
        $count = $cart->getCartCount($userId);
        
        successResponse([
            'items' => $items,
            'total' => $total,
            'count' => $count
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $productId = (int)($input['product_id'] ?? 0);
                $quantity = (int)($input['quantity'] ?? 1);
                
                if ($productId <= 0 || $quantity <= 0) {
                    errorResponse('Invalid product or quantity');
                }
                
                // Check if product exists and is available
                $product = new Product();
                $productData = $product->find($productId);
                
                if (!$productData) {
                    errorResponse('Product not found');
                }
                
                if ($productData['status'] !== 'active') {
                    errorResponse('Product is not available');
                }
                
                // Check stock
                if ($productData['stock_quantity'] < $quantity) {
                    errorResponse('Insufficient stock available');
                }
                
                $result = $cart->addItem($userId, $productId, $quantity);
                
                if ($result) {
                    $count = $cart->getCartCount($userId);
                    $total = $cart->getCartTotal($userId);
                    successResponse([
                        'message' => 'Item added to cart',
                        'count' => $count,
                        'total' => $total
                    ]);
                } else {
                    errorResponse('Failed to add item to cart');
                }
                break;
                
            case 'update':
                $productId = (int)($input['product_id'] ?? 0);
                $quantity = (int)($input['quantity'] ?? 1);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                // Check stock if increasing quantity
                if ($quantity > 0) {
                    $product = new Product();
                    $productData = $product->find($productId);
                    
                    if ($productData && $productData['stock_quantity'] < $quantity) {
                        errorResponse('Insufficient stock available');
                    }
                }
                
                $result = $cart->updateQuantity($userId, $productId, $quantity);
                
                if ($result) {
                    $count = $cart->getCartCount($userId);
                    $total = $cart->getCartTotal($userId);
                    successResponse([
                        'message' => 'Cart updated',
                        'count' => $count,
                        'total' => $total
                    ]);
                } else {
                    errorResponse('Failed to update cart');
                }
                break;
                
            case 'remove':
                $productId = (int)($input['product_id'] ?? 0);
                
                if ($productId <= 0) {
                    errorResponse('Invalid product');
                }
                
                $result = $cart->removeItem($userId, $productId);
                
                if ($result) {
                    $count = $cart->getCartCount($userId);
                    $total = $cart->getCartTotal($userId);
                    successResponse([
                        'message' => 'Item removed from cart',
                        'count' => $count,
                        'total' => $total
                    ]);
                } else {
                    errorResponse('Failed to remove item from cart');
                }
                break;
                
            case 'clear':
                $result = $cart->clearCart($userId);
                
                if ($result) {
                    successResponse([
                        'message' => 'Cart cleared',
                        'count' => 0,
                        'total' => 0
                    ]);
                } else {
                    errorResponse('Failed to clear cart');
                }
                break;
                
            default:
                errorResponse('Invalid action');
        }
        
    } else {
        errorResponse('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    Logger::error('Cart API error: ' . $e->getMessage());
    errorResponse('An error occurred while processing your request', 500);
}
?>