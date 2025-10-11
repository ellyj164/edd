<?php
/**
 * Toast Notification Component
 * Server-side component for generating toast HTML
 */

class Toast {
    
    /**
     * Render toast notification HTML that works with the JavaScript Toast module
     * 
     * @param string $message Toast message
     * @param string $type Toast type (success, error, warning, info)
     * @param array $options Display options
     * @return string HTML output
     */
    public static function render($message, $type = 'info', $options = []) {
        $options = array_merge([
            'duration' => 4000,
            'closable' => true,
            'icon' => true,
            'position' => 'top-right' // top-right, top-left, bottom-right, bottom-left
        ], $options);
        
        $typeClasses = self::getTypeClasses($type);
        $icon = self::getIcon($type);
        $toastId = 'toast-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo $toastId; ?>" 
             class="toast <?php echo $typeClasses; ?> flex items-center gap-3 px-6 py-4 rounded-lg shadow-lg max-w-md transform translate-x-full transition-transform duration-300 ease-out"
             role="alert"
             aria-live="polite"
             aria-atomic="true"
             data-duration="<?php echo $options['duration']; ?>">
            
            <?php if ($options['icon']): ?>
                <span class="toast-icon text-lg flex-shrink-0" aria-hidden="true">
                    <?php echo $icon; ?>
                </span>
            <?php endif; ?>
            
            <div class="toast-content flex-1">
                <p class="toast-message text-sm font-medium m-0">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            </div>
            
            <?php if ($options['closable']): ?>
                <button class="toast-close ml-2 text-lg opacity-70 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-current rounded p-1 flex-shrink-0" 
                        aria-label="Close notification"
                        onclick="UI.Toast.remove(this.closest('.toast'))">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
        
        <!-- Auto-initialize script for server-rendered toasts -->
        <script>
        (function() {
            const toast = document.getElementById('<?php echo $toastId; ?>');
            if (toast && window.UI && window.UI.Toast) {
                // Animate in
                requestAnimationFrame(() => {
                    toast.classList.remove('translate-x-full');
                });
                
                // Auto remove after duration
                setTimeout(() => {
                    if (toast.parentNode) {
                        UI.Toast.remove(toast);
                    }
                }, <?php echo $options['duration']; ?>);
            }
        })();
        </script>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get CSS classes for different toast types
     */
    private static function getTypeClasses($type) {
        $classes = [
            'success' => 'bg-green-50 text-green-800 border border-green-200',
            'error' => 'bg-red-50 text-red-800 border border-red-200',
            'warning' => 'bg-yellow-50 text-yellow-800 border border-yellow-200',
            'info' => 'bg-blue-50 text-blue-800 border border-blue-200'
        ];
        
        return $classes[$type] ?? $classes['info'];
    }
    
    /**
     * Get icon for different toast types
     */
    private static function getIcon($type) {
        $icons = [
            'success' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            'error' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            'warning' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            'info' => '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        ];
        
        return $icons[$type] ?? $icons['info'];
    }
    
    /**
     * Generate JavaScript code to show a toast client-side
     */
    public static function script($message, $type = 'info', $duration = 4000) {
        $message = addslashes(htmlspecialchars($message));
        return "<script>UI.Toast.show('{$message}', '{$type}', {$duration});</script>";
    }
    
    /**
     * Render success toast
     */
    public static function success($message, $options = []) {
        return self::render($message, 'success', $options);
    }
    
    /**
     * Render error toast
     */
    public static function error($message, $options = []) {
        return self::render($message, 'error', $options);
    }
    
    /**
     * Render warning toast
     */
    public static function warning($message, $options = []) {
        return self::render($message, 'warning', $options);
    }
    
    /**
     * Render info toast
     */
    public static function info($message, $options = []) {
        return self::render($message, 'info', $options);
    }
    
    /**
     * Session-based flash messages
     */
    public static function flash($key = 'flash_message') {
        if (!isset($_SESSION[$key])) {
            return '';
        }
        
        $flash = $_SESSION[$key];
        unset($_SESSION[$key]);
        
        if (is_array($flash) && isset($flash['message'], $flash['type'])) {
            return self::render($flash['message'], $flash['type'], $flash['options'] ?? []);
        }
        
        return '';
    }
    
    /**
     * Set a flash message for next page load
     */
    public static function setFlash($message, $type = 'info', $key = 'flash_message') {
        $_SESSION[$key] = [
            'message' => $message,
            'type' => $type,
            'options' => []
        ];
    }
}
?>