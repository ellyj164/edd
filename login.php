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