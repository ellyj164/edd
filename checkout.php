<?php
declare(strict_types=1);

/**
 * Checkout Process - Stripe Payment Intents Integration
 * Modern, secure checkout with Stripe Elements
 */

// CRITICAL: Load environment variables FIRST (before any other code)
// This ensures .env is parsed before Stripe or any other library initializes
// The env loader is idempotent, so it's safe even though init.php also loads it
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/bootstrap/simple_env_loader.php';

// Load application initialization (includes .env loading)
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/stripe/init_stripe.php';
require_once __DIR__ . '/includes/geoip_service.php';
require_once __DIR__ . '/includes/currency_service.php';

// Require login
Session::requireLogin();

$userId = Session::getUserId();
$cart = new Cart();
$user = new User();

// Detect user country and currency
$detectedCountry = GeoIPService::detectCountry();
$_SESSION['detected_country'] = $detectedCountry;

$currencyService = new CurrencyService();
$detectedCurrency = $currencyService->detectCurrency($detectedCountry);
$_SESSION['detected_currency'] = $detectedCurrency;

// Get cart items
$cartItems = $cart->getCartItems($userId);

// Validate cart is not empty
if (empty($cartItems)) {
    redirect('/cart.php?error=empty_cart');
}

// Get user data
$userData = $user->find($userId);
$userEmail = $userData['email'] ?? '';
$userName = $userData['username'] ?? $userData['first_name'] ?? 'Customer';

// Calculate totals server-side
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 1);
}

// Calculate tax and shipping
$taxRate = 0.08; // 8% - adjust based on your jurisdiction
$shippingCost = $subtotal >= 50 ? 0 : 5.99; // Free shipping over $50
$taxAmount = $subtotal * $taxRate;
$total = $subtotal + $taxAmount + $shippingCost;

// Store cart total in session for server-side validation
$_SESSION['cart_total_cents'] = (int)round($total * 100);

// Get Stripe publishable key
$stripePublishableKey = getStripePublishableKey();
$stripeMode = getStripeMode();

// Post-initialization verification (defensive check)
$appEnv = defined('APP_ENV') ? APP_ENV : env('APP_ENV', 'production');
$explicitStripeMode = env('STRIPE_MODE');
if ($appEnv === 'production' && $explicitStripeMode === 'live' && $stripeMode !== 'live') {
    error_log("[STRIPE][DIAGNOSTIC][ERROR] Mode mismatch detected: APP_ENV=production, STRIPE_MODE env var=live, but computed mode={$stripeMode}. Check env var loading and key configuration.");
}

$page_title = 'Checkout';
includeHeader($page_title);
?>

<style>
/* Modern Checkout Styles */
.checkout-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.checkout-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 30px;
    margin-top: 30px;
}

@media (max-width: 968px) {
    .checkout-grid {
        grid-template-columns: 1fr;
    }
}

.checkout-section {
    background: white;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: #333;
}

.section-subtitle {
    font-size: 1.2rem;
    font-weight: 600;
    margin-top: 30px;
    margin-bottom: 15px;
    color: #555;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.section-subtitle:first-of-type {
    margin-top: 20px;
    padding-top: 0;
    border-top: none;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    font-weight: 500;
    margin-bottom: 8px;
    color: #555;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: #635bff;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: normal;
}

.checkbox-label input[type="checkbox"] {
    width: auto;
    cursor: pointer;
}

.order-summary {
    position: sticky;
    top: 20px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.summary-item.total {
    font-weight: 600;
    font-size: 1.25rem;
    border-top: 2px solid #333;
    border-bottom: none;
    padding-top: 20px;
    margin-top: 10px;
}

.cart-item {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.cart-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    background: #f5f5f5;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-weight: 500;
    margin-bottom: 5px;
}

.cart-item-qty {
    color: #666;
    font-size: 0.9rem;
}

.cart-item-price {
    font-weight: 600;
    color: #333;
}

.btn-primary {
    width: 100%;
    padding: 16px;
    background: #635bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
    background: #5046e4;
}

.btn-primary:disabled {
    background: #aaa;
    cursor: not-allowed;
}

.btn-secondary {
    padding: 12px 20px;
    background: #f7f7f7;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-secondary:hover:not(:disabled) {
    background: #e8e8e8;
    border-color: #ccc;
}

.btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.loading-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #635bff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.success-message {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    display: none;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.payment-info-box {
    background: #f0f7ff;
    border: 1px solid #b8daff;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 20px;
}

.stripe-element {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    transition: border-color 0.2s;
}

.stripe-element:focus-within {
    border-color: #635bff;
    box-shadow: 0 0 0 3px rgba(99, 91, 255, 0.1);
}

.stripe-element.StripeElement--invalid {
    border-color: #dc3545;
}

.section-subtitle {
    font-size: 1.2rem;
    margin: 25px 0 15px 0;
    color: #333;
}
</style>

