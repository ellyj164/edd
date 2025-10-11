<?php
/**
 * Payment Gateway Strategy Pattern
 * Supports multiple payment gateways with unified interface
 */

interface PaymentGatewayInterface {
    public function processPayment($amount, $paymentToken, $orderData);
    public function refundPayment($transactionId, $amount, $reason = null);
    public function getTransactionStatus($transactionId);
}

/**
 * Mock Payment Gateway - For testing
 */
class MockPaymentGateway implements PaymentGatewayInterface {
    public function processPayment($amount, $paymentToken, $orderData) {
        // Simulate payment processing
        sleep(1);
        
        // Simulate success/failure (90% success rate)
        $success = rand(1, 10) <= 9;
        
        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'MOCK_' . strtoupper(uniqid()),
                'method' => 'mock',
                'amount' => $amount,
                'status' => 'completed'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Mock payment failed - insufficient funds',
                'code' => 'INSUFFICIENT_FUNDS'
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        return [
            'success' => true,
            'refund_id' => 'REFUND_' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function getTransactionStatus($transactionId) {
        return [
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'USD'
        ];
    }
}

/**
 * Stripe Payment Gateway - Using Official stripe-php Library
 * Implements PaymentIntents API (modern approach)
 */
class StripePaymentGateway implements PaymentGatewayInterface {
    private $secretKey;
    
    public function __construct() {
        $this->secretKey = STRIPE_SECRET_KEY;
        if (empty($this->secretKey)) {
            throw new Exception('Stripe secret key not configured');
        }
        
        // Initialize Stripe API with secret key
        \Stripe\Stripe::setApiKey($this->secretKey);
    }
    
    /**
     * Create a PaymentIntent for client-side confirmation
     * This should be called via AJAX before form submission
     */
    public function createPaymentIntent($amount, $orderData) {
        try {
            // Convert amount to cents (Stripe uses smallest currency unit)
            $amountCents = (int) round($amount * 100);
            
            $params = [
                'amount' => $amountCents,
                'currency' => 'usd',
                'description' => 'Order #' . ($orderData['order_number'] ?? 'Unknown'),
                'metadata' => [
                    'order_id' => $orderData['id'] ?? null,
                    'order_number' => $orderData['order_number'] ?? null,
                    'customer_email' => $orderData['customer_email'] ?? null
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ];
            
            $paymentIntent = \Stripe\PaymentIntent::create($params);
            
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment using PaymentIntent (called after client confirms)
     * $paymentToken is actually the PaymentIntent ID or PaymentMethod ID
     */
    public function processPayment($amount, $paymentToken, $orderData) {
        try {
            // If paymentToken is a PaymentIntent ID, retrieve it
            if (strpos($paymentToken, 'pi_') === 0) {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentToken);
                
                if ($paymentIntent->status === 'succeeded') {
                    return [
                        'success' => true,
                        'transaction_id' => $paymentIntent->id,
                        'method' => 'stripe',
                        'amount' => $amount,
                        'status' => 'completed'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Payment not confirmed',
                        'code' => 'PAYMENT_NOT_CONFIRMED'
                    ];
                }
            }
            
            // If paymentToken is a PaymentMethod ID, create and confirm PaymentIntent
            if (strpos($paymentToken, 'pm_') === 0) {
                $amountCents = (int) round($amount * 100);
                
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amountCents,
                    'currency' => 'usd',
                    'payment_method' => $paymentToken,
                    'confirm' => true,
                    'description' => 'Order #' . ($orderData['order_number'] ?? 'Unknown'),
                    'metadata' => [
                        'order_id' => $orderData['id'] ?? null,
                        'order_number' => $orderData['order_number'] ?? null,
                        'customer_email' => $orderData['customer_email'] ?? null
                    ],
                    'automatic_payment_methods' => [
                        'enabled' => true,
                        'allow_redirects' => 'never' // Disable redirects for server-side processing
                    ],
                ]);
                
                if ($paymentIntent->status === 'succeeded') {
                    return [
                        'success' => true,
                        'transaction_id' => $paymentIntent->id,
                        'method' => 'stripe',
                        'amount' => $amount,
                        'status' => 'completed'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Payment requires additional action',
                        'code' => 'REQUIRES_ACTION',
                        'payment_intent_id' => $paymentIntent->id
                    ];
                }
            }
            
            // Fallback for legacy or test tokens
            return [
                'success' => false,
                'error' => 'Invalid payment token format',
                'code' => 'INVALID_TOKEN'
            ];
            
        } catch (\Stripe\Exception\CardException $e) {
            // Card was declined
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getError()->code ?? 'CARD_DECLINED'
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Other Stripe API error
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'API_ERROR'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'UNKNOWN_ERROR'
            ];
        }
    }
    
    /**
     * Refund a payment
     * $transactionId should be a PaymentIntent ID
     */
    public function refundPayment($transactionId, $amount, $reason = null) {
        try {
            $params = [
                'payment_intent' => $transactionId,
                'amount' => (int) round($amount * 100), // Convert to cents
            ];
            
            if ($reason) {
                $params['reason'] = $reason;
            }
            
            $refund = \Stripe\Refund::create($params);
            
            if ($refund->status === 'succeeded' || $refund->status === 'pending') {
                return [
                    'success' => true,
                    'refund_id' => $refund->id,
                    'amount' => $amount,
                    'status' => $refund->status
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Refund processing failed',
                    'status' => $refund->status
                ];
            }
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get transaction status
     * $transactionId should be a PaymentIntent ID
     */
    public function getTransactionStatus($transactionId) {
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($transactionId);
            
            return [
                'status' => $paymentIntent->status,
                'amount' => ($paymentIntent->amount ?? 0) / 100,
                'currency' => strtoupper($paymentIntent->currency ?? 'USD')
            ];
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'amount' => 0,
                'currency' => 'USD'
            ];
        }
    }
}

/**
 * Flutterwave Payment Gateway (for Mobile Money)
 */
class FlutterwavePaymentGateway implements PaymentGatewayInterface {
    private $publicKey;
    private $secretKey;
    
