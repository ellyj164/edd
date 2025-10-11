<?php
/**
 * E-commerce Notification Examples
 * Shows how to integrate notifications into your e-commerce workflows
 */

require_once __DIR__ . '/../includes/init.php';

// Example 1: Send Order Confirmation
function sendOrderConfirmation($orderId, $userId) {
    $order = getOrderDetails($orderId); // Your function to get order
    
    sendNotification('order_placed', $userId, [
        'order_number' => $order['order_number'],
        'total_amount' => '$' . number_format($order['total'], 2),
        'order_url' => SITE_URL . '/order.php?id=' . $orderId,
        'action_url' => SITE_URL . '/order.php?id=' . $orderId
    ]);
}

// Example 2: Send Shipping Notification
function sendShippingNotification($orderId, $userId, $trackingNumber) {
    $order = getOrderDetails($orderId);
    
    sendNotification('order_shipped', $userId, [
        'order_number' => $order['order_number'],
        'tracking_number' => $trackingNumber,
        'estimated_delivery' => date('F j, Y', strtotime('+5 days')),
        'tracking_url' => 'https://track.example.com/' . $trackingNumber,
        'action_url' => SITE_URL . '/order.php?id=' . $orderId
    ]);
}

// Example 3: Send Delivery Confirmation
function sendDeliveryNotification($orderId, $userId) {
    $order = getOrderDetails($orderId);
    
    sendNotification('order_delivered', $userId, [
        'order_number' => $order['order_number'],
        'review_url' => SITE_URL . '/review.php?order=' . $orderId,
        'action_url' => SITE_URL . '/review.php?order=' . $orderId
    ]);
}

// Example 4: Payment Confirmation
function sendPaymentConfirmation($orderId, $userId, $amount, $paymentMethod) {
    $order = getOrderDetails($orderId);
    
    sendNotification('payment_received', $userId, [
        'order_number' => $order['order_number'],
        'amount' => '$' . number_format($amount, 2),
        'payment_method' => $paymentMethod,
        'action_url' => SITE_URL . '/order.php?id=' . $orderId
    ]);
}

// Example 5: Payment Failed
function sendPaymentFailedNotification($orderId, $userId, $amount) {
    $order = getOrderDetails($orderId);
    
    sendNotification('payment_failed', $userId, [
        'order_number' => $order['order_number'],
        'amount' => '$' . number_format($amount, 2),
        'payment_url' => SITE_URL . '/checkout.php?order=' . $orderId,
        'action_url' => SITE_URL . '/checkout.php?order=' . $orderId
    ]);
}

// Example 6: Refund Issued
function sendRefundNotification($orderId, $userId, $refundAmount) {
    $order = getOrderDetails($orderId);
    
    sendNotification('refund_issued', $userId, [
        'order_number' => $order['order_number'],
        'refund_amount' => '$' . number_format($refundAmount, 2),
        'action_url' => SITE_URL . '/order.php?id=' . $orderId
    ]);
}

// Example 7: Back in Stock Alert
function sendBackInStockNotification($productId, $userIds) {
    $product = getProductDetails($productId); // Your function
    
    foreach ($userIds as $userId) {
        sendNotification('item_back_in_stock', $userId, [
            'product_name' => $product['name'],
            'product_url' => SITE_URL . '/product.php?id=' . $productId,
            'price' => '$' . number_format($product['price'], 2),
            'action_url' => SITE_URL . '/product.php?id=' . $productId
        ]);
    }
}

// Example 8: Price Drop Alert
function sendPriceDropNotification($productId, $oldPrice, $newPrice, $userIds) {
    $product = getProductDetails($productId);
    $discount = round((($oldPrice - $newPrice) / $oldPrice) * 100);
    
    foreach ($userIds as $userId) {
        sendNotification('price_drop', $userId, [
            'product_name' => $product['name'],
            'old_price' => '$' . number_format($oldPrice, 2),
            'new_price' => '$' . number_format($newPrice, 2),
            'discount' => $discount,
            'product_url' => SITE_URL . '/product.php?id=' . $productId,
            'action_url' => SITE_URL . '/product.php?id=' . $productId
        ]);
    }
}

