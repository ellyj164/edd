<?php
declare(strict_types=1);

/**
 * Stripe Configuration and Documentation
 * 
 * This file documents Stripe-specific configuration options and features.
 * The actual Stripe initialization happens in includes/stripe/init_stripe.php
 * 
 * Configuration is loaded from environment variables (.env file)
 */

/**
 * STRIPE CONFIGURATION OVERVIEW
 * ==============================
 * 
 * Required Environment Variables:
 * - STRIPE_PUBLISHABLE_KEY_TEST: Test mode publishable key (pk_test_...)
 * - STRIPE_SECRET_KEY_TEST: Test mode secret key (sk_test_...)
 * - STRIPE_PUBLISHABLE_KEY_LIVE: Live mode publishable key (pk_live_...)
 * - STRIPE_SECRET_KEY_LIVE: Live mode secret key (sk_live_...)
 * - STRIPE_MODE: Either 'test' or 'live' (determines which keys to use)
 * 
 * Optional Environment Variables:
 * - STRIPE_DEFAULT_CURRENCY: Default currency code (default: 'usd')
 * - STRIPE_STATEMENT_DESCRIPTOR: Appears on customer statements (max 22 chars)
 * - STRIPE_CAPTURE_METHOD: 'automatic' or 'manual' (default: 'automatic')
 * - STRIPE_WEBHOOK_SECRET_TEST: Test webhook signing secret (whsec_...)
 * - STRIPE_WEBHOOK_SECRET_LIVE: Live webhook signing secret (whsec_...)
 */

/**
 * SAVE FOR FUTURE BILLING (setup_future_usage)
 * =============================================
 * 
 * When a customer checks "Save this card for future billing" on checkout:
 * 
 * 1. Frontend (js/checkout-stripe.js):
 *    - Detects checkbox state
 *    - Sends { save_for_future: true } to create-payment-intent.php
 * 
 * 2. Backend (api/create-payment-intent.php):
 *    - Reads save_for_future parameter
 *    - Sets PaymentIntent parameter: setup_future_usage: 'off_session'
 *    - This tells Stripe to save the payment method after successful payment
 * 
 * 3. After Payment Success:
 *    - Stripe automatically attaches the PaymentMethod to the Customer
 *    - The PaymentMethod ID is available in paymentIntent.payment_method
 *    - You can persist this to your database using the migration template:
 *      migrations/20251009_stripe_customers_and_payment_methods.sql
 * 
 * 4. Future Payments:
 *    - Retrieve saved payment methods via Stripe API:
 *      $stripe->paymentMethods->all(['customer' => $customerId, 'type' => 'card'])
 *    - Use the payment_method ID when creating new PaymentIntents:
 *      ['payment_method' => $paymentMethodId, 'off_session' => true, 'confirm' => true]
 * 
 * Database Tables (Optional - see migration template):
 * - customers: Maps user_id/email to stripe_customer_id
 * - payment_methods: Stores stripe_payment_method_id, brand, last4, expiry, etc.
 * 
 * The checkout flow works WITHOUT these tables. They're purely for enhanced UX
 * (showing saved cards, making them default, etc.)
 */

/**
 * PAYMENT INTENT FLOW
 * ====================
 * 
 * Standard Flow:
 * 1. Checkout page loads → checkout.php sets up form with Stripe Elements
 * 2. User fills form and clicks Pay
 * 3. JS calls api/create-payment-intent.php → creates PaymentIntent server-side
 * 4. JS calls stripe.confirmCardPayment() → confirms payment with Stripe
 * 5. On success → redirect to order-confirmation.php
 * 6. Webhook (api/stripe-webhook.php) finalizes order and clears cart
 * 
 * With Save for Future:
 * Same flow, but step 3 includes setup_future_usage='off_session'
 * After step 5, the payment method is saved to the Stripe Customer
 * 
 * 3DS/SCA Handling:
 * - stripe.confirmCardPayment() automatically handles 3D Secure challenges
 * - User sees authentication modal if required by their bank
 * - No additional code needed - Stripe.js handles this
 */

/**
 * SEPARATE CARD ELEMENTS
 * =======================
 * 
 * checkout.php creates three containers:
 * - #stripe-card-number: Card number field
 * - #stripe-card-expiry: Expiry date field
 * - #stripe-card-cvc: CVC/CVV field
 * 
 * js/checkout-stripe.js mounts separate Stripe Elements:
 * - elements.create('cardNumber')
 * - elements.create('cardExpiry')
 * - elements.create('cardCvc')
 * 
 * Benefits:
 * - More control over layout/styling
 * - Better mobile UX
 * - Clearer field labels
 * - No collapsed/hidden fields
 */

/**
 * INTERNATIONAL PHONE INPUT
 * ==========================
 * 
 * Uses intl-tel-input library (loaded from CDN):
 * - Automatic flag icons based on country
 * - Auto-formatting with country code prefix
 * - Validates phone numbers
 * - Returns full international format (+1 555-123-4567)
 * 
 * Initialized in js/checkout-stripe.js for:
 * - billing_phone
 * - shipping_phone
 * 
 * Phone numbers are sent to Stripe in billing_details and shipping
 */

/**
 * COUNTRY DROPDOWN
 * =================
 * 
 * js/checkout-stripe.js populates country selects with ~100 countries
 * Embedded in JS (no external API call needed)
 * Default: United States (US)
 * 
 * Country codes use ISO 3166-1 alpha-2 (e.g., US, CA, GB, AU)
 * Sent to Stripe in billing_details.address.country and shipping.address.country
 */

/**
 * SECURITY NOTES
 * ==============
 * 
 * - Card data NEVER touches your server (PCI DSS Level 1 compliance)
 * - Stripe Elements are iframes hosted by Stripe
 * - Only tokenized payment methods are sent to your server
 * - All API calls use HTTPS
 * - Webhook signatures verified with HMAC-SHA256
 * - CSRF protection via session validation
 */

// No code execution in this file - it's purely documentation
// Actual Stripe initialization happens in includes/stripe/init_stripe.php