<div class="checkout-container" data-stripe-mode="<?php echo htmlspecialchars($stripeMode); ?>">
    <h1>Checkout</h1>

    <div class="checkout-grid">
        <!-- Main Checkout Form -->
        <div class="checkout-section">
            <h2 class="section-title">Payment Information</h2>
            
            <form id="checkout-form">
                <!-- Customer Information -->
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($userEmail); ?>" 
                        required
                    >
                </div>

                <!-- Billing Address Section -->
                <h3 class="section-subtitle">Billing Address</h3>
                
                <div class="form-group">
                    <label class="form-label" for="billing_name">Full Name</label>
                    <input 
                        type="text" 
                        id="billing_name" 
                        name="billing_name" 
                        class="form-input" 
                        value="<?php echo htmlspecialchars($userName); ?>" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="billing_phone">Phone</label>
                    <input 
                        type="tel" 
                        id="billing_phone" 
                        name="billing_phone" 
                        class="form-input" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="billing_line1">Address</label>
                    <input 
                        type="text" 
                        id="billing_line1" 
                        name="billing_line1" 
                        class="form-input" 
                        placeholder="Street address" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="billing_line2">Address Line 2 (Optional)</label>
                    <input 
                        type="text" 
                        id="billing_line2" 
                        name="billing_line2" 
                        class="form-input" 
                        placeholder="Apartment, suite, etc."
                    >
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="billing_city">City</label>
                        <input 
                            type="text" 
                            id="billing_city" 
                            name="billing_city" 
                            class="form-input" 
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="billing_state">State / Province</label>
                        <input 
                            type="text" 
                            id="billing_state" 
                            name="billing_state" 
                            class="form-input" 
                            required
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="billing_postal">Postal Code</label>
                        <input 
                            type="text" 
                            id="billing_postal" 
                            name="billing_postal" 
                            class="form-input" 
                            required
                        >
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="billing_country">Country</label>
                        <select 
                            id="billing_country" 
                            name="billing_country" 
                            class="form-input country-select" 
                            required
                        >
                            <option value="">Select country...</option>
                        </select>
                        <div id="currency-note" class="currency-note"></div>
                    </div>
                </div>

                <!-- Shipping Address Section -->
                <h3 class="section-subtitle">Shipping Address</h3>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="same_as_billing" 
                            name="same_as_billing"
                            checked
                        >
                        Same as billing address
                    </label>
                </div>

                <div id="shipping_fields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label" for="shipping_name">Full Name</label>
                        <input 
                            type="text" 
                            id="shipping_name" 
                            name="shipping_name" 
                            class="form-input"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="shipping_phone">Phone</label>
                        <input 
                            type="tel" 
                            id="shipping_phone" 
                            name="shipping_phone" 
                            class="form-input"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="shipping_line1">Address</label>
                        <input 
                            type="text" 
                            id="shipping_line1" 
                            name="shipping_line1" 
                            class="form-input" 
                            placeholder="Street address"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="shipping_line2">Address Line 2 (Optional)</label>
                        <input 
                            type="text" 
                            id="shipping_line2" 
                            name="shipping_line2" 
                            class="form-input" 
                            placeholder="Apartment, suite, etc."
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="shipping_city">City</label>
                            <input 
                                type="text" 
                                id="shipping_city" 
                                name="shipping_city" 
                                class="form-input"
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shipping_state">State / Province</label>
                            <input 
                                type="text" 
                                id="shipping_state" 
                                name="shipping_state" 
                                class="form-input"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="shipping_postal">Postal Code</label>
                            <input 
                                type="text" 
                                id="shipping_postal" 
                                name="shipping_postal" 
                                class="form-input"
                            >
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="shipping_country">Country</label>
                            <select 
                                id="shipping_country" 
                                name="shipping_country" 
                                class="form-input country-select"
                            >
                                <option value="">Select country...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <h3 class="section-subtitle">Payment Details</h3>
                
                <div class="payment-info-box">
                    <p style="margin: 0 0 10px 0; color: #666;">
                        <strong>🔒 Secure Payment with Stripe</strong>
                    </p>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                        Your payment information is encrypted and secure. We never store your card details.
                    </p>
                </div>

                <!-- Card Number Field -->
                <div class="form-group">
                    <label class="form-label" for="card-number">Card Number</label>
                    <div id="card-number-element" class="stripe-element"></div>
                </div>

                <!-- Card Expiry and CVC -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label" for="card-expiry">Expiry Date</label>
                        <div id="card-expiry-element" class="stripe-element"></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="card-cvc">CVC</label>
                        <div id="card-cvc-element" class="stripe-element"></div>
                    </div>
                </div>

                <!-- Coupon Code -->
                <div class="form-group">
                    <label class="form-label" for="coupon_code">Coupon Code (Optional)</label>
                    <div style="display: flex; gap: 10px;">
                        <input 
                            type="text" 
                            id="coupon_code" 
                            name="coupon_code" 
                            class="form-input"
                            placeholder="Enter coupon code"
                            style="flex: 1;"
                        >
                        <button type="button" id="apply-coupon-btn" class="btn-secondary">Apply</button>
                    </div>
                    <div id="coupon-message" style="margin-top: 5px; font-size: 0.9rem;"></div>
                </div>

                <!-- Gift Card -->
                <div class="form-group">
                    <label class="form-label" for="gift_card_code">Gift Card Code (Optional)</label>
                    <div style="display: flex; gap: 10px;">
                        <input 
                            type="text" 
                            id="gift_card_code" 
                            name="gift_card_code" 
                            class="form-input"
                            placeholder="Enter gift card code"
                            style="flex: 1;"
                        >
                        <button type="button" id="apply-giftcard-btn" class="btn-secondary">Apply</button>
                    </div>
                    <div id="giftcard-message" style="margin-top: 5px; font-size: 0.9rem;"></div>
                </div>

                <!-- Save Card Option -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input 
                            type="checkbox" 
                            id="save_card" 
                            name="save_card"
                        >
                        Save this card for future purchases
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="checkout-button" class="btn-primary">
                    <span id="button-text">Pay $<?php echo number_format($total, 2); ?></span>
                    <div class="loading-spinner" id="spinner"></div>
                </button>

                <div id="payment-message" class="error-message" style="display: none;"></div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <img src="https://cdn.jsdelivr.net/gh/stripe/stripe-js/packages/stripe-js/examples/assets/stripe-badge.svg" 
                         alt="Powered by Stripe" 
                         style="height: 30px; opacity: 0.7;">
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="checkout-section order-summary">
            <h2 class="section-title">Order Summary</h2>
            
            <!-- Cart Items -->
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-image">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Product">
                        <?php endif; ?>
                    </div>
                    <div class="cart-item-details">
                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name'] ?? 'Product'); ?></div>
                        <div class="cart-item-qty">Qty: <?php echo (int)$item['quantity']; ?></div>
                    </div>
                    <div class="cart-item-price">
                        $<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 2); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Totals -->
            <div class="summary-item">
                <span>Subtotal</span>
                <span>$<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="summary-item">
                <span>Tax (<?php echo ($taxRate * 100); ?>%)</span>
                <span>$<?php echo number_format($taxAmount, 2); ?></span>
            </div>
            <div class="summary-item">
                <span>Shipping</span>
                <span><?php echo $shippingCost > 0 ? '$' . number_format($shippingCost, 2) : 'FREE'; ?></span>
            </div>
            <div class="summary-item total">
                <span>Total</span>
                <span>$<?php echo number_format($total, 2); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<!-- jQuery (required for Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- International Telephone Input -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<!-- Select2 for searchable country dropdown -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Currency notification styles */
.currency-note {
    background: #e7f3ff;
    border-left: 4px solid #0066cc;
    padding: 12px;
    margin-top: 10px;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #0066cc;
    display: none;
}

/* Select2 customization for country dropdown */
.select2-container--default .select2-selection--single {
    height: auto !important;
    padding: 12px !important;
    border: 1px solid #ddd !important;
    border-radius: 4px !important;
    font-size: 1rem !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    padding: 0 !important;
    line-height: normal !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
}

.select2-results__option {
    padding: 10px 15px !important;
}

.select2-search--dropdown .select2-search__field {
    padding: 8px !important;
    border: 1px solid #ddd !important;
}
</style>

<!-- Embedded Stripe Elements JavaScript -->
<script>
(function() {
    'use strict';
    
    // Server-detected country and currency
    const detectedCountry = '<?php echo addslashes($detectedCountry); ?>';
    const detectedCurrency = '<?php echo addslashes($detectedCurrency); ?>';
    
    // Ensure Stripe.js is loaded
    if (typeof Stripe === 'undefined') {
        console.error('Stripe.js library not loaded');
        const paymentMessage = document.getElementById('payment-message');
        if (paymentMessage) {
            paymentMessage.textContent = 'Payment system not available. Please refresh the page.';
            paymentMessage.style.display = 'block';
        }
        return;
    }
    
    // Get Stripe publishable key from page
    const stripePublishableKey = '<?php echo addslashes($stripePublishableKey); ?>';
    
    console.log('Stripe publishable key:', stripePublishableKey ? 'Present' : 'Missing');
    
    if (!stripePublishableKey) {
        console.error('Stripe publishable key not found');
        const paymentMessage = document.getElementById('payment-message');
        if (paymentMessage) {
            paymentMessage.textContent = 'Payment system configuration error. Please contact support.';
            paymentMessage.style.display = 'block';
        }
        return;
    }
    
    // Initialize Stripe
    console.log('Initializing Stripe...');
    const stripe = Stripe(stripePublishableKey);
    const elements = stripe.elements();
    console.log('Stripe initialized successfully');
    
    // Create card elements with styling
    const elementStyles = {
        base: {
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            fontSmoothing: 'antialiased',
            fontSize: '16px',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#dc3545',
            iconColor: '#dc3545'
        }
    };
    
    const elementClasses = {
        focus: 'focused',
        empty: 'empty',
        invalid: 'invalid',
    };
    
    // Create individual card elements
    console.log('Creating Stripe card elements...');
    const cardNumber = elements.create('cardNumber', {
        style: elementStyles,
        classes: elementClasses
    });
    const cardExpiry = elements.create('cardExpiry', {
        style: elementStyles,
        classes: elementClasses
    });
    const cardCvc = elements.create('cardCvc', {
        style: elementStyles,
        classes: elementClasses
    });
    console.log('Stripe card elements created');
    
    // Mount elements to DOM with error handling
    try {
        console.log('Mounting Stripe elements...');
        cardNumber.mount('#card-number-element');
        console.log('Card number element mounted');
        cardExpiry.mount('#card-expiry-element');
        console.log('Card expiry element mounted');
        cardCvc.mount('#card-cvc-element');
        console.log('Card CVC element mounted');
        console.log('All Stripe elements mounted successfully');
    } catch (error) {
        console.error('Error mounting Stripe elements:', error);
        const paymentMessage = document.getElementById('payment-message');
        if (paymentMessage) {
            paymentMessage.textContent = 'Error initializing payment form. Please refresh the page.';
            paymentMessage.style.display = 'block';
        }
        return;
    }
    
    // Handle real-time validation errors
    cardNumber.on('change', function(event) {
        displayError(event);
    });
    
    cardExpiry.on('change', function(event) {
        displayError(event);
    });
    
    cardCvc.on('change', function(event) {
        displayError(event);
    });
    
    function displayError(event) {
        const displayError = document.getElementById('payment-message');
        if (event.error) {
            displayError.textContent = event.error.message;
            displayError.style.display = 'block';
        } else {
            displayError.textContent = '';
            displayError.style.display = 'none';
        }
    }
    
    // Initialize intl-tel-input for phone fields
    let billingPhoneInput = null;
    const billingPhoneField = document.getElementById('billing_phone');
    
    if (billingPhoneField && window.intlTelInput) {
        billingPhoneInput = window.intlTelInput(billingPhoneField, {
            initialCountry: 'us',
            preferredCountries: ['us', 'rw', 'ca', 'gb', 'au', 'de', 'fr'],
            separateDialCode: true,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        });
    }
    
    // Comprehensive country list with flags, phone codes, and currencies
    const countries = [
        { code: 'AF', name: 'Afghanistan', flag: '🇦🇫', phone: '+93', currency: 'USD' },
        { code: 'AL', name: 'Albania', flag: '🇦🇱', phone: '+355', currency: 'USD' },
        { code: 'DZ', name: 'Algeria', flag: '🇩🇿', phone: '+213', currency: 'USD' },
        { code: 'AD', name: 'Andorra', flag: '🇦🇩', phone: '+376', currency: 'EUR' },
        { code: 'AO', name: 'Angola', flag: '🇦🇴', phone: '+244', currency: 'USD' },
        { code: 'AG', name: 'Antigua and Barbuda', flag: '🇦🇬', phone: '+1268', currency: 'USD' },
        { code: 'AR', name: 'Argentina', flag: '🇦🇷', phone: '+54', currency: 'USD' },
        { code: 'AM', name: 'Armenia', flag: '🇦🇲', phone: '+374', currency: 'USD' },
        { code: 'AU', name: 'Australia', flag: '🇦🇺', phone: '+61', currency: 'USD' },
        { code: 'AT', name: 'Austria', flag: '🇦🇹', phone: '+43', currency: 'EUR' },
        { code: 'AZ', name: 'Azerbaijan', flag: '🇦🇿', phone: '+994', currency: 'USD' },
        { code: 'BS', name: 'Bahamas', flag: '🇧🇸', phone: '+1242', currency: 'USD' },
        { code: 'BH', name: 'Bahrain', flag: '🇧🇭', phone: '+973', currency: 'USD' },
        { code: 'BD', name: 'Bangladesh', flag: '🇧🇩', phone: '+880', currency: 'USD' },
        { code: 'BB', name: 'Barbados', flag: '🇧🇧', phone: '+1246', currency: 'USD' },
        { code: 'BY', name: 'Belarus', flag: '🇧🇾', phone: '+375', currency: 'USD' },
        { code: 'BE', name: 'Belgium', flag: '🇧🇪', phone: '+32', currency: 'EUR' },
        { code: 'BZ', name: 'Belize', flag: '🇧🇿', phone: '+501', currency: 'USD' },
        { code: 'BJ', name: 'Benin', flag: '🇧🇯', phone: '+229', currency: 'USD' },
        { code: 'BT', name: 'Bhutan', flag: '🇧🇹', phone: '+975', currency: 'USD' },
        { code: 'BO', name: 'Bolivia', flag: '🇧🇴', phone: '+591', currency: 'USD' },
        { code: 'BA', name: 'Bosnia and Herzegovina', flag: '🇧🇦', phone: '+387', currency: 'USD' },
        { code: 'BW', name: 'Botswana', flag: '🇧🇼', phone: '+267', currency: 'USD' },
        { code: 'BR', name: 'Brazil', flag: '🇧🇷', phone: '+55', currency: 'USD' },
        { code: 'BN', name: 'Brunei', flag: '🇧🇳', phone: '+673', currency: 'USD' },
        { code: 'BG', name: 'Bulgaria', flag: '🇧🇬', phone: '+359', currency: 'EUR' },
        { code: 'BF', name: 'Burkina Faso', flag: '🇧🇫', phone: '+226', currency: 'USD' },
        { code: 'BI', name: 'Burundi', flag: '🇧🇮', phone: '+257', currency: 'USD' },
        { code: 'KH', name: 'Cambodia', flag: '🇰🇭', phone: '+855', currency: 'USD' },
        { code: 'CM', name: 'Cameroon', flag: '🇨🇲', phone: '+237', currency: 'USD' },
        { code: 'CA', name: 'Canada', flag: '🇨🇦', phone: '+1', currency: 'USD' },
        { code: 'CV', name: 'Cape Verde', flag: '🇨🇻', phone: '+238', currency: 'USD' },
        { code: 'CF', name: 'Central African Republic', flag: '🇨🇫', phone: '+236', currency: 'USD' },
        { code: 'TD', name: 'Chad', flag: '🇹🇩', phone: '+235', currency: 'USD' },
        { code: 'CL', name: 'Chile', flag: '🇨🇱', phone: '+56', currency: 'USD' },
        { code: 'CN', name: 'China', flag: '🇨🇳', phone: '+86', currency: 'USD' },
        { code: 'CO', name: 'Colombia', flag: '🇨🇴', phone: '+57', currency: 'USD' },
        { code: 'KM', name: 'Comoros', flag: '🇰🇲', phone: '+269', currency: 'USD' },
        { code: 'CG', name: 'Congo', flag: '🇨🇬', phone: '+242', currency: 'USD' },
        { code: 'CR', name: 'Costa Rica', flag: '🇨🇷', phone: '+506', currency: 'USD' },
        { code: 'HR', name: 'Croatia', flag: '🇭🇷', phone: '+385', currency: 'EUR' },
        { code: 'CU', name: 'Cuba', flag: '🇨🇺', phone: '+53', currency: 'USD' },
        { code: 'CY', name: 'Cyprus', flag: '🇨🇾', phone: '+357', currency: 'EUR' },
        { code: 'CZ', name: 'Czech Republic', flag: '🇨🇿', phone: '+420', currency: 'EUR' },
        { code: 'DK', name: 'Denmark', flag: '🇩🇰', phone: '+45', currency: 'EUR' },
        { code: 'DJ', name: 'Djibouti', flag: '🇩🇯', phone: '+253', currency: 'USD' },
        { code: 'DM', name: 'Dominica', flag: '🇩🇲', phone: '+1767', currency: 'USD' },
        { code: 'DO', name: 'Dominican Republic', flag: '🇩🇴', phone: '+1', currency: 'USD' },
        { code: 'EC', name: 'Ecuador', flag: '🇪🇨', phone: '+593', currency: 'USD' },
        { code: 'EG', name: 'Egypt', flag: '🇪🇬', phone: '+20', currency: 'USD' },
        { code: 'SV', name: 'El Salvador', flag: '🇸🇻', phone: '+503', currency: 'USD' },
        { code: 'GQ', name: 'Equatorial Guinea', flag: '🇬🇶', phone: '+240', currency: 'USD' },
        { code: 'ER', name: 'Eritrea', flag: '🇪🇷', phone: '+291', currency: 'USD' },
        { code: 'EE', name: 'Estonia', flag: '🇪🇪', phone: '+372', currency: 'EUR' },
        { code: 'ET', name: 'Ethiopia', flag: '🇪🇹', phone: '+251', currency: 'USD' },
        { code: 'FJ', name: 'Fiji', flag: '🇫🇯', phone: '+679', currency: 'USD' },
        { code: 'FI', name: 'Finland', flag: '🇫🇮', phone: '+358', currency: 'EUR' },
        { code: 'FR', name: 'France', flag: '🇫🇷', phone: '+33', currency: 'EUR' },
        { code: 'GA', name: 'Gabon', flag: '🇬🇦', phone: '+241', currency: 'USD' },
        { code: 'GM', name: 'Gambia', flag: '🇬🇲', phone: '+220', currency: 'USD' },
        { code: 'GE', name: 'Georgia', flag: '🇬🇪', phone: '+995', currency: 'USD' },
        { code: 'DE', name: 'Germany', flag: '🇩🇪', phone: '+49', currency: 'EUR' },
        { code: 'GH', name: 'Ghana', flag: '🇬🇭', phone: '+233', currency: 'USD' },
        { code: 'GR', name: 'Greece', flag: '🇬🇷', phone: '+30', currency: 'EUR' },
        { code: 'GD', name: 'Grenada', flag: '🇬🇩', phone: '+1473', currency: 'USD' },
        { code: 'GT', name: 'Guatemala', flag: '🇬🇹', phone: '+502', currency: 'USD' },
        { code: 'GN', name: 'Guinea', flag: '🇬🇳', phone: '+224', currency: 'USD' },
        { code: 'GW', name: 'Guinea-Bissau', flag: '🇬🇼', phone: '+245', currency: 'USD' },
        { code: 'GY', name: 'Guyana', flag: '🇬🇾', phone: '+592', currency: 'USD' },
        { code: 'HT', name: 'Haiti', flag: '🇭🇹', phone: '+509', currency: 'USD' },
        { code: 'HN', name: 'Honduras', flag: '🇭🇳', phone: '+504', currency: 'USD' },
        { code: 'HU', name: 'Hungary', flag: '🇭🇺', phone: '+36', currency: 'EUR' },
        { code: 'IS', name: 'Iceland', flag: '🇮🇸', phone: '+354', currency: 'USD' },
        { code: 'IN', name: 'India', flag: '🇮🇳', phone: '+91', currency: 'USD' },
        { code: 'ID', name: 'Indonesia', flag: '🇮🇩', phone: '+62', currency: 'USD' },
        { code: 'IR', name: 'Iran', flag: '🇮🇷', phone: '+98', currency: 'USD' },
        { code: 'IQ', name: 'Iraq', flag: '🇮🇶', phone: '+964', currency: 'USD' },
        { code: 'IE', name: 'Ireland', flag: '🇮🇪', phone: '+353', currency: 'EUR' },
        { code: 'IL', name: 'Israel', flag: '🇮🇱', phone: '+972', currency: 'USD' },
        { code: 'IT', name: 'Italy', flag: '🇮🇹', phone: '+39', currency: 'EUR' },
        { code: 'JM', name: 'Jamaica', flag: '🇯🇲', phone: '+1876', currency: 'USD' },
        { code: 'JP', name: 'Japan', flag: '🇯🇵', phone: '+81', currency: 'USD' },
        { code: 'JO', name: 'Jordan', flag: '🇯🇴', phone: '+962', currency: 'USD' },
        { code: 'KZ', name: 'Kazakhstan', flag: '🇰🇿', phone: '+7', currency: 'USD' },
        { code: 'KE', name: 'Kenya', flag: '🇰🇪', phone: '+254', currency: 'USD' },
        { code: 'KI', name: 'Kiribati', flag: '🇰🇮', phone: '+686', currency: 'USD' },
        { code: 'KW', name: 'Kuwait', flag: '🇰🇼', phone: '+965', currency: 'USD' },
        { code: 'KG', name: 'Kyrgyzstan', flag: '🇰🇬', phone: '+996', currency: 'USD' },
        { code: 'LA', name: 'Laos', flag: '🇱🇦', phone: '+856', currency: 'USD' },
        { code: 'LV', name: 'Latvia', flag: '🇱🇻', phone: '+371', currency: 'EUR' },
        { code: 'LB', name: 'Lebanon', flag: '🇱🇧', phone: '+961', currency: 'USD' },
        { code: 'LS', name: 'Lesotho', flag: '🇱🇸', phone: '+266', currency: 'USD' },
        { code: 'LR', name: 'Liberia', flag: '🇱🇷', phone: '+231', currency: 'USD' },
        { code: 'LY', name: 'Libya', flag: '🇱🇾', phone: '+218', currency: 'USD' },
        { code: 'LI', name: 'Liechtenstein', flag: '🇱🇮', phone: '+423', currency: 'USD' },
        { code: 'LT', name: 'Lithuania', flag: '🇱🇹', phone: '+370', currency: 'EUR' },
        { code: 'LU', name: 'Luxembourg', flag: '🇱🇺', phone: '+352', currency: 'EUR' },
        { code: 'MK', name: 'Macedonia', flag: '🇲🇰', phone: '+389', currency: 'USD' },
        { code: 'MG', name: 'Madagascar', flag: '🇲🇬', phone: '+261', currency: 'USD' },
        { code: 'MW', name: 'Malawi', flag: '🇲🇼', phone: '+265', currency: 'USD' },
        { code: 'MY', name: 'Malaysia', flag: '🇲🇾', phone: '+60', currency: 'USD' },
        { code: 'MV', name: 'Maldives', flag: '🇲🇻', phone: '+960', currency: 'USD' },
        { code: 'ML', name: 'Mali', flag: '🇲🇱', phone: '+223', currency: 'USD' },
        { code: 'MT', name: 'Malta', flag: '🇲🇹', phone: '+356', currency: 'EUR' },
        { code: 'MH', name: 'Marshall Islands', flag: '🇲🇭', phone: '+692', currency: 'USD' },
        { code: 'MR', name: 'Mauritania', flag: '🇲🇷', phone: '+222', currency: 'USD' },
        { code: 'MU', name: 'Mauritius', flag: '🇲🇺', phone: '+230', currency: 'USD' },
        { code: 'MX', name: 'Mexico', flag: '🇲🇽', phone: '+52', currency: 'USD' },
        { code: 'FM', name: 'Micronesia', flag: '🇫🇲', phone: '+691', currency: 'USD' },
        { code: 'MD', name: 'Moldova', flag: '🇲🇩', phone: '+373', currency: 'USD' },
        { code: 'MC', name: 'Monaco', flag: '🇲🇨', phone: '+377', currency: 'EUR' },
        { code: 'MN', name: 'Mongolia', flag: '🇲🇳', phone: '+976', currency: 'USD' },
        { code: 'ME', name: 'Montenegro', flag: '🇲🇪', phone: '+382', currency: 'EUR' },
        { code: 'MA', name: 'Morocco', flag: '🇲🇦', phone: '+212', currency: 'USD' },
        { code: 'MZ', name: 'Mozambique', flag: '🇲🇿', phone: '+258', currency: 'USD' },
        { code: 'MM', name: 'Myanmar', flag: '🇲🇲', phone: '+95', currency: 'USD' },
        { code: 'NA', name: 'Namibia', flag: '🇳🇦', phone: '+264', currency: 'USD' },
        { code: 'NR', name: 'Nauru', flag: '🇳🇷', phone: '+674', currency: 'USD' },
        { code: 'NP', name: 'Nepal', flag: '🇳🇵', phone: '+977', currency: 'USD' },
        { code: 'NL', name: 'Netherlands', flag: '🇳🇱', phone: '+31', currency: 'EUR' },
        { code: 'NZ', name: 'New Zealand', flag: '🇳🇿', phone: '+64', currency: 'USD' },
        { code: 'NI', name: 'Nicaragua', flag: '🇳🇮', phone: '+505', currency: 'USD' },
        { code: 'NE', name: 'Niger', flag: '🇳🇪', phone: '+227', currency: 'USD' },
        { code: 'NG', name: 'Nigeria', flag: '🇳🇬', phone: '+234', currency: 'USD' },
        { code: 'NO', name: 'Norway', flag: '🇳🇴', phone: '+47', currency: 'USD' },
        { code: 'OM', name: 'Oman', flag: '🇴🇲', phone: '+968', currency: 'USD' },
        { code: 'PK', name: 'Pakistan', flag: '🇵🇰', phone: '+92', currency: 'USD' },
        { code: 'PW', name: 'Palau', flag: '🇵🇼', phone: '+680', currency: 'USD' },
        { code: 'PA', name: 'Panama', flag: '🇵🇦', phone: '+507', currency: 'USD' },
        { code: 'PG', name: 'Papua New Guinea', flag: '🇵🇬', phone: '+675', currency: 'USD' },
        { code: 'PY', name: 'Paraguay', flag: '🇵🇾', phone: '+595', currency: 'USD' },
        { code: 'PE', name: 'Peru', flag: '🇵🇪', phone: '+51', currency: 'USD' },
        { code: 'PH', name: 'Philippines', flag: '🇵🇭', phone: '+63', currency: 'USD' },
        { code: 'PL', name: 'Poland', flag: '🇵🇱', phone: '+48', currency: 'EUR' },
        { code: 'PT', name: 'Portugal', flag: '🇵🇹', phone: '+351', currency: 'EUR' },
        { code: 'QA', name: 'Qatar', flag: '🇶🇦', phone: '+974', currency: 'USD' },
        { code: 'RO', name: 'Romania', flag: '🇷🇴', phone: '+40', currency: 'EUR' },
        { code: 'RU', name: 'Russia', flag: '🇷🇺', phone: '+7', currency: 'USD' },
        { code: 'RW', name: 'Rwanda', flag: '🇷🇼', phone: '+250', currency: 'RWF' },
        { code: 'KN', name: 'Saint Kitts and Nevis', flag: '🇰🇳', phone: '+1869', currency: 'USD' },
        { code: 'LC', name: 'Saint Lucia', flag: '🇱🇨', phone: '+1758', currency: 'USD' },
        { code: 'VC', name: 'Saint Vincent and the Grenadines', flag: '🇻🇨', phone: '+1784', currency: 'USD' },
        { code: 'WS', name: 'Samoa', flag: '🇼🇸', phone: '+685', currency: 'USD' },
        { code: 'SM', name: 'San Marino', flag: '🇸🇲', phone: '+378', currency: 'EUR' },
        { code: 'ST', name: 'Sao Tome and Principe', flag: '🇸🇹', phone: '+239', currency: 'USD' },
        { code: 'SA', name: 'Saudi Arabia', flag: '🇸🇦', phone: '+966', currency: 'USD' },
        { code: 'SN', name: 'Senegal', flag: '🇸🇳', phone: '+221', currency: 'USD' },
        { code: 'RS', name: 'Serbia', flag: '🇷🇸', phone: '+381', currency: 'USD' },
        { code: 'SC', name: 'Seychelles', flag: '🇸🇨', phone: '+248', currency: 'USD' },
        { code: 'SL', name: 'Sierra Leone', flag: '🇸🇱', phone: '+232', currency: 'USD' },
        { code: 'SG', name: 'Singapore', flag: '🇸🇬', phone: '+65', currency: 'USD' },
        { code: 'SK', name: 'Slovakia', flag: '🇸🇰', phone: '+421', currency: 'EUR' },
        { code: 'SI', name: 'Slovenia', flag: '🇸🇮', phone: '+386', currency: 'EUR' },
        { code: 'SB', name: 'Solomon Islands', flag: '🇸🇧', phone: '+677', currency: 'USD' },
        { code: 'SO', name: 'Somalia', flag: '🇸🇴', phone: '+252', currency: 'USD' },
        { code: 'ZA', name: 'South Africa', flag: '🇿🇦', phone: '+27', currency: 'USD' },
        { code: 'KR', name: 'South Korea', flag: '🇰🇷', phone: '+82', currency: 'USD' },
        { code: 'SS', name: 'South Sudan', flag: '🇸🇸', phone: '+211', currency: 'USD' },
        { code: 'ES', name: 'Spain', flag: '🇪🇸', phone: '+34', currency: 'EUR' },
        { code: 'LK', name: 'Sri Lanka', flag: '🇱🇰', phone: '+94', currency: 'USD' },
        { code: 'SD', name: 'Sudan', flag: '🇸🇩', phone: '+249', currency: 'USD' },
        { code: 'SR', name: 'Suriname', flag: '🇸🇷', phone: '+597', currency: 'USD' },
        { code: 'SZ', name: 'Swaziland', flag: '🇸🇿', phone: '+268', currency: 'USD' },
        { code: 'SE', name: 'Sweden', flag: '🇸🇪', phone: '+46', currency: 'EUR' },
        { code: 'CH', name: 'Switzerland', flag: '🇨🇭', phone: '+41', currency: 'USD' },
        { code: 'SY', name: 'Syria', flag: '🇸🇾', phone: '+963', currency: 'USD' },
        { code: 'TW', name: 'Taiwan', flag: '🇹🇼', phone: '+886', currency: 'USD' },
        { code: 'TJ', name: 'Tajikistan', flag: '🇹🇯', phone: '+992', currency: 'USD' },
        { code: 'TZ', name: 'Tanzania', flag: '🇹🇿', phone: '+255', currency: 'USD' },
        { code: 'TH', name: 'Thailand', flag: '🇹🇭', phone: '+66', currency: 'USD' },
        { code: 'TL', name: 'Timor-Leste', flag: '🇹🇱', phone: '+670', currency: 'USD' },
        { code: 'TG', name: 'Togo', flag: '🇹🇬', phone: '+228', currency: 'USD' },
        { code: 'TO', name: 'Tonga', flag: '🇹🇴', phone: '+676', currency: 'USD' },
        { code: 'TT', name: 'Trinidad and Tobago', flag: '🇹🇹', phone: '+1868', currency: 'USD' },
        { code: 'TN', name: 'Tunisia', flag: '🇹🇳', phone: '+216', currency: 'USD' },
        { code: 'TR', name: 'Turkey', flag: '🇹🇷', phone: '+90', currency: 'USD' },
        { code: 'TM', name: 'Turkmenistan', flag: '🇹🇲', phone: '+993', currency: 'USD' },
        { code: 'TV', name: 'Tuvalu', flag: '🇹🇻', phone: '+688', currency: 'USD' },
        { code: 'UG', name: 'Uganda', flag: '🇺🇬', phone: '+256', currency: 'USD' },
        { code: 'UA', name: 'Ukraine', flag: '🇺🇦', phone: '+380', currency: 'USD' },
        { code: 'AE', name: 'United Arab Emirates', flag: '🇦🇪', phone: '+971', currency: 'USD' },
        { code: 'GB', name: 'United Kingdom', flag: '🇬🇧', phone: '+44', currency: 'USD' },
        { code: 'US', name: 'United States', flag: '🇺🇸', phone: '+1', currency: 'USD' },
        { code: 'UY', name: 'Uruguay', flag: '🇺🇾', phone: '+598', currency: 'USD' },
        { code: 'UZ', name: 'Uzbekistan', flag: '🇺🇿', phone: '+998', currency: 'USD' },
        { code: 'VU', name: 'Vanuatu', flag: '🇻🇺', phone: '+678', currency: 'USD' },
        { code: 'VA', name: 'Vatican City', flag: '🇻🇦', phone: '+39', currency: 'EUR' },
        { code: 'VE', name: 'Venezuela', flag: '🇻🇪', phone: '+58', currency: 'USD' },
        { code: 'VN', name: 'Vietnam', flag: '🇻🇳', phone: '+84', currency: 'USD' },
        { code: 'YE', name: 'Yemen', flag: '🇾🇪', phone: '+967', currency: 'USD' },
        { code: 'ZM', name: 'Zambia', flag: '🇿🇲', phone: '+260', currency: 'USD' },
        { code: 'ZW', name: 'Zimbabwe', flag: '🇿🇼', phone: '+263', currency: 'USD' }
    ];
    
    // Function to populate country select
    function populateCountrySelect(selectElement, defaultCode = 'US') {
        if (!selectElement) return;
        
        // Clear existing options except first (placeholder)
        selectElement.innerHTML = '<option value="">Select country...</option>';
        
        // Sort countries alphabetically by name
        const sortedCountries = [...countries].sort((a, b) => a.name.localeCompare(b.name));
        
        // Add all countries with flags
        sortedCountries.forEach(country => {
            const option = document.createElement('option');
            option.value = country.code;
            option.textContent = `${country.flag} ${country.name}`;
            option.dataset.phone = country.phone;
            option.dataset.currency = country.currency;
            if (country.code === defaultCode) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    }
    
    // Function to update phone input when country changes
    function updatePhoneCountryCode(countryCode, phoneInputInstance) {
        if (!phoneInputInstance) return;
        
        const country = countries.find(c => c.code === countryCode);
        if (country && phoneInputInstance.setCountry) {
            phoneInputInstance.setCountry(countryCode.toLowerCase());
        }
    }
    
    // Function to update currency display when country changes
    function updateCurrency(countryCode) {
        const country = countries.find(c => c.code === countryCode);
        if (!country) return;
        
        // Display currency info to user
        const currencyNote = document.getElementById('currency-note');
        if (currencyNote) {
            let currencySymbol = '$';
            if (country.currency === 'EUR') currencySymbol = '€';
            if (country.currency === 'RWF') currencySymbol = 'FRw';
            
            currencyNote.textContent = `Prices will be shown in ${country.currency} (${currencySymbol})`;
            currencyNote.style.display = 'block';
        }
    }
    
    // Populate country selects
    const billingCountrySelect = document.getElementById('billing_country');
    const shippingCountrySelect = document.getElementById('shipping_country');
    
    populateCountrySelect(billingCountrySelect, detectedCountry || 'US');
    populateCountrySelect(shippingCountrySelect, detectedCountry || 'US');
    
    // Initialize Select2 for searchable country dropdowns
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('.country-select').select2({
            placeholder: 'Select a country',
            allowClear: false,
            width: '100%'
        });
    }
    
    // Listen for country selection changes to update phone code and currency
    if (billingCountrySelect) {
        billingCountrySelect.addEventListener('change', function() {
            updatePhoneCountryCode(this.value, billingPhoneInput);
            updateCurrency(this.value);
        });
    }
    
    // Handle form submission
    const form = document.getElementById('checkout-form');
    const checkoutButton = document.getElementById('checkout-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');
    const paymentMessage = document.getElementById('payment-message');
    
    if (!form) {
        console.error('Checkout form not found');
        return;
    }
    
    // Handle "Same as billing" checkbox
    const sameAsBillingCheckbox = document.getElementById('same_as_billing');
    const shippingFields = document.getElementById('shipping_fields');
    
    if (sameAsBillingCheckbox && shippingFields) {
        sameAsBillingCheckbox.addEventListener('change', function() {
            if (this.checked) {
                shippingFields.style.display = 'none';
                // Clear required attributes on shipping fields
                const shippingInputs = shippingFields.querySelectorAll('input, select');
                shippingInputs.forEach(input => {
                    input.removeAttribute('required');
                });
            } else {
                shippingFields.style.display = 'block';
                // Add required attributes back to shipping fields (except optional ones)
                const requiredFields = ['shipping_name', 'shipping_phone', 'shipping_line1', 
                                       'shipping_city', 'shipping_state', 'shipping_postal', 'shipping_country'];
                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) field.setAttribute('required', 'required');
                });
            }
        });
    }
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Disable button to prevent double submission
        setLoading(true);
        
        try {
            // Collect address data
            const billingAddress = {
                name: document.getElementById('billing_name').value,
                phone: document.getElementById('billing_phone').value,
                address: {
                    line1: document.getElementById('billing_line1').value,
                    line2: document.getElementById('billing_line2').value || undefined,
                    city: document.getElementById('billing_city').value,
                    state: document.getElementById('billing_state').value,
                    postal_code: document.getElementById('billing_postal').value,
                    country: document.getElementById('billing_country').value
                }
            };

            // Collect shipping address (same as billing if checkbox is checked)
            let shippingAddress;
            if (sameAsBillingCheckbox.checked) {
                shippingAddress = billingAddress;
            } else {
                shippingAddress = {
                    name: document.getElementById('shipping_name').value,
                    phone: document.getElementById('shipping_phone').value,
                    address: {
                        line1: document.getElementById('shipping_line1').value,
                        line2: document.getElementById('shipping_line2').value || undefined,
                        city: document.getElementById('shipping_city').value,
                        state: document.getElementById('shipping_state').value,
                        postal_code: document.getElementById('shipping_postal').value,
                        country: document.getElementById('shipping_country').value
                    }
                };
            }

            // Check if user wants to save card
            const saveCard = document.getElementById('save_card').checked;

            // Step 1: Create Payment Intent on server
            console.log('Creating Payment Intent...');
            const response = await fetch('/api/create-payment-intent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    save_for_future: saveCard,
                    billing_address: billingAddress,
                    shipping_address: shippingAddress
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ error: 'Server error' }));
                throw new Error(errorData.error || 'Failed to create payment intent');
            }
            
            const data = await response.json();
            
            if (!data.success || !data.clientSecret) {
                throw new Error(data.error || 'Invalid server response');
            }
            
            console.log('Payment Intent created, confirming payment...');
            
            // Step 2: Confirm payment with Stripe
            const {error, paymentIntent} = await stripe.confirmCardPayment(
                data.clientSecret,
                {
                    payment_method: {
                        card: cardNumber,
                        billing_details: {
                            name: billingAddress.name,
                            email: document.getElementById('email').value,
                            phone: billingAddress.phone,
                            address: billingAddress.address
                        }
                    },
                    shipping: shippingAddress
                }
            );
            
            if (error) {
                // Payment failed
                throw new Error(error.message);
            }
            
            if (paymentIntent.status === 'succeeded') {
                // Payment succeeded - redirect to success page
                console.log('Payment succeeded!');
                window.location.href = '/order-confirmation.php?ref=' + data.orderRef;
            } else {
                throw new Error('Payment was not successful. Status: ' + paymentIntent.status);
            }
            
        } catch (error) {
            console.error('Checkout error:', error);
            paymentMessage.textContent = error.message || 'An error occurred during checkout';
            paymentMessage.style.display = 'block';
            setLoading(false);
        }
    });
    
    function setLoading(isLoading) {
        if (isLoading) {
            checkoutButton.disabled = true;
            buttonText.style.display = 'none';
            spinner.style.display = 'block';
        } else {
            checkoutButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        }
    }
})();
</script>

<?php includeFooter(); ?>
