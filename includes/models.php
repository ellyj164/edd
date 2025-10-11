<?php
/**
 * User Model
 * E-Commerce Platform
 */

require_once __DIR__ . '/database.php';

class User extends BaseModel {
    protected $table = 'users';
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function authenticate($email, $password) {
        if (!checkLoginAttempts($email)) {
            logSecurityEvent(null, 'login_blocked_rate_limit', 'user', null, ['email' => $email]);
            return ['error' => 'Too many login attempts. Please try again later.'];
        }
        
        $user = $this->findByEmail($email);
        
        if ($user && verifyPassword($password, $user['pass_hash'])) {
            if ($user['status'] !== 'active') {
                logLoginAttempt($email, false);
                logSecurityEvent($user['id'], 'login_failed_inactive', 'user', $user['id']);
                
                if ($user['status'] === 'pending' && empty($user['verified_at'])) {
                    return ['error' => 'Please verify your email address before logging in. <a href="/resend-verification.php?email=' . urlencode($email) . '">Resend verification email</a>.'];
                } else {
                    return ['error' => 'Account is not active. Please contact support.'];
                }
            }
            logLoginAttempt($email, true);
            clearLoginAttempts($email);
            logSecurityEvent($user['id'], 'login_success', 'user', $user['id']);
            return $user;
        } else {
            logLoginAttempt($email, false);
            logSecurityEvent(null, 'login_failed', 'user', null, ['email' => $email]);
            return ['error' => 'Invalid email or password.'];
        }
    }
    
