<?php
// /cart/ajax-add.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Start session
session_start();

// Adjust the path based on your file structure
require_once __DIR__ . '/../includes/init.php';

/**
 * Sends a JSON response.
 * @param bool $success
 * @param array $data
 */
function json_response($success, $data = []) {
    $response = ['success' => (bool)$success];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// 1. Check if user is logged in
if (!Session::isLoggedIn()) {
    json_response(false, ['message' => 'User not logged in.', 'login_required' => true]);
}

// 2. Check for POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, ['message' => 'Invalid request method.']);
}

// 3. Get and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    json_response(false, ['message' => 'Invalid JSON input.']);
}

// 4. Validate input
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1; // Default to 1

if ($product_id <= 0 || $quantity <= 0) {
    json_response(false, ['message' => 'Invalid product ID or quantity.']);
}

// 5. Get user ID from session
$user_id = Session::getUserId();

// Get database connection
$pdo = db();

try {
    // 6. Fetch product details (including price and stock) from the database
    $stmt = $pdo->prepare("SELECT price, stock_quantity, status FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        json_response(false, ['message' => 'Product not found.']);
    }

    // Check if product is active
    if ($product['status'] !== 'active') {
        json_response(false, ['message' => 'Product is not available.']);
    }

    // Check stock availability
    if ($product['stock_quantity'] < $quantity) {
        json_response(false, ['message' => 'Insufficient stock available.']);
    }

    $price = $product['price'];

    // 7. Check if the item is already in the cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cart_item) {
        // If item exists, update the quantity
        $new_quantity = $cart_item['quantity'] + $quantity;
        $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_stmt->execute([$new_quantity, $cart_item['id']]);
    } else {
        // If item does not exist, insert it with the fetched price
        $insert_stmt = $pdo->prepare(
            "INSERT INTO cart (user_id, product_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
        );
        $insert_stmt->execute([$user_id, $product_id, $quantity, $price]);
    }

    // 8. Calculate the new total cart count for the user
    $count_stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $count_stmt->execute([$user_id]);
    $cart_count = $count_stmt->fetchColumn();

    // 9. Send success response
    json_response(true, ['cart_count' => (int)$cart_count]);

} catch (PDOException $e) {
    // Log the error for debugging - do not show detailed error to user
    error_log("Cart Add Error: " . $e->getMessage());
    json_response(false, ['message' => 'An error occurred while adding the product to the cart.']);
}