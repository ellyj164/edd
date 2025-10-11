<?php
/**
 * Update User Profile API
 * Handle profile updates for Overview section
 */

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json');

// Require login
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Verify CSRF token
    if (!verifyCsrfToken($input['csrf_token'] ?? '')) {
        throw new Exception('Invalid CSRF token');
    }
    
    $db = db();
    $userId = Session::getUserId();
    
    // Validate and sanitize inputs
    $firstName = trim($input['first_name'] ?? '');
    $lastName = trim($input['last_name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $gender = trim($input['gender'] ?? '');
    $dateOfBirth = trim($input['date_of_birth'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email)) {
        throw new Exception('First name, last name, and email are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if email is already taken by another user
    $emailCheckStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheckStmt->execute([$email, $userId]);
    if ($emailCheckStmt->fetch()) {
        throw new Exception('Email is already in use by another account');
    }
    
    // Update user record
    $updateStmt = $db->prepare("
        UPDATE users 
        SET first_name = ?, last_name = ?, email = ?, phone = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $updateStmt->execute([$firstName, $lastName, $email, $phone, $userId]);
    
    // Update or create user profile with additional fields
    $profileCheckStmt = $db->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
    $profileCheckStmt->execute([$userId]);
    $profile = $profileCheckStmt->fetch();
    
    if ($profile) {
        // Update existing profile
        $updateProfileStmt = $db->prepare("
            UPDATE user_profiles 
            SET gender = ?, date_of_birth = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $updateProfileStmt->execute([$gender ?: null, $dateOfBirth ?: null, $userId]);
    } else {
        // Create new profile if table exists
        try {
            $createProfileStmt = $db->prepare("
                INSERT INTO user_profiles (user_id, gender, date_of_birth, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $createProfileStmt->execute([$userId, $gender ?: null, $dateOfBirth ?: null]);
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }
    
    // Log the action
    logSecurityEvent($userId, 'profile_updated', 'user', $userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
