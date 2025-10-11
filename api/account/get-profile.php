<?php
/**
 * Get User Profile API
 * Return current user profile data
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Require login
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = db();
    $userId = Session::getUserId();
    
    // Get user basic info
    $userStmt = $db->prepare("SELECT id, first_name, last_name, email, phone, created_at FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get additional profile info if exists
    $profileStmt = $db->prepare("SELECT gender, date_of_birth FROM user_profiles WHERE user_id = ?");
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();
    
    $data = [
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'gender' => $profile ? $profile['gender'] : '',
        'date_of_birth' => $profile ? $profile['date_of_birth'] : '',
        'member_since' => $user['created_at']
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
