<?php
/**
 * Order Confirmation Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Require login
Session::requireLogin();

// Accept both 'order' (legacy) and 'ref' (new) parameters
$orderNumber = $_GET['ref'] ?? $_GET['order'] ?? '';
if (empty($orderNumber)) {
    redirect('/account.php?tab=orders');
}

$order = new Order();
$user = new User();

// Get order details
$orderData = $order->findByOrderNumber($orderNumber);
if (!$orderData || $orderData['user_id'] != Session::getUserId()) {
    redirect('/account.php?tab=orders&error=order_not_found');
}

// Get order items
$orderItems = $order->getOrderItems($orderData['id']);

$page_title = 'Order Confirmation - ' . $orderNumber;
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Success Message -->
            <div class="text-center mb-5">
                <div class="mb-4">
                    <div style="font-size: 4rem; color: #28a745;">✅</div>
                </div>
                <h1 class="text-success">Order Confirmed!</h1>
                <p class="lead">Thank you for your order. We'll send you a confirmation email shortly.</p>
                <p class="text-muted">
                    Order Number: <strong><?php echo htmlspecialchars($orderData['order_number']); ?></strong><br>
                    Order Date: <?php echo formatDate($orderData['placed_at']); ?>
                </p>
            </div>

            <!-- Order Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>📋 Order Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Order Status</h6>
                            <span class="badge bg-<?php echo $orderData['status'] === 'pending' ? 'warning' : 'success'; ?>">
                                <?php echo ucfirst($orderData['status']); ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Status</h6>
                            <span class="badge bg-<?php echo $orderData['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $orderData['payment_status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>🛍️ Order Items</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <img src="<?php echo getSafeProductImageUrl($item, getProductImageUrl($item['product_image'] ?? 'placeholder.jpg')); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 class="me-3" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                <small class="text-muted">Quantity: <?php echo $item['quantity']; ?></small>
                                <div class="mt-1">
                                    <span class="fw-bold">$<?php echo number_format($item['total'], 2); ?></span>
                                    <small class="text-muted">($<?php echo number_format($item['price'], 2); ?> each)</small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>💰 Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($orderData['subtotal'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span>$<?php echo number_format($orderData['tax_amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping:</span>
                                <span>$<?php echo number_format($orderData['shipping_amount'], 2); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>$<?php echo number_format($orderData['total'], 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shipping Information -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6>📍 Shipping Address</h6>
                        </div>
                        <div class="card-body">
                            <?php 
                            $shippingAddress = json_decode($orderData['shipping_address'], true);
                            if ($shippingAddress):
                            ?>
                                <address class="mb-0">
                                    <strong><?php echo htmlspecialchars($shippingAddress['first_name'] . ' ' . $shippingAddress['last_name']); ?></strong><br>
                                    <?php echo htmlspecialchars($shippingAddress['address_line1']); ?><br>
                                    <?php if (!empty($shippingAddress['address_line2'])): ?>
                                        <?php echo htmlspecialchars($shippingAddress['address_line2']); ?><br>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($shippingAddress['city'] . ', ' . $shippingAddress['state'] . ' ' . $shippingAddress['postal_code']); ?><br>
                                    <?php if (!empty($shippingAddress['phone'])): ?>
                                        📞 <?php echo htmlspecialchars($shippingAddress['phone']); ?>
                                    <?php endif; ?>
                                </address>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6>💳 Payment Method</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">
                                <?php if ($orderData['payment_method']): ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($orderData['payment_method']); ?></span><br>
                                    <?php if ($orderData['payment_transaction_id']): ?>
                                        <small class="text-muted">
                                            Transaction ID: <?php echo htmlspecialchars($orderData['payment_transaction_id']); ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Payment pending</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- What's Next -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>🚀 What's Next?</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="mb-2" style="font-size: 2rem;">📧</div>
                            <h6>Confirmation Email</h6>
                            <p class="small text-muted">You'll receive an email confirmation with your order details.</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="mb-2" style="font-size: 2rem;">📦</div>
                            <h6>Processing</h6>
                            <p class="small text-muted">Your order will be processed and prepared for shipping.</p>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="mb-2" style="font-size: 2rem;">🚚</div>
                            <h6>Shipping</h6>
                            <p class="small text-muted">We'll send tracking information when your order ships.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-md-6">
                    <a href="/account.php?tab=orders" class="btn btn-outline-primary w-100">
                        📋 View All Orders
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/products.php" class="btn btn-primary w-100">
                        🛍️ Continue Shopping
                    </a>
                </div>
            </div>

            <!-- Support Information -->
            <div class="alert alert-info mt-4">
                <h6>Need Help?</h6>
                <p class="mb-2">If you have questions about your order, please contact our customer support team:</p>
                <ul class="mb-0">
                    <li>📧 Email: <a href="mailto:support@duns1.fezalogistics.com">support@duns1.fezalogistics.com</a></li>
                    <li>💬 Live Chat: Available 24/7 on our website</li>
                    <li>📞 Phone: +1 (555) 123-4567</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>