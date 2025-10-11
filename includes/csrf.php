<?php
declare(strict_types=1);

/**
 * CSRF utilities
 * - getCsrfToken(): returns a hidden input for HTML forms
 * - csrfTokenInput(): alias for getCsrfToken() (compat)
 * - csrfMeta(): returns a meta tag for AJAX clients
 * - validateCsrfAndRateLimit(): validates CSRF on POST and rate limits submissions
 * - csrfToken(): returns the raw token string
 * - generateCsrfToken(): alias for csrfToken() (compat)
 * - verifyCsrfToken($token): boolean validator (compat)
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Ensure a CSRF token exists in the session and return it.
 * GUARDED to prevent redeclaration with functions.php
 */
if (!function_exists('csrfToken')) {
    function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }
}

/**
 * Compatibility alias if other code expects generateCsrfToken().
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken(): string {
        return csrfToken();
    }
}

/**
 * Return a hidden input containing the CSRF token for use inside <form>.
 * Usage: echo getCsrfToken();
 * GUARDED to prevent redeclaration
 */
if (!function_exists('getCsrfToken')) {
    function getCsrfToken(): string {
        $token = csrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

/**
 * Compatibility alias if templates call csrfTokenInput()
 */
if (!function_exists('csrfTokenInput')) {
    function csrfTokenInput(): string {
        return getCsrfToken();
    }
}

/**
 * Return a meta tag with CSRF token for AJAX (read it in JS and send via X-CSRF-Token).
 * Include this in <head>.
 * GUARDED to prevent redeclaration
 */
if (!function_exists('csrfMeta')) {
    function csrfMeta(): string {
        $token = csrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

/**
 * Simple boolean validator for CSRF tokens (compat for existing code).
 */
if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(?string $token): bool {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (!$sessionToken || !is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}

/**
 * Compatibility alias for validateCSRFToken (note capital letters).
 * Some modules use this naming convention.
 */
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken(?string $token): bool {
        return verifyCsrfToken($token);
    }
}

/**
 * Validate the CSRF token on POST requests and apply a simple per-session rate limit.
 * Throws Exception on failure so callers can catch and surface a friendly error.
 * GUARDED to prevent redeclaration
 */
if (!function_exists('validateCsrfAndRateLimit')) {
    function validateCsrfAndRateLimit(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) !== 'POST') {
            return;
        }

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $postToken = $_POST['csrf_token'] ?? '';

        // Also accept X-CSRF-Token for AJAX requests
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $headers[$name] = $value;
                }
            }
        }
        $headerToken = $headers['X-CSRF-Token'] ?? $headers['X-Csrf-Token'] ?? '';

        $suppliedToken = $postToken ?: $headerToken;

        if (!$sessionToken || !$suppliedToken || !hash_equals($sessionToken, $suppliedToken)) {
            throw new Exception('Invalid CSRF token.');
        }

        // Simple rate limiting: 3 requests per 2 seconds
        $now = microtime(true);
        $window = 2.0;
        $maxRequests = 3;

        if (!isset($_SESSION['csrf_rl'])) {
            $_SESSION['csrf_rl'] = [];
        }

        // Remove timestamps outside the window
        $_SESSION['csrf_rl'] = array_filter(
            (array) $_SESSION['csrf_rl'],
            static fn($t) => ($now - (float) $t) <= $window
        );

        if (count($_SESSION['csrf_rl']) >= $maxRequests) {
            throw new Exception('Too many requests. Please wait a moment and try again.');
        }

        // Record this request
        $_SESSION['csrf_rl'][] = $now;
    }
}