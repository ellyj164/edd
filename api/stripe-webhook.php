<?php
declare(strict_types=1);

/**
 * Stripe Webhook Endpoint
 * Handles payment events from Stripe with signature verification and idempotency
 * 
 * Webhook URL: https://yourdomain.com/api/stripe-webhook.php
 * 
 * Events handled:
 * - payment_intent.succeeded: Mark order as paid
 * - payment_intent.payment_failed: Mark order as failed
 * - charge.refunded: Record refund
 * - refund.succeeded: Record refund success
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

// Set content type
header('Content-Type: application/json');

// Get the raw POST body
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    // Get webhook secret
    $webhookSecret = getStripeWebhookSecret();
    
    if (empty($webhookSecret)) {
        logStripe("Webhook secret not configured - skipping signature verification", 'warning');
        $event = json_decode($payload, true);
    } else {
        // Verify webhook signature
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sig_header,
            $webhookSecret
        );
    }
    
    if (!$event || !isset($event['type'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload']);
        exit;
    }
    
    $eventId = $event['id'] ?? 'unknown';
    $eventType = $event['type'];
    
    logStripe("Received webhook: {$eventType} (ID: {$eventId})");
    
    // Check idempotency - if already processed, return success
    if (event_already_processed($eventId)) {
        logStripe("Event {$eventId} already processed, skipping");
        http_response_code(200);
        echo json_encode(['received' => true, 'note' => 'Already processed']);
        exit;
    }
    
    // Process event based on type
    $processed = false;
    
    switch ($eventType) {
        case 'checkout.session.completed':
            $processed = handle_checkout_session_completed($event);
            break;
            
        case 'payment_intent.succeeded':
            $processed = handle_payment_intent_succeeded($event);
            break;
            
        case 'payment_intent.payment_failed':
            $processed = handle_payment_intent_failed($event);
            break;
            
        case 'charge.refunded':
            $processed = handle_charge_refunded($event);
            break;
            
        case 'refund.succeeded':
            $processed = handle_refund_succeeded($event);
            break;
            
        default:
            logStripe("Unhandled event type: {$eventType}", 'info');
            $processed = true; // Mark as processed to avoid retries
            break;
    }
    
    if ($processed) {
        // Record event as processed
        record_processed_event($eventId, $eventType, $event);
        
        http_response_code(200);
        echo json_encode(['received' => true]);
    } else {
        logStripe("Failed to process event {$eventId}", 'error');
        http_response_code(500);
        echo json_encode(['error' => 'Processing failed']);
    }
    
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    logStripe("Webhook signature verification failed: " . $e->getMessage(), 'error');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
} catch (Exception $e) {
    logStripe("Webhook processing error: " . $e->getMessage(), 'error');
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle checkout.session.completed event
 * Marks order as paid when checkout session completes successfully
 */
