<?php
/**
 * Core Functions and Utilities
 * E-Commerce Platform
 */

/**
 * Environment variable helper function
 */
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
        
        // Convert common string values to proper types
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'null':
            case '(null)':
                return null;
            case 'empty':
            case '(empty)':
                return '';
        }
        
        return $value;
    }
}

/**
 * Session Management
 */
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    public static function set($key, $value) { self::start(); $_SESSION[$key] = $value; }
    public static function get($key, $default = null) { self::start(); return $_SESSION[$key] ?? $default; }
    public static function remove($key) { self::start(); unset($_SESSION[$key]); }
    public static function destroy() { self::start(); session_destroy(); session_unset(); }
    public static function isLoggedIn() { return self::get('user_id') !== null; }
    public static function getUserId() { return self::get('user_id'); }
    public static function getUserRole() { return self::get('user_role', 'customer'); }
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            if (!empty($_SERVER['REQUEST_URI'])) self::set('intended_url', $_SERVER['REQUEST_URI']);
            redirect('/login.php');
        }
        $userId = self::getUserId();
        $sessionToken = self::get('session_token');
        if ($userId && $sessionToken && !validateSessionToken($userId, $sessionToken)) {
            self::destroy();
            redirect('/login.php?error=session_expired');
        }
    }
    public static function requireRole($role) {
        self::requireLogin();
        if (self::getUserRole() !== $role && self::getUserRole() !== 'admin') {
            logSecurityEvent(self::getUserId(), 'access_denied', 'page', null, [
                'required_role' => $role,
                'user_role' => self::getUserRole(),
                'url' => $_SERVER['REQUEST_URI'] ?? ''
            ]);
            redirect('/login.php?error=access_denied');
        }
    }
    public static function getIntendedUrl() {
        $url = self::get('intended_url', '/'); self::remove('intended_url'); return $url;
    }
    public static function setFlash($type, $message) {
        self::set('flash_' . $type, $message);
    }
    public static function getFlash($type) {
        $message = self::get('flash_' . $type);
        self::remove('flash_' . $type);
        return $message;
    }
    public static function hasFlash($type) {
        return self::get('flash_' . $type) !== null;
    }
}

/**
 * Security Functions
 */
