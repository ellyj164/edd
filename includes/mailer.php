<?php
/**
 * Mailer Include - Required in all admin pages
 * Standardized email functionality for admin notifications
 */

/**
 * Send admin notification email
 */
function sendAdminNotification($to, $subject, $message, $priority = 'normal') {
    try {
        // Use existing email system if available
        if (class_exists('EmailSystem')) {
            $emailSystem = new EmailSystem();
            return $emailSystem->sendEmail($to, $subject, $message, 'admin');
        }
        
        // Fallback to simple mail
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Admin System <' . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@example.com') . '>',
            'Reply-To: ' . (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@example.com'),
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: ' . ($priority === 'high' ? '1' : ($priority === 'low' ? '5' : '3'))
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    } catch (Exception $e) {
        error_log("Failed to send admin notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send user action notification to admin
 */
function notifyAdminOfUserAction($action, $userId, $details = []) {
    try {
        $user = Database::query("SELECT username, email FROM users WHERE id = ?", [$userId])->fetch();
        if (!$user) return false;
        
        $subject = "Admin Alert: {$action}";
        $message = "
            <h3>User Action Notification</h3>
            <p><strong>Action:</strong> {$action}</p>
            <p><strong>User:</strong> {$user['username']} ({$user['email']})</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
        ";
        
        if (!empty($details)) {
            $message .= "<h4>Details:</h4><ul>";
            foreach ($details as $key => $value) {
                $message .= "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
            }
            $message .= "</ul>";
        }
        
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@example.com';
        return sendAdminNotification($adminEmail, $subject, $message, 'high');
    } catch (Exception $e) {
        error_log("Failed to notify admin of user action: " . $e->getMessage());
        return false;
    }
}

/**
 * Send security alert email
 */
function sendSecurityAlert($event, $details = []) {
    try {
        $subject = "Security Alert: {$event}";
        $message = "
            <h3>Security Alert</h3>
            <p><strong>Event:</strong> {$event}</p>
            <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>IP Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</p>
            <p><strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</p>
        ";
        
        if (!empty($details)) {
            $message .= "<h4>Details:</h4><ul>";
            foreach ($details as $key => $value) {
                $message .= "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars(is_array($value) ? json_encode($value) : $value) . "</li>";
            }
            $message .= "</ul>";
        }
        
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@example.com';
        return sendAdminNotification($adminEmail, $subject, $message, 'high');
    } catch (Exception $e) {
        error_log("Failed to send security alert: " . $e->getMessage());
        return false;
    }
}

/**
 * Send order status notification
 */
function sendOrderStatusNotification($orderId, $oldStatus, $newStatus, $notes = '') {
    try {
        $order = Database::query(
            "SELECT o.*, u.email, u.username 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE o.id = ?", 
            [$orderId]
        )->fetch();
        
        if (!$order) return false;
        
        $subject = "Order #{$orderId} Status Updated";
        $message = "
            <h3>Order Status Update</h3>
            <p>Dear {$order['username']},</p>
            <p>Your order #{$orderId} status has been updated:</p>
            <ul>
                <li><strong>Previous Status:</strong> " . ucfirst($oldStatus) . "</li>
                <li><strong>New Status:</strong> " . ucfirst($newStatus) . "</li>
                <li><strong>Order Total:</strong> $" . number_format($order['total'], 2) . "</li>
            </ul>
        ";
        
        if (!empty($notes)) {
            $message .= "<p><strong>Notes:</strong> " . htmlspecialchars($notes) . "</p>";
        }
        
        $message .= "
            <p>You can track your order progress in your account dashboard.</p>
            <p>Thank you for your business!</p>
        ";
        
        return sendAdminNotification($order['email'], $subject, $message);
    } catch (Exception $e) {
        error_log("Failed to send order status notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send vendor approval notification
 */
function sendVendorApprovalNotification($vendorId, $approved = true) {
    try {
        $vendor = Database::query(
            "SELECT v.*, u.email, u.username 
             FROM vendors v 
             JOIN users u ON v.user_id = u.id 
             WHERE v.id = ?", 
            [$vendorId]
        )->fetch();
        
        if (!$vendor) return false;
        
        $subject = $approved ? "Vendor Application Approved" : "Vendor Application Requires Review";
        $message = "
            <h3>Vendor Application Update</h3>
            <p>Dear {$vendor['username']},</p>
        ";
        
        if ($approved) {
            $message .= "
                <p>Congratulations! Your vendor application has been approved.</p>
                <p>You can now start selling on our platform. Please log in to your vendor dashboard to:</p>
                <ul>
                    <li>Complete your store setup</li>
                    <li>Add your first products</li>
                    <li>Configure payment and shipping options</li>
                </ul>
            ";
        } else {
            $message .= "
                <p>Thank you for your vendor application. We are currently reviewing your submission.</p>
                <p>Our team will contact you within 3-5 business days with an update.</p>
                <p>If you have any questions, please don't hesitate to contact our support team.</p>
            ";
        }
        
        $message .= "<p>Thank you for choosing to partner with us!</p>";
        
        return sendAdminNotification($vendor['email'], $subject, $message);
    } catch (Exception $e) {
        error_log("Failed to send vendor approval notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Send KYC status notification
 */
function sendKycStatusNotification($userId, $status, $notes = '') {
    try {
        $user = Database::query("SELECT username, email FROM users WHERE id = ?", [$userId])->fetch();
        if (!$user) return false;
        
        $subject = "KYC Verification Update";
        $message = "
            <h3>KYC Verification Status Update</h3>
            <p>Dear {$user['username']},</p>
            <p>Your KYC verification status has been updated to: <strong>" . ucfirst($status) . "</strong></p>
        ";
        
        switch ($status) {
            case 'approved':
                $message .= "<p>Congratulations! Your identity verification has been approved. You now have full access to all platform features.</p>";
                break;
            case 'rejected':
                $message .= "<p>Unfortunately, we were unable to verify your identity with the provided documents. Please review the notes below and resubmit with corrected information.</p>";
                break;
            case 'pending':
                $message .= "<p>Your documents are currently under review. We will notify you once the verification is complete.</p>";
                break;
        }
        
        if (!empty($notes)) {
            $message .= "<p><strong>Review Notes:</strong> " . htmlspecialchars($notes) . "</p>";
        }
        
        $message .= "<p>If you have any questions, please contact our support team.</p>";
        
        return sendAdminNotification($user['email'], $subject, $message);
    } catch (Exception $e) {
        error_log("Failed to send KYC status notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Queue email for batch processing (if email queue system exists)
 */
function queueAdminEmail($to, $subject, $message, $priority = 'normal', $templateData = []) {
    try {
        // Check if email queue table exists
        $tableExists = Database::query("SHOW TABLES LIKE 'email_queue'")->fetch();
        
        if ($tableExists) {
            Database::query(
                "INSERT INTO email_queue (recipient, subject, message, priority, template_data, created_at, status) 
                 VALUES (?, ?, ?, ?, ?, NOW(), 'pending')",
                [$to, $subject, $message, $priority, json_encode($templateData)]
            );
            return true;
        } else {
            // Fallback to direct sending
            return sendAdminNotification($to, $subject, $message, $priority);
        }
    } catch (Exception $e) {
        error_log("Failed to queue admin email: " . $e->getMessage());
        return false;
    }
}

/**
 * Get email template (simple template system)
 */
function getEmailTemplate($templateName, $variables = []) {
    $templates = [
        'admin_alert' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #2c3e50;">{{title}}</h2>
                <p>{{message}}</p>
                <hr style="border: 1px solid #eee;">
                <p style="font-size: 12px; color: #666;">
                    This is an automated message from the admin system.
                </p>
            </div>
        ',
        'user_notification' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                <h2 style="color: #2c3e50;">{{title}}</h2>
                <p>Dear {{username}},</p>
                <p>{{message}}</p>
                <p>Best regards,<br>The Team</p>
            </div>
        '
    ];
    
    $template = $templates[$templateName] ?? $templates['admin_alert'];
    
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', htmlspecialchars($value), $template);
    }
    
    return $template;
}