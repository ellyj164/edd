<?php
/**
 * Notification System
 * Handles sending notifications for e-commerce events
 */

class NotificationService {
    private $db;
    
    public function __construct() {
        $this->db = db();
    }
    
    /**
     * Send a notification based on template
     * 
     * @param string $type Notification type (matches template type)
     * @param int $userId User ID to send to
     * @param array $variables Variables to replace in template
     * @param bool $sendEmail Also send email
     * @param bool $sendInApp Also send in-app notification
     * @return bool Success
     */
    public function send($type, $userId, $variables = [], $sendEmail = true, $sendInApp = true) {
        try {
            // Get template
            $stmt = $this->db->prepare("
                SELECT * FROM notification_templates 
                WHERE type = ? AND enabled = 1
            ");
            $stmt->execute([$type]);
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                error_log("Notification template not found: {$type}");
                return false;
            }
            
            // Get user info
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                error_log("User not found: {$userId}");
                return false;
            }
            
            // Add default variables
            $variables['customer_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            if (empty(trim($variables['customer_name']))) {
                $variables['customer_name'] = $user['username'] ?? 'Customer';
            }
            
            // Replace variables in template
            $subject = $this->replaceVariables($template['subject'], $variables);
            $body = $this->replaceVariables($template['body_template'], $variables);
            
            // Send in-app notification
            if ($sendInApp) {
                $stmt = $this->db->prepare("
                    INSERT INTO notifications (user_id, type, title, message, action_url, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $userId,
                    $type,
                    $subject,
                    $body,
                    $variables['action_url'] ?? null
                ]);
            }
            
            // Queue email
            if ($sendEmail && !empty($user['email'])) {
                $this->queueEmail($user['email'], $user['username'], $subject, $body);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Notification send error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send bulk notifications to multiple users
     */
    public function sendBulk($type, $userIds, $variables = [], $sendEmail = true, $sendInApp = true) {
        $success = 0;
        foreach ($userIds as $userId) {
            if ($this->send($type, $userId, $variables, $sendEmail, $sendInApp)) {
                $success++;
            }
        }
        return $success;
    }
    
    /**
     * Send notification to all users with specific role
     */
    public function sendToRole($type, $role, $variables = [], $sendEmail = true, $sendInApp = true) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = ? AND status = 'active'");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $this->sendBulk($type, $users, $variables, $sendEmail, $sendInApp);
    }
    
    /**
     * Send notification to all active users
     */
    public function sendToAll($type, $variables = [], $sendEmail = true, $sendInApp = true) {
        $stmt = $this->db->query("SELECT id FROM users WHERE status = 'active'");
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return $this->sendBulk($type, $users, $variables, $sendEmail, $sendInApp);
    }
    
    /**
     * Replace template variables with actual values
     */
    private function replaceVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
    
    /**
     * Queue email for sending
     */
    private function queueEmail($email, $name, $subject, $body) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_queue (recipient_email, recipient_name, subject, body, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            return $stmt->execute([$email, $name, $subject, $body]);
        } catch (Exception $e) {
            error_log("Email queue error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's unread notification count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND read_at IS NULL
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE notifications 
            SET read_at = NOW() 
            WHERE user_id = ? AND read_at IS NULL
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Get user's notifications
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Delete old notifications (cleanup)
     */
    public function cleanupOldNotifications($daysOld = 90) {
        $stmt = $this->db->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            AND read_at IS NOT NULL
        ");
        return $stmt->execute([$daysOld]);
    }
}

// Helper functions for easy access
if (!function_exists('sendNotification')) {
    function sendNotification($type, $userId, $variables = [], $sendEmail = true, $sendInApp = true) {
        $service = new NotificationService();
        return $service->send($type, $userId, $variables, $sendEmail, $sendInApp);
    }
}

if (!function_exists('sendNotificationToRole')) {
    function sendNotificationToRole($type, $role, $variables = [], $sendEmail = true, $sendInApp = true) {
        $service = new NotificationService();
        return $service->sendToRole($type, $role, $variables, $sendEmail, $sendInApp);
    }
}

if (!function_exists('sendNotificationToAll')) {
    function sendNotificationToAll($type, $variables = [], $sendEmail = true, $sendInApp = true) {
        $service = new NotificationService();
        return $service->sendToAll($type, $variables, $sendEmail, $sendInApp);
    }
}
