<?php
/**
 * Professional Email Template Helper
 * Centralized email templating system
 */

class EmailTemplate {
    private $templateDir;
    
    public function __construct() {
        $this->templateDir = __DIR__ . '/emails/';
    }
    
    /**
     * Render email template
     */
    public function render($templateName, $data = []) {
        $templatePath = $this->templateDir . $templateName . '.php';
        
        if (!file_exists($templatePath)) {
            error_log("Email template not found: {$templatePath}");
            return $this->fallbackTemplate($data);
        }
        
        // Extract data for template
        extract($data);
        
        // Start output buffering
        ob_start();
        include $templatePath;
        $html = ob_get_clean();
        
        return $html;
    }
    
    /**
     * Send email using template
     */
    public function send($to, $subject, $templateName, $data = []) {
        $html = $this->render($templateName, $data);
        
        // Use existing mailer
        if (function_exists('send_email')) {
            return send_email($to, $subject, $html);
        }
        
        // Fallback to PHP mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . env('FROM_EMAIL', 'no-reply@fezamarket.com')
        ];
        
        return mail($to, $subject, $html, implode("\r\n", $headers));
    }
    
    /**
     * Fallback template
     */
    private function fallbackTemplate($data) {
        $siteName = env('APP_NAME', 'FezaMarket');
        $content = $data['content'] ?? 'Email content';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
        </head>
        <body style='font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px;'>
                <h1 style='color: #333;'>{$siteName}</h1>
                <div>{$content}</div>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
                <p style='color: #666; font-size: 12px; text-align: center;'>
                    © " . date('Y') . " {$siteName}. All rights reserved.
                </p>
            </div>
        </body>
        </html>
        ";
    }
}

/**
 * Helper function to send templated email
 */
function send_template_email($to, $subject, $templateName, $data = []) {
    $emailTemplate = new EmailTemplate();
    return $emailTemplate->send($to, $subject, $templateName, $data);
}
