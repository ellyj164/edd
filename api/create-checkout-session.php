<?php
declare(strict_types=1);

/**
 * Create Checkout Session API Endpoint
 * Creates a Stripe Checkout Session and returns the session URL
 * 
 * This endpoint:
 * 1. Validates user session and cart
 * 2. Calculates order totals server-side
 * 3. Creates internal draft order
 * 4. Creates Stripe Checkout Session
 * 5. Returns session URL for frontend redirect
 */

// Load environment variables and dependencies
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../bootstrap/simple_env_loader.php';

// Load application initialization
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/stripe/init_stripe.php';
require_once __DIR__ . '/../includes/orders.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Require authenticated user
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    $userId = Session::getUserId();
    
    // Get cart items
    $cart = new Cart();
    $cartItems = $cart->getCartItems($userId);
    
    if (empty($cartItems)) {
        throw new Exception('Cart is empty');
    }
    
    // Calculate totals server-side (NEVER trust client-supplied amounts)
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
    }
    
    // Calculate tax and shipping
    $taxRate = 0.08; // 8% tax rate
    $shippingCost = $subtotal >= 50 ? 0 : 5.99; // Free shipping over $50
    
    $taxAmount = $subtotal * $taxRate;
    $total = $subtotal + $taxAmount + $shippingCost;
    
    // Convert to minor units (cents)
    $subtotalMinor = (int)round($subtotal * 100);
    $taxMinor = (int)round($taxAmount * 100);
    $shippingMinor = (int)round($shippingCost * 100);
    $totalMinor = (int)round($total * 100);
    
    // Get user info
    $user = new User();
    $userData = $user->find($userId);
    $customerEmail = $userData['email'] ?? Session::get('user_email') ?? '';
    $customerName = $userData['username'] ?? $userData['first_name'] ?? 'Customer';
    
    // Create draft internal order (status: pending_payment)
    $orderData = [
        'user_id' => $userId,
        'status' => 'pending_payment',
        'currency' => getStripeCurrency(),
        'amount_minor' => $totalMinor,
        'tax_minor' => $taxMinor,
        'shipping_minor' => $shippingMinor,
        'subtotal_minor' => $subtotalMinor,
        'customer_email' => $customerEmail,
        'customer_name' => $customerName
    ];
    
    $orderId = create_internal_order($orderData, $cartItems);
    $orderRef = generate_order_reference($orderId);
    
    // Find or create Stripe customer
    $customerId = find_or_create_stripe_customer_for_user($userId, [
        'email' => $customerEmail,
        'name' => $customerName
    ]);
    
    // Get domain for redirect URLs
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $domain = $protocol . '://' . $_SERVER['HTTP_HOST'];
    
    // Initialize Stripe
    $stripe = initStripe();
    
    // Build line items for Checkout Session
    $lineItems = [];
    foreach ($cartItems as $item) {
        $lineItems[] = [
            'price_data' => [
                'currency' => getStripeCurrency(),
                'product_data' => [
                    'name' => $item['name'] ?? 'Product',
                    'images' => !empty($item['image']) ? [$item['image']] : [],
                ],
                'unit_amount' => (int)round(($item['price'] ?? 0) * 100),
            ],
            'quantity' => $item['quantity'] ?? 1,
        ];
    }
    
    // Add shipping as a line item if applicable
    if ($shippingMinor > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => getStripeCurrency(),
                'product_data' => [
                    'name' => 'Shipping',
                ],
                'unit_amount' => $shippingMinor,
            ],
            'quantity' => 1,
        ];
    }
    
    // Add tax as a line item
    if ($taxMinor > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => getStripeCurrency(),
                'product_data' => [
                    'name' => 'Tax',
                ],
                'unit_amount' => $taxMinor,
            ],
            'quantity' => 1,
        ];
    }
    
    // Create Checkout Session
    $sessionParams = [
        'customer' => $customerId,
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => $domain . '/checkout-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $domain . '/checkout.php?canceled=1',
        'metadata' => [
            'order_id' => (string)$orderId,
            'order_ref' => $orderRef,
            'user_id' => (string)$userId,
        ],
        'phone_number_collection' => [
            'enabled' => true,
        ],
        'billing_address_collection' => 'required',
        'shipping_address_collection' => [
            'allowed_countries' => ['US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'CH', 'IE', 'SE', 'NO', 'DK', 'FI', 'NZ', 'JP', 'SG'],
        ],
        'customer_update' => [
            'address' => 'auto',
            'name' => 'auto',
        ],
        'payment_method_types' => ['card'],
        'payment_intent_data' => [
            'setup_future_usage' => 'off_session', // Save payment method for future use
            'description' => "Order {$orderRef}",
            'metadata' => [
                'order_id' => (string)$orderId,
                'order_ref' => $orderRef,
                'user_id' => (string)$userId,
            ],
        ],
    ];
    
    $checkoutSession = $stripe->checkout->sessions->create($sessionParams);
    
    logStripe("Created Checkout Session {$checkoutSession->id} for order #{$orderId} ({$orderRef}), amount: {$totalMinor}");
    
    // Store session ID with order
    if (function_exists('update_order_checkout_session')) {
        update_order_checkout_session($orderId, $checkoutSession->id);
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'sessionId' => $checkoutSession->id,
        'sessionUrl' => $checkoutSession->url,
        'orderId' => $orderId,
        'orderRef' => $orderRef,
    ]);
    
} catch (Exception $e) {
    logStripe("Checkout Session creation failed: " . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
