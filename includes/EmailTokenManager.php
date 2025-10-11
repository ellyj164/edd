<?php
/**
 * Secure Email Token Manager
 * Implements PHP 8 + MariaDB compatible OTP verification with security enhancements
 */

class EmailTokenManager {
    private static $pepper = null;
    private PDO $db;

    // Detect and use the correct storage column for token hashes (prefer token_hash; legacy token supported)
    private string $tokenColumn = 'token_hash';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        // Initialize pepper from environment or generate secure fallback
        if (self::$pepper === null) {
            self::$pepper = env('OTP_PEPPER', SECRET_KEY . '_otp_salt_v1');
        }

        // Tables should exist from schema.sql - only ensure if missing
        $this->ensureRequiredTablesExist();

        // Detect whether email_tokens has token_hash or legacy token
        $this->detectTokenColumn();
    }

    /**
     * Detect which column exists in email_tokens for storing the OTP hash.
     * Prefer token_hash if present; fall back to token for legacy compatibility.
     */
    private function detectTokenColumn(): void {
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM email_tokens LIKE 'token_hash'");
            if ($stmt && $stmt->rowCount() > 0) {
                $this->tokenColumn = 'token_hash';
                return;
            }
            $stmt = $this->db->query("SHOW COLUMNS FROM email_tokens LIKE 'token'");
            if ($stmt && $stmt->rowCount() > 0) {
                $this->tokenColumn = 'token'; // legacy schema
                Logger::warning("email_tokens is missing token_hash; using legacy 'token' column");
                return;
            }
            Logger::error("email_tokens table has neither 'token_hash' nor 'token' column.");
        } catch (Exception $e) {
            Logger::error("Failed to detect token column: " . $e->getMessage());
        }
    }

    /**
     * Generate secure OTP token and store with hash
     */
    public function generateToken(int $userId, string $type = 'email_verification', string $email = null, int $expiryMinutes = 15): ?string {
        try {
            // Generate 6-digit OTP (stored as string to preserve leading zeros)
            $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

            // Create hash with pepper for storage
            $tokenHash = hash('sha256', $otp . self::$pepper);

            // Calculate expiry time in UTC
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));

            $this->db->beginTransaction();

            try {
                // Mark any existing unconsumed tokens as used (better for audit trail)
                $stmt = $this->db->prepare("
                    UPDATE email_tokens 
                    SET used_at = NOW()
                    WHERE user_id = ? AND type = ? AND used_at IS NULL
                ");
                $stmt->execute([$userId, $type]);

                // Insert new hashed token using detected column
                $sql = "
                    INSERT INTO email_tokens (user_id, {$this->tokenColumn}, type, email, expires_at, ip_address, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $this->db->prepare($sql);

                $stmt->execute([
                    $userId,
                    $tokenHash, // Store hash, not plain OTP
                    $type,
                    $email,
                    $expiresAt,
                    $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    date('Y-m-d H:i:s')
                ]);

                $this->db->commit();

                Logger::info("OTP generated for user {$userId}, type {$type} using column {$this->tokenColumn}");
                return $otp; // Return plain OTP for sending in email

            } catch (Exception $e) {
                $this->db->rollBack();
                Logger::error("Failed to store OTP token: " . $e->getMessage());
                return null;
            }

        } catch (Exception $e) {
            Logger::error("Failed to generate OTP: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify OTP token with rate limiting and security checks
     */
    public function verifyToken(string $otp, string $type, int $userId, string $email = null): array {
        $result = ['success' => false, 'message' => 'Invalid verification code.', 'rate_limited' => false];

        try {
            // Sanitize OTP input: trim whitespace and ensure exactly 6 digits
            $otp = trim($otp);
            if (!preg_match('/^\d{6}$/', $otp)) {
                Logger::warning("Invalid OTP format for user {$userId}: " . strlen($otp) . " characters");
                return $result;
            }

            // Check rate limiting first
            if (!$this->checkRateLimit($userId, $email, $type)) {
                $result['rate_limited'] = true;
                $result['message'] = 'Too many attempts. Please try again later.';
                return $result;
            }

            // Log the attempt
            $this->logAttempt($userId, $email, $type, false);

            // Hash the provided OTP with pepper
            $otpHash = hash('sha256', $otp . self::$pepper);

            // First check if token exists (regardless of expiry)
            $checkSql = "
                SELECT id, user_id, email, expires_at, used_at, created_at
                FROM email_tokens 
                WHERE user_id = ? AND type = ? AND {$this->tokenColumn} = ?
                ORDER BY created_at DESC
                LIMIT 1
            ";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$userId, $type, $otpHash]);
            $existingToken = $checkStmt->fetch();

            if ($existingToken) {
                // Token exists, check if it's expired or already used
                if ($existingToken['used_at'] !== null) {
                    Logger::warning("Attempt to use already consumed token for user {$userId}");
                    $result['message'] = 'This verification code has already been used.';
                    return $result;
                }
                
                if (strtotime($existingToken['expires_at']) <= time()) {
                    Logger::warning("Expired token for user {$userId}");
                    $result['message'] = 'This verification code has expired. Please request a new one.';
                    $result['expired'] = true;
                    return $result;
                }
            }

            // Find valid token using hash comparison against detected column
            $sql = "
                SELECT id, user_id, email, expires_at, used_at, created_at
                FROM email_tokens 
                WHERE user_id = ? AND type = ? AND {$this->tokenColumn} = ? 
                AND expires_at > NOW() AND used_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);

            $stmt->execute([$userId, $type, $otpHash]);
            $tokenData = $stmt->fetch();

            if (!$tokenData) {
                Logger::warning("Invalid OTP attempt for user {$userId}, type {$type} (column {$this->tokenColumn})");
                return $result;
            }

            // Mark token as used (single-use)
            $this->markTokenAsUsed($tokenData['id']);

            // Log successful attempt
            $this->logAttempt($userId, $email, $type, true);

            Logger::info("OTP verified successfully for user {$userId}, type {$type} using column {$this->tokenColumn}");

            $result['success'] = true;
            $result['message'] = 'Verification successful.';
            return $result;

        } catch (Exception $e) {
            Logger::error("Token verification error: " . $e->getMessage());
            return $result;
        }
    }

    /**
     * Check rate limiting for OTP attempts
     */
    private function checkRateLimit(int $userId, ?string $email, string $type, int $maxAttempts = 5, int $windowMinutes = 15): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM otp_attempts 
                WHERE (user_id = ? OR email = ?) 
                AND token_type = ?
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
                AND success = 0
            ");

            $stmt->execute([$userId, $email, $type, $windowMinutes]);
            $attempts = (int)$stmt->fetchColumn();

            return $attempts < $maxAttempts;

        } catch (Exception $e) {
            Logger::error("Rate limit check failed: " . $e->getMessage());
            return true; // Allow on error to avoid blocking legitimate users
        }
    }

    /**
     * Log OTP attempt for rate limiting
     */
    private function logAttempt(int $userId, ?string $email, string $type, bool $success): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO otp_attempts (user_id, email, ip_address, attempted_at, success, token_type)
                VALUES (?, ?, ?, NOW(), ?, ?)
            ");

            $stmt->execute([
                $userId,
                $email,
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $success ? 1 : 0,
                $type
            ]);

        } catch (Exception $e) {
            Logger::error("Failed to log OTP attempt: " . $e->getMessage());
        }
    }

    /**
     * Mark token as used (single-use enforcement)
     */
    private function markTokenAsUsed(int $tokenId): void {
        try {
            $stmt = $this->db->prepare("
                UPDATE email_tokens 
                SET used_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$tokenId]);

        } catch (Exception $e) {
            Logger::error("Failed to mark token as used: " . $e->getMessage());
        }
    }

    /**
     * Clean up expired tokens (should be called periodically)
     */
    public function cleanupExpiredTokens(): int {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM email_tokens 
                WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stmt->execute();

            $deletedCount = $stmt->rowCount();
            Logger::info("Cleaned up {$deletedCount} expired tokens");

            return $deletedCount;

        } catch (Exception $e) {
            Logger::error("Token cleanup failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up old OTP attempts (should be called periodically)
     */
    public function cleanupOldAttempts(): int {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM otp_attempts 
                WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute();

            $deletedCount = $stmt->rowCount();
            Logger::info("Cleaned up {$deletedCount} old OTP attempts");

            return $deletedCount;

        } catch (Exception $e) {
            Logger::error("OTP attempts cleanup failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Ensure required tables exist (they should be created by schema.sql)
     */
    private function ensureRequiredTablesExist(): void {
        try {
            // Check if tables exist, if not log error (they should be created by schema.sql)
            $stmt = $this->db->query("SHOW TABLES LIKE 'email_tokens'");
            if ($stmt->rowCount() === 0) {
                Logger::error("email_tokens table does not exist - please run schema.sql");
            }

            $stmt = $this->db->query("SHOW TABLES LIKE 'otp_attempts'");
            if ($stmt->rowCount() === 0) {
                Logger::error("otp_attempts table does not exist - please run schema.sql");
            }
        } catch (Exception $e) {
            Logger::error("Failed to check required tables: " . $e->getMessage());
        }
    }
}