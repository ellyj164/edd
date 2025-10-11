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
require_once __DIR__ . '/includes/countries_service.php';

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

// Load countries from database (with fallback to static data)
$countriesJson = '[]';
try {
    if (CountriesService::isAvailable()) {
        $countriesJson = CountriesService::getAsJson();
    } else {
        // Fallback to static countries if database not available
        error_log("[CHECKOUT] Countries table not available, using fallback static data");
        require_once __DIR__ . '/includes/countries_data.php';
        $staticCountries = CountriesData::getAll();
        $countriesJson = json_encode($staticCountries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Exception $e) {
    error_log("[CHECKOUT] Error loading countries: " . $e->getMessage());
    // Use minimal fallback
    $countriesJson = '[{"code":"US","name":"United States","flag":"🇺🇸","phone":"+1","currency":"USD"}]';
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
                    <div id="phone-error" class="form-error" style="display: none;"></div>
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

/* Form error message styles */
.form-error {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 6px;
    display: none;
}

/* Invalid input state */
.form-input.invalid {
    border-color: #dc3545;
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

/* intl-tel-input customization */
.iti {
    width: 100%;
    display: block;
}

.iti__flag-container {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 0;
    padding: 1px;
}

.iti__selected-flag {
    padding: 0 8px 0 20px;
    height: 100%;
}

.iti__country-list {
    max-height: 300px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 4px;
    margin-top: 4px;
}

.iti__country {
    padding: 10px 15px;
}

.iti__country:hover {
    background-color: #f5f5f5;
}

.iti__country.iti__highlight {
    background-color: #e7f3ff;
}

/* Make phone input work with intl-tel-input */
#billing_phone {
    padding-left: 100px !important;
}

/* Mobile responsive adjustments */
@media (max-width: 768px) {
    .iti__country-list {
        max-height: 200px;
    }
    
    .select2-results__option {
        padding: 12px 15px !important;
        font-size: 16px !important; /* Prevents zoom on iOS */
    }
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
    
    // Initialize intl-tel-input for phone fields with enhanced features
    let billingPhoneInput = null;
    const billingPhoneField = document.getElementById('billing_phone');
    
    if (billingPhoneField && window.intlTelInput) {
        billingPhoneInput = window.intlTelInput(billingPhoneField, {
            // Use detected country or default to US
            initialCountry: detectedCountry ? detectedCountry.toLowerCase() : 'us',
            preferredCountries: ['us', 'rw', 'ca', 'gb', 'au', 'de', 'fr'],
            separateDialCode: true,
            // Enable search by name and dial code
            searchPlaceholder: 'Search by country or code',
            // Show all countries in dropdown
            onlyCountries: [],
            // Format as user types
            autoPlaceholder: 'aggressive',
            formatOnDisplay: true,
            nationalMode: false,
            // Load utils for formatting and validation
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js'
        });
        
        // Add validation on blur
        billingPhoneField.addEventListener('blur', function() {
            if (billingPhoneInput && billingPhoneInput.isValidNumber) {
                const isValid = billingPhoneInput.isValidNumber();
                const errorMsg = document.getElementById('phone-error');
                if (!isValid && this.value.trim()) {
                    if (errorMsg) {
                        errorMsg.textContent = 'Please enter a valid phone number for the selected country';
                        errorMsg.style.display = 'block';
                    }
                    this.classList.add('invalid');
                } else {
                    if (errorMsg) {
                        errorMsg.style.display = 'none';
                    }
                    this.classList.remove('invalid');
                }
            }
        });
        
        // Sync phone country with country selector when user changes phone country
        billingPhoneField.addEventListener('countrychange', function() {
            if (billingPhoneInput) {
                const selectedCountryData = billingPhoneInput.getSelectedCountryData();
                const countryCode = selectedCountryData.iso2.toUpperCase();
                const billingCountrySelect = document.getElementById('billing_country');
                
                // Update country selector if different
                if (billingCountrySelect && billingCountrySelect.value !== countryCode) {
                    billingCountrySelect.value = countryCode;
                    // Trigger Select2 update
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        jQuery(billingCountrySelect).trigger('change');
                    }
                    // Update currency display
                    updateCurrency(countryCode);
                }
            }
        });
    }
    
    // Load country list from database (server-side PHP)
    // Countries are loaded from the database and passed to JavaScript via PHP
    const countries = <?php echo $countriesJson; ?>;
    
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
        
        // Store selected currency in a variable for payment intent
        window.selectedCurrency = country.currency;
        
        // Display currency info to user
        const currencyNote = document.getElementById('currency-note');
        if (currencyNote) {
            let currencySymbol = '$';
            if (country.currency === 'EUR') currencySymbol = '€';
            if (country.currency === 'RWF') currencySymbol = 'FRw';
            
            // Special message for Rwanda
            if (countryCode === 'RW') {
                currencyNote.textContent = `Payment will be processed in ${country.currency} (${currencySymbol}). Exchange rate will be applied automatically.`;
                currencyNote.style.background = '#fff3cd';
                currencyNote.style.borderLeftColor = '#ffc107';
                currencyNote.style.color = '#856404';
            } else {
                currencyNote.textContent = `Prices will be shown in ${country.currency} (${currencySymbol})`;
                currencyNote.style.background = '#e7f3ff';
                currencyNote.style.borderLeftColor = '#0066cc';
                currencyNote.style.color = '#0066cc';
            }
            currencyNote.style.display = 'block';
        }
    }
    
    // Populate country selects
    const billingCountrySelect = document.getElementById('billing_country');
    const shippingCountrySelect = document.getElementById('shipping_country');
    
    populateCountrySelect(billingCountrySelect, detectedCountry || 'US');
    populateCountrySelect(shippingCountrySelect, detectedCountry || 'US');
    
    // Initialize Select2 for searchable country dropdowns with enhanced options
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery('.country-select').select2({
            placeholder: 'Select a country',
            allowClear: false,
            width: '100%',
            // Support keyboard navigation
            minimumResultsForSearch: 0,
            // Custom matcher for searching by country name or dial code
            matcher: function(params, data) {
                // If there are no search terms, return all data
                if (jQuery.trim(params.term) === '') {
                    return data;
                }
                
                // Skip if there is no 'text' property
                if (typeof data.text === 'undefined') {
                    return null;
                }
                
                // Get the country data
                const countryCode = jQuery(data.element).val();
                const country = countries.find(c => c.code === countryCode);
                
                if (!country) {
                    return null;
                }
                
                // Convert search term to lowercase for case-insensitive search
                const term = params.term.toLowerCase();
                
                // Search in country name
                if (country.name.toLowerCase().indexOf(term) > -1) {
                    return data;
                }
                
                // Search in dial code (with or without +)
                const dialCode = country.phone.replace('+', '');
                if (dialCode.indexOf(term.replace('+', '')) > -1) {
                    return data;
                }
                
                // Search in country code
                if (country.code.toLowerCase().indexOf(term) > -1) {
                    return data;
                }
                
                return null;
            },
            // ARIA labels for accessibility
            language: {
                inputTooShort: function() {
                    return 'Type to search countries';
                },
                noResults: function() {
                    return 'No country found';
                },
                searching: function() {
                    return 'Searching...';
                }
            }
        });
        
        // Trigger initial currency update
        if (billingCountrySelect.value) {
            updateCurrency(billingCountrySelect.value);
        }
    }
    
    // Listen for country selection changes to update phone code and currency
    if (billingCountrySelect) {
        billingCountrySelect.addEventListener('change', function() {
            updatePhoneCountryCode(this.value, billingPhoneInput);
            updateCurrency(this.value);
        });
    }
    
    // Restore form values from sessionStorage (for persistence on validation errors)
    function restoreFormValues() {
        try {
            const savedValues = sessionStorage.getItem('checkoutFormValues');
            if (savedValues) {
                const values = JSON.parse(savedValues);
                
                // Restore billing fields
                if (values.billing_name) document.getElementById('billing_name').value = values.billing_name;
                if (values.billing_phone) {
                    document.getElementById('billing_phone').value = values.billing_phone;
                    // Let intl-tel-input process the number
                    if (billingPhoneInput && billingPhoneInput.setNumber) {
                        billingPhoneInput.setNumber(values.billing_phone);
                    }
                }
                if (values.billing_line1) document.getElementById('billing_line1').value = values.billing_line1;
                if (values.billing_line2) document.getElementById('billing_line2').value = values.billing_line2;
                if (values.billing_city) document.getElementById('billing_city').value = values.billing_city;
                if (values.billing_state) document.getElementById('billing_state').value = values.billing_state;
                if (values.billing_postal) document.getElementById('billing_postal').value = values.billing_postal;
                if (values.billing_country) {
                    document.getElementById('billing_country').value = values.billing_country;
                    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        jQuery('#billing_country').trigger('change');
                    }
                }
                
                // Don't auto-restore after successful load
                sessionStorage.removeItem('checkoutFormValues');
            }
        } catch (error) {
            console.error('Error restoring form values:', error);
        }
    }
    
    // Save form values to sessionStorage before submission
    function saveFormValues() {
        try {
            const values = {
                billing_name: document.getElementById('billing_name').value,
                billing_phone: document.getElementById('billing_phone').value,
                billing_line1: document.getElementById('billing_line1').value,
                billing_line2: document.getElementById('billing_line2').value,
                billing_city: document.getElementById('billing_city').value,
                billing_state: document.getElementById('billing_state').value,
                billing_postal: document.getElementById('billing_postal').value,
                billing_country: document.getElementById('billing_country').value
            };
            sessionStorage.setItem('checkoutFormValues', JSON.stringify(values));
        } catch (error) {
            console.error('Error saving form values:', error);
        }
    }
    
    // Restore values on page load
    restoreFormValues();
    
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
        
        // Save form values for persistence
        saveFormValues();
        
        // Validate phone number before submission
        if (billingPhoneInput) {
            const phoneField = document.getElementById('billing_phone');
            const isValid = billingPhoneInput.isValidNumber ? billingPhoneInput.isValidNumber() : true;
            
            if (!isValid && phoneField.value.trim()) {
                const errorMsg = document.getElementById('phone-error');
                if (errorMsg) {
                    errorMsg.textContent = 'Please enter a valid phone number for the selected country';
                    errorMsg.style.display = 'block';
                }
                phoneField.classList.add('invalid');
                phoneField.focus();
                return;
            }
            
            // Format phone number in international format
            if (billingPhoneInput.getNumber) {
                const formattedNumber = billingPhoneInput.getNumber();
                phoneField.value = formattedNumber;
            }
        }
        
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
                    shipping_address: shippingAddress,
                    currency: window.selectedCurrency || detectedCurrency
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
