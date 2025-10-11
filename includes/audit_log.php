<?php
/**
 * Audit Log Include - Required in all admin pages
 * Comprehensive audit logging for admin actions and security events
 */

/**
 * Log security events and admin actions
 */
if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($userId, $event, $category, $targetId = null, $details = []) {
    try {
        // Ensure audit_logs table exists
        ensureAuditLogTable();
        
        $data = [
            'user_id' => $userId,
            'event' => $event,
            'category' => $category,
            'target_id' => $targetId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'details' => json_encode($details),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db = db();
        $stmt = $db->prepare(
            "INSERT INTO audit_logs (user_id, event, category, target_id, ip_address, user_agent, url, method, details, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute(array_values($data));
        
        // Send security alerts for critical events
        $criticalEvents = [
            'login_failed_multiple',
            'permission_denied',
            'unauthorized_access',
            'data_breach_attempt',
            'admin_privilege_escalation',
            'bulk_delete',
            'password_reset_admin'
        ];
        
        if (in_array($event, $criticalEvents)) {
            sendSecurityAlert($event, array_merge($details, [
                'user_id' => $userId,
                'target_id' => $targetId,
                'ip_address' => $data['ip_address']
            ]));
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
        return false;
    }
}
}

/**
 * Log audit events for admin actions
 */
if (!function_exists('logAuditEvent')) {
    function logAuditEvent($targetType, $targetId, $action, $details = []) {
        try {
            $userId = getCurrentUserId() ?: 1; // Fallback to user 1 for admin bypass
            
            logAdminAction($action, $targetType, $targetId, null, $details, '');
            
            return true;
        } catch (Exception $e) {
            error_log("Failed to log audit event: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Log admin actions with detailed information
 */
if (!function_exists('logAdminAction')) {
    function logAdminAction($action, $targetType, $targetId, $oldData = null, $newData = null, $notes = '') {
    try {
        $userId = getCurrentUserId();
        $details = [
            'action' => $action,
            'target_type' => $targetType,
            'notes' => $notes
        ];
        
        if ($oldData !== null) {
            $details['old_data'] = $oldData;
        }
        
        if ($newData !== null) {
            $details['new_data'] = $newData;
        }
        
        // Log the action
        logSecurityEvent($userId, $action, 'admin_action', $targetId, $details);
        
        // Also log to separate admin actions table if it exists
        try {
            $db = db();
            $stmt = $db->prepare(
                "INSERT INTO admin_actions (user_id, action, target_type, target_id, old_data, new_data, notes, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                    $userId,
                    $action,
                    $targetType,
                    $targetId,
                    $oldData ? json_encode($oldData) : null,
                    $newData ? json_encode($newData) : null,
                    $notes
                ]
            );
        } catch (Exception $e) {
            // Table might not exist, that's okay
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
        return false;
    }
}
}

/**
 * Log user actions from admin panel
 */
function logUserAction($action, $userId, $details = []) {
    try {
        logSecurityEvent($userId, $action, 'user_action', null, $details);
        
        // Notify admin for important user actions
        $notifiableActions = [
            'account_created',
            'account_suspended',
            'password_changed_admin',
            'role_changed',
            'large_order_placed',
            'refund_requested'
        ];
        
        if (in_array($action, $notifiableActions)) {
            notifyAdminOfUserAction($action, $userId, $details);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log user action: " . $e->getMessage());
        return false;
    }
}

/**
 * Log data access events
 */
function logDataAccess($dataType, $recordId, $accessType = 'view') {
    try {
        $userId = getCurrentUserId();
        $details = [
            'data_type' => $dataType,
            'record_id' => $recordId,
            'access_type' => $accessType
        ];
        
        logSecurityEvent($userId, 'data_access', 'data', $recordId, $details);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log data access: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit logs with filtering
 */
function getAuditLogs($filters = [], $page = 1, $limit = 50) {
    try {
        $whereConditions = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $whereConditions[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['event'])) {
            $whereConditions[] = "al.event = ?";
            $params[] = $filters['event'];
        }
        
        if (!empty($filters['category'])) {
            $whereConditions[] = "al.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereConditions[] = "al.created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereConditions[] = "al.created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['ip_address'])) {
            $whereConditions[] = "al.ip_address = ?";
            $params[] = $filters['ip_address'];
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        $offset = ($page - 1) * $limit;
        
        // Get logs with user information
        $sql = "
            SELECT al.*, u.username, u.email 
            FROM audit_logs al 
            LEFT JOIN users u ON al.user_id = u.id 
            {$whereClause} 
            ORDER BY al.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $db = db();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM audit_logs al {$whereClause}";
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        return [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    } catch (Exception $e) {
        error_log("Failed to get audit logs: " . $e->getMessage());
        return ['logs' => [], 'total' => 0, 'page' => 1, 'limit' => $limit, 'pages' => 0];
    }
}

/**
 * Get security dashboard data
 */
function getSecurityDashboard($days = 7) {
    try {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        // Failed login attempts
        $db = db();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM audit_logs 
             WHERE event LIKE '%login_failed%' AND created_at >= ?",
            [$startDate]
        )->fetchColumn();
        
        // Permission denials
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM audit_logs 
             WHERE event = 'permission_denied' AND created_at >= ?"
        );
        $stmt->execute([$startDate]);
        $permissionDenials = $stmt->fetchColumn();
        
        // Unique IPs
        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT ip_address) FROM audit_logs 
             WHERE created_at >= ? AND ip_address IS NOT NULL"
        );
        $stmt->execute([$startDate]);
        $uniqueIps = $stmt->fetchColumn();
        
        // Admin actions
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM audit_logs 
             WHERE category = 'admin_action' AND created_at >= ?"
        );
        $stmt->execute([$startDate]);
        $adminActions = $stmt->fetchColumn();
        
        // Recent security events
        $stmt = $db->prepare(
            "SELECT al.*, u.username 
             FROM audit_logs al 
             LEFT JOIN users u ON al.user_id = u.id 
             WHERE al.category IN ('security', 'admin_action') 
             AND al.created_at >= ? 
             ORDER BY al.created_at DESC 
             LIMIT 10"
        );
        $stmt->execute([$startDate]);
        $recentEvents = $stmt->fetchAll();
        
        return [
            'failed_logins' => $failedLogins,
            'permission_denials' => $permissionDenials,
            'unique_ips' => $uniqueIps,
            'admin_actions' => $adminActions,
            'recent_events' => $recentEvents
        ];
    } catch (Exception $e) {
        error_log("Failed to get security dashboard: " . $e->getMessage());
        return [
            'failed_logins' => 0,
            'permission_denials' => 0,
            'unique_ips' => 0,
            'admin_actions' => 0,
            'recent_events' => []
        ];
    }
}

/**
 * Ensure audit log table exists
 */
function ensureAuditLogTable() {
    try {
        $db = db();
        $stmt = $db->prepare("SHOW TABLES LIKE 'audit_logs'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            $db->query("
                CREATE TABLE audit_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    event VARCHAR(255) NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    target_id INT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    url VARCHAR(500),
                    method VARCHAR(10),
                    details JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_event (event),
                    INDEX idx_category (category),
                    INDEX idx_created_at (created_at),
                    INDEX idx_ip_address (ip_address)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
    } catch (Exception $e) {
        error_log("Failed to ensure audit log table: " . $e->getMessage());
    }
}

/**
 * Clean old audit logs (data retention)
 */
function cleanOldAuditLogs($retentionDays = 365) {
    try {
        $cutoffDate = date('Y-m-d', strtotime("-{$retentionDays} days"));
        
        $db = db();
        $stmt = $db->prepare("DELETE FROM audit_logs WHERE created_at < ?");
        $stmt->execute([$cutoffDate]);
        $deleted = $stmt->rowCount();
        
        if ($deleted) {
            logSecurityEvent(
                getCurrentUserId(), 
                'audit_log_cleanup', 
                'system', 
                null, 
                ['retention_days' => $retentionDays, 'cutoff_date' => $cutoffDate]
            );
        }
        
        return $deleted;
    } catch (Exception $e) {
        error_log("Failed to clean old audit logs: " . $e->getMessage());
        return false;
    }
}

/**
 * Export audit logs for compliance
 */
function exportAuditLogs($filters = [], $format = 'csv') {
    try {
        $logs = getAuditLogs($filters, 1, 10000); // Get up to 10k records
        
        if ($format === 'csv') {
            $output = fopen('php://temp', 'r+');
            
            // CSV headers
            fputcsv($output, [
                'ID', 'User ID', 'Username', 'Event', 'Category', 'Target ID',
                'IP Address', 'User Agent', 'URL', 'Method', 'Details', 'Created At'
            ]);
            
            // CSV data
            foreach ($logs['logs'] as $log) {
                fputcsv($output, [
                    $log['id'],
                    $log['user_id'],
                    $log['username'],
                    $log['event'],
                    $log['category'],
                    $log['target_id'],
                    $log['ip_address'],
                    $log['user_agent'],
                    $log['url'],
                    $log['method'],
                    $log['details'],
                    $log['created_at']
                ]);
            }
            
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);
            
            return $csvContent;
        }
        
        return json_encode($logs['logs'], JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        error_log("Failed to export audit logs: " . $e->getMessage());
        return false;
    }
}

// Initialize audit log table on include
ensureAuditLogTable();