function sanitizeInput($input) { return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'); }
function validateEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
function hashPassword($password) {
    if (defined('PASSWORD_ARGON2ID') && PASSWORD_ARGON2ID) {
        return password_hash($password, PASSWORD_ARGON2ID, ['memory_cost'=>65536,'time_cost'=>4,'threads'=>3]);
    }
    return password_hash($password, PASSWORD_DEFAULT, ['cost'=>BCRYPT_COST]);
}
function verifyPassword($password, $hash) { return password_verify($password, $hash); }
function generateToken($length = 32) { return bin2hex(random_bytes($length)); }
function csrfToken() { if (!Session::get('csrf_token')) Session::set('csrf_token', generateToken()); return Session::get('csrf_token'); }
function csrfTokenInput() { $t = csrfToken(); return '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($t).'">'; }
function verifyCsrfToken($token) { return hash_equals(Session::get('csrf_token', ''), $token); }

/**
 * INFORMATION_SCHEMA helpers
 */
if (!function_exists('db_table_exists')) {
    function db_table_exists(PDO $db, string $table): bool {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
            "); $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }
}
if (!function_exists('db_column_exists')) {
    function db_column_exists(PDO $db, string $table, string $column): bool {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            "); $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }
}

/**
 * Ensure audit_log (self-heal)
 */
if (!function_exists('ensure_audit_log_table')) {
    function ensure_audit_log_table(PDO $db): void {
        if (db_table_exists($db, 'audit_log')) return;
        $sql = "
            CREATE TABLE IF NOT EXISTS audit_log (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NULL,
                action VARCHAR(100) NOT NULL,
                resource_type VARCHAR(100) NULL,
                resource_id VARCHAR(100) NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                new_values LONGTEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at),
                INDEX idx_action (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "; try { $db->exec($sql); } catch (Throwable $e) {}
    }
}

/**
 * Ensure user_sessions (self-heal and migrate columns if missing)
 */
if (!function_exists('ensure_user_sessions_table')) {
    function ensure_user_sessions_table(PDO $db): void {
        if (!db_table_exists($db, 'user_sessions')) {
            $sql = "
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    session_token VARCHAR(128) NOT NULL,
                    csrf_token VARCHAR(64) NOT NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent VARCHAR(255) NULL,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_session_token (session_token),
                    INDEX idx_user_expires (user_id, expires_at),
                    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            try { $db->exec($sql); } catch (Throwable $e) { /* ignore */ }
        } else {
            // Add any missing columns
            $adds = [];
            if (!db_column_exists($db, 'user_sessions', 'session_token')) $adds[] = "ADD COLUMN session_token VARCHAR(128) NOT NULL";
            if (!db_column_exists($db, 'user_sessions', 'csrf_token'))    $adds[] = "ADD COLUMN csrf_token VARCHAR(64) NOT NULL";
            if (!db_column_exists($db, 'user_sessions', 'ip_address'))    $adds[] = "ADD COLUMN ip_address VARCHAR(45) NULL";
            if (!db_column_exists($db, 'user_sessions', 'user_agent'))    $adds[] = "ADD COLUMN user_agent VARCHAR(255) NULL";
            if (!db_column_exists($db, 'user_sessions', 'expires_at'))    $adds[] = "ADD COLUMN expires_at DATETIME NOT NULL";
            if (!db_column_exists($db, 'user_sessions', 'created_at'))    $adds[] = "ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
            if (!db_column_exists($db, 'user_sessions', 'updated_at'))    $adds[] = "ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            if ($adds) {
                $sql = "ALTER TABLE user_sessions " . implode(", ", $adds);
                try { $db->exec($sql); } catch (Throwable $e) { /* ignore */ }
            }
        }
    }
}

/**
 * Helpers for login_attempts schema differences
 */
if (!function_exists('la_column_exists')) {
    function la_column_exists(PDO $db, string $table, string $column): bool {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            "); $stmt->execute([$table, $column]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) { return false; }
    }
}
if (!function_exists('la_identifier_columns')) {
    function la_identifier_columns(PDO $db): array {
        $cols = [];
        foreach (['email','username','identifier'] as $c) if (la_column_exists($db, 'login_attempts', $c)) $cols[] = $c;
        return $cols;
    }
}
if (!function_exists('la_ip_predicate')) {
    function la_ip_predicate(PDO $db, string $ip): array {
        try {
            if (la_column_exists($db, 'login_attempts', 'ip_address')) return ["ip_address = ?", $ip, false, 'ip_address'];
            if (la_column_exists($db, 'login_attempts', 'ip')) {
                $stmt = $db->prepare("
                    SELECT DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_attempts' AND COLUMN_NAME = 'ip' LIMIT 1
                "); $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $dt = strtolower((string)($row['DATA_TYPE'] ?? ''));
                $ct = strtolower((string)($row['COLUMN_TYPE'] ?? ''));
                $isBinary = str_contains($dt, 'binary') || str_contains($ct, 'binary');
                return $isBinary ? ["ip = INET6_ATON(?)", $ip, true, 'ip'] : ["ip = ?", $ip, false, 'ip'];
            }
        } catch (Throwable $e) {}
        return ['', null, false, null];
    }
}

/**
 * Rate limit login attempts (resilient)
 */
function checkLoginAttempts($identifier, $maxAttempts = 5, $windowMinutes = 15) {
    $db = Database::getInstance()->getConnection();
    try {
        $idCols = la_identifier_columns($db);
        if (empty($idCols)) return true;
        $whereParts = []; $params = [];
        foreach ($idCols as $c) { $whereParts[] = "{$c} = ?"; $params[] = $identifier; }
        $idWhere = '(' . implode(' OR ', $whereParts) . ')';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        [$ipPred, $ipParam] = la_ip_predicate($db, $ip);
        $ipWhere = $ipPred ? " AND {$ipPred}" : '';
        $minutes = (int)$windowMinutes;
        $sql = "
            SELECT COUNT(*) FROM login_attempts
            WHERE {$idWhere} {$ipWhere}
              AND attempted_at > DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)
              AND success = 0
        ";
        $bind = $params; if ($ipPred) $bind[] = $ipParam;
        $stmt = $db->prepare($sql); $stmt->execute($bind);
        $attempts = (int)$stmt->fetchColumn();
        return $attempts < $maxAttempts;
    } catch (Throwable $e) { return true; }
}
function logLoginAttempt($identifier, $success = false) {
    $db = Database::getInstance()->getConnection();
    try {
        $idCols = la_identifier_columns($db); if (empty($idCols)) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        [$ipPred, $ipParam, $needsInet6, $ipCol] = la_ip_predicate($db, $ip);
        $cols = $idCols; $placeholders = array_fill(0, count($idCols), '?'); $values = array_fill(0, count($idCols), $identifier);
        if ($ipCol) { $cols[] = $ipCol; $placeholders[] = $needsInet6 ? 'INET6_ATON(?)' : '?'; $values[] = $ipParam; }
        if (la_column_exists($db, 'login_attempts', 'attempted_at')) { $cols[] = 'attempted_at'; $placeholders[] = 'NOW()'; }
        if (la_column_exists($db, 'login_attempts', 'success')) { $cols[] = 'success'; $placeholders[] = '?'; $values[] = $success ? 1 : 0; }
        $sql = "INSERT INTO login_attempts (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $db->prepare($sql); $stmt->execute($values);
    } catch (Throwable $e) { error_log('logLoginAttempt failed: ' . $e->getMessage()); }
}
function clearLoginAttempts($identifier) {
    $db = Database::getInstance()->getConnection();
    try {
        $idCols = la_identifier_columns($db); if (empty($idCols)) return;
        $whereParts = []; $params = [];
        foreach ($idCols as $c) { $whereParts[] = "{$c} = ?"; $params[] = $identifier; }
        $idWhere = '(' . implode(' OR ', $whereParts) . ')';
        $sql = la_column_exists($db, 'login_attempts', 'success')
            ? "DELETE FROM login_attempts WHERE {$idWhere} AND success = 0"
            : "DELETE FROM login_attempts WHERE {$idWhere}";
        $stmt = $db->prepare($sql); $stmt->execute($params);
    } catch (Throwable $e) {}
}

/**
 * Sessions
 */
function generateSecureToken($length = 64) { return bin2hex(random_bytes($length)); }

function createSecureSession($userId) {
    session_regenerate_id(true);
    $db = Database::getInstance()->getConnection();

    // Ensure schema exists/migrated
    ensure_user_sessions_table($db);

    $sessionToken = generateSecureToken(); // 128 hex chars
    $csrfToken = generateToken();          // 64 hex chars

    // Use MariaDB-compatible datetime functions
    $stmt = $db->prepare("
        INSERT INTO user_sessions (user_id, session_token, csrf_token, ip_address, user_agent, expires_at, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW(), NOW())
    ");
    $stmt->execute([
        $userId,
        $sessionToken,
        $csrfToken,
        getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? '',
    ]);

    Session::set('user_id', $userId);
    Session::set('session_token', $sessionToken);
    Session::set('csrf_token', $csrfToken);

    setSecureCookie('session_token', $sessionToken);
    return $sessionToken;
}

function validateSessionToken($userId, $sessionToken) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT id FROM user_sessions
        WHERE user_id = ? AND session_token = ? AND expires_at > NOW()
    ");
    $stmt->execute([$userId, $sessionToken]);
    return $stmt->fetchColumn() !== false;
}

function setSecureCookie($name, $value, $expire = 3600) {
    setcookie($name, $value, [
        'expires' => time() + $expire,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Audit logging (self-healing)
 */
function logSecurityEvent($userId, $action, $resourceType = null, $resourceId = null, $metadata = []) {
    $db = Database::getInstance()->getConnection();
    try {
        ensure_audit_log_table($db);
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, resource_type, resource_id, ip_address, user_agent, new_values, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId, $action, $resourceType, $resourceId,
            getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? '', json_encode($metadata)
        ]);
    } catch (Throwable $e) {
        error_log("audit_log insert failed: " . $e->getMessage() . " | action={$action}");
    }
}

/**
 * Utility
 */
function redirect($url) { header("Location: {$url}"); exit; }
function formatCurrency($amount, $currency = 'USD') { return '$' . number_format($amount, 2); }
function formatDateTime($datetime, $format = 'M j, Y g:i A') { return date($format, strtotime($datetime)); }
function formatDate($datetime, $format = 'M j, Y') { return date($format, strtotime($datetime)); }
function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' min ago';
    if ($time < 86400) return floor($time/3600) . ' hr ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    return formatDate($datetime);
}
function getProductImageUrl($imagePath, $size = 'medium') {
    // Default placeholder image
    $placeholder = '/images/placeholder-product.png';
    
    if (empty($imagePath)) {
        return $placeholder;
    }
    
    // If it's already a full URL, validate and return as-is
    if (strpos($imagePath, 'http') === 0) {
        return $imagePath;
    }
    
    // If it's a relative path starting with /, return as-is but validate
    if (strpos($imagePath, '/') === 0) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
        if (file_exists($fullPath) && is_readable($fullPath)) {
            return $imagePath;
        }
        // If file doesn't exist, return placeholder
        return $placeholder;
    }
    
    // For product images, prepend the uploads URL and validate
    $productImagePath = '/uploads/products/' . $imagePath;
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $productImagePath;
    
    if (file_exists($fullPath) && is_readable($fullPath)) {
        return $productImagePath;
    }
    
    // Try in images/products directory as fallback
    $imagesProductPath = '/images/products/' . $imagePath;
    $imagesFullPath = $_SERVER['DOCUMENT_ROOT'] . $imagesProductPath;
    
    if (file_exists($imagesFullPath) && is_readable($imagesFullPath)) {
        return $imagesProductPath;
    }
    
    // If nothing found, return placeholder
    return $placeholder;
}

/**
 * Validate if an image file exists and is readable
 * 
 * @param string $imagePath The image path to validate
 * @return bool True if image exists and is readable
 */
function validateImageFile($imagePath) {
    if (empty($imagePath)) {
        return false;
    }
    
    // Handle full URLs - we assume they're valid (could add HTTP check if needed)
    if (strpos($imagePath, 'http') === 0) {
        return true;
    }
    
    // For local paths, check if file exists
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
    return file_exists($fullPath) && is_readable($fullPath);
}

/**
 * Get safe product image URL with proper fallback handling
 * This is an enhanced version that can be used as a drop-in replacement
 * 
 * @param array $product Product data array
 * @param string $fallback Custom fallback image path
 * @return string Safe image URL
 */
function getSafeProductImageUrl($product, $fallback = '/images/placeholder-product.png') {
    $imageUrl = '';
    
    // Try different image field names that might exist in product data
    $imageFields = ['image_url', 'thumbnail_path', 'product_image', 'main_image'];
    
    foreach ($imageFields as $field) {
        if (!empty($product[$field])) {
            $imageUrl = $product[$field];
            break;
        }
    }
    
    // Use getProductImageUrl which now has validation built-in
    return getProductImageUrl($imageUrl, 'medium');
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * File Upload
 */
function uploadImage($file, $directory = 'products') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return false;
    $uploadDir = UPLOAD_PATH . $directory . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_TYPES)) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE) return false;
    $fileName = uniqid() . '_' . time() . '.' . $ext;
    $filePath = $uploadDir . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) return "uploads/{$directory}/{$fileName}";
    return false;
}

