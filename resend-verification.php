<?php
/**
 * Resend Email Verification Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$error = '';
$success = '';
$email_param = $_GET['email'] ?? ''; // Get email from URL parameter

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(sanitizeInput($_POST['email'] ?? '')));
    
    if (empty($email)) {
        $error = 'Email address is required.';
    } elseif (!validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $user = new User();
            $userData = $user->findByEmail($email);
            
            if (!$userData) {
                // Don't reveal if email exists for security
                $success = 'If an account with that email exists and needs verification, we\'ve sent a new verification email.';
            } elseif ($userData['status'] === 'active' && $userData['verified_at']) {
                $success = 'Your email is already verified. You can log in to your account.';
            } else {
                // Generate new verification link token
                $db = Database::getInstance()->getConnection();
                $token = bin2hex(random_bytes(32));
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                
                // Invalidate any existing tokens for this user
                $stmt = $db->prepare("
                    UPDATE email_verifications 
                    SET verified_at = NOW() 
                    WHERE user_id = ? AND verified_at IS NULL
                ");
                $stmt->execute([$userData['id']]);
                
                // Create new verification token
                $stmt = $db->prepare("
                    INSERT INTO email_verifications (user_id, token, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$userData['id'], $token, $ip, $agent]);
                
                // Send verification email using enhanced email system
                try {
                    // Load enhanced email system if not already loaded
                    if (!isset($GLOBALS['emailSystem'])) {
                        require_once __DIR__ . '/includes/enhanced_email_system.php';
                    }
                    
                    $emailSent = sendVerificationEmail(
                        $email,
                        $userData['first_name'],
                        $token,
                        $userData['id']
                    );
                    
                    if ($emailSent) {
                        $success = 'A new verification link has been sent to your email address. Please check your email and click the link to verify your account.';
                        Logger::info("Verification link resent to: {$email}");
                    } else {
                        $error = 'Failed to send verification email. Please try again later or contact support.';
                        Logger::error("Failed to resend verification email to: {$email}");
                    }
                } catch (Exception $emailException) {
                    Logger::error("Exception sending verification email to {$email}: " . $emailException->getMessage());
                    $error = 'Failed to send verification email. Please try again later or contact support.';
                }
            }
            
        } catch (Exception $e) {
            Logger::error("Resend verification error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}

$page_title = 'Resend Verification Email';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-6">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title text-center">Resend Verification Email</h1>
                    
                    <p class="text-center text-muted mb-4">
                        Enter your email address and we'll send you a new verification link if your account needs verification.
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        
                        <div class="text-center mt-4">
                            <a href="/login.php" class="btn btn-primary">Go to Login</a>
                            <a href="/" class="btn btn-outline">Continue Browsing</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="resend-form">
                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       class="form-control" 
                                       placeholder="Enter your email address"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? $email_param); ?>"
                                       required>
                                <small class="form-text">We'll send a verification email to this address</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                                Send Verification Email
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                Remember your login details? <a href="/login.php">Sign in here</a><br>
                                Don't have an account? <a href="/register.php">Create one</a>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tips Section -->
            <div class="card mt-4">
                <div class="card-body">
                    <h2>Verification Tips</h2>
                    <ul class="tips-list">
                        <li><strong>Check Spam Folder:</strong> Verification emails sometimes end up in spam or junk folders</li>
                        <li><strong>Wait a Few Minutes:</strong> Email delivery can take 5-10 minutes during busy periods</li>
                        <li><strong>One-Time Links:</strong> Each verification link can only be used once for security</li>
                        <li><strong>24-Hour Expiry:</strong> Verification links expire after 24 hours</li>
                        <li><strong>Contact Support:</strong> If you continue having issues, our support team can help</li>
                    </ul>
                    
                    <div class="text-center mt-3">
                        <a href="/contact.php" class="btn btn-outline">Contact Support</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.resend-form {
    margin: 2rem 0;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s, box-shadow 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-text {
    display: block;
    margin-top: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}

.alert-success {
    background: #d1fae5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-body {
    padding: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-block;
    text-align: center;
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-outline {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.125rem;
}

.tips-list {
    list-style: none;
    padding: 0;
}

.tips-list li {
    padding: 1rem 0;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.tips-list li:last-child {
    border-bottom: none;
}

.tips-list li:before {
    content: "💡";
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.text-center {
    text-align: center;
}

.text-muted {
    color: #6b7280;
}

.text-muted a {
    color: #3b82f6;
    text-decoration: none;
}

.text-muted a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .col-6 {
        width: 100%;
        padding: 0 1rem;
    }
    
    .card-body {
        padding: 1.5rem;
    }
}
</style>

<?php includeFooter(); ?>