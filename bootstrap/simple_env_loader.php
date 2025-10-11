<?php
/**
 * Simple Environment Variable Loader
 * 
 * Minimal .env file parser for runtime environment loading.
 * Loads variables into both putenv() and $_ENV if not already set.
 * 
 * This loader is idempotent (safe to include multiple times).
 * 
 * Usage:
 *   require_once __DIR__ . '/bootstrap/simple_env_loader.php';
 * 
 * @package EDD
 * @since 2.0.0
 */

// Guard against multiple inclusions
if (defined('SIMPLE_ENV_LOADER_LOADED')) {
    return;
}
define('SIMPLE_ENV_LOADER_LOADED', true);

/**
 * Load environment variables from .env file
 * 
 * Parses KEY=VALUE lines, ignoring comments and empty lines.
 * Values are stripped of surrounding quotes.
 * 
 * @param string $envPath Path to .env file (default: repository root)
 * @return void
 */
function loadSimpleEnv($envPath = null) {
    // Determine .env file path
    if ($envPath === null) {
        $envPath = __DIR__ . '/../.env';
    }
    
    // Check if file exists
    if (!file_exists($envPath)) {
        // Silently return if .env doesn't exist (not an error in all cases)
        return;
    }
    
    // Read file contents
    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("[ENV_LOADER] Failed to read .env file at: {$envPath}");
        return;
    }
    
    // Parse each line
    foreach ($lines as $lineNum => $line) {
        // Trim whitespace
        $line = trim($line);
        
        // Skip empty lines
        if (empty($line)) {
            continue;
        }
        
        // Skip comments (lines starting with #)
        if (strpos($line, '#') === 0) {
            continue;
        }
        
        // Check for KEY=VALUE format
        if (strpos($line, '=') === false) {
            continue; // Skip malformed lines
        }
        
        // Split on first = sign
        list($key, $value) = explode('=', $line, 2);
        
        // Clean key and value
        $key = trim($key);
        $value = trim($value);
        
        // Strip surrounding quotes from value (both single and double)
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        // Only set if not already defined (existing env vars take precedence)
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Auto-load .env file when this script is included
loadSimpleEnv();
