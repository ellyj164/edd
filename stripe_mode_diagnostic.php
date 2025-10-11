<?php
declare(strict_types=1);

/**
 * Stripe Mode Diagnostic Endpoint
 * 
 * Safely displays which Stripe mode and key prefixes are in use
 * WITHOUT revealing actual secret keys
 * 
 * Usage: Access via browser or curl
 * Example: https://yourdomain.com/stripe_mode_diagnostic.php
 * 
 * SECURITY: Only shows key prefixes (pk_live_/pk_test_/sk_live_/sk_test_)
 *          Never displays actual secret values
 */

// Load configuration
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/stripe/init_stripe.php';

// Set content type to JSON
header('Content-Type: application/json');

// Security: In production, you may want to restrict access
// Uncomment below to require admin authentication
// Session::requireRole('admin');

try {
    // Get mode
    $mode = getStripeMode();
    
    // Get keys (safely)
    $publishableKey = getStripePublishableKey();
    $secretKey = getStripeSecretKey();
    $webhookSecret = getStripeWebhookSecret();
    
    // Extract only the prefixes (safe to display)
    $publishablePrefix = $publishableKey ? substr($publishableKey, 0, 8) . '...' : null;
    $secretPrefix = $secretKey ? substr($secretKey, 0, 8) . '...' : null;
    $webhookPrefix = $webhookSecret ? substr($webhookSecret, 0, 6) . '...' : null;
    
    // Check configuration status
    $config = checkStripeConfiguration();
    
    // Get environment info
    $appEnv = defined('APP_ENV') ? APP_ENV : 'unknown';
    $allowTestInProduction = env('ALLOW_TEST_MODE_IN_PRODUCTION', false);
    
    // Build diagnostic response
    $diagnostic = [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s T'),
        'stripe' => [
            'mode' => $mode,
            'configured' => $config['configured'],
            'keys' => [
                'publishable' => [
                    'configured' => !empty($publishableKey),
                    'prefix' => $publishablePrefix,
                ],
                'secret' => [
                    'configured' => !empty($secretKey),
                    'prefix' => $secretPrefix,
                ],
                'webhook' => [
                    'configured' => !empty($webhookSecret),
                    'prefix' => $webhookPrefix,
                ],
            ],
        ],
        'environment' => [
            'app_env' => $appEnv,
            'allow_test_in_production' => $allowTestInProduction,
        ],
        'warnings' => [],
        'errors' => $config['errors'],
    ];
    
    // Add warnings
    if ($appEnv === 'production' && $mode === 'test') {
        $diagnostic['warnings'][] = 'TEST MODE active in PRODUCTION environment - Real payments will NOT be processed!';
    }
    
    if ($mode === 'live' && strpos($secretPrefix ?? '', 'sk_test_') === 0) {
        $diagnostic['warnings'][] = 'Mode is set to LIVE but test keys detected!';
    }
    
    if ($mode === 'test' && strpos($secretPrefix ?? '', 'sk_live_') === 0) {
        $diagnostic['warnings'][] = 'Mode is set to TEST but live keys detected!';
    }
    
    if (!$config['configured']) {
        $diagnostic['warnings'][] = 'Stripe is not fully configured. Check errors array for details.';
    }
    
    // Success response
    http_response_code(200);
    echo json_encode($diagnostic, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s T'),
    ], JSON_PRETTY_PRINT);
}
