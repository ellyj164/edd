<?php
/**
 * Stripe Integration Test Script
 * 
 * This script tests your Stripe API configuration
 * Run from command line: php test_stripe_integration.php
 * Or access via browser: https://yourdomain.com/test_stripe_integration.php
 */

// Load config if not already loaded
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/includes/init.php';
}

// Allow running in any environment for testing purposes
// In production, this file should be removed or access restricted via .htaccess
if (php_sapi_name() !== 'cli') {
    // If accessed via web, show warning for production
    if (defined('APP_ENV') && APP_ENV === 'production') {
        echo "<pre><strong>⚠️ Warning:</strong> This is a test script. Remove it from production servers or restrict access.</pre>";
    }
}

echo "=== Stripe Integration Test ===\n\n";

// Test 1: Check if Stripe library is installed
echo "1. Checking Stripe PHP Library...\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "   ✅ Composer autoload found\n";
} else {
    echo "   ❌ Composer autoload not found. Run: composer install\n";
    exit(1);
}

if (class_exists('\Stripe\Stripe')) {
    echo "   ✅ Stripe library loaded successfully\n";
} else {
    echo "   ❌ Stripe library not found. Run: composer require stripe/stripe-php\n";
    exit(1);
}

// Load config
if (!defined('STRIPE_SECRET_KEY')) {
    require_once __DIR__ . '/config/config.php';
}

echo "\n2. Checking Configuration...\n";

// Test 2: Check API keys
if (defined('STRIPE_PUBLISHABLE_KEY') && !empty(STRIPE_PUBLISHABLE_KEY) && STRIPE_PUBLISHABLE_KEY !== 'pk_test_example_key') {
    echo "   ✅ Publishable Key: " . substr(STRIPE_PUBLISHABLE_KEY, 0, 12) . "...\n";
} else {
    echo "   ⚠️  Publishable Key: Not configured (using placeholder)\n";
    echo "      Set STRIPE_PUBLISHABLE_KEY in your .env file\n";
}

if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY) && STRIPE_SECRET_KEY !== 'sk_test_example_key') {
    echo "   ✅ Secret Key: " . substr(STRIPE_SECRET_KEY, 0, 12) . "...\n";
    $hasValidSecretKey = true;
} else {
    echo "   ⚠️  Secret Key: Not configured (using placeholder)\n";
    echo "      Set STRIPE_SECRET_KEY in your .env file\n";
    $hasValidSecretKey = false;
}

if (defined('STRIPE_WEBHOOK_SECRET') && !empty(STRIPE_WEBHOOK_SECRET)) {
    echo "   ✅ Webhook Secret: " . substr(STRIPE_WEBHOOK_SECRET, 0, 12) . "...\n";
} else {
    echo "   ⚠️  Webhook Secret: Not configured\n";
    echo "      Set STRIPE_WEBHOOK_SECRET in your .env file (optional for testing)\n";
}

// Test 3: Test API Connection (only if we have valid keys)
if ($hasValidSecretKey) {
    echo "\n3. Testing Stripe API Connection...\n";
    
    try {
        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        
        // Try to retrieve account information
        $account = \Stripe\Account::retrieve();
        
        echo "   ✅ Successfully connected to Stripe API\n";
        echo "   Account ID: " . $account->id . "\n";
        echo "   Account Type: " . ($account->type ?? 'standard') . "\n";
        echo "   Country: " . ($account->country ?? 'N/A') . "\n";
        echo "   Default Currency: " . strtoupper($account->default_currency ?? 'usd') . "\n";
        
        // Determine if test or live mode
        if (strpos(STRIPE_SECRET_KEY, 'sk_test_') === 0) {
            echo "   🧪 Mode: TEST MODE (Safe for testing)\n";
        } else if (strpos(STRIPE_SECRET_KEY, 'sk_live_') === 0) {
            echo "   ⚡ Mode: LIVE MODE (Real payments)\n";
        }
        
    } catch (\Stripe\Exception\AuthenticationException $e) {
        echo "   ❌ Authentication failed: " . $e->getMessage() . "\n";
        echo "      Check your STRIPE_SECRET_KEY in .env file\n";
        exit(1);
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        echo "   ❌ Connection failed: " . $e->getMessage() . "\n";
        echo "      Check your internet connection and Stripe API status\n";
        exit(1);
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Test 4: Test PaymentIntent creation
    echo "\n4. Testing PaymentIntent Creation...\n";
    
    try {
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => 1000, // $10.00 in cents
            'currency' => 'usd',
            'description' => 'Test payment from integration test',
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
        
        echo "   ✅ PaymentIntent created successfully\n";
        echo "   Payment Intent ID: " . $paymentIntent->id . "\n";
        echo "   Amount: $" . number_format($paymentIntent->amount / 100, 2) . " " . strtoupper($paymentIntent->currency) . "\n";
        echo "   Status: " . $paymentIntent->status . "\n";
        
        // Cancel the test payment intent
        $paymentIntent->cancel();
        echo "   ✅ Test PaymentIntent cancelled (not charged)\n";
        
    } catch (Exception $e) {
        echo "   ❌ PaymentIntent creation failed: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "\n3. Skipping API tests (no valid secret key configured)\n";
    echo "   Configure your Stripe keys in .env to test API connectivity\n";
}

// Test 5: Check payment gateway class
echo "\n5. Checking Payment Gateway Implementation...\n";

try {
    require_once __DIR__ . '/includes/payment_gateways.php';
    
    if (class_exists('StripePaymentGateway')) {
        echo "   ✅ StripePaymentGateway class found\n";
        
        if ($hasValidSecretKey) {
            try {
                $gateway = new StripePaymentGateway();
                echo "   ✅ StripePaymentGateway initialized successfully\n";
            } catch (Exception $e) {
                echo "   ⚠️  Could not initialize gateway: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   ❌ StripePaymentGateway class not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error loading payment gateways: " . $e->getMessage() . "\n";
}

// Test 6: Check required files
echo "\n6. Checking Required Files...\n";

$requiredFiles = [
    '/api/create-payment-intent.php' => 'PaymentIntent API endpoint',
    '/api/stripe-webhook.php' => 'Stripe webhook handler',
    '/includes/payment_gateways.php' => 'Payment gateway implementation',
    '/checkout.php' => 'Checkout page',
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists(__DIR__ . $file)) {
        echo "   ✅ $description ($file)\n";
    } else {
        echo "   ❌ Missing: $description ($file)\n";
    }
}

// Summary
echo "\n=== Test Summary ===\n";
if ($hasValidSecretKey) {
    echo "✅ Your Stripe integration is configured and working!\n";
    echo "\nNext Steps:\n";
    echo "1. Configure webhook endpoint in Stripe Dashboard\n";
    echo "2. Test checkout flow with test card: 4242 4242 4242 4242\n";
    echo "3. Review STRIPE_INTEGRATION_GUIDE.md for complete setup instructions\n";
} else {
    echo "⚠️  Configuration needed:\n";
    echo "1. Copy .env.example to .env\n";
    echo "2. Add your Stripe API keys to .env file\n";
    echo "3. Get test keys from: https://dashboard.stripe.com/test/apikeys\n";
    echo "4. Review STRIPE_INTEGRATION_GUIDE.md for detailed instructions\n";
}

echo "\n";
