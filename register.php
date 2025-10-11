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
                    
                    <div class="text-center">
                        <p>Already have an account? <a href="/login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>