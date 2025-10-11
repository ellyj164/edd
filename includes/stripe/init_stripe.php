<?php
declare(strict_types=1);

/**
 * Stripe Initialization and Configuration
 * Handles mode switching (test/live) and API key retrieval
 * 
 * Usage:
 *   require_once __DIR__ . '/stripe/init_stripe.php';
 *   $stripe = initStripe(); // Returns configured \Stripe\StripeClient instance
 */

// CRITICAL: Force-load environment configuration before any mode detection
// This ensures STRIPE_MODE, APP_ENV, and all Stripe keys are available
// even if this file is included before includes/init.php
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/../../config/config.php';
}

// Load Stripe library via Composer
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    throw new RuntimeException('Stripe library not installed. Run: composer install');
}

// Defensive: Provide env() fallback if not already defined
if (!function_exists('env')) {
    /**
     * Fallback env() helper function
     * Only used if config/config.php hasn't loaded yet
     * 
     * @param string $key Environment variable key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env($key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Convert string boolean to actual boolean
        if (is_string($value)) {
            if (strtolower($value) === 'true') return true;
            if (strtolower($value) === 'false') return false;
        }
        
        return $value;
    }
}

/**
 * Log Stripe configuration state for diagnostics
 * Logs a one-line summary without exposing full keys
 * 
 * @param string|null $explicitMode The explicit STRIPE_MODE env var value
 * @param string $computedMode The computed mode (test or live)
 * @return void
 */
function logStripeConfigState(?string $explicitMode, string $computedMode): void {
    $hasLiveSecret = !empty(env('STRIPE_LIVE_SECRET_KEY')) ? 'yes' : 'no';
    $hasLivePub = !empty(env('STRIPE_LIVE_PUBLISHABLE_KEY')) ? 'yes' : 'no';
    $hasTestSecret = !empty(env('STRIPE_TEST_SECRET_KEY')) ? 'yes' : 'no';
    $hasTestPub = !empty(env('STRIPE_TEST_PUBLISHABLE_KEY')) ? 'yes' : 'no';
    $appEnv = defined('APP_ENV') ? APP_ENV : env('APP_ENV', 'production');
    
    $explicitStr = $explicitMode ?: 'not set';
    
    error_log("[STRIPE][CONFIG] explicit={$explicitStr} computed={$computedMode} liveSec={$hasLiveSecret} livePub={$hasLivePub} testSec={$hasTestSecret} testPub={$hasTestPub} appEnv={$appEnv}");
}

/**
 * Get Stripe mode (test or live) from environment
 * 
 * === HOW MODE DETECTION WORKS ===
 * This function determines whether Stripe should operate in test or live mode.
 * It follows a strict hierarchy to ensure production safety:
 * 
 * 1. **Explicit Mode Check**: If STRIPE_MODE is set (via constant or env var),
 *    that value takes priority (must be 'test' or 'live', defaults to 'test' if invalid)
 * 
 * 2. **Smart Key Detection**: If no explicit mode is set:
 *    - If only live keys exist → use 'live' mode
 *    - If both live and test keys exist → prefer 'live' in production, 'test' otherwise
 *    - If no keys or only test keys → default to 'test' mode
 * 
 * === WHY LOADING CONFIG.PHP EARLY IS REQUIRED ===
 * This file may be included before includes/init.php in some execution paths.
 * If config.php hasn't loaded yet, environment variables won't be available,
 * causing STRIPE_MODE to appear empty even when correctly set in .env.
 * 
 * The guard at the top of this file (if !defined('APP_ENV')) ensures config.php
 * is always loaded first, preventing false positives in mode detection.
 * 
 * === SECURITY IMPLICATIONS OF TEST MODE IN PRODUCTION ===
 * Using test mode in production (APP_ENV=production) is a security risk because:
 * - Real customer orders would use fake payment processing
 * - Revenue tracking would be inaccurate
 * - Production data would mix with test transactions
 * 
 * This function enforces live mode in production by default. It will throw
 * a RuntimeException if test mode is detected in production, UNLESS
 * ALLOW_TEST_MODE_IN_PRODUCTION=true (not recommended, use only for debugging).
 * 
 * @return string 'test' or 'live'
 * @throws RuntimeException if production environment configured for test mode without override
 */
function getStripeMode(): string {
    // Check both defined constant and environment variable for maximum reliability
    // Defined constants take precedence (set by config.php when loaded)
    $explicitMode = defined('STRIPE_MODE') ? STRIPE_MODE : env('STRIPE_MODE');
    $appEnv = defined('APP_ENV') ? APP_ENV : env('APP_ENV', 'production');
    
    // Check if we have explicit mode setting
    if ($explicitMode !== null && $explicitMode !== '') {
        $mode = strtolower(trim($explicitMode));
        $mode = in_array($mode, ['test', 'live']) ? $mode : 'test';
    } else {
        // Smart detection: prefer live if live keys exist
        $hasLiveKeys = !empty(env('STRIPE_LIVE_SECRET_KEY')) || !empty(env('STRIPE_LIVE_PUBLISHABLE_KEY'));
        $hasTestKeys = !empty(env('STRIPE_TEST_SECRET_KEY')) || !empty(env('STRIPE_TEST_PUBLISHABLE_KEY'));
        
        if ($hasLiveKeys && !$hasTestKeys) {
            // Only live keys configured, use live mode
            $mode = 'live';
        } elseif ($hasLiveKeys && $hasTestKeys) {
            // Both sets exist, prefer live in production, test otherwise
            $mode = ($appEnv === 'production') ? 'live' : 'test';
        } else {
            // Default to test mode if no keys or only test keys
            $mode = 'test';
        }
    }
    
    // Production safeguard: enforce live mode in production environment
    if ($appEnv === 'production' && $mode !== 'live') {
        // Check override flag (both constant and env var)
        $allowTestInProduction = defined('ALLOW_TEST_MODE_IN_PRODUCTION') 
            ? ALLOW_TEST_MODE_IN_PRODUCTION 
            : env('ALLOW_TEST_MODE_IN_PRODUCTION', false);
        
        if ($allowTestInProduction === true || $allowTestInProduction === 'true') {
            // Override enabled - log warning but allow test mode
            error_log("[STRIPE][SECURITY WARNING] Test mode allowed in production via ALLOW_TEST_MODE_IN_PRODUCTION override flag. This should only be used for debugging! Real payments will NOT be processed.");
            logStripeConfigState($explicitMode, $mode);
        } else {
            // No override - throw security exception
            // Log configuration state for diagnostics
            logStripeConfigState($explicitMode, $mode);
            
            throw new RuntimeException(
                "SECURITY: Cannot use Stripe test mode in production environment (APP_ENV=production). " .
                "Set STRIPE_MODE=live and configure live keys, or set ALLOW_TEST_MODE_IN_PRODUCTION=true to override (not recommended)."
            );
        }
    }
    
    // Warn if test keys detected in production domain (after override check passes)
    if ($appEnv === 'production' && $mode === 'test') {
        error_log("[STRIPE][SECURITY WARNING] Using test mode in production environment. Real payments will not be processed!");
    }
    
    return $mode;
}

/**
 * Get Stripe publishable key based on current mode
 * Falls back to legacy STRIPE_PUBLISHABLE_KEY if segmented keys not found
 * 
 * In live mode, will NOT fall back to test keys - fails fast instead
 * 
 * @return string|null
 */
function getStripePublishableKey(): ?string {
    $mode = getStripeMode();
    
    // Try segmented keys first
    $key = null;
    if ($mode === 'test') {
        $key = env('STRIPE_TEST_PUBLISHABLE_KEY');
    } else {
        $key = env('STRIPE_LIVE_PUBLISHABLE_KEY');
    }
    
    // Fallback to legacy key if segmented key not found
    if (empty($key)) {
        $legacyKey = env('STRIPE_PUBLISHABLE_KEY');
        
        // In live mode, only accept live keys
        if ($mode === 'live') {
            if (!empty($legacyKey) && strpos($legacyKey, 'pk_live_') === 0) {
                $key = $legacyKey;
            }
            // Don't fall back to test keys in live mode - fail fast
        } else {
            // In test mode, accept any legacy key
            $key = $legacyKey;
        }
    }
    
    // Validate key format matches mode
    if (!empty($key)) {
        $expectedPrefix = $mode === 'test' ? 'pk_test_' : 'pk_live_';
        if (strpos($key, $expectedPrefix) !== 0) {
            error_log("[STRIPE][WARNING] Publishable key prefix mismatch: expected {$expectedPrefix} for {$mode} mode");
            // In live mode with wrong key type, reject it
            if ($mode === 'live' && strpos($key, 'pk_test_') === 0) {
                error_log("[STRIPE][ERROR] Cannot use test key (pk_test_) in live mode. Configure STRIPE_LIVE_PUBLISHABLE_KEY.");
                return null;
            }
        }
    }
    
    return $key ?: null;
}

/**
 * Get Stripe secret key based on current mode
 * Falls back to legacy STRIPE_SECRET_KEY if segmented keys not found
 * 
 * In live mode, will NOT fall back to test keys - fails fast instead
 * 
 * @return string|null
 */
function getStripeSecretKey(): ?string {
    $mode = getStripeMode();
    
    // Try segmented keys first
    $key = null;
    if ($mode === 'test') {
        $key = env('STRIPE_TEST_SECRET_KEY');
    } else {
        $key = env('STRIPE_LIVE_SECRET_KEY');
    }
    
    // Fallback to legacy key if segmented key not found
    if (empty($key)) {
        $legacyKey = env('STRIPE_SECRET_KEY');
        
        // In live mode, only accept live keys
        if ($mode === 'live') {
            if (!empty($legacyKey) && strpos($legacyKey, 'sk_live_') === 0) {
                $key = $legacyKey;
            }
            // Don't fall back to test keys in live mode - fail fast
        } else {
            // In test mode, accept any legacy key
            $key = $legacyKey;
        }
    }
    
    // Validate key format matches mode
    if (!empty($key)) {
        $expectedPrefix = $mode === 'test' ? 'sk_test_' : 'sk_live_';
        if (strpos($key, $expectedPrefix) !== 0) {
            error_log("[STRIPE][WARNING] Secret key prefix mismatch: expected {$expectedPrefix} for {$mode} mode, but key starts with " . substr($key, 0, 8));
            // In live mode with wrong key type, reject it
            if ($mode === 'live' && strpos($key, 'sk_test_') === 0) {
                error_log("[STRIPE][ERROR] Cannot use test key (sk_test_) in live mode. Configure STRIPE_LIVE_SECRET_KEY.");
                return null;
            }
        }
    }
    
    return $key ?: null;
}

/**
 * Get Stripe webhook secret based on current mode
 * Falls back to legacy STRIPE_WEBHOOK_SECRET if segmented keys not found
 * 
 * @return string|null
 */
function getStripeWebhookSecret(): ?string {
    $mode = getStripeMode();
    
    // Try segmented keys first
    $key = null;
    if ($mode === 'test') {
        $key = env('STRIPE_WEBHOOK_SECRET_TEST');
    } else {
        $key = env('STRIPE_WEBHOOK_SECRET_LIVE');
    }
    
    // Fallback to legacy key if segmented key not found
    if (empty($key)) {
        $key = env('STRIPE_WEBHOOK_SECRET');
    }
    
    return $key ?: null;
}

/**
 * Get Stripe currency setting
 * 
 * @return string Default is 'usd'
 */
function getStripeCurrency(): string {
    return strtolower(env('STRIPE_DEFAULT_CURRENCY', 'usd'));
}

/**
 * Get Stripe statement descriptor
 * 
 * @return string Default is 'FEZAMARKET'
 */
function getStripeStatementDescriptor(): string {
    return env('STRIPE_STATEMENT_DESCRIPTOR', 'FEZAMARKET');
}

/**
 * Get Stripe capture method (automatic or manual)
 * 
 * @return string 'automatic' or 'manual'
 */
function getStripeCaptureMethod(): string {
    $method = strtolower(env('STRIPE_CAPTURE_METHOD', 'automatic'));
    return in_array($method, ['automatic', 'manual']) ? $method : 'automatic';
}

/**
 * Initialize and configure Stripe API
 * 
 * @return \Stripe\StripeClient
 * @throws RuntimeException if secret key is not configured
 */
function initStripe(): \Stripe\StripeClient {
    $mode = getStripeMode();
    $secretKey = getStripeSecretKey();
    $publishableKey = getStripePublishableKey();
    
    // Get APP_ENV for logging
    $appEnv = defined('APP_ENV') ? APP_ENV : env('APP_ENV', 'production');
    
    // Log initialization for diagnostics
    error_log("[STRIPE] Initialized in {$mode} mode with APP_ENV={$appEnv}");
    
    // Enhanced validation: If STRIPE_MODE=live is explicitly set, ensure live keys are present
    $explicitMode = env('STRIPE_MODE');
    if ($explicitMode === 'live' && $mode === 'live') {
        $missingKeys = [];
        
        if (empty(env('STRIPE_LIVE_SECRET_KEY'))) {
            $missingKeys[] = 'STRIPE_LIVE_SECRET_KEY';
        }
        if (empty(env('STRIPE_LIVE_PUBLISHABLE_KEY'))) {
            $missingKeys[] = 'STRIPE_LIVE_PUBLISHABLE_KEY';
        }
        
        if (!empty($missingKeys)) {
            $missingList = implode(', ', $missingKeys);
            throw new RuntimeException(
                "STRIPE_MODE=live is set, but the following required environment variables are missing or empty: {$missingList}. " .
                "Please configure live Stripe keys in your .env file."
            );
        }
    }
    
    if (empty($secretKey)) {
        throw new RuntimeException(
            "Stripe secret key not configured for {$mode} mode. " .
            "Please set STRIPE_" . strtoupper($mode) . "_SECRET_KEY in your .env file."
        );
    }
    
    // Set API key globally (for backward compatibility with old Stripe library usage)
    \Stripe\Stripe::setApiKey($secretKey);
    
    // Set API version for consistency (recommended by Stripe)
    // Pin to a specific version to prevent unexpected changes
    $apiVersion = env('STRIPE_API_VERSION', '2024-11-20.acacia');
    \Stripe\Stripe::setApiVersion($apiVersion);
    
    // Set app info for better support and debugging (recommended by Stripe)
    // See: https://github.com/stripe/stripe-php#configuring-a-client
    $appVersion = defined('APP_VERSION') ? APP_VERSION : env('APP_VERSION', '2.0.0');
    \Stripe\Stripe::setAppInfo(
        'FezaMarket E-Commerce',
        $appVersion,
        env('APP_URL', 'https://fezamarket.com')
    );
    
    // Return StripeClient instance (preferred modern approach)
    // The StripeClient inherits the global configuration set above
    return new \Stripe\StripeClient($secretKey);
}

/**
 * Check if Stripe is properly configured
 * 
 * @return array ['configured' => bool, 'mode' => string, 'errors' => array]
 */
function checkStripeConfiguration(): array {
    $errors = [];
    $mode = getStripeMode();
    
    $secretKey = getStripeSecretKey();
    if (empty($secretKey)) {
        $errors[] = "Secret key not configured for {$mode} mode";
    }
    
    $publishableKey = getStripePublishableKey();
    if (empty($publishableKey)) {
        $errors[] = "Publishable key not configured for {$mode} mode";
    }
    
    $webhookSecret = getStripeWebhookSecret();
    if (empty($webhookSecret)) {
        $errors[] = "Webhook secret not configured for {$mode} mode";
    }
    
    return [
        'configured' => empty($errors),
        'mode' => $mode,
        'errors' => $errors
    ];
}

/**
 * Log Stripe-related messages with consistent prefix
 * 
 * @param string $message
 * @param string $level 'info', 'warning', 'error'
 */
function logStripe(string $message, string $level = 'info'): void {
    $prefix = "[STRIPE]";
    $mode = getStripeMode();
    $fullMessage = "{$prefix}[{$mode}] {$message}";
    error_log($fullMessage);
}