function handle_checkout_session_completed(array $event): bool {
    $session = $event['data']['object'] ?? [];
    $sessionId = $session['id'] ?? null;
    $paymentIntentId = $session['payment_intent'] ?? null;
    $paymentStatus = $session['payment_status'] ?? '';
    $amountTotal = $session['amount_total'] ?? 0;
    
    if (!$sessionId) {
        logStripe("No session_id in event", 'error');
        return false;
    }
    
    logStripe("Processing checkout.session.completed: {$sessionId}, payment_status: {$paymentStatus}, amount: {$amountTotal}");
    
    // Get order ID from metadata
    $metadata = $session['metadata'] ?? [];
    $orderId = isset($metadata['order_id']) ? (int)$metadata['order_id'] : null;
    
    if (!$orderId) {
        logStripe("No order_id in Checkout Session metadata", 'error');
        return false;
    }
    
    // Verify order exists
    $order = get_order_by_id($orderId);
    if (!$order) {
        logStripe("Order #{$orderId} not found", 'error');
        return false;
    }
    
    // Update order with session information
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("UPDATE orders SET stripe_checkout_session_id = ? WHERE id = ?");
        $stmt->execute([$sessionId, $orderId]);
    } catch (Exception $e) {
        logStripe("Failed to update order with session ID: " . $e->getMessage(), 'warning');
    }
    
    // If payment is paid, finalize the order
    if ($paymentStatus === 'paid' && $paymentIntentId) {
        logStripe("Payment completed for order #{$orderId}, PaymentIntent: {$paymentIntentId}");
        
        // Update PaymentIntent record if needed
        persist_payment_intent($paymentIntentId, $orderId, $order['order_reference'] ?? null, [
            'amount_minor' => $amountTotal,
            'currency' => $session['currency'] ?? 'usd',
            'status' => 'succeeded',
            'payment_method' => $session['payment_method_types'][0] ?? 'card',
            'customer_id' => $session['customer'] ?? null,
            'metadata' => $metadata,
            'last_payload' => []
        ]);
        
        // Finalize order as paid
        $success = finalize_order_paid($orderId, $paymentIntentId, $amountTotal);
        
        if ($success) {
            // Clear user's cart
            try {
                $cart = new Cart();
                $cart->clearCart($order['user_id']);
                logStripe("Cleared cart for user #{$order['user_id']}");
            } catch (Exception $e) {
                logStripe("Failed to clear cart: " . $e->getMessage(), 'warning');
            }
        }
        
        return $success;
    } else if ($paymentStatus === 'unpaid') {
        logStripe("Checkout session completed but payment is unpaid for order #{$orderId}");
        return true; // Session completed successfully, just not paid yet
    }
    
    return true;
}

/**
 * Handle payment_intent.succeeded event
 * Marks order as paid and finalizes it
 */
function handle_payment_intent_succeeded(array $event): bool {
    $paymentIntent = $event['data']['object'] ?? [];
    $paymentIntentId = $paymentIntent['id'] ?? null;
    $amount = $paymentIntent['amount'] ?? 0;
    $status = $paymentIntent['status'] ?? '';
    
    if (!$paymentIntentId) {
        logStripe("No payment_intent_id in event", 'error');
        return false;
    }
    
    logStripe("Processing payment_intent.succeeded: {$paymentIntentId}, amount: {$amount}, status: {$status}");
    
    // Get order ID from metadata
    $metadata = $paymentIntent['metadata'] ?? [];
    $orderId = isset($metadata['order_id']) ? (int)$metadata['order_id'] : null;
    
    if (!$orderId) {
        logStripe("No order_id in PaymentIntent metadata", 'error');
        return false;
    }
    
    // Verify order exists and amounts match
    $order = get_order_by_id($orderId);
    if (!$order) {
        logStripe("Order #{$orderId} not found", 'error');
        return false;
    }
    
    // Verify amount matches (important security check)
    if ($order['amount_minor'] != $amount) {
        logStripe("Amount mismatch! Order: {$order['amount_minor']}, PI: {$amount}", 'error');
        // Still process but log the discrepancy
    }
    
    // Update PaymentIntent record
    persist_payment_intent($paymentIntentId, $orderId, $order['order_reference'] ?? null, [
        'amount_minor' => $amount,
        'currency' => $paymentIntent['currency'] ?? 'usd',
        'status' => $status,
        'payment_method' => $paymentIntent['payment_method'] ?? null,
        'customer_id' => $paymentIntent['customer'] ?? null,
        'metadata' => $metadata,
        'last_payload' => $paymentIntent
    ]);
    
    // Finalize order as paid
    $success = finalize_order_paid($orderId, $paymentIntentId, $amount);
    
    if ($success) {
        // Save addresses to user_addresses table if present in order
        save_user_addresses_from_order($orderId, $order['user_id']);
        
        // Save payment method if setup_future_usage was set
        $saveForFuture = isset($metadata['save_for_future']) && $metadata['save_for_future'] === 'true';
        if ($saveForFuture && !empty($paymentIntent['payment_method'])) {
            save_user_payment_method($order['user_id'], $paymentIntent['payment_method']);
        }
        
        // Clear user's cart (order is now complete)
        try {
            $cart = new Cart();
            $cart->clearCart($order['user_id']);
            logStripe("Cleared cart for user #{$order['user_id']}");
        } catch (Exception $e) {
            logStripe("Failed to clear cart: " . $e->getMessage(), 'warning');
        }
        
        // TODO: Send order confirmation email
        // sendOrderConfirmation($orderId);
    }
    
    return $success;
}