// Example 9: Wishlist Item on Sale
function sendWishlistSaleNotification($productId, $originalPrice, $salePrice, $userIds) {
    $product = getProductDetails($productId);
    
    foreach ($userIds as $userId) {
        sendNotification('wishlist_sale', $userId, [
            'product_name' => $product['name'],
            'original_price' => '$' . number_format($originalPrice, 2),
            'sale_price' => '$' . number_format($salePrice, 2),
            'product_url' => SITE_URL . '/product.php?id=' . $productId,
            'action_url' => SITE_URL . '/product.php?id=' . $productId
        ]);
    }
}

// Example 10: Abandoned Cart Reminder
function sendAbandonedCartNotification($userId, $itemCount, $totalAmount) {
    sendNotification('abandoned_cart', $userId, [
        'item_count' => $itemCount,
        'cart_url' => SITE_URL . '/cart.php',
        'total_amount' => '$' . number_format($totalAmount, 2),
        'action_url' => SITE_URL . '/cart.php'
    ]);
}

// Example 11: Welcome New User
function sendWelcomeNotification($userId) {
    sendNotification('welcome', $userId, [
        'action_url' => SITE_URL . '/products.php'
    ]);
}

// Example 12: Email Verification Success
function sendEmailVerifiedNotification($userId) {
    sendNotification('account_verified', $userId, [
        'action_url' => SITE_URL . '/account.php'
    ]);
}

// Example 13: Password Changed Alert
function sendPasswordChangedNotification($userId) {
    sendNotification('password_changed', $userId, [
        'action_url' => SITE_URL . '/account.php'
    ], true, true); // Send both email and in-app
}

// Example 14: Order Review Request
function sendReviewRequestNotification($orderId, $userId, $productName) {
    $order = getOrderDetails($orderId);
    
    sendNotification('order_review_request', $userId, [
        'order_number' => $order['order_number'],
        'product_name' => $productName,
        'review_url' => SITE_URL . '/review.php?order=' . $orderId,
        'action_url' => SITE_URL . '/review.php?order=' . $orderId
    ]);
}

// Example 15: Special Promotion
function sendPromotionNotification($title, $message, $url, $discountCode = null) {
    // Send to all active users
    sendNotificationToAll('promotion', [
        'promotion_title' => $title,
        'promotion_message' => $message,
        'promotion_url' => $url,
        'discount_code' => $discountCode ?? 'N/A',
        'action_url' => $url
    ]);
}

// Example 16: Send to Specific Role (e.g., all sellers)
function sendSellerAnnouncement($title, $message, $url) {
    sendNotificationToRole('promotion', 'seller', [
        'promotion_title' => $title,
        'promotion_message' => $message,
        'promotion_url' => $url,
        'action_url' => $url
    ]);
}

// Helper function (you would implement this based on your database structure)
function getOrderDetails($orderId) {
    // Placeholder - implement based on your database
    return [
        'id' => $orderId,
        'order_number' => 'ORD-' . str_pad($orderId, 6, '0', STR_PAD_LEFT),
        'total' => 99.99,
        'status' => 'pending'
    ];
}

// Helper function (you would implement this based on your database structure)
function getProductDetails($productId) {
    // Placeholder - implement based on your database
    return [
        'id' => $productId,
        'name' => 'Sample Product',
        'price' => 29.99,
        'stock' => 10
    ];
}

// Example usage in your order processing:
/*
// In your order processing code:
if ($order_created) {
    sendOrderConfirmation($order_id, $user_id);
}

// In your shipping code:
if ($order_shipped) {
    sendShippingNotification($order_id, $user_id, $tracking_number);
}

// In your payment processing:
if ($payment_successful) {
    sendPaymentConfirmation($order_id, $user_id, $amount, $payment_method);
} else {
    sendPaymentFailedNotification($order_id, $user_id, $amount);
}

// When user registers:
sendWelcomeNotification($new_user_id);

// When email is verified:
sendEmailVerifiedNotification($user_id);
*/
