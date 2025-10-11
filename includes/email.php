<?php
/**
 * Email Notification System
 * E-Commerce Platform
 */

/**
 * Email Service Class
 */
class EmailService {
    private static $instance = null;
    private $queue = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Send email notification
     */
    public function send($to, $subject, $templateName, $data = [], $options = []) {
        try {
            $emailData = [
                'to' => $to,
                'subject' => $subject,
                'template' => $templateName,
                'data' => $data,
                'options' => $options,
                'created_at' => date('Y-m-d H:i:s'),
                'status' => 'pending'
            ];
            
            if (isset($options['immediate']) && $options['immediate']) {
                return $this->sendImmediate($emailData);
            } else {
                return $this->queueEmail($emailData);
            }
        } catch (Exception $e) {
            Logger::error("Email send failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Queue email for later processing
     */
    private function queueEmail($emailData) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO mail_queue (to_email, subject, template_name, template_data, options, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', ?)
        ");
        
        return $stmt->execute([
            $emailData['to'],
            $emailData['subject'],
            $emailData['template'],
            json_encode($emailData['data']),
            json_encode($emailData['options']),
            $emailData['created_at']
        ]);
    }
    
    /**
     * Send email immediately
     */
    private function sendImmediate($emailData) {
        $body = $this->renderTemplate($emailData['template'], $emailData['data']);
        
        if (!$body) {
            throw new Exception("Failed to render email template: " . $emailData['template']);
        }
        
        // Use PHP mail() function for now - can be upgraded to PHPMailer later
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . FROM_NAME . ' <' . FROM_EMAIL . '>',
            'Reply-To: ' . FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $success = mail(
            $emailData['to'],
            $emailData['subject'],
            $body,
            implode("\r\n", $headers)
        );
        
        // Log the attempt
        $this->logEmailAttempt($emailData, $success);
        
        return $success;
    }
    
    /**
     * Render email template
     */
    private function renderTemplate($templateName, $data) {
        $templatePath = __DIR__ . "/../templates/emails/{$templateName}.php";
        
        if (!file_exists($templatePath)) {
            Logger::error("Email template not found: {$templatePath}");
            return false;
        }
        
        extract($data);
        ob_start();
        try {
            include $templatePath;
            $content = ob_get_contents();
        } catch (Exception $e) {
            Logger::error("Error rendering template {$templateName}: " . $e->getMessage());
            $content = false;
        } finally {
            ob_end_clean();
        }
        
        return $content;
    }
    
    /**
     * Log email attempt
     */
    private function logEmailAttempt($emailData, $success) {
        $db = Database::getInstance()->getConnection();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO email_log (to_email, subject, template_name, status, sent_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $emailData['to'],
                $emailData['subject'],
                $emailData['template'],
                $success ? 'sent' : 'failed',
                date('Y-m-d H:i:s'),
                $emailData['created_at']
            ]);
        } catch (Exception $e) {
            Logger::error("Failed to log email attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Process email queue (for cron job)
     */
    public function processQueue($limit = 10) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM mail_queue 
            WHERE status = 'pending' 
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $emails = $stmt->fetchAll();
        
        foreach ($emails as $email) {
            try {
                $emailData = [
                    'to' => $email['to_email'],
                    'subject' => $email['subject'],
                    'template' => $email['template_name'],
                    'data' => json_decode($email['template_data'], true) ?: [],
                    'options' => json_decode($email['options'], true) ?: [],
                    'created_at' => $email['created_at']
                ];
                
                $success = $this->sendImmediate($emailData);
                
                $updateStmt = $db->prepare("
                    UPDATE mail_queue 
                    SET status = ?, processed_at = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $success ? 'sent' : 'failed',
                    date('Y-m-d H:i:s'),
                    $email['id']
                ]);
                
            } catch (Exception $e) {
                Logger::error("Queue processing error for email {$email['id']}: " . $e->getMessage());
                
                $updateStmt = $db->prepare("
                    UPDATE mail_queue 
                    SET status = 'failed', processed_at = ?, error_message = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    date('Y-m-d H:i:s'),
                    $e->getMessage(),
                    $email['id']
                ]);
            }
        }
        
        return count($emails);
    }
}

/**
 * Ensure the email_tokens table exists
 */
if (!function_exists('ensure_email_tokens_table')) {
    function ensure_email_tokens_table(PDO $db): void {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_tokens'
            ");
            $stmt->execute();
            if ((int)$stmt->fetchColumn() > 0) return;
        } catch (Throwable $e) {
            // if INFORMATION_SCHEMA fails, attempt create anyway
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS email_tokens (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                type VARCHAR(50) NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_token_hash (token_hash),
                INDEX idx_user_type (user_id, type),
                INDEX idx_expires (expires_at),
                CONSTRAINT fk_email_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        try { $db->exec($sql); } catch (Throwable $e) {}
    }
}

/**
 * Email Token Management
 * Guard against redeclaration and self-heal the email_tokens table.
 */
if (!class_exists('EmailTokenManager')) {
    class EmailTokenManager {
        public static function generateToken($userId, $type, $expiresInMinutes = 60) {
            $db = Database::getInstance()->getConnection();
            ensure_email_tokens_table($db);

            self::cleanupExpiredTokens();
            
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expiresAt = date('Y-m-d H:i:s', time() + ($expiresInMinutes * 60));
            
            $stmt = $db->prepare("
                INSERT INTO email_tokens (user_id, type, token_hash, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $userId,
                $type,
                $tokenHash,
                $expiresAt,
                date('Y-m-d H:i:s')
            ]);
            
            return $success ? $token : false;
        }
        
        public static function verifyToken($token, $type) {
            $db = Database::getInstance()->getConnection();
            ensure_email_tokens_table($db);
            
            $tokenHash = hash('sha256', $token);
            $now = date('Y-m-d H:i:s');
            
            $stmt = $db->prepare("
                SELECT * FROM email_tokens 
                WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > ?
            ");
            $stmt->execute([$tokenHash, $type, $now]);
            $tokenData = $stmt->fetch();
            
            if (!$tokenData) {
                return false;
            }
            
            $updateStmt = $db->prepare("
                UPDATE email_tokens 
                SET used_at = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([
                date('Y-m-d H:i:s'),
                $tokenData['id']
            ]);
            
            return $tokenData;
        }
        
        public static function cleanupExpiredTokens() {
            $db = Database::getInstance()->getConnection();
            ensure_email_tokens_table($db);
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("DELETE FROM email_tokens WHERE expires_at < ?");
            $stmt->execute([$now]);
        }
    }
}

/**
 * Notification Helpers - Simplified for consistency
 */
function sendWelcomeEmail($user) {
    $subject = "Welcome to " . FROM_NAME . "!";
    $message = "Hello {$user['first_name']},\n\n";
    $message .= "Welcome to " . FROM_NAME . "! We're excited to have you as part of our community.\n\n";
    $message .= "Your account is now ready:\n";
    $message .= "- Email: {$user['email']}\n";
    $message .= "- Account Type: " . ucfirst($user['role'] ?? 'Customer') . "\n\n";
    $message .= "You can now start shopping and managing your account.\n\n";
    $message .= "Visit our website: " . APP_URL . "\n\n";
    $message .= "If you have any questions, feel free to contact our support team.\n\n";
    $message .= "Welcome aboard!\n\n";
    $message .= "Best regards,\n" . FROM_NAME;
    
    $headers = "From: " . FROM_EMAIL;
    
    $success = mail($user['email'], $subject, $message, $headers);
    if ($success) {
        Logger::info("Welcome email sent to: {$user['email']}");
    } else {
        Logger::error("Failed to send welcome email to: {$user['email']}");
    }
    return $success;
}

function sendEmailVerification($user) {
    // This function is now handled directly in User::register()
    // Keeping for compatibility but redirecting to the new approach
    Logger::info("sendEmailVerification called - handled by User::register()");
    return true;
}

function sendPasswordResetEmail($user) {
    // This function is now handled directly in forgot-password.php
    // Keeping for compatibility but redirecting to the new approach
    Logger::info("sendPasswordResetEmail called - handled by forgot-password.php");
    return true;
}

function sendOrderConfirmationEmail($order, $user) {
    $subject = "Order Confirmation - Order #{$order['id']} - " . FROM_NAME;
    $message = "Hello {$user['first_name']},\n\n";
    $message .= "Thank you for your order! Here are the details:\n\n";
    $message .= "Order Number: #{$order['id']}\n";
    $message .= "Order Total: $" . number_format($order['total'] ?? 0, 2) . "\n";
    $message .= "Order Date: " . date('Y-m-d H:i:s') . "\n\n";
    $message .= "You can track your order status by visiting:\n";
    $message .= APP_URL . "/account.php?section=orders&id=" . $order['id'] . "\n\n";
    $message .= "We'll send you updates as your order is processed and shipped.\n\n";
    $message .= "Thank you for shopping with " . FROM_NAME . "!\n\n";
    $message .= "Best regards,\n" . FROM_NAME;
    
    $headers = "From: " . FROM_EMAIL;
    
    $success = mail($user['email'], $subject, $message, $headers);
    if ($success) {
        Logger::info("Order confirmation email sent to: {$user['email']} for order #{$order['id']}");
    } else {
        Logger::error("Failed to send order confirmation email to: {$user['email']} for order #{$order['id']}");
    }
    return $success;
}

function sendSellerApprovalEmail($vendor, $user) {
    $subject = "Your Seller Account Has Been Approved! - " . FROM_NAME;
    $message = "Hello {$user['first_name']},\n\n";
    $message .= "Great news! Your seller account has been approved.\n\n";
    $message .= "Business Name: {$vendor['business_name']}\n";
    $message .= "Account Type: Seller\n\n";
    $message .= "You can now start selling your products on " . FROM_NAME . ".\n\n";
    $message .= "Visit your seller center: " . APP_URL . "/seller-center.php\n\n";
    $message .= "Welcome to our seller community!\n\n";
    $message .= "Best regards,\n" . FROM_NAME;
    
    $headers = "From: " . FROM_EMAIL;
    
    $success = mail($user['email'], $subject, $message, $headers);
    if ($success) {
        Logger::info("Seller approval email sent to: {$user['email']}");
    } else {
        Logger::error("Failed to send seller approval email to: {$user['email']}");
    }
    return $success;
}

function sendLoginAlertEmail($user, $deviceInfo) {
    $subject = "New Sign-In to Your Account - " . FROM_NAME;
    $message = "Hello {$user['first_name']},\n\n";
    $message .= "We detected a new sign-in to your account:\n\n";
    $message .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $message .= "Device: " . ($deviceInfo['device'] ?? 'Unknown') . "\n";
    $message .= "Location: " . ($deviceInfo['location'] ?? 'Unknown') . "\n\n";
    $message .= "If this was you, you can safely ignore this email.\n\n";
    $message .= "If you didn't sign in, please secure your account immediately:\n";
    $message .= APP_URL . "/account.php?tab=security\n\n";
    $message .= "Best regards,\n" . FROM_NAME;
    
    $headers = "From: " . FROM_EMAIL;
    
    $success = mail($user['email'], $subject, $message, $headers);
    if ($success) {
        Logger::info("Login alert email sent to: {$user['email']}");
    } else {
        Logger::error("Failed to send login alert email to: {$user['email']}");
    }
    return $success;
}
?>