/**
 * Pagination
 */
class Pagination {
    public static function create($totalItems, $itemsPerPage, $currentPage, $baseUrl) {
        $totalPages = ceil($totalItems / $itemsPerPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $pagination = [
            'current_page'=>$currentPage, 'total_pages'=>$totalPages, 'total_items'=>$totalItems,
            'items_per_page'=>$itemsPerPage, 'has_previous'=>$currentPage>1, 'has_next'=>$currentPage<$totalPages,
            'previous_page'=>max(1,$currentPage-1), 'next_page'=>min($totalPages,$currentPage+1),
            'offset'=>($currentPage-1)*$itemsPerPage, 'links'=>[]
        ];
        $start = max(1, $currentPage - 2); $end = min($totalPages, $currentPage + 2);
        for ($i=$start; $i<=$end; $i++) $pagination['links'][] = ['page'=>$i, 'url'=>$baseUrl.'?page='.$i, 'current'=>$i===$currentPage];
        return $pagination;
    }
}

/**
 * JSON Responses
 */
function jsonResponse($data, $status = 200) { http_response_code($status); header('Content-Type: application/json'); echo json_encode($data); exit; }
function errorResponse($message, $status = 400) { jsonResponse(['error'=>$message], $status); }
function successResponse($data = [], $message = 'Success') { jsonResponse(['success'=>true,'message'=>$message,'data'=>$data]); }

/**
 * TOTP 2FA
 */
function generateTotpSecret($length = 32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $secret = '';
    for ($i=0; $i<$length; $i++) $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $secret;
}
function generateTotpCode($secret, $timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    $key = base32Decode($secret);
    $timeCounter = floor($timestamp / 30);
    $timeBytes = pack('N*', 0) . pack('N*', $timeCounter);
    $hash = hash_hmac('sha1', $timeBytes, $key, true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset+1]) & 0xff) << 16) |
        ((ord($hash[$offset+2]) & 0xff) << 8) |
        (ord($hash[$offset+3]) & 0xff)
    ) % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}
