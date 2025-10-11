<?php
declare(strict_types=1);

/**
 * Order and Payment Helper Functions
 * Supports Stripe Payment Intents integration
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/stripe/init_stripe.php';

/**
 * Create internal order with cart items
 * 
 * @param array $orderData Order details (user_id, currency, amounts in minor units)
 * @param array $cartItems Cart items to add to order
 * @return int Created order ID
 * @throws Exception on failure
 */
function create_internal_order(array $orderData, array $cartItems): int {
    $db = db();
    
    try {
        $db->beginTransaction();
        
        // Insert order
        $stmt = $db->prepare("
            INSERT INTO orders (
                user_id, order_number, order_reference, status, 
                currency, amount_minor, tax_minor, shipping_minor, 
                subtotal, tax_amount, shipping_amount, total,
                customer_email, customer_name, billing_address, shipping_address,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        
        $orderNumber = $orderData['order_number'] ?? ('ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)));
        $orderRef = $orderData['order_reference'] ?? null; // Will be generated after insert
        
        // Convert minor units to decimal for legacy fields
        $subtotalDecimal = ($orderData['subtotal_minor'] ?? $orderData['amount_minor'] ?? 0) / 100;
        $taxDecimal = ($orderData['tax_minor'] ?? 0) / 100;
        $shippingDecimal = ($orderData['shipping_minor'] ?? 0) / 100;
        $totalDecimal = $subtotalDecimal + $taxDecimal + $shippingDecimal;
        
        $stmt->execute([
            $orderData['user_id'],
            $orderNumber,
            $orderRef,
            $orderData['status'] ?? 'pending_payment',
            $orderData['currency'] ?? 'usd',
            $orderData['amount_minor'] ?? 0,
            $orderData['tax_minor'] ?? 0,
            $orderData['shipping_minor'] ?? 0,
            $subtotalDecimal,
            $taxDecimal,
            $shippingDecimal,
            $totalDecimal,
            $orderData['customer_email'] ?? null,
            $orderData['customer_name'] ?? null,
            $orderData['billing_address'] ?? null,
            $orderData['shipping_address'] ?? null
        ]);
        
        $orderId = (int)$db->lastInsertId();
        
        // Generate and update order reference
        if (empty($orderRef)) {
            $orderRef = generate_order_reference($orderId);
            $updateStmt = $db->prepare("UPDATE orders SET order_reference = ? WHERE id = ?");
            $updateStmt->execute([$orderRef, $orderId]);
        }
        
        // Insert order items
        if (!empty($cartItems)) {
            $itemStmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, vendor_id, product_name, sku,
                    qty, price, price_minor, subtotal, subtotal_minor
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cartItems as $item) {
                $unitPrice = $item['price'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                $lineSubtotal = $unitPrice * $quantity;
                
                $itemStmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['vendor_id'] ?? null,
                    $item['name'] ?? $item['product_name'] ?? 'Unknown Product',
                    $item['sku'] ?? null,
                    $quantity,
                    $unitPrice,
                    (int)round($unitPrice * 100),
                    $lineSubtotal,
                    (int)round($lineSubtotal * 100)
                ]);
            }
        }
        
        $db->commit();
        logStripe("Created order #{$orderId} ({$orderRef}) with " . count($cartItems) . " items");
        return $orderId;
        
    } catch (Exception $e) {
        $db->rollBack();
        logStripe("Failed to create order: " . $e->getMessage(), 'error');
        throw $e;
    }
}

/**
 * Generate human-readable order reference
 * Format: ORD-YYYYMMDD-######
 * 
 * @param int $orderId Internal order ID
 * @return string Order reference
 */
function generate_order_reference(int $orderId): string {
    return sprintf('ORD-%s-%06d', date('Ymd'), $orderId);
}

/**
 * Find payment intent record by order ID
 * 
 * @param int $orderId Internal order ID
 * @return array|null Payment intent data or null if not found
 */
function find_payment_intent_by_order(int $orderId): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM stripe_payment_intents WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$orderId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Persist PaymentIntent to database
 * 
 * @param string $paymentIntentId Stripe PaymentIntent ID
 * @param int|null $orderId Internal order ID
 * @param string|null $orderRef Order reference
 * @param array $data PaymentIntent data
 * @return bool Success
 */
function persist_payment_intent(string $paymentIntentId, ?int $orderId, ?string $orderRef, array $data): bool {
    $db = db();
    
    try {
        // Check if record exists
        $stmt = $db->prepare("SELECT id FROM stripe_payment_intents WHERE payment_intent_id = ?");
        $stmt->execute([$paymentIntentId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing
            $updateStmt = $db->prepare("
                UPDATE stripe_payment_intents 
                SET order_id = ?, order_reference = ?, amount_minor = ?, currency = ?, 
                    status = ?, client_secret = ?, payment_method = ?, customer_id = ?,
                    metadata = ?, last_payload = ?, updated_at = CURRENT_TIMESTAMP
                WHERE payment_intent_id = ?
            ");
            $updateStmt->execute([
                $orderId,
                $orderRef,
                $data['amount_minor'] ?? 0,
                $data['currency'] ?? 'usd',
                $data['status'] ?? 'unknown',
                $data['client_secret'] ?? null,
                $data['payment_method'] ?? null,
                $data['customer_id'] ?? null,
                json_encode($data['metadata'] ?? []),
                json_encode($data['last_payload'] ?? []),
                $paymentIntentId
            ]);
        } else {
            // Insert new
            $insertStmt = $db->prepare("
                INSERT INTO stripe_payment_intents (
                    payment_intent_id, order_id, order_reference, amount_minor, currency,
                    status, client_secret, payment_method, customer_id, metadata, last_payload
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $paymentIntentId,
                $orderId,
                $orderRef,
                $data['amount_minor'] ?? 0,
                $data['currency'] ?? 'usd',
                $data['status'] ?? 'unknown',
                $data['client_secret'] ?? null,
                $data['payment_method'] ?? null,
                $data['customer_id'] ?? null,
                json_encode($data['metadata'] ?? []),
                json_encode($data['last_payload'] ?? [])
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        logStripe("Failed to persist payment intent {$paymentIntentId}: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Finalize order as paid (called by webhook on payment success)
 * 
 * @param int $orderId Internal order ID
 * @param string $paymentIntentId Stripe PaymentIntent ID
 * @param int $amountMinor Paid amount in minor units
 * @return bool Success
 */
function finalize_order_paid(int $orderId, string $paymentIntentId, int $amountMinor): bool {
    $db = db();
    
    try {
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'pending', 
                payment_status = 'paid',
                stripe_payment_intent_id = ?,
                placed_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'pending_payment'
        ");
        $stmt->execute([$paymentIntentId, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            logStripe("Order #{$orderId} marked as paid (PI: {$paymentIntentId}, amount: {$amountMinor})");
            return true;
        } else {
            logStripe("Order #{$orderId} not updated (already paid or not found)", 'warning');
            return false;
        }
    } catch (Exception $e) {
        logStripe("Failed to finalize order #{$orderId}: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Mark order as failed (called by webhook on payment failure)
 * 
 * @param int $orderId Internal order ID
 * @param string $reason Failure reason
 * @return bool Success
 */
function mark_order_failed(int $orderId, string $reason): bool {
    $db = db();
    
    try {
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = 'failed', 
                payment_status = 'failed',
                notes = CONCAT(COALESCE(notes, ''), '\n', 'Payment failed: ', ?),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$reason, $orderId]);
        
        logStripe("Order #{$orderId} marked as failed: {$reason}", 'warning');
        return true;
    } catch (Exception $e) {
        logStripe("Failed to mark order #{$orderId} as failed: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Record a refund
 * 
 * @param string $refundId Stripe Refund ID
 * @param string $paymentIntentId Stripe PaymentIntent ID
 * @param int $orderId Internal order ID
 * @param int $amountMinor Refunded amount in minor units
 * @param string $currency Currency code
 * @param string $status Refund status
 * @param string|null $reason Refund reason
 * @return bool Success
 */
function record_refund(string $refundId, string $paymentIntentId, int $orderId, int $amountMinor, string $currency, string $status, ?string $reason = null): bool {
    $db = db();
    
    try {
        // Check if refund already recorded
        $stmt = $db->prepare("SELECT id FROM stripe_refunds WHERE refund_id = ?");
        $stmt->execute([$refundId]);
        if ($stmt->fetch()) {
            logStripe("Refund {$refundId} already recorded", 'info');
            return true; // Already exists, consider success
        }
        
        // Insert refund record
        $insertStmt = $db->prepare("
            INSERT INTO stripe_refunds (
                refund_id, payment_intent_id, order_id, amount_minor, currency, status, reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$refundId, $paymentIntentId, $orderId, $amountMinor, $currency, $status, $reason]);
        
        // Update order status if fully refunded
        $updateStmt = $db->prepare("
            UPDATE orders 
            SET status = 'refunded', payment_status = 'refunded', updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $updateStmt->execute([$orderId]);
        
        logStripe("Recorded refund {$refundId} for order #{$orderId}, amount: {$amountMinor} {$currency}");
        return true;
    } catch (Exception $e) {
        logStripe("Failed to record refund {$refundId}: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Check if webhook event has already been processed (idempotency)
 * 
 * @param string $eventId Stripe Event ID
 * @return bool True if already processed
 */
function event_already_processed(string $eventId): bool {
    $db = db();
    $stmt = $db->prepare("SELECT id FROM stripe_events WHERE event_id = ?");
    $stmt->execute([$eventId]);
    return (bool)$stmt->fetch();
}

/**
 * Record processed webhook event (idempotency)
 * 
 * @param string $eventId Stripe Event ID
 * @param string $type Event type
 * @param array|null $payload Full event payload for debugging
 * @return bool Success
 */
function record_processed_event(string $eventId, string $type, ?array $payload = null): bool {
    $db = db();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO stripe_events (event_id, event_type, payload, processed_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$eventId, $type, json_encode($payload ?? [])]);
        return true;
    } catch (Exception $e) {
        // Duplicate key is OK (race condition with idempotency check)
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            return true;
        }
        logStripe("Failed to record event {$eventId}: " . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Find or create Stripe customer for user
 * Creates customer in Stripe if not exists, persists customer ID to users table
 * 
 * @param int $userId Internal user ID
 * @param array $customerInput Customer data (email, name, etc.)
 * @return string|null Stripe Customer ID or null on failure
 */
function find_or_create_stripe_customer_for_user(int $userId, array $customerInput): ?string {
    $db = db();
    
    try {
        // Check if user already has stripe_customer_id
        $stmt = $db->prepare("SELECT stripe_customer_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['stripe_customer_id'])) {
            return $user['stripe_customer_id'];
        }
        
        // Create new Stripe customer
        $stripe = initStripe();
        $customerData = [
            'email' => $customerInput['email'] ?? null,
            'name' => $customerInput['name'] ?? null,
            'metadata' => [
                'user_id' => $userId
            ]
        ];
        
        $customer = $stripe->customers->create($customerData);
        $customerId = $customer->id;
        
        // Persist to database
        $updateStmt = $db->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        $updateStmt->execute([$customerId, $userId]);
        
        logStripe("Created Stripe customer {$customerId} for user #{$userId}");
        return $customerId;
        
    } catch (Exception $e) {
        logStripe("Failed to find/create Stripe customer for user #{$userId}: " . $e->getMessage(), 'error');
        return null;
    }
}

/**
 * Get order by internal ID
 * 
 * @param int $orderId Internal order ID
 * @return array|null Order data or null if not found
 */
function get_order_by_id(int $orderId): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Get order by payment intent ID
 * 
 * @param string $paymentIntentId Stripe PaymentIntent ID
 * @return array|null Order data or null if not found
 */
function get_order_by_payment_intent(string $paymentIntentId): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM orders WHERE stripe_payment_intent_id = ?");
    $stmt->execute([$paymentIntentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}
