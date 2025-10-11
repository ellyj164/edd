<?php
/**
 * Digital Download Service
 * Handles secure digital product downloads with token generation
 */

class DigitalDownloadService {
    private $db;
    private $tokenLifetime = 3600; // 1 hour
    
    public function __construct($db = null) {
        $this->db = $db ?? db();
    }
    
    /**
     * Generate secure download token
     */
    public function generateDownloadToken($userId, $productId, $orderId = null) {
        // Verify entitlement
        if (!$this->hasEntitlement($userId, $productId, $orderId)) {
            throw new Exception('You do not have access to this digital product');
        }
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->tokenLifetime);
        
        // Store token
        $stmt = $this->db->prepare("
            INSERT INTO download_tokens 
            (token, user_id, product_id, order_id, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$token, $userId, $productId, $orderId, $expiresAt]);
        
        return $token;
    }
    
    /**
     * Validate download token
     */
    public function validateToken($token) {
        $stmt = $this->db->prepare("
            SELECT dt.*, pf.file_path, pf.file_name, pf.file_size
            FROM download_tokens dt
            LEFT JOIN product_files pf ON dt.product_id = pf.product_id
            WHERE dt.token = ? 
            AND dt.expires_at > NOW()
            AND dt.downloaded = 0
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tokenData) {
            throw new Exception('Invalid or expired download token');
        }
        
        return $tokenData;
    }
    
    /**
     * Mark token as used
     */
    public function markTokenUsed($token) {
        $stmt = $this->db->prepare("
            UPDATE download_tokens 
            SET downloaded = 1, downloaded_at = NOW()
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        
        // Increment download count
        $stmt = $this->db->prepare("
            UPDATE product_files 
            SET download_count = download_count + 1
            WHERE product_id = (
                SELECT product_id FROM download_tokens WHERE token = ?
            )
        ");
        $stmt->execute([$token]);
    }
    
    /**
     * Check if user has entitlement to product
     */
    public function hasEntitlement($userId, $productId, $orderId = null) {
        // Check if product is digital
        $stmt = $this->db->prepare("
            SELECT is_digital FROM products WHERE id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product || !$product['is_digital']) {
            return false;
        }
        
        // Check if user has purchased this product
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ?
            AND oi.product_id = ?
            AND o.status IN ('completed', 'processing')
        ");
        $stmt->execute([$userId, $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Get user's digital products
     */
    public function getUserDigitalProducts($userId) {
        $stmt = $this->db->prepare("
            SELECT DISTINCT 
                p.id, p.name, p.description, p.image,
                pf.file_name, pf.file_size,
                o.id as order_id, o.created_at as purchase_date
            FROM products p
            JOIN product_files pf ON p.id = pf.product_id
            JOIN order_items oi ON p.id = oi.product_id
            JOIN orders o ON oi.order_id = o.id
            WHERE p.is_digital = 1
            AND o.user_id = ?
            AND o.status IN ('completed', 'processing')
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create download token table if not exists
     */
    public static function createTable($db) {
        $db->exec("
            CREATE TABLE IF NOT EXISTS download_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                token VARCHAR(64) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                order_id INT NULL,
                downloaded TINYINT(1) DEFAULT 0,
                downloaded_at TIMESTAMP NULL,
                expires_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
}