function verifyTotpCode($secret, $userCode, $tolerance = 1) {
    $currentTime = time();
    for ($i = -$tolerance; $i <= $tolerance; $i++) {
        $testTime = $currentTime + ($i * 30);
        if (hash_equals(generateTotpCode($secret, $testTime), $userCode)) return true;
    }
    return false;
}
function base32Decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper($data); $output = ''; $v = 0; $vbits = 0;
    for ($i=0, $j=strlen($data); $i<$j; $i++) {
        $v <<= 5; if (($x = strpos($alphabet, $data[$i])) !== false) { $v += $x; $vbits += 5;
            if ($vbits >= 8) { $output .= chr(($v >> ($vbits - 8)) & 255); $vbits -= 8; } }
    }
    return $output;
}

/**
 * Additional Helper Functions
 */
// generateCsrfToken() function moved to csrf.php to avoid conflicts

function sellerUrl($path = '') {
    $base = '/seller';
    if ($path === 'register') return '/seller-register.php';
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}

function adminUrl($path = '') {
    return '/admin' . ($path ? '/' . ltrim($path, '/') : '');
}
// ---- Role helper (footer compatibility) ----
if (!function_exists('getCurrentUserRole')) {
    /**
     * Returns current user's role as a lowercase string.
     * Falls back to 'guest' (not logged) or 'user' (logged but role unknown).
     */
    function getCurrentUserRole(): string {
        try {
            if (!class_exists('Session') || !Session::isLoggedIn()) {
                return 'guest';
            }
            // If role was stored in session
            $role = null;
            if (method_exists('Session','get')) { $role = Session::get('role'); }
            if ($role) {
                return strtolower((string)$role);
            }
            // Try users table
            if (class_exists('Database')) {
                $uid = method_exists('Session','getUserId') ? Session::getUserId() : null;
                if ($uid) {
                    $row = Database::query("SELECT role FROM users WHERE id=? LIMIT 1", [$uid])->fetch(PDO::FETCH_ASSOC);
                    if ($row && isset($row['role']) && $row['role'] !== '') {
                        return strtolower((string)$row['role']);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("getCurrentUserRole() error: ".$e->getMessage());
        }
        // Logged-in but no explicit role
        return (class_exists('Session') && Session::isLoggedIn()) ? 'user' : 'guest';
    }
}

/**
 * Audit Logging Function
 * Logs administrative actions to the audit_log table
 */
if (!function_exists('logAuditEvent')) {
    function logAuditEvent($entity, $entity_id, $action, $data = []) {
        try {
            $db = db();
            $userId = Session::isLoggedIn() ? Session::getUserId() : null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt = $db->prepare("
                INSERT INTO audit_log (user_id, entity_type, entity_id, action, data, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $entity,
                $entity_id,
                $action,
                json_encode($data),
                $ipAddress,
                $userAgent
            ]);
        } catch (Exception $e) {
            // Fallback to error log if database insert fails
            error_log("Audit: $action on $entity $entity_id by user " . ($userId ?? 'unknown') . ": " . json_encode($data));
        }
    }
}

?>