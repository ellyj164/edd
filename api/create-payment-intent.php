<?php
declare(strict_types=1);

/**
 * Create Payment Intent API Endpoint
 * Returns client secret for Stripe.js to confirm payment
 * 
 * This endpoint:
 * 1. Validates user session and cart
 * 2. Calculates order totals server-side (never trust client amounts)
 * 3. Creates internal draft order
 * 4. Creates Stripe PaymentIntent with order metadata
 * 5. Returns client_secret for frontend confirmation
 */

// CRITICAL: Load environment variables FIRST (before any other code)
// This ensures .env is parsed before Stripe or any other library initializes
// The env loader is idempotent, so it's safe even though init.php also loads it
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/../bootstrap/simple_env_loader.php';

// Load application initialization (includes .env loading)
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/stripe/init_stripe.php';
require_once __DIR__ . '/../includes/orders.php';
require_once __DIR__ . '/../includes/currency_service.php';

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
    
    // Parse request body for save_for_future parameter and addresses
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true) ?? [];
    $saveForFuture = isset($requestData['save_for_future']) && $requestData['save_for_future'] === true;
    $billingAddress = $requestData['billing_address'] ?? null;
    $shippingAddress = $requestData['shipping_address'] ?? null;
    $couponCode = $requestData['coupon_code'] ?? null;
    $giftCardCode = $requestData['gift_card_code'] ?? null;
    $selectedCurrency = $requestData['currency'] ?? $_SESSION['detected_currency'] ?? 'USD';
    
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
    
    // Calculate tax and shipping (simplified - use your actual tax/shipping logic)
    $taxRate = 0.08; // 8% tax rate - adjust based on jurisdiction
    $shippingCost = 5.99; // Flat shipping - adjust based on your logic
    
    $taxAmount = $subtotal * $taxRate;
    $total = $subtotal + $taxAmount + $shippingCost;
    
    // Apply coupon discount
    $discountAmount = 0;
    $couponId = null;
    if ($couponCode) {
        $db = db();
        $stmt = $db->prepare("
            SELECT * FROM coupons 
            WHERE code = ? AND status = 'active'
            AND (valid_from IS NULL OR valid_from <= NOW())
            AND (valid_to IS NULL OR valid_to >= NOW())
        ");
        $stmt->execute([$couponCode]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($coupon) {
            $couponId = $coupon['id'];
            
            // Check minimum amount
            if ($coupon['minimum_amount'] > 0 && $subtotal < $coupon['minimum_amount']) {
                throw new Exception('Order amount does not meet coupon minimum');
            }
            
            // Calculate discount
            if ($coupon['type'] === 'percentage') {
                $discountAmount = ($subtotal * $coupon['value']) / 100;
            } else {
                $discountAmount = $coupon['value'];
            }
            
            // Apply maximum discount cap
            if ($coupon['maximum_discount'] > 0 && $discountAmount > $coupon['maximum_discount']) {
                $discountAmount = $coupon['maximum_discount'];
            }
            
            // Discount cannot exceed total
            if ($discountAmount > $total) {
                $discountAmount = $total;
            }
        }
    }
    
    // Apply gift card
    $giftCardAmount = 0;
    $giftCardId = null;
    if ($giftCardCode) {
        $db = db();
        $stmt = $db->prepare("
            SELECT * FROM giftcards 
            WHERE code = ? AND redeemed_by IS NULL
        ");
        $stmt->execute([$giftCardCode]);
        $giftCard = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($giftCard) {
            $giftCardId = $giftCard['id'];
            $giftCardAmount = min($giftCard['amount'], $total - $discountAmount);
        }
    }
    
    // Calculate final total
    $total = $total - $discountAmount - $giftCardAmount;
    if ($total < 0) $total = 0;
    
    // Convert currency if needed
    $currencyService = new CurrencyService();
    $baseCurrency = 'USD';
    
    if ($selectedCurrency !== $baseCurrency) {
        $subtotal = $currencyService->convert($subtotal, $baseCurrency, $selectedCurrency);
        $taxAmount = $currencyService->convert($taxAmount, $baseCurrency, $selectedCurrency);
        $shippingCost = $currencyService->convert($shippingCost, $baseCurrency, $selectedCurrency);
        $discountAmount = $currencyService->convert($discountAmount, $baseCurrency, $selectedCurrency);
        $giftCardAmount = $currencyService->convert($giftCardAmount, $baseCurrency, $selectedCurrency);
        $total = $currencyService->convert($total, $baseCurrency, $selectedCurrency);
    }
    
    // Convert to minor units (cents)
    $subtotalMinor = (int)round($subtotal * 100);
    $taxMinor = (int)round($taxAmount * 100);
    $shippingMinor = (int)round($shippingCost * 100);
    $discountMinor = (int)round($discountAmount * 100);
    $giftCardMinor = (int)round($giftCardAmount * 100);
    $totalMinor = (int)round($total * 100);
    
    // Get user info
    $user = new User();
    $userData = $user->find($userId);
    $customerEmail = $userData['email'] ?? Session::get('user_email') ?? '';
    $customerName = $userData['username'] ?? $userData['first_name'] ?? 'Customer';
    
    // Prepare addresses in JSON format for database
    $billingAddressJson = $billingAddress ? json_encode($billingAddress) : null;
    $shippingAddressJson = $shippingAddress ? json_encode($shippingAddress) : null;
    
    // Create draft internal order (status: pending_payment)
    $orderData = [
        'user_id' => $userId,
        'status' => 'pending_payment',
        'currency' => $selectedCurrency,
        'amount_minor' => $totalMinor,
        'tax_minor' => $taxMinor,
        'shipping_minor' => $shippingMinor,
        'subtotal_minor' => $subtotalMinor,
        'discount_minor' => $discountMinor,
        'customer_email' => $customerEmail,
        'customer_name' => $customerName,
        'billing_address' => $billingAddressJson,
        'shipping_address' => $shippingAddressJson,
        'coupon_id' => $couponId,
        'gift_card_id' => $giftCardId
    ];
    
    $orderId = create_internal_order($orderData, $cartItems);
    $orderRef = generate_order_reference($orderId);
    
    // Find or create Stripe customer
    $customerId = find_or_create_stripe_customer_for_user($userId, [
        'email' => $customerEmail,
        'name' => $customerName
    ]);
    
    // Create PaymentIntent via Stripe API
    $stripe = initStripe();
    $captureMethod = getStripeCaptureMethod();
    
    $piParams = [
        'amount' => $totalMinor,
        'currency' => strtolower($selectedCurrency),
        'customer' => $customerId,
        'description' => "Order {$orderRef}",
        'statement_descriptor' => substr(getStripeStatementDescriptor(), 0, 22), // Max 22 chars
        'metadata' => [
            'order_id' => (string)$orderId,
            'order_ref' => $orderRef,
            'user_id' => (string)$userId,
            'save_for_future' => $saveForFuture ? 'true' : 'false',
            'coupon_code' => $couponCode ?? '',
            'gift_card_code' => $giftCardCode ?? ''
        ],
        'automatic_payment_methods' => [
            'enabled' => true,
        ]
    ];
    
    // If saving for future billing, set setup_future_usage
    if ($saveForFuture) {
        $piParams['setup_future_usage'] = 'off_session';
        logStripe("Payment Intent will save payment method for future use (setup_future_usage=off_session)");
    }
    
    if ($captureMethod === 'manual') {
        $piParams['capture_method'] = 'manual';
    }
    
    $paymentIntent = $stripe->paymentIntents->create($piParams);
    
    // Persist PaymentIntent to database
    persist_payment_intent($paymentIntent->id, $orderId, $orderRef, [
        'amount_minor' => $totalMinor,
        'currency' => getStripeCurrency(),
        'status' => $paymentIntent->status,
        'client_secret' => $paymentIntent->client_secret,
        'customer_id' => $customerId,
        'metadata' => $piParams['metadata'],
        'last_payload' => []
    ]);
    
    logStripe("Created PaymentIntent {$paymentIntent->id} for order #{$orderId} ({$orderRef}), amount: {$totalMinor}");
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'paymentIntentId' => $paymentIntent->id,
        'orderId' => $orderId,
        'orderRef' => $orderRef,
        'amount' => $total,
        'amountMinor' => $totalMinor,
        'currency' => getStripeCurrency()
    ]);
    
} catch (Exception $e) {
    logStripe("Payment Intent creation failed: " . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
