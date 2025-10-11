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
 * Send security alert email to user
 * @param int $userId User ID
 * @param string $event Event type (e.g., 'New Login', 'Account Changed')
 * @param array $details Additional details about the event
 */
function sendUserSecurityAlert($userId, $event, $details = []) {
    try {
        // Get user information
        $user = Database::query("SELECT username, email, first_name, last_name FROM users WHERE id = ?", [$userId])->fetch();
        if (!$user) {
            error_log("User not found for security alert: " . $userId);
            return false;
        }
        
        $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'];
        $userEmail = $user['email'];
        
        // Determine subject and message based on event type
        $subject = "Security Alert: {$event}";
        $message = "
            <div style=\"font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;\">
                <div style=\"background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin-bottom: 20px;\">
                    <h2 style=\"color: #856404; margin: 0 0 10px 0;\">🔐 Security Alert</h2>
                    <p style=\"margin: 0; color: #856404; font-weight: bold;\">{$event}</p>
                </div>
                
                <p>Dear {$userName},</p>
                
                <p>We detected the following activity on your account:</p>
                
                <div style=\"background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;\">
                    <p style=\"margin: 5px 0;\"><strong>Event:</strong> {$event}</p>
                    <p style=\"margin: 5px 0;\"><strong>Time:</strong> " . date('F j, Y, g:i a') . "</p>
                    <p style=\"margin: 5px 0;\"><strong>IP Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</p>
                    <p style=\"margin: 5px 0;\"><strong>Device/Browser:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "</p>
        ";
        
        if (!empty($details)) {
            foreach ($details as $key => $value) {
                $displayValue = is_array($value) ? json_encode($value) : $value;
                $message .= "<p style=\"margin: 5px 0;\"><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($displayValue) . "</p>";
            }
        }
        
        $message .= "
                </div>
                
                <p><strong>If this was you:</strong> No action is needed.</p>
                
                <p><strong>If this wasn't you:</strong> Please secure your account immediately:</p>
                <ul>
                    <li>Change your password</li>
                    <li>Review your recent account activity</li>
                    <li>Contact our support team if you need assistance</li>
                </ul>
                
                <div style=\"margin-top: 30px; padding-top: 20px; border-top: 1px solid #e0e0e0;\">
                    <p style=\"font-size: 12px; color: #666;\">
                        This is an automated security notification. If you have any concerns, please contact our support team.
                    </p>
                    <p style=\"font-size: 12px; color: #666;\">
                        <strong>Best regards,</strong><br>
                        The Security Team
                    </p>
                </div>
            </div>
        ";
        
        return sendAdminNotification($userEmail, $subject, $message, 'high');
    } catch (Exception $e) {
        error_log("Failed to send user security alert: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if login is from new device/location and send alert
 * @param int $userId User ID
 * @param array $userInfo User information array
 */
function checkAndSendLoginAlert($userId, $userInfo) {
    try {
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Check if we have seen this IP + User Agent combination before
        $recentLogin = Database::query(
            "SELECT id FROM user_sessions 
             WHERE user_id = ? 
             AND ip_address = ? 
             AND user_agent = ? 
             AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
             LIMIT 1",
            [$userId, $currentIp, $currentUserAgent]
        )->fetch();
        
        // If no recent login from this device/IP, send alert
        if (!$recentLogin) {
            $deviceInfo = parseUserAgentSimple($currentUserAgent);
            $locationInfo = geolocateIp($currentIp);
            
            $details = [
                'Device Type' => $deviceInfo['device'] ?? 'Unknown',
                'Browser' => $deviceInfo['browser'] ?? 'Unknown',
                'Operating System' => $deviceInfo['os'] ?? 'Unknown',
                'Location' => $locationInfo ?? 'Unknown'
            ];
            
            sendUserSecurityAlert($userId, 'New Login from Unrecognized Device', $details);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to check login alert: " . $e->getMessage());
        return false;
    }
}

/**
 * Simple user agent parser
 */
function parseUserAgentSimple($userAgent) {
    $info = [];
    
    // Detect browser
    if (preg_match('/Chrome\/[\d.]+/', $userAgent)) {
        $info['browser'] = 'Google Chrome';
    } elseif (preg_match('/Firefox\/[\d.]+/', $userAgent)) {
        $info['browser'] = 'Mozilla Firefox';
    } elseif (preg_match('/Safari\/[\d.]+/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
        $info['browser'] = 'Safari';
    } elseif (preg_match('/Edge\/[\d.]+/', $userAgent)) {
        $info['browser'] = 'Microsoft Edge';
    } else {
        $info['browser'] = 'Unknown Browser';
    }
    
    // Detect OS
    if (preg_match('/Windows NT/', $userAgent)) {
        $info['os'] = 'Windows';
    } elseif (preg_match('/Mac OS X/', $userAgent)) {
        $info['os'] = 'macOS';
    } elseif (preg_match('/Linux/', $userAgent)) {
        $info['os'] = 'Linux';
    } elseif (preg_match('/Android/', $userAgent)) {
        $info['os'] = 'Android';
    } elseif (preg_match('/iOS|iPhone|iPad/', $userAgent)) {
        $info['os'] = 'iOS';
    } else {
        $info['os'] = 'Unknown OS';
    }
    
    // Detect device type
    if (preg_match('/Mobile|Android|iPhone/', $userAgent)) {
        $info['device'] = 'Mobile';
    } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
        $info['device'] = 'Tablet';
    } else {
        $info['device'] = 'Desktop';
    }
    
    return $info;
}

/**
 * Simple IP geolocation (placeholder - integrate with GeoIP service)
 */
function geolocateIp($ip) {
    // In production, integrate with a GeoIP service like MaxMind, IPinfo, or ip-api.com
    // For now, return a placeholder
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return 'Local/Localhost';
    }
    return 'Location Unknown (Integrate GeoIP service)';
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