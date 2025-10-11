<?php
declare(strict_types=1);

/**
 * Checkout Success Page
 * Displays order confirmation after successful Stripe Checkout payment
 */

// Load environment variables and dependencies
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/bootstrap/simple_env_loader.php';

// Load application initialization
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/stripe/init_stripe.php';

// Require login
Session::requireLogin();

$userId = Session::getUserId();
$sessionId = $_GET['session_id'] ?? '';

$orderDetails = null;
$error = null;

if (empty($sessionId)) {
    $error = 'No checkout session found.';
} else {
    try {
        // Initialize Stripe
        $stripe = initStripe();
        
        // Retrieve the checkout session
        $checkoutSession = $stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent', 'customer']
        ]);
        
        // Verify the session belongs to the current user
        $orderIdFromSession = $checkoutSession->metadata['order_id'] ?? null;
        $userIdFromSession = $checkoutSession->metadata['user_id'] ?? null;
        
        if ($userIdFromSession != $userId) {
            throw new Exception('Unauthorized access to this order.');
        }
        
        // Get order details
        if ($orderIdFromSession) {
            // Fetch order from database
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$orderIdFromSession, $userId]);
            $orderDetails = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // Clear cart after successful payment
        if ($checkoutSession->payment_status === 'paid') {
            $cart = new Cart();
            $cart->clearCart($userId);
        }
        
    } catch (Exception $e) {
        logStripe("Error retrieving checkout session: " . $e->getMessage(), 'error');
        $error = 'Unable to retrieve order details. Please contact support with your order reference.';
    }
}

$page_title = 'Order Confirmation';
includeHeader($page_title);
?>

<style>
.success-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
}

.success-card {
    background: white;
    border-radius: 8px;
    padding: 40px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.success-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 40px;
}

.success-title {
    font-size: 2rem;
    margin-bottom: 10px;
    color: #28a745;
}

.success-message {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 30px;
}

.order-details {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 20px;
    margin: 20px 0;
    text-align: left;
}

.order-detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e0e0e0;
}

.order-detail-row:last-child {
    border-bottom: none;
}

.order-detail-label {
    font-weight: 600;
    color: #333;
}

.order-detail-value {
    color: #666;
}

.btn-primary {
    display: inline-block;
    padding: 12px 30px;
    background: #635bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    margin-top: 20px;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #5046e4;
}

.error-card {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    color: #721c24;
}
</style>

<div class="success-container">
    <?php if ($error): ?>
        <div class="error-card">
            <h2>Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="/account.php" class="btn-primary">Go to My Account</a>
        </div>
    <?php else: ?>
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1 class="success-title">Payment Successful!</h1>
            <p class="success-message">
                Thank you for your order. We've sent a confirmation email to your address.
            </p>
            
            <?php if ($orderDetails): ?>
                <div class="order-details">
                    <div class="order-detail-row">
                        <span class="order-detail-label">Order Number:</span>
                        <span class="order-detail-value"><?php echo htmlspecialchars($orderDetails['order_ref'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="order-detail-row">
                        <span class="order-detail-label">Amount Paid:</span>
                        <span class="order-detail-value">
                            $<?php echo number_format(($orderDetails['amount_minor'] ?? 0) / 100, 2); ?>
                        </span>
                    </div>
                    <div class="order-detail-row">
                        <span class="order-detail-label">Order Status:</span>
                        <span class="order-detail-value"><?php echo ucfirst($orderDetails['status'] ?? 'pending'); ?></span>
                    </div>
                    <div class="order-detail-row">
                        <span class="order-detail-label">Email:</span>
                        <span class="order-detail-value"><?php echo htmlspecialchars($orderDetails['customer_email'] ?? 'N/A'); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <a href="/account.php" class="btn-primary">View My Orders</a>
            <br>
            <a href="/index.php" style="margin-top: 10px; display: inline-block; color: #635bff;">Continue Shopping</a>
        </div>
    <?php endif; ?>
</div>

<?php includeFooter(); ?>
