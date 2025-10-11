<?php
/**
 * Reusable Add to Cart Button Component
 * Accessible button with loading states and error handling
 */

class AddToCartButton {
    
    /**
     * Render add to cart button with accessibility and interaction states
     * 
     * @param int $productId Product ID
     * @param array $options Button options
     * @return string HTML output
     */
    public static function render($productId, $options = []) {
        $options = array_merge([
            'size' => 'default', // sm, default, lg
            'variant' => 'primary', // primary, secondary, outline
            'full_width' => false,
            'icon' => true,
            'text' => 'Add to Cart',
            'loading_text' => 'Adding...',
            'success_text' => 'Added!',
            'disabled' => false,
            'quantity' => 1,
            'product_name' => '',
            'show_quantity_selector' => false
        ], $options);
        
        $buttonClasses = self::getButtonClasses($options);
        $productName = htmlspecialchars($options['product_name']);
        $ariaLabel = $productName ? "Add {$productName} to cart" : "Add to cart";
        
        ob_start();
        ?>
        <div class="add-to-cart-container" data-product-id="<?php echo $productId; ?>">
            
            <?php if ($options['show_quantity_selector']): ?>
                <!-- Quantity Selector -->
                <div class="quantity-selector flex items-center gap-2 mb-3">
                    <label for="quantity-<?php echo $productId; ?>" class="text-sm font-medium text-neutral-700">
                        Quantity:
                    </label>
                    <div class="quantity-controls flex items-center border border-neutral-300 rounded-lg overflow-hidden">
                        <button type="button" 
                                class="quantity-btn decrease px-3 py-2 bg-neutral-50 hover:bg-neutral-100 text-neutral-600 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                data-action="decrease"
                                aria-label="Decrease quantity">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <input type="number" 
                               id="quantity-<?php echo $productId; ?>"
                               class="quantity-input w-16 px-3 py-2 text-center border-0 focus:outline-none focus:ring-0"
                               value="<?php echo $options['quantity']; ?>" 
                               min="1" 
                               max="99"
                               aria-label="Product quantity">
                        <button type="button" 
                                class="quantity-btn increase px-3 py-2 bg-neutral-50 hover:bg-neutral-100 text-neutral-600 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                data-action="increase"
                                aria-label="Increase quantity">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Add to Cart Button -->
            <button type="button" 
                    class="add-to-cart-btn <?php echo $buttonClasses; ?>"
                    data-product-id="<?php echo $productId; ?>"
                    data-quantity="<?php echo $options['quantity']; ?>"
                    data-loading-text="<?php echo htmlspecialchars($options['loading_text']); ?>"
                    data-success-text="<?php echo htmlspecialchars($options['success_text']); ?>"
                    data-default-text="<?php echo htmlspecialchars($options['text']); ?>"
                    aria-label="<?php echo $ariaLabel; ?>"
                    <?php echo $options['disabled'] ? 'disabled' : ''; ?>>
                
                <!-- Button Content Container -->
                <span class="button-content flex items-center justify-center gap-2">
                    
                    <!-- Loading Spinner (hidden by default) -->
                    <svg class="loading-spinner animate-spin w-4 h-4 hidden" 
                         fill="none" viewBox="0 0 24 24" 
                         aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    
                    <!-- Cart Icon -->
                    <?php if ($options['icon']): ?>
                        <svg class="cart-icon w-4 h-4" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24" 
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5L21 21H9m0 0v-8"></path>
                        </svg>
                    <?php endif; ?>
                    
                    <!-- Success Icon (hidden by default) -->
                    <svg class="success-icon w-4 h-4 hidden text-green-500" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24" 
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M5 13l4 4L19 7"></path>
                    </svg>
                    
                    <!-- Button Text -->
                    <span class="button-text">
                        <?php echo htmlspecialchars($options['text']); ?>
                    </span>
                </span>
                
                <!-- Screen Reader Status Updates -->
                <span class="sr-only" aria-live="polite" aria-atomic="true"></span>
            </button>
            
            <!-- Error Message Container -->
            <div class="error-message hidden mt-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"
                 role="alert" 
                 aria-live="polite">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="error-text"></span>
                </div>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get button CSS classes based on options
     */
    private static function getButtonClasses($options) {
        $baseClasses = 'btn transition-all duration-200 focus-ring relative';
        
        // Size classes
        $sizeClasses = [
            'sm' => 'btn-sm',
            'default' => '',
            'lg' => 'btn-lg'
        ];
        
        // Variant classes
        $variantClasses = [
            'primary' => 'btn-primary',
            'secondary' => 'btn-secondary',
            'outline' => 'btn-outline'
        ];
        
        // Width classes
        $widthClass = $options['full_width'] ? 'w-full' : '';
        
        // Disabled state
        $disabledClass = $options['disabled'] ? 'opacity-50 cursor-not-allowed' : '';
        
        return implode(' ', array_filter([
            $baseClasses,
            $sizeClasses[$options['size']] ?? '',
            $variantClasses[$options['variant']] ?? $variantClasses['primary'],
            $widthClass,
            $disabledClass
        ]));
    }
    
    /**
     * Render simple add to cart button without quantity selector
     */
    public static function simple($productId, $text = 'Add to Cart') {
        return self::render($productId, [
            'text' => $text,
            'show_quantity_selector' => false,
            'size' => 'default'
        ]);
    }
    
    /**
     * Render compact button for product cards
     */
    public static function compact($productId) {
        return self::render($productId, [
            'size' => 'sm',
            'full_width' => true,
            'show_quantity_selector' => false,
            'text' => 'Add to Cart'
        ]);
    }
    
    /**
     * Render full-featured button for product detail page
     */
    public static function detailed($productId, $productName = '') {
        return self::render($productId, [
            'size' => 'lg',
            'show_quantity_selector' => true,
            'product_name' => $productName,
            'text' => 'Add to Cart'
        ]);
    }
}
?>