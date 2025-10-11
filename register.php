<?php
/**
 * User Registration Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token first
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $formData = [
            'username' => sanitizeInput($_POST['username'] ?? ''),
            'email' => strtolower(trim(sanitizeInput($_POST['email'] ?? ''))),
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Validation
        $errors = [];
        
        if (empty($formData['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($formData['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        }
        
        if (empty($formData['email'])) {
            $errors[] = 'Email is required';
        } elseif (!validateEmail($formData['email'])) {
            $errors[] = 'Please enter a valid email address';
        }
        
        if (empty($formData['first_name'])) {
            $errors[] = 'First name is required';
        }
        
        if (empty($formData['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        if (empty($formData['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($formData['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters long';
        }
        
        if ($formData['password'] !== $formData['confirm_password']) {
            $errors[] = 'Passwords do not match';
        }
        
        // Check if username/email already exists
        if (empty($errors)) {
            $user = new User();
            
            if ($user->findByUsername($formData['username'])) {
                $errors[] = 'Username already exists';
            }
            
            if ($user->findByEmail($formData['email'])) {
                $errors[] = 'Email already exists';
            }
        }
        
        if (empty($errors)) {
            try {
                $user = new User();
                $userId = $user->register($formData);
                
                if ($userId) {
                    Logger::info("New user registered: {$formData['email']}");
                    // Show success message with resend link
                    $registeredEmail = htmlspecialchars($formData['email']);
                    $success = 'Registration successful! Please check your email for a verification link. <br><br>Didn\'t receive the email? <a href="/resend-verification.php?email=' . urlencode($formData['email']) . '">Resend verification email</a>';
                } else {
                    $error = 'Failed to create account or send verification email. Please try again.';
                }
            } catch (Exception $e) {
                Logger::error("Registration error: " . $e->getMessage());
                $error = 'An error occurred during registration. Please try again.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    } // End of CSRF token verification else block
}

$page_title = 'Register';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-8">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title text-center">Create Your Account</h1>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="validate-form">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" id="first_name" name="first_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" id="last_name" name="last_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" required
                                   value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" id="password" name="password" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Confirm Password *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" required>
                                I agree to the <a href="/terms.php" target="_blank">Terms of Service</a> and 
                                <a href="/privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-lg" style="width: 100%; margin-bottom: 1rem;">
                            Create Account
                        </button>
                    </form>
                    
                    <div style="text-align: center; margin: 1.5rem 0; position: relative;">
                        <hr style="border: none; border-top: 1px solid #e5e5e5;">
                        <span style="position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: white; padding: 0 15px; color: #767676; font-size: 14px;">OR</span>
                    </div>
                    
                    <a href="/auth/google-callback.php" class="btn btn-lg" style="width: 100%; margin-bottom: 1rem; background: #fff; color: #3c4043; border: 1px solid #dadce0; display: flex; align-items: center; justify-content: center; gap: 12px; font-weight: 500;">
                        <svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                            <path fill="none" d="M0 0h48v48H0z"/>
                        </svg>
                        Sign up with Google
                    </a>
                    
                    <div class="text-center">
                        <p>Already have an account? <a href="/login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>