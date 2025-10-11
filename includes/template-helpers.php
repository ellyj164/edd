<?php
/**
 * E-commerce Template Helper Functions - Platform Products
 *
 * This file fetches real products from your platform's database
 */

// Ensure database connection is available
require_once __DIR__ . '/db.php';

class PlatformDataFetcher {
    private $db;
    private $cache_duration = 300; // 5 minutes cache
    
    public function __construct() {
        // Use the db() function from your existing setup
        try {
            $this->db = db();
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("PlatformDataFetcher initialization error: " . $e->getMessage());
            $this->db = null;
        }
    }
    
    /**
     * Check if database is available
     */
    private function isDatabaseAvailable() {
        return $this->db !== null && $this->db instanceof PDO;
    }
    
    /**
     * Fetch products from platform (matches your actual database structure)
     */
    public function fetchPlatformProducts($category = null, $limit = 12, $featured_only = false) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Query based on your actual database structure
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    WHERE p.status = 'active'";
            
            $params = [];
            
            if ($category && $category !== 'all') {
                $sql .= " AND c.slug = :category";
                $params['category'] = $category;
            }
            
            if ($featured_only) {
                // Check if featured column exists, otherwise use a fallback
                try {
                    $this->db->query("SELECT featured FROM products LIMIT 1");
                    $sql .= " AND p.featured = 1";
                } catch (Exception $e) {
                    // Featured column doesn't exist, order by newest instead
                    $sql .= " AND p.id IN (SELECT id FROM products ORDER BY created_at DESC LIMIT 10)";
                }
            }
            
