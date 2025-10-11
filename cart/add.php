<?php
/**
 * Add Product to Cart
 * E-Commerce Platform
 */

require_once __DIR__ . '/../includes/init.php';

// Require login for cart operations
Session::requireLogin();

$userId = Session::getUserId();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /products.php');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    Session::setFlash('error', 'Invalid request. Please try again.');
    header('Location: /products.php');
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validate inputs
if ($productId <= 0) {
    Session::setFlash('error', 'Invalid product selected.');
    header('Location: /products.php');
    exit;
}

if ($quantity <= 0) {
    $quantity = 1;
}

// Validate product exists and is available
$productModel = new Product();
$product = $productModel->find($productId);

if (!$product) {
    Session::setFlash('error', 'Product not found.');
    header('Location: /products.php');
    exit;
}

if ($product['status'] !== 'active') {
    Session::setFlash('error', 'This product is not available.');
    header('Location: /product.php?id=' . $productId);
    exit;
}

// Check stock availability
if ($product['stock_quantity'] < $quantity) {
    Session::setFlash('error', 'Not enough stock available. Only ' . $product['stock_quantity'] . ' items left.');
    header('Location: /product.php?id=' . $productId);
    exit;
}

try {
    $cart = new Cart();
    
    // Check if product already exists in cart using the Cart's method
    $existingItems = $cart->getCartItems($userId);
    $existingCartItem = null;
    foreach ($existingItems as $item) {
        if ($item['product_id'] == $productId) {
            $existingCartItem = $item;
            break;
        }
    }
    
    if ($existingCartItem) {
        // Update quantity if item already exists
        $newQuantity = $existingCartItem['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($newQuantity > $product['stock_quantity']) {
            Session::setFlash('error', 'Cannot add more items. Cart would exceed available stock.');
            header('Location: /product.php?id=' . $productId);
            exit;
        }
        
        $cart->updateQuantity($userId, $productId, $newQuantity);
        Session::setFlash('success', 'Updated quantity in cart.');
    } else {
        // Add new item to cart
        $cart->addItem($userId, $productId, $quantity);
        Session::setFlash('success', 'Product added to cart.');
    }
    
    // Log activity
    try {
        logSecurityEvent($userId, 'cart_add', 'product', $productId, [
            'quantity' => $quantity,
            'product_name' => $product['name'],
            'product_price' => $product['price']
        ]);
    } catch (Exception $e) {
        // Log activity failed, but don't break the flow
        error_log('Failed to log cart activity: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    Session::setFlash('error', 'Failed to add product to cart. Please try again.');
    error_log('Cart add error: ' . $e->getMessage());
    header('Location: /product.php?id=' . $productId);
    exit;
}

// Redirect back to product or to cart based on user preference
// Default to cart.php for homepage add-to-cart actions
if (isset($_POST['redirect_to_cart']) || !isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'product.php') === false) {
    header('Location: /cart.php');
} else {
    header('Location: /product.php?id=' . $productId);
}
exit;