/**
 * Handle payment_intent.payment_failed event
 * Marks order as failed
 */
function handle_payment_intent_failed(array $event): bool {
    $paymentIntent = $event['data']['object'] ?? [];
    $paymentIntentId = $paymentIntent['id'] ?? null;
    $lastError = $paymentIntent['last_payment_error'] ?? [];
    $errorMessage = $lastError['message'] ?? 'Payment failed';
    
    if (!$paymentIntentId) {
        logStripe("No payment_intent_id in event", 'error');
        return false;
    }
    
    logStripe("Processing payment_intent.payment_failed: {$paymentIntentId}, error: {$errorMessage}");
    
    // Get order ID from metadata
    $metadata = $paymentIntent['metadata'] ?? [];
    $orderId = isset($metadata['order_id']) ? (int)$metadata['order_id'] : null;
    
    if (!$orderId) {
        logStripe("No order_id in PaymentIntent metadata", 'error');
        return false;
    }
    
    // Update PaymentIntent record
    persist_payment_intent($paymentIntentId, $orderId, null, [
        'amount_minor' => $paymentIntent['amount'] ?? 0,
        'currency' => $paymentIntent['currency'] ?? 'usd',
        'status' => $paymentIntent['status'] ?? 'failed',
        'metadata' => $metadata,
        'last_payload' => $paymentIntent
    ]);
    
    // Mark order as failed
    return mark_order_failed($orderId, $errorMessage);
}

/**
 * Handle charge.refunded event
 * Records refund information
 */
function handle_charge_refunded(array $event): bool {
    $charge = $event['data']['object'] ?? [];
    $paymentIntentId = $charge['payment_intent'] ?? null;
    $refunds = $charge['refunds']['data'] ?? [];
    
    if (!$paymentIntentId || empty($refunds)) {
        logStripe("Missing payment_intent or refunds in charge.refunded event", 'error');
        return false;
    }
    
    logStripe("Processing charge.refunded: PI {$paymentIntentId}, " . count($refunds) . " refund(s)");
    
    // Get order by payment intent
    $order = get_order_by_payment_intent($paymentIntentId);
    if (!$order) {
        logStripe("Order not found for PI {$paymentIntentId}", 'error');
        return false;
    }
    
    // Record each refund
    $success = true;
    foreach ($refunds as $refund) {
        $recorded = record_refund(
            $refund['id'],
            $paymentIntentId,
            $order['id'],
            $refund['amount'],
            $refund['currency'],
            $refund['status'],
            $refund['reason'] ?? null
        );
        $success = $success && $recorded;
    }
    
    return $success;
}

/**
 * Handle refund.succeeded event
 * Updates refund status
 */
function handle_refund_succeeded(array $event): bool {
    $refund = $event['data']['object'] ?? [];
    $refundId = $refund['id'] ?? null;
    $paymentIntentId = $refund['payment_intent'] ?? null;
    
    if (!$refundId || !$paymentIntentId) {
        logStripe("Missing refund_id or payment_intent in refund.succeeded event", 'error');
        return false;
    }
    
    logStripe("Processing refund.succeeded: {$refundId} for PI {$paymentIntentId}");
    
    // Get order by payment intent
    $order = get_order_by_payment_intent($paymentIntentId);
    if (!$order) {
        logStripe("Order not found for PI {$paymentIntentId}", 'error');
        return false;
    }
    
    // Record refund
    return record_refund(
        $refundId,
        $paymentIntentId,
        $order['id'],
        $refund['amount'],
        $refund['currency'],
        'succeeded',
        $refund['reason'] ?? null
    );
}

