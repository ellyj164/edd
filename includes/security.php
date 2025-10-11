<?php
/**
 * Data Scoping Middleware
 * Ensures sellers can only access their own data
 * E-Commerce Platform
 */

class DataScopeMiddleware {
    
    /**
     * Scope query to vendor's data only
     */
    public static function scopeToVendor($query, $vendor_id, $vendor_column = 'vendor_id') {
        return $query . " AND $vendor_column = " . (int)$vendor_id;
    }
    
    /**
     * Scope query to user's data only
     */
    public static function scopeToUser($query, $user_id, $user_column = 'user_id') {
        return $query . " AND $user_column = " . (int)$user_id;
    }
    
    /**
     * Verify vendor owns the resource
     */
    public static function verifyVendorOwnership($vendor_id, $table, $resource_id, $vendor_column = 'vendor_id') {
        $db = getDatabase();
        $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE id = ? AND $vendor_column = ?");
        $stmt->execute([$resource_id, $vendor_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verify user owns the resource
     */
    public static function verifyUserOwnership($user_id, $table, $resource_id, $user_column = 'user_id') {
        $db = getDatabase();
        $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE id = ? AND $user_column = ?");
        $stmt->execute([$resource_id, $user_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get vendor ID for current user
     */
    public static function getCurrentVendorId() {
        static $vendor_id = null;
        
        if ($vendor_id === null) {
            $db = getDatabase();
            $stmt = $db->prepare("SELECT id FROM vendors WHERE user_id = ?");
            $stmt->execute([Session::getUserId()]);
            $vendor_id = $stmt->fetchColumn() ?: false;
        }
        
        return $vendor_id;
    }
    
    /**
     * Require vendor ownership of resource
     */
    public static function requireVendorOwnership($table, $resource_id, $vendor_column = 'vendor_id') {
        $vendor_id = self::getCurrentVendorId();
        if (!$vendor_id) {
            http_response_code(403);
            die('Access denied: Not a vendor');
        }
        
        if (!self::verifyVendorOwnership($vendor_id, $table, $resource_id, $vendor_column)) {
            http_response_code(403);
            die('Access denied: Resource not found or not owned by you');
        }
        
        return $vendor_id;
    }
    
    /**
     * Require user ownership of resource
     */
    public static function requireUserOwnership($table, $resource_id, $user_column = 'user_id') {
        $user_id = Session::getUserId();
        if (!$user_id) {
            http_response_code(403);
            die('Access denied: Not logged in');
        }
        
        if (!self::verifyUserOwnership($user_id, $table, $resource_id, $user_column)) {
            http_response_code(403);
            die('Access denied: Resource not found or not owned by you');
        }
        
        return $user_id;
    }
    
    /**
     * Get scoped database connection for vendor
     */
    public static function getScopedConnection($vendor_id = null) {
        if ($vendor_id === null) {
            $vendor_id = self::getCurrentVendorId();
        }
        
        if (!$vendor_id) {
            throw new Exception('No vendor context available');
        }
        
        return new ScopedDatabase($vendor_id);
    }
}

/**
 * Scoped Database Wrapper
 * Automatically applies vendor scoping to queries
 */
class ScopedDatabase {
    private $vendor_id;
    private $db;
    
    public function __construct($vendor_id) {
        $this->vendor_id = (int)$vendor_id;
        $this->db = getDatabase();
    }
    
    /**
     * Execute a vendor-scoped query
     */
    public function query($sql, $params = [], $vendor_column = 'vendor_id') {
        // Automatically add vendor scoping to SELECT queries
        if (stripos(trim($sql), 'SELECT') === 0) {
            $sql = DataScopeMiddleware::scopeToVendor($sql, $this->vendor_id, $vendor_column);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Get vendor's products
     */
    public function getProducts($limit = null, $offset = 0, $filters = []) {
        $where_conditions = ["vendor_id = ?"];
        $params = [$this->vendor_id];
        
        foreach ($filters as $column => $value) {
            $where_conditions[] = "$column = ?";
            $params[] = $value;
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        $limit_clause = $limit ? "LIMIT $limit OFFSET $offset" : "";
        
        $sql = "SELECT * FROM products $where_clause ORDER BY created_at DESC $limit_clause";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get vendor's orders
     */
    public function getOrders($limit = null, $offset = 0, $filters = []) {
        $where_conditions = ["o.vendor_id = ?"];
        $params = [$this->vendor_id];
        
        foreach ($filters as $column => $value) {
            $where_conditions[] = "o.$column = ?";
            $params[] = $value;
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        $limit_clause = $limit ? "LIMIT $limit OFFSET $offset" : "";
        
        $sql = "
            SELECT o.*, u.first_name, u.last_name, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            $where_clause
            ORDER BY o.created_at DESC
            $limit_clause
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get vendor's financial data
     */
    public function getFinancialSummary($date_from = null, $date_to = null) {
        $date_filter = "";
        $params = [$this->vendor_id];
        
        if ($date_from && $date_to) {
            $date_filter = "AND created_at BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        }
        
        $sql = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'pending' THEN total ELSE 0 END) as pending_revenue,
                AVG(CASE WHEN status = 'completed' THEN total ELSE NULL END) as avg_order_value
            FROM orders 
            WHERE vendor_id = ? $date_filter
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}

/**
 * CSRF Protection Helper
 */
class CsrfProtection {
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token field HTML
     */
    public static function getTokenField() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::generateToken()) . '">';
    }
    
    /**
     * Require valid CSRF token
     */
    public static function requireValidToken() {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!self::verifyToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}

/**
 * Rate Limiting
 */
class RateLimit {
    
    /**
     * Check rate limit for action
     */
    public static function checkLimit($action, $max_attempts = 10, $window_minutes = 15) {
        $key = $action . '_' . (Session::getUserId() ?: $_SERVER['REMOTE_ADDR']);
        $cache_key = 'rate_limit_' . md5($key);
        
        // Simple file-based rate limiting (replace with Redis/Memcached in production)
        $cache_file = sys_get_temp_dir() . '/' . $cache_key;
        $window_start = time() - ($window_minutes * 60);
        
        $attempts = [];
        if (file_exists($cache_file)) {
            $attempts = json_decode(file_get_contents($cache_file), true) ?: [];
            // Remove old attempts
            $attempts = array_filter($attempts, function($timestamp) use ($window_start) {
                return $timestamp > $window_start;
            });
        }
        
        if (count($attempts) >= $max_attempts) {
            return false;
        }
        
        // Record this attempt
        $attempts[] = time();
        file_put_contents($cache_file, json_encode($attempts));
        
        return true;
    }
    
    /**
     * Require rate limit check
     */
    public static function requireLimit($action, $max_attempts = 10, $window_minutes = 15) {
        if (!self::checkLimit($action, $max_attempts, $window_minutes)) {
            http_response_code(429);
            die('Rate limit exceeded. Please try again later.');
        }
    }
}

/**
 * Audit Logging
 */
class AuditLog {
    
    /**
     * Log user action
     */
    public static function log($action, $table, $record_id, $old_data = null, $new_data = null, $notes = '') {
        $db = getDatabase();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO audit_logs 
                (user_id, action, table_name, record_id, old_data, new_data, notes, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                Session::getUserId(),
                $action,
                $table,
                $record_id,
                $old_data ? json_encode($old_data) : null,
                $new_data ? json_encode($new_data) : null,
                $notes,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (Exception $e) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Log sensitive action
     */
    public static function logSensitive($action, $details) {
        self::log($action, 'sensitive_actions', null, null, $details);
    }
}

/**
 * Input Validation and Sanitization
 */
class InputValidator {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $max_length = null) {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        if ($max_length && strlen($input) > $max_length) {
            $input = substr($input, 0, $max_length);
        }
        
        return $input;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate integer
     */
    public static function validateInteger($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_INT);
        if ($value === false) return false;
        
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        
        return $value;
    }
    
    /**
     * Validate decimal
     */
    public static function validateDecimal($value, $min = null, $max = null) {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($value === false) return false;
        
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        
        return $value;
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($data, $required_fields) {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        return $errors;
    }
}