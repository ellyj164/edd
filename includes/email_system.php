<?php
/**
 * Email System for E-Commerce Platform
 * PHP 8 with enhanced security and delivery tracking
 */

class EmailSystem {
    private $smtpHost;
    private $smtpPort; 
    private $smtpUsername;
    private $smtpPassword;
    private $smtpEncryption;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        $this->smtpHost = SMTP_HOST;
        $this->smtpPort = SMTP_PORT;
        $this->smtpUsername = SMTP_USERNAME;
        $this->smtpPassword = SMTP_PASSWORD;
        $this->smtpEncryption = SMTP_ENCRYPTION ?? 'tls';
        $this->fromEmail = FROM_EMAIL;
        $this->fromName = FROM_NAME;
    }
    
    /**
     * Send email using SMTP with enhanced error handling
     */
    public function sendEmail($to, $subject, $body, $isHtml = true, $attachments = []) {
        try {
            // Create message headers
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
                'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
                'Reply-To: ' . $this->fromEmail,
                'X-Mailer: PHP/' . phpversion(),
                'X-Priority: 3',
                'Date: ' . date('r')
            ];
            
            // Add DKIM-ready headers
            $headers[] = 'Message-ID: <' . uniqid() . '@' . parse_url(APP_URL, PHP_URL_HOST) . '>';
            
            // For production, use SMTP
            if ($this->smtpHost !== 'localhost' && !empty($this->smtpUsername)) {
                return $this->sendViaSMTP($to, $subject, $body, $isHtml, $attachments);
            }
            
            // Fallback to PHP mail() for development
            $headerString = implode("\r\n", $headers);
            $success = mail($to, $subject, $body, $headerString);
            
            // Log email attempt
            $this->logEmailAttempt($to, $subject, $success ? 'sent' : 'failed');
            
            return $success;
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            $this->logEmailAttempt($to, $subject, 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send via SMTP with socket connection
     */
    private function sendViaSMTP($to, $subject, $body, $isHtml, $attachments = []) {
        $socket = fsockopen($this->smtpHost, $this->smtpPort, $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Could not connect to SMTP server: $errstr ($errno)");
        }
        
        // Read initial response
        fgets($socket, 512);
        
        // EHLO
        fwrite($socket, "EHLO " . parse_url(APP_URL, PHP_URL_HOST) . "\r\n");
        fgets($socket, 512);
        
        // Start TLS if enabled
        if ($this->smtpEncryption === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            fgets($socket, 512);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // EHLO again after TLS
            fwrite($socket, "EHLO " . parse_url(APP_URL, PHP_URL_HOST) . "\r\n");
            fgets($socket, 512);
        }
        
        // Authentication
        if (!empty($this->smtpUsername)) {
            fwrite($socket, "AUTH LOGIN\r\n");
            fgets($socket, 512);
            
            fwrite($socket, base64_encode($this->smtpUsername) . "\r\n");
            fgets($socket, 512);
            
            fwrite($socket, base64_encode($this->smtpPassword) . "\r\n");
            fgets($socket, 512);
        }
        
        // Mail from
        fwrite($socket, "MAIL FROM: <" . $this->fromEmail . ">\r\n");
        fgets($socket, 512);
        
        // Recipients
        $recipients = is_array($to) ? $to : [$to];
        foreach ($recipients as $recipient) {
            fwrite($socket, "RCPT TO: <$recipient>\r\n");
            fgets($socket, 512);
        }
        
        // Data
        fwrite($socket, "DATA\r\n");
        fgets($socket, 512);
        
        // Headers
        $message = "From: " . $this->fromName . " <" . $this->fromEmail . ">\r\n";
        $message .= "To: " . (is_array($to) ? implode(', ', $to) : $to) . "\r\n";
        $message .= "Subject: " . $subject . "\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: " . ($isHtml ? 'text/html' : 'text/plain') . "; charset=UTF-8\r\n";
        $message .= "Date: " . date('r') . "\r\n";
        $message .= "\r\n";
        $message .= $body . "\r\n";
        $message .= ".\r\n";
        
        fwrite($socket, $message);
        fgets($socket, 512);
        
        // Quit
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        $this->logEmailAttempt(is_array($to) ? implode(', ', $to) : $to, $subject, 'sent');
        return true;
    }
    
    /**
     * Log email attempts for debugging and delivery tracking
     */
    private function logEmailAttempt($to, $subject, $status, $error = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("
                INSERT INTO email_logs (recipient, subject, status, error_message, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([$to, $subject, $status, $error]);
        } catch (Exception $e) {
            error_log("Could not log email attempt: " . $e->getMessage());
        }
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($userEmail, $userName, $verificationToken) {
        $subject = "Verify Your Email Address - " . APP_NAME;
        $verificationUrl = APP_URL . "/verify-email.php?token=" . urlencode($verificationToken);
        
        $body = $this->getEmailTemplate('verification', [
            'user_name' => $userName,
            'verification_url' => $verificationUrl,
            'app_name' => APP_NAME,
            'app_url' => APP_URL
        ]);
        
        return $this->sendEmail($userEmail, $subject, $body, true);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($userEmail, $userName, $resetToken) {
        $subject = "Reset Your Password - " . APP_NAME;
        $resetUrl = APP_URL . "/reset-password.php?token=" . urlencode($resetToken);
        
        $body = $this->getEmailTemplate('password_reset', [
            'user_name' => $userName,
            'reset_url' => $resetUrl,
            'app_name' => APP_NAME,
            'app_url' => APP_URL
        ]);
        
        return $this->sendEmail($userEmail, $subject, $body, true);
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($userEmail, $userName, $orderDetails) {
        $subject = "Order Confirmation #" . $orderDetails['order_number'] . " - " . APP_NAME;
        
        $body = $this->getEmailTemplate('order_confirmation', [
            'user_name' => $userName,
            'order_number' => $orderDetails['order_number'],
            'order_total' => $orderDetails['total'],
            'order_items' => $orderDetails['items'],
            'tracking_url' => APP_URL . "/account.php?section=orders&id=" . $orderDetails['id'],
            'app_name' => APP_NAME,
            'app_url' => APP_URL
        ]);
        
        return $this->sendEmail($userEmail, $subject, $body, true);
    }
    
    /**
     * Get email template with variable substitution
     */
    private function getEmailTemplate($templateName, $variables = []) {
        $templateFile = __DIR__ . "/../templates/email/{$templateName}.html";
        
        if (file_exists($templateFile)) {
            $template = file_get_contents($templateFile);
        } else {
            $template = $this->getDefaultTemplate($templateName);
        }
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Default email templates if files don't exist
     */
    private function getDefaultTemplate($templateName) {
        switch ($templateName) {
            case 'verification':
                return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verify Your Email</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #333;">Welcome to {{app_name}}!</h1>
    <p>Hello {{user_name}},</p>
    <p>Thank you for registering with {{app_name}}. To complete your registration, please verify your email address by clicking the button below:</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{verification_url}}" style="background-color: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email Address</a>
    </div>
    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
    <p><a href="{{verification_url}}">{{verification_url}}</a></p>
    <p>This verification link will expire in 24 hours.</p>
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
    <p style="color: #666; font-size: 12px;">This email was sent from {{app_name}}. If you didn\'t create an account, please ignore this email.</p>
</body>
</html>';
            
            case 'password_reset':
                return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h1 style="color: #333;">Password Reset Request</h1>
    <p>Hello {{user_name}},</p>
    <p>We received a request to reset your password for your {{app_name}} account.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{reset_url}}" style="background-color: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
    </div>
    <p>If the button doesn\'t work, you can copy and paste this link into your browser:</p>
    <p><a href="{{reset_url}}">{{reset_url}}</a></p>
    <p>This password reset link will expire in 1 hour. If you didn\'t request this reset, please ignore this email.</p>
    <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
    <p style="color: #666; font-size: 12px;">This email was sent from {{app_name}}. For security, never share this link with anyone.</p>
</body>
</html>';
            
            default:
                return '<html><body><h1>{{app_name}}</h1><p>Email content</p></body></html>';
        }
    }
}

// Global email function
function sendEmail($to, $subject, $body, $isHtml = true) {
    $emailSystem = new EmailSystem();
    return $emailSystem->sendEmail($to, $subject, $body, $isHtml);
}
?>