            $sql .= " ORDER BY p.created_at DESC LIMIT :limit";
            $params['limit'] = $limit;
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                if ($key === 'limit') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching platform products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get trending products (based on recent sales and views)
     */
    public function getTrendingProducts($limit = 8) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Get products with most sales in the last 7 days
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name,
                           COALESCE(SUM(oi.quantity), 0) AS sold
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    LEFT JOIN order_items oi ON oi.product_id = p.id
                    LEFT JOIN orders o ON o.id = oi.order_id 
                        AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                        AND o.status IN ('paid','shipped','delivered')
                    WHERE p.status = 'active'
                    GROUP BY p.id, p.name, p.price, c.name, c.slug, u.username, u.first_name, u.last_name
                    ORDER BY sold DESC, p.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no products with sales, fall back to newest products
            if (empty($products)) {
                return $this->fetchPlatformProducts(null, $limit);
            }
            
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching trending products: " . $e->getMessage());
            // Fallback to newest products
            return $this->fetchPlatformProducts(null, $limit);
        }
    }
    
    /**
     * Get deals and discounted products
     */
    public function getDealsAndPromotions($limit = 6) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Look for products with compare_price > price (indicating discounts)
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name,
                           (p.compare_price - p.price) as discount_amount,
                           ROUND(((p.compare_price - p.price) / p.compare_price) * 100, 0) as discount_percent
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    WHERE p.status = 'active'
                    AND p.compare_price > 0 
                    AND p.compare_price > p.price
                    ORDER BY discount_percent DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no discounted products, get regular products
            if (empty($products)) {
                return $this->fetchPlatformProducts(null, $limit);
            }
            
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching deals: " . $e->getMessage());
            return $this->fetchPlatformProducts(null, $limit);
        }
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory($category_slug, $limit = 8) {
        return $this->fetchPlatformProducts($category_slug, $limit);
    }
    
    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 12) {
        return $this->fetchPlatformProducts(null, $limit, true);
    }
    
    /**
     * Get new arrivals (newest products)
     */
    public function getNewArrivals($limit = 8) {
        return $this->fetchPlatformProducts(null, $limit);
    }
    
    /**
     * Get best selling products (fallback to newest for now)
     */
    public function getBestSellers($limit = 8) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Get products ranked by sales_count (from migration)
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    WHERE p.status = 'active'
                    ORDER BY p.sales_count DESC, p.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If sales_count column doesn't exist yet (migration not applied), fall back
            if (empty($products)) {
                return $this->fetchPlatformProducts(null, $limit);
            }
            
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching best sellers: " . $e->getMessage());
            // Fallback to newest products
            return $this->fetchPlatformProducts(null, $limit);
        }
    }
    
    /**
     * Get popular products (ranked by view count)
     */
    public function getPopularProducts($limit = 8) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Get products ranked by view_count (from migration)
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    WHERE p.status = 'active'
                    ORDER BY p.view_count DESC, p.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If view_count column doesn't exist yet (migration not applied), fall back
            if (empty($products)) {
                return $this->fetchPlatformProducts(null, $limit);
            }
            
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching popular products: " . $e->getMessage());
            // Fallback to newest products
            return $this->fetchPlatformProducts(null, $limit);
        }
    }
    
    /**
     * Get AI recommended products (flagged by admin)
     */
    public function getAIRecommendedProducts($limit = 8) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Get products with is_ai_recommended flag set to 1
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    WHERE p.status = 'active'
                    AND p.is_ai_recommended = 1
                    ORDER BY p.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no AI recommended products, return empty array
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching AI recommended products: " . $e->getMessage());
            // Return empty array if column doesn't exist
            return [];
        }
    }
    
    /**
     * Get sponsored products (active sponsorships)
     */
    public function getSponsoredProducts($limit = 8) {
        if (!$this->isDatabaseAvailable()) {
            return [];
        }
        
        try {
            // Get sponsored products that are currently active
            $sql = "SELECT p.*, 
                           c.name as category_name,
                           c.slug as category_slug,
                           u.username as seller_name,
                           u.first_name,
                           u.last_name,
                           sp.position as sponsor_position
                    FROM sponsored_products sp
                    JOIN products p ON sp.product_id = p.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN users u ON p.seller_id = u.id
                    WHERE p.status = 'active'
                    AND sp.status = 'active'
                    AND sp.admin_approved = 1
                    AND sp.start_date <= NOW()
                    AND (sp.end_date IS NULL OR sp.end_date >= NOW())
                    ORDER BY sp.position ASC, sp.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // If no sponsored products, return empty array
            return $this->normalizeProducts($products);
            
        } catch (Exception $e) {
            error_log("Error fetching sponsored products: " . $e->getMessage());
            // Return empty array if table doesn't exist
            return [];
        }
    }
    
    /**
     * Get platform statistics
     */
    public function getPlatformStats() {
        if (!$this->isDatabaseAvailable()) {
            return [
                'stores' => 0,
                'products' => 0,
                'users' => 0,
                'orders' => 0
            ];
        }
        
        try {
            $stats = [];
            
            // Count active sellers
            try {
                $stmt = $this->db->prepare("SELECT COUNT(DISTINCT u.id) as count 
                                           FROM users u 
                                           JOIN products p ON u.id = p.seller_id 
                                           WHERE u.role IN ('seller', 'vendor') 
                                           AND p.status = 'active'");
                $stmt->execute();
                $stats['stores'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $stats['stores'] = 0;
            }
            
            // Count active products
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
                $stmt->execute();
                $stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $stats['products'] = 0;
            }
            
            // Count total users
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
                $stmt->execute();
                $stats['users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $stats['users'] = 0;
            }
            
            // Count orders (if orders table exists)
            try {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders");
                $stmt->execute();
                $stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            } catch (Exception $e) {
                $stats['orders'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error fetching platform stats: " . $e->getMessage());
            return [
                'stores' => 0,
                'products' => 0,
                'users' => 0,
                'orders' => 0
            ];
        }
    }
    
    /**
     * Normalize products from database to match expected structure
     */
    private function normalizeProducts($products) {
        $normalized = [];
        
        foreach ($products as $product) {
            // Get product images if they exist
            $image = $this->getProductImage($product);
            
            $normalized[] = [
                'id' => $product['id'] ?? 0,
                'title' => $product['name'] ?? 'Product',
                'description' => $product['description'] ?? $product['short_description'] ?? '',
                'price' => $this->formatPrice($product['price'] ?? 0),
                'original_price' => isset($product['compare_price']) && $product['compare_price'] > 0 ? $this->formatPrice($product['compare_price']) : null,
                'discount_percent' => $product['discount_percent'] ?? null,
                'image' => $image,
                'url' => '/product/' . ($product['slug'] ?? $product['id']),
                'store_name' => $this->getStoreName($product),
                'seller_name' => $this->getSellerName($product),
                'category_name' => $product['category_name'] ?? 'General',
                'category_slug' => $product['category_slug'] ?? '',
                'rating' => rand(4, 5), // Placeholder until you have reviews
                'reviews_count' => rand(5, 50), // Placeholder
                'stock' => $product['stock_quantity'] ?? 0,
                'featured' => $product['featured'] ?? false,
                'created_at' => $product['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
        
        return $normalized;
    }
    
    /**
     * Get product image from database or generate placeholder
     */
    private function getProductImage($product) {
        // Check if product has an image URL
        if (!empty($product['image_url'])) {
            return $product['image_url'];
        }
        
        // Try to get image from product_images table
        try {
            if ($this->isDatabaseAvailable() && isset($product['id'])) {
                $stmt = $this->db->prepare("SELECT file_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1");
                $stmt->execute([$product['id']]);
                $result = $stmt->fetchColumn();
                
                if ($result) {
                    return $result;
                }
            }
        } catch (Exception $e) {
            // Continue to placeholder
        }
        
        // Generate placeholder based on product ID
        $seed = $product['id'] ?? rand(1, 1000);
        return "https://picsum.photos/400/400?random={$seed}";
    }
    
    /**
     * Get store/seller name
     */
    private function getStoreName($product) {
        if (!empty($product['business_name'])) {
            return $product['business_name'];
        }
        
        if (!empty($product['seller_name'])) {
            return $product['seller_name'] . "'s Store";
        }
        
        if (!empty($product['first_name']) || !empty($product['last_name'])) {
            return trim($product['first_name'] . ' ' . $product['last_name']) . "'s Store";
        }
        
        return 'FezaMarket Store';
    }
    
    /**
     * Get seller display name
     */
    private function getSellerName($product) {
        if (!empty($product['first_name']) || !empty($product['last_name'])) {
            return trim($product['first_name'] . ' ' . $product['last_name']);
        }
        
        return $product['seller_name'] ?? 'Seller';
    }
    
    /**
     * Format price with currency symbol
     */
    private function formatPrice($amount, $currency = 'USD') {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥'
        ];
        
        $symbol = $symbols[$currency] ?? '$';
        return $symbol . number_format((float)$amount, 2);
    }
}

// Legacy function compatibility - now fetches from platform
function get_content_for_section($key, $count) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getProductsByCategory($key, $count);
}

function get_mosaic_section_content() {
    $fetcher = new PlatformDataFetcher();
    $deals = $fetcher->getDealsAndPromotions(7);
    
    $mosaic = [];
    $types = ['big', 'wide', 'card', 'tall', 'wide', 'card', 'card'];
    
    for ($i = 0; $i < min(count($deals), 7); $i++) {
        $deal = $deals[$i];
        $mosaic[] = [
            'type' => $types[$i],
            'img_src' => $deal['image'],
            'alt' => $deal['title'],
            'title' => $deal['title'],
            'price' => $deal['price'],
            'original_price' => $deal['original_price'],
            'discount_percent' => $deal['discount_percent'],
            'url' => $deal['url'],
            'store_name' => $deal['store_name']
        ];
    }
    
    return $mosaic;
}

function get_furniture_section_content() {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getProductsByCategory('furniture', 6);
}

function get_trending_products($count = 8) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getTrendingProducts($count);
}

function get_deals_section($count = 6) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getDealsAndPromotions($count);
}

function get_featured_products($count = 12) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getFeaturedProducts($count);
}

function get_new_arrivals($count = 8) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getNewArrivals($count);
}

function get_best_sellers($count = 8) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getBestSellers($count);
}

function get_popular_products($count = 8) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getPopularProducts($count);
}

function get_ai_recommended_products($count = 8) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getAIRecommendedProducts($count);
}

function get_sponsored_products($count = 8) {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getSponsoredProducts($count);
}

function get_platform_stats() {
    $fetcher = new PlatformDataFetcher();
    return $fetcher->getPlatformStats();
}
?>