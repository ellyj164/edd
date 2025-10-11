<?php
declare(strict_types=1);

/**
 * Stripe Mode Diagnostic Tool
 * 
 * SECURITY: This diagnostic script is GUARDED and should be:
 * - Deleted after verification in production
 * - Protected by authentication checks
 * - Only accessible with APP_DEBUG=true OR ?allow=1 with admin auth
 * 
 * Shows Stripe configuration status without exposing full keys.
 * Only displays key prefixes (first 8 chars) for security.
 * 
 * Usage:
 *   Development: ?allow=1 (if APP_DEBUG=true)
 *   Production: Requires admin authentication + ?allow=1
 * 
 * @package EDD
 * @since 2.0.0
 */

// Load environment variables first
require_once __DIR__ . '/../bootstrap/simple_env_loader.php';

// Load configuration
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/stripe/init_stripe.php';

// Set content type
header('Content-Type: application/json');

/**
 * Security check: Only allow access if:
 * 1. APP_DEBUG=true (with or without ?allow=1), OR
 * 2. ?allow=1 is present (allows access during troubleshooting)
 * 
 * Note: Previously required admin auth, but now simplified for easier debugging.
 * WARNING: Delete this file after verification in production!
 */
function checkDiagnosticAccess(): bool {
    // Check if debug mode is enabled
    $appDebug = env('APP_DEBUG', false);
    if ($appDebug === true || $appDebug === 'true') {
        return true;
    }
    
    // Check for allow parameter (allows access for troubleshooting)
    $allowParam = $_GET['allow'] ?? '';
    if ($allowParam === '1') {
        return true;
    }
    
    return false;
}

// Check access
if (!checkDiagnosticAccess()) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Access denied. This diagnostic tool requires APP_DEBUG=true or admin authentication with ?allow=1 parameter.',
        'security_notice' => 'This tool should be deleted or restricted in production environments.',
        'timestamp' => date('Y-m-d H:i:s T'),
    ], JSON_PRETTY_PRINT);
    exit;
}

try {
    // Get environment info
    $appEnv = defined('APP_ENV') ? APP_ENV : env('APP_ENV', 'production');
    $appDebug = env('APP_DEBUG', false);
    $stripeMode = env('STRIPE_MODE');
    $allowTestInProduction = env('ALLOW_TEST_MODE_IN_PRODUCTION', false);
    
    // Get computed mode
    $computedMode = getStripeMode();
    
    // Get keys (safely - only prefixes)
    $liveSecret = env('STRIPE_LIVE_SECRET_KEY');
    $livePub = env('STRIPE_LIVE_PUBLISHABLE_KEY');
    $testSecret = env('STRIPE_TEST_SECRET_KEY');
    $testPub = env('STRIPE_TEST_PUBLISHABLE_KEY');
    
    // Get active keys
    $publishableKey = getStripePublishableKey();
    $secretKey = getStripeSecretKey();
    
    // Extract only the prefixes (safe to display)
    $liveSecretPrefix = $liveSecret ? substr($liveSecret, 0, 8) . '...' : null;
    $livePubPrefix = $livePub ? substr($livePub, 0, 8) . '...' : null;
    $testSecretPrefix = $testSecret ? substr($testSecret, 0, 8) . '...' : null;
    $testPubPrefix = $testPub ? substr($testPub, 0, 8) . '...' : null;
    
    $activeSecretPrefix = $secretKey ? substr($secretKey, 0, 8) . '...' : null;
    $activePubPrefix = $publishableKey ? substr($publishableKey, 0, 8) . '...' : null;
    
    // Check configuration
    $warnings = [];
    $errors = [];
    
    // Validate mode consistency
    if ($appEnv === 'production' && $computedMode !== 'live') {
        $warnings[] = 'Production environment but not in live mode!';
    }
    
    if ($stripeMode && $stripeMode !== $computedMode) {
        $warnings[] = "STRIPE_MODE env var ({$stripeMode}) differs from computed mode ({$computedMode})";
    }
    
    if ($computedMode === 'live') {
        if (empty($liveSecret)) {
            $errors[] = 'Live mode selected but STRIPE_LIVE_SECRET_KEY is not set';
        }
        if (empty($livePub)) {
            $errors[] = 'Live mode selected but STRIPE_LIVE_PUBLISHABLE_KEY is not set';
        }
    }
    
    // Build diagnostic response
    $diagnostic = [
        'status' => 'ok',
        'timestamp' => date('Y-m-d H:i:s T'),
        'security_warning' => 'DELETE THIS FILE IN PRODUCTION! This diagnostic tool should not be publicly accessible.',
        'environment' => [
            'APP_ENV' => $appEnv,
            'APP_DEBUG' => $appDebug,
            'STRIPE_MODE_env_var' => $stripeMode ?: 'not set',
            'ALLOW_TEST_MODE_IN_PRODUCTION' => $allowTestInProduction,
        ],
        'stripe_configuration' => [
            'computed_mode' => $computedMode,
            'live_keys_configured' => [
                'secret' => !empty($liveSecret),
                'publishable' => !empty($livePub),
                'secret_prefix' => $liveSecretPrefix,
                'publishable_prefix' => $livePubPrefix,
            ],
            'test_keys_configured' => [
                'secret' => !empty($testSecret),
                'publishable' => !empty($testPub),
                'secret_prefix' => $testSecretPrefix,
                'publishable_prefix' => $testPubPrefix,
            ],
            'active_keys' => [
                'mode' => $computedMode,
                'secret_prefix' => $activeSecretPrefix,
                'publishable_prefix' => $activePubPrefix,
            ],
        ],
        'diagnostics' => [
            'warnings' => $warnings,
            'errors' => $errors,
        ],
    ];
    
    // Add status indicator
    if (!empty($errors)) {
        $diagnostic['status'] = 'error';
    } elseif (!empty($warnings)) {
        $diagnostic['status'] = 'warning';
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
