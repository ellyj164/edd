<?php
/**
 * Enhanced Email Notification System
 * For professional email delivery on Contabo VPS
 */

class EnhancedEmailSystem {
    private $config;
    private $db;
    
    public function __construct() {
        $this->config = [
            'smtp_host' => SMTP_HOST,
            'smtp_port' => SMTP_PORT,
            'smtp_username' => SMTP_USERNAME,
            'smtp_password' => SMTP_PASSWORD,
            'smtp_encryption' => SMTP_ENCRYPTION,
            'from_email' => FROM_EMAIL,
            'from_name' => FROM_NAME
        ];
        
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log("Email system database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($userEmail, $userName, $userId) {
        $subject = "Welcome to " . APP_NAME . "!";
        $template = $this->getEmailTemplate('welcome');
        
        $body = str_replace([
            '{{USER_NAME}}',
            '{{APP_NAME}}',
            '{{APP_URL}}',
            '{{SUPPORT_EMAIL}}'
        ], [
            htmlspecialchars($userName),
            APP_NAME,
            APP_URL,
            FROM_EMAIL
        ], $template);
        
        return $this->sendEmail($userEmail, $subject, $body, true, $userId, 'welcome');
    }
    
    /**
     * Send email verification
     */
    public function sendVerificationEmail($userEmail, $userName, $verificationToken, $userId) {
        $subject = "Verify your email address - " . APP_NAME;
        $template = $this->getEmailTemplate('verification');
        
        $verificationUrl = APP_URL . "/verify-email.php?token=" . urlencode($verificationToken);
        
        $body = str_replace([
            '{{USER_NAME}}',
            '{{APP_NAME}}',
            '{{VERIFICATION_URL}}',
            '{{APP_URL}}',
            '{{SUPPORT_EMAIL}}'
        ], [
            htmlspecialchars($userName),
            APP_NAME,
            $verificationUrl,
            APP_URL,
            SUPPORT_EMAIL
        ], $template);
        
        return $this->sendEmail($userEmail, $subject, $body, true, $userId, 'verification');
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($userEmail, $userName, $orderData, $userId) {
        $subject = "Order Confirmation #" . $orderData['order_number'] . " - " . APP_NAME;
        $template = $this->getEmailTemplate('order_confirmation');
        
        $orderItemsHtml = '';
        foreach ($orderData['items'] as $item) {
            $orderItemsHtml .= "<tr>
                <td>" . htmlspecialchars($item['name']) . "</td>
                <td>" . $item['quantity'] . "</td>
                <td>$" . number_format($item['price'], 2) . "</td>
                <td>$" . number_format($item['subtotal'], 2) . "</td>
            </tr>";
        }
        
        $body = str_replace([
            '{{USER_NAME}}',
            '{{ORDER_NUMBER}}',
            '{{ORDER_ITEMS}}',
            '{{ORDER_TOTAL}}',
            '{{APP_NAME}}',
            '{{APP_URL}}'
        ], [
            htmlspecialchars($userName),
            $orderData['order_number'],
            $orderItemsHtml,
            '$' . number_format($orderData['total'], 2),
            APP_NAME,
            APP_URL
        ], $template);
        
        return $this->sendEmail($userEmail, $subject, $body, true, $userId, 'order_confirmation');
    }
    
    /**
     * Enhanced SMTP email sending
     */
    private function sendEmail($to, $subject, $body, $isHtml = true, $userId = null, $type = 'general') {
        try {
            // Log email attempt
            $this->logEmailAttempt($to, $subject, $type, $userId);
            
            // Create proper email headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
                'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                'Reply-To: ' . $this->config['from_email'],
                'X-Mailer: ' . APP_NAME . ' Mailer',
                'X-Priority: 3',
                'Message-ID: <' . uniqid() . '@' . parse_url(APP_URL, PHP_URL_HOST) . '>',
                'Date: ' . date('r')
            ];
            
            // Use PHP's mail() function with proper headers for VPS delivery
            $headerString = implode("\r\n", $headers);
            $success = mail($to, $subject, $body, $headerString);
            
            if ($success) {
                $this->updateEmailStatus($to, $subject, 'sent');
                return true;
            } else {
                $this->updateEmailStatus($to, $subject, 'failed');
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            $this->updateEmailStatus($to, $subject, 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($templateName) {
        $templatePath = __DIR__ . "/../templates/emails/{$templateName}.html";
        
        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }
        
        // Fallback templates
        switch ($templateName) {
            case 'welcome':
                return $this->getWelcomeTemplate();
            case 'verification':
                return $this->getVerificationTemplate();
            case 'order_confirmation':
                return $this->getOrderConfirmationTemplate();
            default:
                return $this->getDefaultTemplate();
        }
    }
    
    /**
     * Log email attempts
     */
    private function logEmailAttempt($to, $subject, $type, $userId = null) {
        if (!$this->db) return;
        
        try {
            $sql = "INSERT INTO email_logs (recipient, subject, type, user_id, status, created_at) 
                    VALUES (?, ?, ?, ?, 'pending', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$to, $subject, $type, $userId]);
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Update email status
     */
    private function updateEmailStatus($to, $subject, $status, $error = null) {
        if (!$this->db) return;
        
        try {
            $sql = "UPDATE email_logs SET status = ?, error_message = ?, updated_at = NOW() 
                    WHERE recipient = ? AND subject = ? AND status = 'pending' 
                    ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $error, $to, $subject]);
        } catch (Exception $e) {
            error_log("Email status update error: " . $e->getMessage());
        }
    }
    
    // Template methods
    private function getWelcomeTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Welcome to {{APP_NAME}}</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #0654ba;">Welcome to {{APP_NAME}}!</h1>
                <p>Hello {{USER_NAME}},</p>
                <p>Thank you for joining {{APP_NAME}}! We\'re excited to have you as part of our community.</p>
                <p>You can now:</p>
                <ul>
                    <li>Browse thousands of products</li>
                    <li>Create your wishlist</li>
                    <li>Track your orders</li>
                    <li>Manage your account</li>
                </ul>
                <p><a href="{{APP_URL}}" style="background: #0654ba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Start Shopping</a></p>
                <p>If you have any questions, feel free to contact us at {{SUPPORT_EMAIL}}</p>
                <p>Best regards,<br>The {{APP_NAME}} Team</p>
            </div>
        </body>
        </html>';
    }
    
    private function getVerificationTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Verify Your Email - {{APP_NAME}}</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #0654ba;">Verify Your Email Address</h1>
                <p>Hello {{USER_NAME}},</p>
                <p>Please click the button below to verify your email address and activate your {{APP_NAME}} account:</p>
                <p><a href="{{VERIFICATION_URL}}" style="background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Verify Email Address</a></p>
                <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                <p><a href="{{VERIFICATION_URL}}">{{VERIFICATION_URL}}</a></p>
                <p>This verification link will expire in 24 hours.</p>
                <p>If you didn\'t create an account with {{APP_NAME}}, please ignore this email.</p>
                <p>Need help? Contact our support team at <a href="mailto:{{SUPPORT_EMAIL}}" style="color: #0654ba;">{{SUPPORT_EMAIL}}</a></p>
                <p>Best regards,<br>The {{APP_NAME}} Team</p>
            </div>
        </body>
        </html>';
    }
    
    private function getOrderConfirmationTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Order Confirmation - {{APP_NAME}}</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #0654ba;">Order Confirmation</h1>
                <p>Hello {{USER_NAME}},</p>
                <p>Thank you for your order! Your order <strong>#{{ORDER_NUMBER}}</strong> has been confirmed.</p>
                <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Product</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Qty</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Price</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{ORDER_ITEMS}}
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa; font-weight: bold;">
                            <td colspan="3" style="padding: 10px; border: 1px solid #ddd; text-align: right;">Total:</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">{{ORDER_TOTAL}}</td>
                        </tr>
                    </tfoot>
                </table>
                <p>We\'ll send you another email when your order ships.</p>
                <p><a href="{{APP_URL}}/buyer/orders.php" style="background: #0654ba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Track Your Order</a></p>
                <p>Best regards,<br>The {{APP_NAME}} Team</p>
            </div>
        </body>
        </html>';
    }
    
    private function getDefaultTemplate() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>{{APP_NAME}}</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h1 style="color: #0654ba;">{{APP_NAME}}</h1>
                <p>{{EMAIL_CONTENT}}</p>
                <p>Best regards,<br>The {{APP_NAME}} Team</p>
            </div>
        </body>
        </html>';
    }
}

// Initialize global email system instance
if (!isset($GLOBALS['emailSystem'])) {
    $GLOBALS['emailSystem'] = new EnhancedEmailSystem();
}

/**
 * Helper functions for easy email sending
 * Note: Some function names may conflict with legacy email.php
 * Enhanced versions have different signatures (individual params vs user array)
 */

// Only define if not already defined (legacy email.php may have it)
if (!function_exists('sendWelcomeEmail')) {
    function sendWelcomeEmail($userEmail, $userName, $userId) {
        return $GLOBALS['emailSystem']->sendWelcomeEmail($userEmail, $userName, $userId);
    }
}

// This function name doesn't conflict with legacy (it uses sendEmailVerification)
if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail($userEmail, $userName, $verificationToken, $userId) {
        return $GLOBALS['emailSystem']->sendVerificationEmail($userEmail, $userName, $verificationToken, $userId);
    }
}

if (!function_exists('sendOrderConfirmationEmail')) {
    function sendOrderConfirmationEmail($userEmail, $userName, $orderData, $userId) {
        return $GLOBALS['emailSystem']->sendOrderConfirmation($userEmail, $userName, $orderData, $userId);
    }
}
?>