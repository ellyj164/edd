<?php
/**
 * Update Preferences API
 * Handle user preference updates
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }
    
    $db = db();
    $userId = Session::getUserId();
    
    // Extract preferences
    $language = $input['language'] ?? 'en';
    $currency = $input['currency'] ?? 'USD';
    $timezone = $input['timezone'] ?? 'UTC';
    $marketingOptIn = isset($input['marketing_opt_in']) && $input['marketing_opt_in'] ? 1 : 0;
    $emailNotifications = isset($input['email_notifications']) && $input['email_notifications'] ? 1 : 0;
    $smsNotifications = isset($input['sms_notifications']) && $input['sms_notifications'] ? 1 : 0;
    $pushNotifications = isset($input['push_notifications']) && $input['push_notifications'] ? 1 : 0;
    
    // Check if preferences exist
    $checkStmt = $db->prepare("SELECT id FROM user_preferences WHERE user_id = ?");
    $checkStmt->execute([$userId]);
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        // Update existing preferences
        $updateStmt = $db->prepare("
            UPDATE user_preferences 
            SET language = ?, currency = ?, timezone = ?, marketing_opt_in = ?,
                email_notifications = ?, sms_notifications = ?, push_notifications = ?,
                updated_at = NOW()
            WHERE user_id = ?
        ");
        $updateStmt->execute([
            $language, $currency, $timezone, $marketingOptIn,
            $emailNotifications, $smsNotifications, $pushNotifications, $userId
        ]);
    } else {
        // Create new preferences
        $insertStmt = $db->prepare("
            INSERT INTO user_preferences (
                user_id, language, currency, timezone, marketing_opt_in,
                email_notifications, sms_notifications, push_notifications, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $insertStmt->execute([
            $userId, $language, $currency, $timezone, $marketingOptIn,
            $emailNotifications, $smsNotifications, $pushNotifications
        ]);
    }
    
    logSecurityEvent($userId, 'preferences_updated', 'user', $userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Preferences updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
