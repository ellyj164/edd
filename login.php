<?php
/**
 * User Login Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address';
        } else {
            $user = new User();
            $result = $user->authenticate($email, $password);
            
            if (isset($result['error'])) {
                $error = $result['error'];
            } elseif ($result) {
                // Create secure session
                createSecureSession($result['id']);
                
                // Set additional session data
                Session::set('user_role', $result['role']);
                Session::set('user_email', $result['email']);
                
                // Log activity
                Logger::info("User logged in: {$email}");
                
                // Send security alert for new device/location
                if (function_exists('checkAndSendLoginAlert')) {
                    checkAndSendLoginAlert($result['id'], $result);
                }
                
                // Check if there's an intended action to execute
                $intendedAction = Session::get('intended_action');
                $redirectToCart = false;
                if ($intendedAction && isset($intendedAction['action'])) {
                    // Execute the stored action
                    if ($intendedAction['action'] === 'add_to_cart' && isset($intendedAction['product_id'])) {
                        // Add product to cart
                        try {
                            $cart = new Cart();
                            $cart->addItem(
                                $result['id'],
                                $intendedAction['product_id'],
                                $intendedAction['quantity'] ?? 1
                            );
                            Session::setFlash('success', 'Product added to cart successfully!');
                            $redirectToCart = true;
                        } catch (Exception $e) {
                            Session::setFlash('error', 'Could not add product to cart.');
                        }
                    }
                    // Remove the intended action
                    Session::remove('intended_action');
                }
                
                // Redirect to cart if add_to_cart action was executed, otherwise to intended page or dashboard
                if ($redirectToCart) {
                    redirect('/cart.php');
                }
                
                $redirect = Session::getIntendedUrl();
                
                // If no intended URL or it's the root, redirect to role-based dashboard
                if ($redirect === '/' || empty($redirect)) {
                    $redirect = getDashboardUrl($result['role']);
                }
                
                redirect($redirect);
            } else {
                $error = 'Login failed. Please try again.';
            }
        }
    }
}

$page_title = 'Login';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-6">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title text-center">Login to Your Account</h1>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="validate-form">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                Remember me
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-lg" style="width: 100%; margin-bottom: 1rem;">
                            Login
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
                        Continue with Google
                    </a>
                    
                    <div class="text-center">
                        <p><a href="/forgot-password.php">Forgot your password?</a></p>
                        <p>Don't have an account? <a href="/register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>