<?php
/**
 * Global Error and Exception Handler
 * E-Commerce Platform - Admin Panel Enhancement
 */

class GlobalErrorHandler {
    private static bool $initialized = false;
    
    public static function initialize(): void {
        if (self::$initialized) {
            return;
        }
        
        // Set up error and exception handlers
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        
        // Shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
        
        self::$initialized = true;
    }
    
    /**
     * Handle regular PHP errors
     */
    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }
        
        $errorType = self::getErrorType($errno);
        $message = "PHP Error [$errorType]: $errstr in $errfile on line $errline";
        
        self::logError($message, $errno, $errfile, $errline);
        
        // In development, show errors
        if (APP_DEBUG) {
            echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;'>";
            echo "<strong>$errorType:</strong> $errstr<br>";
            echo "<strong>File:</strong> $errfile<br>";
            echo "<strong>Line:</strong> $errline";
            echo "</div>";
        }
        
        return true;
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(Throwable $exception): void {
        $message = "Uncaught Exception: " . $exception->getMessage() . 
                  " in " . $exception->getFile() . " on line " . $exception->getLine();
        
        self::logError($message, 0, $exception->getFile(), $exception->getLine(), $exception);
        
        // Show user-friendly error page
        if (!headers_sent()) {
            http_response_code(500);
        }
        
        if (APP_DEBUG) {
            self::showDebugError($exception);
        } else {
            self::showProductionError();
        }
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleShutdown(): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
            self::logError($message, $error['type'], $error['file'], $error['line']);
            
            if (!APP_DEBUG) {
                self::showProductionError();
            }
        }
    }
    
    /**
     * Log error to file and system log
     */
    private static function logError(string $message, int $errno = 0, string $file = '', int $line = 0, ?Throwable $exception = null): void {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $logEntry = "[$timestamp] $message\n";
        $logEntry .= "IP: $ip | User-Agent: $userAgent | URI: $requestUri\n";
        
        if ($exception) {
            $logEntry .= "Stack Trace:\n" . $exception->getTraceAsString() . "\n";
        }
        
        $logEntry .= str_repeat('-', 80) . "\n";
        
        // Log to application log file
        $logFile = ERROR_LOG_PATH . 'app.log';
        error_log($logEntry, 3, $logFile);
        
        // Also use Logger class if available
        if (class_exists('Logger')) {
            Logger::error($message);
        }
    }
    
    /**
     * Show debug error page (development)
     */
    private static function showDebugError(Throwable $exception): void {
        if (headers_sent()) {
            return;
        }
        
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Error - " . APP_NAME . "</title>";
        echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .error{background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:20px;border-radius:4px;} .trace{background:#f1f3f4;padding:15px;margin-top:15px;overflow:auto;} pre{margin:0;}</style>";
        echo "</head><body>";
        echo "<div class='error'>";
        echo "<h2>🚨 Application Error</h2>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<div class='trace'><strong>Stack Trace:</strong><pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre></div>";
        echo "</div>";
        echo "</body></html>";
    }
    
    /**
     * Show production error page
     */
    private static function showProductionError(): void {
        if (headers_sent()) {
            return;
        }
        
        echo "<!DOCTYPE html>";
        echo "<html><head><title>Error - " . APP_NAME . "</title>";
        echo "<style>body{font-family:Arial,sans-serif;text-align:center;margin-top:100px;color:#666;} .error-box{max-width:400px;margin:0 auto;padding:30px;border:1px solid #ddd;border-radius:8px;}</style>";
        echo "</head><body>";
        echo "<div class='error-box'>";
        echo "<h2>⚠️ Something went wrong</h2>";
        echo "<p>We're sorry, but something went wrong. Our team has been notified.</p>";
        echo "<p><a href='/'>← Return to Homepage</a></p>";
        echo "</div>";
        echo "</body></html>";
    }
    
    /**
     * Get human-readable error type
     */
    private static function getErrorType(int $errno): string {
        switch ($errno) {
            case E_ERROR: return 'Fatal Error';
            case E_WARNING: return 'Warning';
            case E_PARSE: return 'Parse Error';
            case E_NOTICE: return 'Notice';
            case E_CORE_ERROR: return 'Core Error';
            case E_CORE_WARNING: return 'Core Warning';
            case E_COMPILE_ERROR: return 'Compile Error';
            case E_COMPILE_WARNING: return 'Compile Warning';
            case E_USER_ERROR: return 'User Error';
            case E_USER_WARNING: return 'User Warning';
            case E_USER_NOTICE: return 'User Notice';
            case E_STRICT: return 'Strict Standards';
            case E_RECOVERABLE_ERROR: return 'Recoverable Error';
            case E_DEPRECATED: return 'Deprecated';
            case E_USER_DEPRECATED: return 'User Deprecated';
            default: return 'Unknown Error';
        }
    }
}