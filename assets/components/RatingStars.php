<?php
/**
 * Reusable Rating Stars Component
 * Accessible star rating display with screen reader support
 */

class RatingStars {
    
    /**
     * Render star rating with accessibility features
     * 
     * @param float $rating Rating value (0-5)
     * @param int $reviewCount Number of reviews
     * @param array $options Display options
     * @return string HTML output
     */
    public static function render($rating, $reviewCount = 0, $options = []) {
        $options = array_merge([
            'size' => 'default', // sm, default, lg
            'show_count' => true,
            'show_rating_value' => false,
            'interactive' => false,
            'color' => 'yellow' // yellow, primary, custom
        ], $options);
        
        $rating = max(0, min(5, $rating)); // Ensure rating is between 0-5
        $fullStars = floor($rating);
        $hasHalfStar = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
        
        $sizeClasses = self::getSizeClasses($options['size']);
        $colorClasses = self::getColorClasses($options['color']);
        
        $ariaLabel = self::buildAriaLabel($rating, $reviewCount);
        
        ob_start();
        ?>
        <div class="rating-stars <?php echo $options['interactive'] ? 'interactive' : 'static'; ?>" 
             role="img" 
             aria-label="<?php echo $ariaLabel; ?>">
             
            <!-- Star Container -->
            <div class="stars-container flex items-center gap-1">
                
                <!-- Full Stars -->
                <?php for ($i = 0; $i < $fullStars; $i++): ?>
                    <svg class="star filled <?php echo $sizeClasses; ?> <?php echo $colorClasses['filled']; ?>" 
                         fill="currentColor" viewBox="0 0 20 20" 
                         aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
                
                <!-- Half Star -->
                <?php if ($hasHalfStar): ?>
                    <div class="star half-star relative <?php echo $sizeClasses; ?>">
                        <!-- Background (empty) star -->
                        <svg class="absolute <?php echo $colorClasses['empty']; ?>" 
                             fill="currentColor" viewBox="0 0 20 20" 
                             aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <!-- Foreground (filled) star with 50% clip -->
                        <svg class="absolute <?php echo $colorClasses['filled']; ?>" 
                             style="clip-path: inset(0 50% 0 0);"
                             fill="currentColor" viewBox="0 0 20 20" 
                             aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                <?php endif; ?>
                
                <!-- Empty Stars -->
                <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                    <svg class="star empty <?php echo $sizeClasses; ?> <?php echo $colorClasses['empty']; ?>" 
                         fill="currentColor" viewBox="0 0 20 20" 
                         aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
                
                <!-- Rating Text -->
                <?php if ($options['show_rating_value'] || $options['show_count']): ?>
                    <div class="rating-text flex items-center ml-2 text-sm text-neutral-600">
                        <?php if ($options['show_rating_value']): ?>
                            <span class="rating-value font-medium" aria-label="Rating: <?php echo number_format($rating, 1); ?> out of 5">
                                <?php echo number_format($rating, 1); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($options['show_count'] && $reviewCount > 0): ?>
                            <span class="review-count <?php echo $options['show_rating_value'] ? 'ml-1' : ''; ?>"
                                  aria-label="<?php echo number_format($reviewCount); ?> reviews">
                                (<?php echo number_format($reviewCount); ?>)
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Hidden data for screen readers -->
        <span class="sr-only">
            <?php echo $ariaLabel; ?>
        </span>
        
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get size-based CSS classes
     */
    private static function getSizeClasses($size) {
        $classes = [
            'sm' => 'w-3 h-3',
            'default' => 'w-4 h-4',
            'lg' => 'w-5 h-5'
        ];
        
        return $classes[$size] ?? $classes['default'];
    }
    
    /**
     * Get color-based CSS classes
     */
    private static function getColorClasses($color) {
        $classes = [
            'yellow' => [
                'filled' => 'text-yellow-400',
                'empty' => 'text-neutral-300'
            ],
            'primary' => [
                'filled' => 'text-primary-500',
                'empty' => 'text-neutral-300'
            ],
            'custom' => [
                'filled' => 'text-orange-400',
                'empty' => 'text-neutral-300'
            ]
        ];
        
        return $classes[$color] ?? $classes['yellow'];
    }
    
    /**
     * Build accessible aria-label
     */
    private static function buildAriaLabel($rating, $reviewCount) {
        $ratingText = number_format($rating, 1) . " out of 5 stars";
        
        if ($reviewCount > 0) {
            $reviewText = $reviewCount == 1 ? "1 review" : number_format($reviewCount) . " reviews";
            return "Rated {$ratingText} based on {$reviewText}";
        }
        
        return "Rated {$ratingText}";
    }
    
    /**
     * Render compact rating for lists
     */
    public static function compact($rating, $reviewCount = 0) {
        return self::render($rating, $reviewCount, [
            'size' => 'sm',
            'show_count' => true,
            'show_rating_value' => false
        ]);
    }
    
    /**
     * Render detailed rating for product pages
     */
    public static function detailed($rating, $reviewCount = 0) {
        return self::render($rating, $reviewCount, [
            'size' => 'lg',
            'show_count' => true,
            'show_rating_value' => true
        ]);
    }
}
?>