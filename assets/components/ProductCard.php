<?php
/**
 * Reusable Product Card Component
 * Accessible, responsive, and optimized for performance
 */

class ProductCard {
    
    /**
     * Render a product card with modern design and accessibility
     * 
     * @param array $product Product data
     * @param array $options Display options
     * @return string HTML output
     */
    public static function render($product, $options = []) {
        $options = array_merge([
            'show_rating' => true,
            'show_badge' => true,
            'lazy_load' => true,
            'show_quick_actions' => true,
            'size' => 'default' // default, compact, large
        ], $options);
        
        $cardClass = self::getCardClasses($options['size']);
        $imageUrl = getSafeProductImageUrl($product, '/images/placeholder-product.png');
        $price = number_format($product['price'], 2);
        $comparePrice = isset($product['compare_price']) && $product['compare_price'] > $product['price'] 
            ? number_format($product['compare_price'], 2) : null;
        
        $productId = $product['id'];
        $productName = htmlspecialchars($product['name']);
        $productUrl = "/product.php?id=" . $productId;
        
        ob_start();
        ?>
        <div class="product-card <?php echo $cardClass; ?> card card-hover group" 
             data-product-id="<?php echo $productId; ?>"
             role="article" 
             aria-labelledby="product-title-<?php echo $productId; ?>">
             
            <!-- Product Image -->
            <div class="product-image-container relative overflow-hidden bg-neutral-100">
                <a href="<?php echo $productUrl; ?>" 
                   class="block aspect-square" 
                   aria-label="View <?php echo $productName; ?> details">
                   
                    <?php if ($options['lazy_load']): ?>
                        <img class="product-image w-full h-full object-cover transition-transform group-hover:scale-105"
                             data-src="<?php echo $imageUrl; ?>"
                             data-srcset="<?php echo $imageUrl; ?> 1x, <?php echo str_replace('.jpg', '@2x.jpg', $imageUrl); ?> 2x"
                             alt="<?php echo $productName; ?>"
                             loading="lazy">
                    <?php else: ?>
                        <img class="product-image w-full h-full object-cover transition-transform group-hover:scale-105"
                             src="<?php echo $imageUrl; ?>"
                             srcset="<?php echo $imageUrl; ?> 1x, <?php echo str_replace('.jpg', '@2x.jpg', $imageUrl); ?> 2x"
                             alt="<?php echo $productName; ?>">
                    <?php endif; ?>
                </a>
                
                <!-- Product Badge -->
                <?php if ($options['show_badge'] && isset($product['badge'])): ?>
                    <div class="product-badge absolute top-2 left-2">
                        <?php echo self::renderBadge($product['badge']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <?php if ($options['show_quick_actions']): ?>
                    <div class="quick-actions absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button class="quick-action-btn wishlist-btn p-2 bg-white rounded-full shadow-md hover:shadow-lg transition-shadow"
                                data-product-id="<?php echo $productId; ?>"
                                aria-label="Add <?php echo $productName; ?> to wishlist"
                                title="Add to Wishlist">
                            <svg class="w-4 h-4 text-neutral-600 hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Content -->
            <div class="product-content p-4 flex flex-col gap-3">
                
                <!-- Product Title -->
                <h3 id="product-title-<?php echo $productId; ?>" 
                    class="product-title text-sm font-medium text-neutral-800 line-clamp-2 group-hover:text-primary-600 transition-colors">
                    <a href="<?php echo $productUrl; ?>" class="hover:underline">
                        <?php echo $productName; ?>
                    </a>
                </h3>
                
                <!-- Rating -->
                <?php if ($options['show_rating'] && isset($product['rating'])): ?>
                    <div class="product-rating">
                        <?php echo RatingStars::render($product['rating'], $product['rating_count'] ?? 0); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Price -->
                <div class="product-price">
                    <?php echo PriceBadge::render($product['price'], $comparePrice); ?>
                </div>
                
                <!-- Add to Cart Button -->
                <div class="product-actions mt-auto">
                    <?php echo AddToCartButton::render($productId, [
                        'size' => 'sm',
                        'full_width' => true,
                        'product_name' => $productName
                    ]); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get CSS classes based on card size
     */
    private static function getCardClasses($size) {
        $classes = [
            'compact' => 'w-48',
            'default' => 'w-64',
            'large' => 'w-80'
        ];
        
        return $classes[$size] ?? $classes['default'];
    }
    
    /**
     * Render product badge
     */
    private static function renderBadge($badge) {
        $badges = [
            'new' => '<span class="badge badge-primary">New</span>',
            'sale' => '<span class="badge badge-error">Sale</span>',
            'featured' => '<span class="badge badge-warning">Featured</span>',
            'bestseller' => '<span class="badge badge-success">Best Seller</span>',
        ];
        
        return $badges[$badge] ?? '';
    }
}
?>