    public function register($data) {
        try {
            $this->db->beginTransaction();
            $userData = [
                'username' => $data['username'],
                'email' => $data['email'],
                'pass_hash' => hashPassword($data['password']),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'role' => 'customer',
                'status' => 'pending',
                'verified_at' => null,
                'is_verified' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $userId = $this->create($userData);
            if (!$userId) {
                $this->db->rollBack();
                return false;
            }
            
            // Generate secure verification token
            $token = bin2hex(random_bytes(32));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            // Store verification token
            $stmt = $this->db->prepare("
                INSERT INTO email_verifications (user_id, token, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $token, $ip, $agent]);
            
            $this->db->commit();
            
            // Send verification email using enhanced email system
            try {
                // Load enhanced email system if not already loaded
                if (!isset($GLOBALS['emailSystem'])) {
                    require_once __DIR__ . '/enhanced_email_system.php';
                }
                
                $emailSent = sendVerificationEmail(
                    $userData['email'],
                    $userData['first_name'],
                    $token,
                    $userId
                );
                
                if (!$emailSent) {
                    Logger::error("Failed to send verification email to user {$userId}: {$userData['email']}");
                    Logger::info("User registered successfully but email sending failed: {$userData['email']}");
                } else {
                    Logger::info("User registered successfully with verification email sent: {$userData['email']}");
                }
            } catch (Exception $emailException) {
                Logger::error("Exception sending verification email to user {$userId}: " . $emailException->getMessage());
                Logger::info("User registered successfully but email sending encountered an error: {$userData['email']}");
            }
            
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            Logger::error("Registration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updatePassword($userId, $newPassword) {
        return $this->update($userId, ['pass_hash' => hashPassword($newPassword)]);
    }
    
    public function verifyEmail($userId) {
        return $this->update($userId, [
            'verified_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ]);
    }
    
    public function getAddresses($userId) {
        $stmt = $this->db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    // Alias method for backward compatibility
    public function getUserAddresses($userId) {
        return $this->getAddresses($userId);
    }
    
    public function addAddress($userId, $addressData) {
        $addressData['user_id'] = $userId;
        $stmt = $this->db->prepare("INSERT INTO addresses (user_id, type, address_line1, address_line2, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $userId,
            $addressData['type'],
            $addressData['address_line1'],
            $addressData['address_line2'] ?? '',
            $addressData['city'],
            $addressData['state'],
            $addressData['postal_code'],
            $addressData['country'],
            $addressData['is_default'] ?? 0
        ]);
    }
    
    public function getUsersByRole($role, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE role = ?";
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
}

/**
 * Product Model
 */
class Product extends BaseModel {
    protected $table = 'products';
    
    public function findWithVendor($id) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, c.name as category_name,
                   pi.file_path as image_url, pi.alt_text as image_alt
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function findByCategory($categoryId, $limit = PRODUCTS_PER_PAGE, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name,
                   pi.file_path as image_url, pi.alt_text as image_alt
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.category_id = ? AND p.status = 'active' 
            ORDER BY p.featured DESC, p.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll();
    }
    
    public function search($query, $categoryId = null, $limit = PRODUCTS_PER_PAGE, $offset = 0) {
        $searchTerm = "%{$query}%";
        $sql = "
            SELECT p.*, v.business_name as vendor_name,
                   COALESCE(p.image_url, pi.file_path) as image_url, 
                   pi.alt_text as image_alt
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE (p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ? OR p.keywords LIKE ?) 
            AND p.status = 'active'
        ";
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        $sql .= " ORDER BY 
                    CASE 
                        WHEN p.name LIKE ? THEN 1
                        WHEN p.keywords LIKE ? THEN 2  
                        WHEN p.short_description LIKE ? THEN 3
                        WHEN p.description LIKE ? THEN 4
                        ELSE 5
                    END,
                    p.featured DESC, p.updated_at DESC, p.created_at DESC";
        
        // Add search terms for relevance sorting
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function findAll($limit = null, $offset = 0) {
        // Fix #13: Remove product_images table references and use only existing columns
        $sql = "
            SELECT p.*, v.business_name as vendor_name
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.status = 'active'  
            ORDER BY p.created_at DESC
        ";
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getByVendorId($vendorId, $limit = null, $offset = 0) {
        $sql = "
            SELECT p.*, c.name as category_name 
            FROM {$this->table} p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.vendor_id = ? 
            ORDER BY p.created_at DESC
        ";
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
    
    public function getFeatured($limit = 8) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name,
                   pi.file_path as image_url, pi.alt_text as image_alt
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.featured = 1 AND p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getByVendor($vendorId, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE vendor_id = ?";
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }
    
    public function updateStock($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock_quantity = ? WHERE id = ?");
        return $stmt->execute([$quantity, $productId]);
    }
    
    public function decreaseStock($productId, $quantity) {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        return $stmt->execute([$quantity, $productId, $quantity]);
    }
    
    public function getImages($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function addImage($productId, $imageUrl, $altText = '', $isPrimary = false) {
        $stmt = $this->db->prepare("INSERT INTO product_images (product_id, image_url, alt_text, is_primary) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$productId, $imageUrl, $altText, $isPrimary ? 1 : 0]);
    }
    
    public function getReviews($productId, $limit = REVIEWS_PER_PAGE, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name 
            FROM reviews r 
            JOIN users u ON r.user_id = u.id 
            WHERE r.product_id = ? AND r.status = 'approved' 
            ORDER BY r.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    public function getAverageRating($productId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE product_id = ? AND status = 'approved'");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    
    public function getRandomProducts($limit = 10) {
        $randomFunc = defined('USE_SQLITE') && USE_SQLITE ? 'RANDOM()' : 'RAND()';
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, pi.image_url
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.status = 'active'
            GROUP BY p.id
            ORDER BY {$randomFunc}
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getLatest($limit = 8) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            WHERE p.status = 'active' 
            ORDER BY p.created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function findByFilters($filters = [], $sort = 'name', $limit = PRODUCTS_PER_PAGE, $offset = 0) {
        $where = ["p.status = 'active'"];
        $params = [];
        if (isset($filters['category_id']) && $filters['category_id'] > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $where[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $where[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0";
        }
        $orderBy = match($sort) {
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC', 
            'newest' => 'p.created_at DESC',
            'rating' => 'p.id DESC',
            default => 'p.name ASC'
        };
        $whereClause = implode(' AND ', $where);
        $sql = "
            SELECT p.*, v.business_name as vendor_name, pi.image_url
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE {$whereClause}
            GROUP BY p.id
            ORDER BY {$orderBy}
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function countByFilters($filters = []) {
        $where = ["p.status = 'active'"];
        $params = [];
        if (isset($filters['category_id']) && $filters['category_id'] > 0) {
            $where[] = "p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        if (isset($filters['min_price']) && $filters['min_price'] !== null) {
            $where[] = "p.price >= ?";
            $params[] = $filters['min_price'];
        }
        if (isset($filters['max_price']) && $filters['max_price'] !== null) {
            $where[] = "p.price <= ?";
            $params[] = $filters['max_price'];
        }
        if (isset($filters['on_sale']) && $filters['on_sale']) {
            $where[] = "p.sale_price IS NOT NULL AND p.sale_price > 0";
        }
        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM {$this->table} p WHERE {$whereClause}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    public function findBySlug($slug) {
        $stmt = $this->db->prepare("
            SELECT p.*, v.business_name as vendor_name, c.name as category_name 
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.slug = ? AND p.status = 'active'
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    
    /**
     * Find similar products by name and keywords
     * Used for "Similar Items" section when no category match is found
     */
    public function findSimilarByNameAndKeywords($productId, $productName, $keywords = '', $limit = 8) {
        // Extract key words from product name (split by spaces, remove short words)
        $nameWords = array_filter(
            explode(' ', strtolower($productName)),
            function($word) {
                return strlen($word) > 3; // Only use words longer than 3 characters
            }
        );
        
        // Build search conditions for name similarity
        $searchConditions = [];
        $params = [];
        
        foreach ($nameWords as $word) {
            $searchConditions[] = "(p.name LIKE ? OR p.keywords LIKE ? OR p.short_description LIKE ?)";
            $searchTerm = "%{$word}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Add keywords if provided
        if (!empty($keywords)) {
            $keywordList = array_filter(
                explode(',', strtolower($keywords)),
                function($kw) {
                    return strlen(trim($kw)) > 0;
                }
            );
            
            foreach ($keywordList as $keyword) {
                $keyword = trim($keyword);
                if (strlen($keyword) > 2) {
                    $searchConditions[] = "(p.name LIKE ? OR p.keywords LIKE ?)";
                    $searchTerm = "%{$keyword}%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
            }
        }
        
        if (empty($searchConditions)) {
            return []; // No valid search terms
        }
        
        $whereClause = '(' . implode(' OR ', $searchConditions) . ')';
        $params[] = $productId; // Exclude current product
        
        $sql = "
            SELECT p.*, v.business_name as vendor_name,
                   pi.file_path as image_url, pi.alt_text as image_alt
            FROM {$this->table} p 
            LEFT JOIN vendors v ON p.vendor_id = v.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE {$whereClause}
              AND p.id != ?
              AND p.status = 'active' 
            ORDER BY p.featured DESC, p.created_at DESC 
            LIMIT {$limit}
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

/**
 * Category Model
 */
class Category extends BaseModel {
    protected $table = 'categories';
    
    /**
     * Added to support legacy calls like Category::getAll()
     */
    public function getAll($includeInactive = false) {
        $sql = "SELECT * FROM {$this->table}";
        if (!$includeInactive) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActive() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getChildren($parentId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id = ? AND status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }
    
    public function getParents() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE parent_id IS NULL AND status = 'active' ORDER BY sort_order ASC, name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getProductCount($categoryId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ? AND status = 'active'");
        $stmt->execute([$categoryId]);
        return $stmt->fetchColumn();
    }
    
    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        $result = $stmt->fetch();
        
        // Fallback: try matching by slugified name if no direct slug match
        if (!$result) {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active'");
            $stmt->execute();
            $categories = $stmt->fetchAll();
            foreach ($categories as $category) {
                if (slugify($category['name']) === $slug) {
                    return $category;
                }
            }
        }
        
        return $result;
    }
}

/**
 * Cart Model
 */
class Cart extends BaseModel {
    protected $table = 'cart';
    
    public function getCartItems($userId) {
        // Fix #3: Handle missing product_images table gracefully and make sku optional
        $stmt = $this->db->prepare("
            SELECT c.*, p.name as product_name, p.price, p.stock_quantity,
                   p.image_url as product_image, p.sku,
                   v.business_name as vendor_name
            FROM {$this->table} c 
            JOIN products p ON c.product_id = p.id 
            LEFT JOIN vendors v ON p.vendor_id = v.id
            WHERE c.user_id = ? AND p.status = 'active'
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function addItem($userId, $productId, $quantity = 1) {
        // Get product price for cart item
        $product = new Product();
        $productData = $product->find($productId);
        
        if (!$productData) {
            return false;
        }
        
        $price = $productData['price'];
        
        // Check if item already exists and update quantity
        $stmt = $this->db->prepare("SELECT id, quantity FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $this->db->prepare("UPDATE {$this->table} SET quantity = ?, price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$newQuantity, $price, $existing['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO {$this->table} (user_id, product_id, quantity, price, created_at, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            return $stmt->execute([$userId, $productId, $quantity, $price]);
        }
    }
    
    public function updateQuantity($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($userId, $productId);
        }
        $stmt = $this->db->prepare("UPDATE {$this->table} SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $userId, $productId]);
    }
    
    public function removeItem($userId, $productId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    
    public function getCartTotal($userId) {
        $stmt = $this->db->prepare("
            SELECT SUM(c.quantity * p.price) as total 
            FROM {$this->table} c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    public function getCartCount($userId) {
        if (!$this->db) {
            return 0;
        }
        $stmt = $this->db->prepare("SELECT SUM(quantity) FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: 0;
    }
}
?>