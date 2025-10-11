<?php
/**
 * Reusable Price Badge Component
 * Displays price with optional compare price and discount calculations
 */

class PriceBadge {
    
    /**
     * Render price badge with accessibility and semantic markup
     * 
     * @param float $price Current price
     * @param float|null $comparePrice Original price for comparison
     * @param array $options Display options
     * @return string HTML output
     */
    public static function render($price, $comparePrice = null, $options = []) {
        $options = array_merge([
            'currency' => 'USD',
            'currency_symbol' => '$',
            'show_discount' => true,
            'size' => 'default', // sm, default, lg
            'layout' => 'horizontal' // horizontal, vertical
        ], $options);
        
        $formattedPrice = self::formatPrice($price, $options['currency_symbol']);
        $hasDiscount = $comparePrice && $comparePrice > $price;
        $discountPercent = $hasDiscount ? round((($comparePrice - $price) / $comparePrice) * 100) : 0;
        $formattedComparePrice = $hasDiscount ? self::formatPrice($comparePrice, $options['currency_symbol']) : null;
        
        $sizeClasses = self::getSizeClasses($options['size']);
        $layoutClasses = self::getLayoutClasses($options['layout']);
        
        ob_start();
        ?>
        <div class="price-badge <?php echo $layoutClasses; ?>" role="region" aria-label="Product pricing">
            
            <!-- Current Price -->
            <div class="current-price">
                <span class="price-amount <?php echo $sizeClasses['primary']; ?> font-bold text-neutral-900" 
                      aria-label="Current price">
                    <?php echo $formattedPrice; ?>
                </span>
                
                <?php if ($hasDiscount && $options['show_discount']): ?>
                    <span class="discount-badge inline-flex items-center px-2 py-1 ml-2 text-xs font-medium bg-red-100 text-red-800 rounded-full"
                          aria-label="<?php echo $discountPercent; ?>% discount">
                        -<?php echo $discountPercent; ?>%
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Compare Price -->
            <?php if ($hasDiscount): ?>
                <div class="compare-price">
                    <span class="original-price <?php echo $sizeClasses['secondary']; ?> text-neutral-500 line-through" 
                          aria-label="Original price">
                        <?php echo $formattedComparePrice; ?>
                    </span>
                    
                    <?php if ($options['show_discount']): ?>
                        <span class="savings-amount <?php echo $sizeClasses['tertiary']; ?> text-success-600 ml-2"
                              aria-label="You save <?php echo self::formatPrice($comparePrice - $price, $options['currency_symbol']); ?>">
                            Save <?php echo self::formatPrice($comparePrice - $price, $options['currency_symbol']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($hasDiscount): ?>
            <!-- Structured Data for SEO -->
            <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "Offer",
                "price": "<?php echo $price; ?>",
                "priceCurrency": "<?php echo $options['currency']; ?>",
                "priceValidUntil": "<?php echo date('Y-m-d', strtotime('+30 days')); ?>",
                "availability": "https://schema.org/InStock"
            }
            </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Format price with currency symbol
     */
    private static function formatPrice($price, $symbol = '$') {
        return $symbol . number_format($price, 2);
    }
    
    /**
     * Get size-based CSS classes
     */
    private static function getSizeClasses($size) {
        $classes = [
            'sm' => [
                'primary' => 'text-sm',
                'secondary' => 'text-xs',
                'tertiary' => 'text-xs'
            ],
            'default' => [
                'primary' => 'text-lg',
                'secondary' => 'text-sm',
                'tertiary' => 'text-sm'
            ],
            'lg' => [
                'primary' => 'text-2xl',
                'secondary' => 'text-lg',
                'tertiary' => 'text-base'
            ]
        ];
        
        return $classes[$size] ?? $classes['default'];
    }
    
    /**
     * Get layout-based CSS classes
     */
    private static function getLayoutClasses($layout) {
        $classes = [
            'horizontal' => 'flex flex-wrap items-center gap-2',
            'vertical' => 'flex flex-col space-y-1'
        ];
        
        return $classes[$layout] ?? $classes['horizontal'];
    }
    
    /**
     * Render compact price for lists and grids
     */
    public static function compact($price, $comparePrice = null) {
        return self::render($price, $comparePrice, [
            'size' => 'sm',
            'layout' => 'horizontal',
            'show_discount' => true
        ]);
    }
    
    /**
     * Render large price for product detail pages
     */
    public static function large($price, $comparePrice = null) {
        return self::render($price, $comparePrice, [
            'size' => 'lg',
            'layout' => 'vertical',
            'show_discount' => true
        ]);
    }
}
?>