    public function __construct() {
        $this->publicKey = $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? '';
        $this->secretKey = $_ENV['FLUTTERWAVE_SECRET_KEY'] ?? '';
        
        if (empty($this->publicKey) || empty($this->secretKey)) {
            throw new Exception('Flutterwave credentials not configured');
        }
    }
    
    public function processPayment($amount, $paymentToken, $orderData) {
        // Flutterwave Rave API integration would be implemented here
        
        return [
            'success' => true,
            'transaction_id' => 'FLW_' . strtoupper(uniqid()),
            'method' => 'flutterwave',
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        return [
            'success' => true,
            'refund_id' => 'FLW_REFUND_' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function getTransactionStatus($transactionId) {
        return [
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'USD'
        ];
    }
}

/**
 * Payment Gateway Factory
 */
class PaymentGatewayFactory {
    public static function create($gateway = null) {
        $gateway = $gateway ?: (defined('PAYMENT_GATEWAY') ? PAYMENT_GATEWAY : 'stripe');
        
        switch ($gateway) {
            case 'stripe':
                return new StripePaymentGateway();
            case 'mock':
            default:
                return new MockPaymentGateway();
        }
    }
    
    /**
     * Get all available and configured payment gateways
     * Returns array of gateway info: ['id', 'name', 'description', 'icon', 'enabled']
     */
    public static function getAvailableGateways() {
        $gateways = [];
        
        // Stripe - the only payment gateway
        if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY) && STRIPE_SECRET_KEY !== '') {
            $gateways[] = [
                'id' => 'stripe',
                'name' => 'Credit/Debit Card',
                'description' => 'Pay securely with Stripe',
                'icon' => '💳',
                'enabled' => true
            ];
        }
        
        return $gateways;
    }
}

/**
 * Main payment processing function
 */
function processPayment($orderId, $paymentMethodId, $amount, $gateway = null) {
    try {
        $paymentToken = new PaymentToken();
        $order = new Order();
        $transaction = new Transaction();
        
        // Get payment method details
        $paymentMethod = $paymentToken->find($paymentMethodId);
        if (!$paymentMethod) {
            throw new Exception('Payment method not found');
        }
        
        // Get order details
        $orderData = $order->find($orderId);
        if (!$orderData) {
            throw new Exception('Order not found');
        }
        
        // Get appropriate gateway
        $gatewayInstance = PaymentGatewayFactory::create($gateway);
        
        // Process payment
        $result = $gatewayInstance->processPayment($amount, $paymentMethod['token'], $orderData);
        
        // Record transaction
        if ($result['success']) {
            $transaction->create([
                'order_id' => $orderId,
                'gateway' => $result['method'],
                'transaction_id' => $result['transaction_id'],
                'amount' => $amount,
                'status' => $result['status'],
                'response_data' => json_encode($result)
            ]);
        }
        
        return $result;
        
    } catch (Exception $e) {
        Logger::error("Payment processing error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmation($orderId) {
    try {
        $order = new Order();
        $user = new User();
        
        $orderData = $order->find($orderId);
        $userData = $user->find($orderData['user_id']);
        
        $emailData = [
            'to_email' => $userData['email'],
            'to_name' => $userData['first_name'] . ' ' . $userData['last_name'],
            'subject' => 'Order Confirmation - ' . $orderData['order_number'],
            'template' => 'order_confirmation',
            'data' => [
                'order' => $orderData,
                'user' => $userData,
                'order_items' => $order->getOrderItems($orderId)
            ]
        ];
        
        return sendEmail($emailData);
        
    } catch (Exception $e) {
        Logger::error("Failed to send order confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Webhook handler for payment gateway notifications
 */
function handlePaymentWebhook($gateway, $payload) {
    try {
        $transaction = new Transaction();
        $order = new Order();
        
        if ($gateway === 'stripe') {
            $eventType = $payload['type'] ?? '';
            
            if ($eventType === 'payment_intent.succeeded') {
                // PaymentIntent succeeded - payment is confirmed
                $paymentIntent = $payload['data']['object'];
                $paymentIntentId = $paymentIntent['id'];
                $amount = $paymentIntent['amount'] / 100;
                $orderId = $paymentIntent['metadata']['order_id'] ?? null;
                
                if ($orderId) {
                    // Find transaction by PaymentIntent ID
                    $txn = $transaction->findByTransactionId($paymentIntentId);
                    if ($txn) {
                        $transaction->update($txn['id'], ['status' => 'completed']);
                        $order->update($txn['order_id'], [
                            'payment_status' => 'paid', 
                            'status' => 'processing'
                        ]);
                    } else {
                        // Transaction doesn't exist yet, update order directly
                        $order->update($orderId, [
                            'payment_status' => 'paid',
                            'status' => 'processing',
                            'payment_transaction_id' => $paymentIntentId
                        ]);
                    }
                }
                
                error_log("Payment succeeded for PaymentIntent: {$paymentIntentId}");
            } elseif ($eventType === 'payment_intent.payment_failed') {
                // Payment failed
                $paymentIntent = $payload['data']['object'];
                $paymentIntentId = $paymentIntent['id'];
                $orderId = $paymentIntent['metadata']['order_id'] ?? null;
                
                if ($orderId) {
                    $order->update($orderId, [
                        'payment_status' => 'failed',
                        'status' => 'cancelled'
                    ]);
                }
                
                error_log("Payment failed for PaymentIntent: {$paymentIntentId}");
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Webhook processing error: " . $e->getMessage());
        return false;
    }
}