/**
 * Save user addresses from order to user_addresses table
 * 
 * @param int $orderId Order ID
 * @param int $userId User ID
 */
function save_user_addresses_from_order(int $orderId, int $userId): void {
    try {
        $db = db();
        
        // Get order with addresses
        $stmt = $db->prepare("SELECT billing_address, shipping_address FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            return;
        }
        
        // Save billing address if present
        if (!empty($order['billing_address'])) {
            $billingData = json_decode($order['billing_address'], true);
            if ($billingData && isset($billingData['name'], $billingData['address'])) {
                $stmt = $db->prepare("
                    INSERT INTO user_addresses (
                        user_id, address_type, full_name, phone, address_line, 
                        city, state, postal_code, country
                    ) VALUES (?, 'billing', ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $billingData['name'],
                    $billingData['phone'] ?? null,
                    $billingData['address']['line1'] ?? null,
                    $billingData['address']['city'] ?? null,
                    $billingData['address']['state'] ?? null,
                    $billingData['address']['postal_code'] ?? null,
                    $billingData['address']['country'] ?? null
                ]);
                logStripe("Saved billing address for user #{$userId}");
            }
        }
        
        // Save shipping address if present and different from billing
        if (!empty($order['shipping_address']) && $order['shipping_address'] !== $order['billing_address']) {
            $shippingData = json_decode($order['shipping_address'], true);
            if ($shippingData && isset($shippingData['name'], $shippingData['address'])) {
                $stmt = $db->prepare("
                    INSERT INTO user_addresses (
                        user_id, address_type, full_name, phone, address_line, 
                        city, state, postal_code, country
                    ) VALUES (?, 'shipping', ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $shippingData['name'],
                    $shippingData['phone'] ?? null,
                    $shippingData['address']['line1'] ?? null,
                    $shippingData['address']['city'] ?? null,
                    $shippingData['address']['state'] ?? null,
                    $shippingData['address']['postal_code'] ?? null,
                    $shippingData['address']['country'] ?? null
                ]);
                logStripe("Saved shipping address for user #{$userId}");
            }
        }
    } catch (Exception $e) {
        logStripe("Failed to save user addresses: " . $e->getMessage(), 'warning');
    }
}

/**
 * Save user payment method from Stripe
 * 
 * @param int $userId User ID
 * @param string $paymentMethodId Stripe payment method ID
 */
function save_user_payment_method(int $userId, string $paymentMethodId): void {
    try {
        // Initialize Stripe
        $stripe = initStripe();
        
        // Retrieve payment method details from Stripe
        $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethodId);
        
        if ($paymentMethod->type === 'card' && isset($paymentMethod->card)) {
            $db = db();
            
            // Check if payment method already exists
            $checkStmt = $db->prepare("
                SELECT id FROM user_payment_methods 
                WHERE user_id = ? AND stripe_payment_method_id = ?
            ");
            $checkStmt->execute([$userId, $paymentMethodId]);
            
            if (!$checkStmt->fetch()) {
                // Insert new payment method
                $stmt = $db->prepare("
                    INSERT INTO user_payment_methods (
                        user_id, stripe_payment_method_id, brand, last4, 
                        exp_month, exp_year, is_default
                    ) VALUES (?, ?, ?, ?, ?, ?, 0)
                ");
                
                $stmt->execute([
                    $userId,
                    $paymentMethodId,
                    $paymentMethod->card->brand,
                    $paymentMethod->card->last4,
                    $paymentMethod->card->exp_month,
                    $paymentMethod->card->exp_year
                ]);
                
                logStripe("Saved payment method {$paymentMethodId} for user #{$userId}");
            }
        }
    } catch (Exception $e) {
        logStripe("Failed to save payment method: " . $e->getMessage(), 'warning');
    }
}
