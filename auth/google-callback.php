<?php
/**
 * Google OAuth Callback Handler
 * Handles the OAuth 2.0 authentication flow with Google
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../vendor/autoload.php';

use League\OAuth2\Client\Provider\Google;

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect('/');
}

// Get configuration from environment
$clientId = env('GOOGLE_CLIENT_ID');
$clientSecret = env('GOOGLE_CLIENT_SECRET');
$redirectUri = env('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');

// Validate configuration
if (empty($clientId) || empty($clientSecret)) {
    Session::setFlash('error', 'Google login is not properly configured. Please contact support.');
    redirect('/login.php');
}

// Initialize the Google provider
$provider = new Google([
    'clientId'     => $clientId,
    'clientSecret' => $clientSecret,
    'redirectUri'  => $redirectUri,
]);

try {
    // Handle OAuth callback
    if (!isset($_GET['code'])) {
        // No authorization code present, redirect to Google
        $authorizationUrl = $provider->getAuthorizationUrl([
            'scope' => ['email', 'profile'],
        ]);
        
        // Store state for CSRF protection
        Session::set('oauth2_state', $provider->getState());
        
        header('Location: ' . $authorizationUrl);
        exit;
    }
    
    // Verify state to protect against CSRF
    $state = $_GET['state'] ?? '';
    $storedState = Session::get('oauth2_state');
    
    if (empty($state) || ($state !== $storedState)) {
        Session::remove('oauth2_state');
        throw new Exception('Invalid state parameter');
    }
    
    Session::remove('oauth2_state');
    
    // Get access token
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    
    // Get user details from Google
    $googleUser = $provider->getResourceOwner($token);
    $googleData = $googleUser->toArray();
    
    // Extract user information
    $email = $googleData['email'] ?? null;
    $googleId = $googleData['sub'] ?? $googleData['id'] ?? null;
    $firstName = $googleData['given_name'] ?? '';
    $lastName = $googleData['family_name'] ?? '';
    $avatar = $googleData['picture'] ?? null;
    $emailVerified = $googleData['email_verified'] ?? false;
    
    if (empty($email) || empty($googleId)) {
        throw new Exception('Unable to retrieve user information from Google');
    }
    
    // Check if user exists by email or OAuth ID
    $user = new User();
    $existingUser = $user->findByEmail($email);
    
    if (!$existingUser) {
        // Check if user exists by OAuth provider ID
        $stmt = db()->prepare("
            SELECT * FROM users 
            WHERE oauth_provider = 'google' AND oauth_provider_id = ?
        ");
        $stmt->execute([$googleId]);
        $existingUser = $stmt->fetch();
    }
    
    if ($existingUser) {
        // User exists - update OAuth info and log them in
        $stmt = db()->prepare("
            UPDATE users 
            SET oauth_provider = 'google',
                oauth_provider_id = ?,
                last_login_at = NOW(),
                last_login_ip = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $googleId,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $existingUser['id']
        ]);
        
        $userId = $existingUser['id'];
        Logger::info("User logged in via Google OAuth: {$email}");
    } else {
        // Create new user account
        // Generate a unique username from email
        $baseUsername = explode('@', $email)[0];
        $username = $baseUsername;
        $counter = 1;
        
        while ($user->findByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Create user with OAuth data
        $userData = [
            'username' => $username,
            'email' => $email,
            'pass_hash' => null, // No password for OAuth users
            'first_name' => $firstName,
            'last_name' => $lastName,
            'role' => 'customer',
            'status' => 'active', // OAuth users are pre-verified
            'is_verified' => $emailVerified ? 1 : 0,
            'verified_at' => $emailVerified ? date('Y-m-d H:i:s') : null,
            'email_verified_at' => $emailVerified ? date('Y-m-d H:i:s') : null,
            'avatar' => $avatar,
            'oauth_provider' => 'google',
            'oauth_provider_id' => $googleId,
            'created_at' => date('Y-m-d H:i:s'),
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        ];
        
        $userId = $user->create($userData);
        
        if (!$userId) {
            throw new Exception('Failed to create user account');
        }
        
        Logger::info("New user registered via Google OAuth: {$email}");
    }
    
    // Create secure session
    createSecureSession($userId);
    
    // Get updated user data
    $userData = $user->find($userId);
    
    // Set additional session data
    Session::set('user_role', $userData['role']);
    Session::set('user_email', $userData['email']);
    
    // Log security event
    logSecurityEvent($userId, 'oauth_login_success', 'user', $userId, [
        'provider' => 'google',
        'email' => $email
    ]);
    
    // Set success message
    Session::setFlash('success', 'Successfully logged in with Google!');
    
    // Redirect to intended page or dashboard
    $redirect = Session::getIntendedUrl();
    
    if ($redirect === '/' || empty($redirect)) {
        $redirect = getDashboardUrl($userData['role']);
    }
    
    redirect($redirect);
    
} catch (Exception $e) {
    // Log error
    Logger::error("Google OAuth error: " . $e->getMessage());
    
    // Show user-friendly error message
    Session::setFlash('error', 'Unable to login with Google. Please try again or use email/password login.');
    redirect('/login.php');
}
