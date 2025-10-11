<?php
/**
 * API Authentication Middleware
 * E-Commerce Platform
 * 
 * Validates API keys and handles authentication for API requests
 */

class ApiAuth {
    private $db;
    private $environment;
    private $apiKey;
    private $keyData;
    
    public function __construct($db = null) {
        $this->db = $db ?? db();
    }
    
    /**
     * Authenticate an API request
     * 
     * @return array|false Returns key data if valid, false otherwise
     */
    public function authenticate() {
        // Get API key from Authorization header
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (empty($authHeader)) {
            $this->sendError(401, 'MISSING_AUTH', 'Authorization header is required');
            return false;
        }
        
        // Extract bearer token
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->sendError(401, 'INVALID_AUTH', 'Invalid Authorization header format');
            return false;
        }
        
        $this->apiKey = trim($matches[1]);
        
        // Determine environment from API key prefix
        if (strpos($this->apiKey, 'feza_sandbox_') === 0) {
            $this->environment = 'sandbox';
        } elseif (strpos($this->apiKey, 'feza_live_') === 0) {
            $this->environment = 'live';
        } else {
            $this->sendError(401, 'INVALID_KEY', 'Invalid API key format');
            return false;
        }
        
        // Validate API key in database
        try {
            $stmt = $this->db->prepare("
                SELECT id, user_id, name, environment, is_active, rate_limit, rate_window, last_used_at 
                FROM api_keys 
                WHERE api_key = ? AND environment = ?
            ");
            $stmt->execute([$this->apiKey, $this->environment]);
            $this->keyData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$this->keyData) {
                $this->sendError(401, 'INVALID_KEY', 'Invalid API key');
                return false;
            }
            
            if (!$this->keyData['is_active']) {
                $this->sendError(403, 'KEY_INACTIVE', 'This API key has been deactivated');
                return false;
            }
            
            // Check rate limit
            if (!$this->checkRateLimit()) {
                $this->sendError(429, 'RATE_LIMIT_EXCEEDED', 'Rate limit exceeded');
                return false;
            }
            
            // Update last_used_at
            $this->updateLastUsed();
            
            return $this->keyData;
            
        } catch (Exception $e) {
            error_log("API Auth Error: " . $e->getMessage());
            $this->sendError(500, 'AUTH_ERROR', 'Authentication error');
            return false;
        }
    }
    
    /**
     * Check rate limit for API key
     */
    private function checkRateLimit() {
        if (!$this->keyData) {
            return false;
        }
        
        $keyId = $this->keyData['id'];
        $rateLimit = $this->keyData['rate_limit'];
        $rateWindow = $this->keyData['rate_window'];
        
        // Count requests in the current window
        $windowStart = date('Y-m-d H:i:s', time() - $rateWindow);
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as request_count 
                FROM api_logs 
                WHERE api_key_id = ? AND created_at >= ?
            ");
            $stmt->execute([$keyId, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $requestCount = $result['request_count'] ?? 0;
            
            // Set rate limit headers
            header("X-RateLimit-Limit: $rateLimit");
            header("X-RateLimit-Remaining: " . max(0, $rateLimit - $requestCount));
            header("X-RateLimit-Reset: " . (time() + $rateWindow));
            
            return $requestCount < $rateLimit;
            
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Allow request on error
        }
    }
    
    /**
     * Update last_used_at timestamp
     */
    private function updateLastUsed() {
        if (!$this->keyData) {
            return;
        }
        
        try {
            $stmt = $this->db->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
            $stmt->execute([$this->keyData['id']]);
        } catch (Exception $e) {
            error_log("Update last_used error: " . $e->getMessage());
        }
    }
    
    /**
     * Log API request
     */
    public function logRequest($endpoint, $method, $statusCode, $responseBody = null) {
        if (!$this->keyData) {
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO api_logs (api_key_id, endpoint, method, request_headers, request_body, 
                                     response_status, response_body, ip_address, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $requestHeaders = json_encode(getallheaders());
            $requestBody = file_get_contents('php://input');
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt->execute([
                $this->keyData['id'],
                $endpoint,
                $method,
                $requestHeaders,
                $requestBody,
                $statusCode,
                $responseBody,
                $ipAddress
            ]);
        } catch (Exception $e) {
            error_log("API log error: " . $e->getMessage());
        }
    }
    
    /**
     * Get environment (sandbox or live)
     */
    public function getEnvironment() {
        return $this->environment;
    }
    
    /**
     * Get authenticated key data
     */
    public function getKeyData() {
        return $this->keyData;
    }
    
    /**
     * Send error response and exit
     */
    private function sendError($statusCode, $errorCode, $message) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message
            ]
        ]);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function sendSuccess($data, $pagination = null) {
        http_response_code(200);
        header('Content-Type: application/json');
        
        $response = [
            'success' => true,
            'data' => $data
        ];
        
        if ($pagination) {
            $response['pagination'] = $pagination;
        }
        
        echo json_encode($response);
        exit;
    }
}
