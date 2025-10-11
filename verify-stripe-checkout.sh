#!/bin/bash
# Stripe Checkout Integration - Quick Verification Script
# Run this to verify the implementation is complete

echo "=== Stripe Checkout Integration Verification ==="
echo ""

# Check if required files exist
echo "1. Checking required files..."
FILES=(
    "api/create-checkout-session.php"
    "checkout-success.php"
    "checkout.php"
    "api/stripe-webhook.php"
    "migrations/20251009_add_stripe_checkout_support.sql"
)

ALL_EXIST=true
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✓ $file"
    else
        echo "   ✗ $file (MISSING)"
        ALL_EXIST=false
    fi
done

if [ "$ALL_EXIST" = false ]; then
    echo ""
    echo "ERROR: Some required files are missing!"
    exit 1
fi

echo ""
echo "2. Checking PHP syntax..."
php -l api/create-checkout-session.php >/dev/null 2>&1 && echo "   ✓ api/create-checkout-session.php" || echo "   ✗ api/create-checkout-session.php (SYNTAX ERROR)"
php -l checkout-success.php >/dev/null 2>&1 && echo "   ✓ checkout-success.php" || echo "   ✗ checkout-success.php (SYNTAX ERROR)"
php -l checkout.php >/dev/null 2>&1 && echo "   ✓ checkout.php" || echo "   ✗ checkout.php (SYNTAX ERROR)"
php -l api/stripe-webhook.php >/dev/null 2>&1 && echo "   ✓ api/stripe-webhook.php" || echo "   ✗ api/stripe-webhook.php (SYNTAX ERROR)"

echo ""
echo "3. Checking Composer dependencies..."
if [ -d "vendor/stripe/stripe-php" ]; then
    echo "   ✓ stripe/stripe-php installed"
else
    echo "   ✗ stripe/stripe-php NOT installed"
    echo "      Run: composer install"
fi

echo ""
echo "4. Checking checkout.php changes..."
if grep -q "create-checkout-session.php" checkout.php; then
    echo "   ✓ checkout.php uses create-checkout-session.php"
else
    echo "   ✗ checkout.php does not call create-checkout-session.php"
fi

if ! grep -q "stripe-card-number" checkout.php; then
    echo "   ✓ checkout.php removed Stripe Elements"
else
    echo "   ✗ checkout.php still has Stripe Elements (should be removed)"
fi

if ! grep -q "intl-tel-input" checkout.php; then
    echo "   ✓ checkout.php removed intl-tel-input"
else
    echo "   ✗ checkout.php still has intl-tel-input (should be removed)"
fi

echo ""
echo "5. Checking webhook handler..."
if grep -q "checkout.session.completed" api/stripe-webhook.php; then
    echo "   ✓ Webhook handles checkout.session.completed"
else
    echo "   ✗ Webhook missing checkout.session.completed handler"
fi

if grep -q "handle_checkout_session_completed" api/stripe-webhook.php; then
    echo "   ✓ Function handle_checkout_session_completed exists"
else
    echo "   ✗ Function handle_checkout_session_completed missing"
fi

echo ""
echo "6. Next steps:"
echo "   [ ] Apply database migration:"
echo "       mysql -u [user] -p [database] < migrations/20251009_add_stripe_checkout_support.sql"
echo ""
echo "   [ ] Configure Stripe webhook:"
echo "       1. Go to Stripe Dashboard → Webhooks"
echo "       2. Add endpoint: https://yourdomain.com/api/stripe-webhook.php"
echo "       3. Listen for events: checkout.session.completed, payment_intent.succeeded"
echo "       4. Copy webhook signing secret to .env"
echo ""
echo "   [ ] Test checkout flow:"
echo "       1. Add items to cart"
echo "       2. Go to checkout page"
echo "       3. Click 'Continue to Secure Checkout'"
echo "       4. Complete payment on Stripe page (use test card: 4242 4242 4242 4242)"
echo "       5. Verify redirect to success page"
echo "       6. Check webhook was received in Stripe Dashboard"
echo ""
echo "=== Verification Complete ==="
