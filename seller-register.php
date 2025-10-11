<?php
/**
 * Seller Registration Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

// Defensive alias for current user to avoid array offset on false/null
$cu = is_array($current_user ?? null) ? $current_user : [];

$error = '';
$success = '';
$step = $_GET['step'] ?? '1';

// If user is already logged in and is a vendor, redirect to seller center
if (Session::isLoggedIn()) {
    $vendor = new Vendor();
    $existingVendor = $vendor->findByUserId(Session::getUserId());
    if ($existingVendor) {
        redirect('/seller-center.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid request. Please try again.';
    } else {
        if ($step === '1') {
            // Basic account creation or login requirement
            if (!Session::isLoggedIn()) {
                // User needs to create account first
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $firstName = sanitizeInput($_POST['first_name'] ?? '');
                $lastName = sanitizeInput($_POST['last_name'] ?? '');
                $businessType = sanitizeInput($_POST['business_type'] ?? 'individual');
                
                if (empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
                    $error = 'Please fill in all required fields';
                } elseif (!validateEmail($email)) {
                    $error = 'Please enter a valid email address';
                } elseif (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters long';
                } else {
                    $user = new User();
                    
                    // Check if email already exists
                    if ($user->findByEmail($email)) {
                        $error = 'An account with this email already exists. <a href="/login.php">Sign in</a> to continue.';
                    } else {
                        // Create user account
                        $userData = [
                            'email' => $email,
                            'password' => $password,
                            'first_name' => $firstName,
                            'last_name' => $lastName,
                            'username' => $email, // Use email as username for now
                            // Note: User::register sets default role internally; session role is set below
                        ];
                        
                        $userId = $user->register($userData);
                        if ($userId) {
                            // Create session
                            createSecureSession($userId);
                            // Ensure user_id is set in session for subsequent vendor insert (FK requires it)
                            Session::set('user_id', $userId);
                            Session::set('user_role', 'vendor');
                            Session::set('user_email', $email);
                            Session::set('seller_registration_type', $businessType);
                            
                            // Send welcome email
                            $newUser = $user->find($userId);
                            sendWelcomeEmail($newUser);
                            
                            // Redirect to step 2
                            redirect(sellerUrl('register?step=2'));
                        } else {
                            $error = 'Failed to create account. Please try again.';
                        }
                    }
                }
            } else {
                // User is logged in, proceed to step 2
                Session::set('seller_registration_type', sanitizeInput($_POST['business_type'] ?? 'individual'));
                redirect(sellerUrl('register?step=2'));
            }
        } elseif ($step === '2') {
            // Business profile setup
            Session::requireLogin();
            
            $businessName = sanitizeInput($_POST['business_name'] ?? '');
            $businessDescription = sanitizeInput($_POST['business_description'] ?? '');
            $businessAddress = sanitizeInput($_POST['business_address'] ?? '');
            $taxId = sanitizeInput($_POST['tax_id'] ?? '');
            $businessType = Session::get('seller_registration_type', 'individual');
            
            if (empty($businessName)) {
                $error = 'Business name is required';
            } else {
                // Validate that the session user exists before inserting vendor (to satisfy FK)
                $userId = Session::getUserId();
                $userModel = new User();
                $userRow = $userId ? $userModel->find($userId) : null;

                if (!$userId || !$userRow) {
                    $error = 'Your session is invalid or expired. Please sign in again to continue.';
                } else {
                    $vendor = new Vendor();
                    $vendorData = [
                        'user_id' => $userId,
                        'business_name' => $businessName,
                        'business_description' => $businessDescription,
                        'business_address' => $businessAddress,
                        'tax_id' => $taxId,
                        'status' => 'pending'
                    ];
                    
                    if ($vendor->create($vendorData)) {
                        // Send seller registration notification to admin (optional)
                        redirect(sellerUrl('onboarding'));
                    } else {
                        $error = 'Failed to create seller profile. Please try again.';
                    }
                }
            }
        }
    }
}

$page_title = 'Become a Seller - FezaMarket';
includeHeader($page_title);
?>

<div class="container">
    <div class="seller-registration">
        <!-- Progress Steps -->
        <div class="registration-progress">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">Account</div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">Business Profile</div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">Verification</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($step === '1'): ?>
            <!-- Step 1: Account Setup -->
            <div class="registration-step">
                <h1>Start selling on FezaMarket</h1>
                <p class="step-description">Create your seller account or sign in to begin</p>

                <?php if (!Session::isLoggedIn()): ?>
                    <div class="registration-form">
                        <form method="POST" class="seller-form">
                            <?php echo csrfTokenInput(); ?>
                            
                            <div class="form-section">
                                <h3>Account Information</h3>
                                <div class="form-group">
                                    <label for="email">Email Address *</label>
                                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="password">Password *</label>
                                    <input type="password" id="password" name="password" required minlength="8">
                                    <div class="form-help">At least 8 characters</div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_name">First Name *</label>
                                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h3>Business Type</h3>
                                <div class="business-type-options">
                                    <label class="business-type-option">
                                        <input type="radio" name="business_type" value="individual" checked>
                                        <div class="option-content">
                                            <h4>Individual Seller</h4>
                                            <p>Sell items occasionally or as a hobby</p>
                                        </div>
                                    </label>
                                    <label class="business-type-option">
                                        <input type="radio" name="business_type" value="business">
                                        <div class="option-content">
                                            <h4>Business Seller</h4>
                                            <p>Sell regularly with a registered business</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-large">Continue</button>
                            </div>
                        </form>
                    </div>

                    <div class="existing-account">
                        <p>Already have an account? <a href="/login.php?redirect=<?php echo urlencode(sellerUrl('register')); ?>">Sign in</a></p>
                    </div>
                <?php else: ?>
                    <div class="logged-in-continue">
                        <p>Welcome back, <?php echo htmlspecialchars($cu['first_name'] ?? 'User'); ?>!</p>
                        <form method="POST" class="seller-form">
                            <?php echo csrfTokenInput(); ?>
                            
                            <div class="form-section">
                                <h3>Choose Your Business Type</h3>
                                <div class="business-type-options">
                                    <label class="business-type-option">
                                        <input type="radio" name="business_type" value="individual" checked>
                                        <div class="option-content">
                                            <h4>Individual Seller</h4>
                                            <p>Sell items occasionally or as a hobby</p>
                                        </div>
                                    </label>
                                    <label class="business-type-option">
                                        <input type="radio" name="business_type" value="business">
                                        <div class="option-content">
                                            <h4>Business Seller</h4>
                                            <p>Sell regularly with a registered business</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-large">Continue</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($step === '2'): ?>
            <!-- Step 2: Business Profile -->
            <div class="registration-step">
                <h1>Set up your business profile</h1>
                <p class="step-description">Tell us about your business</p>

                <form method="POST" class="seller-form">
                    <?php echo csrfTokenInput(); ?>
                    
                    <div class="form-section">
                        <h3>Business Information</h3>
                        <div class="form-group">
                            <label for="business_name">Business Name *</label>
                            <input type="text" id="business_name" name="business_name" required value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>">
                            <div class="form-help">This will be displayed to customers</div>
                        </div>
                        <div class="form-group">
                            <label for="business_description">Business Description</label>
                            <textarea id="business_description" name="business_description" rows="4"><?php echo htmlspecialchars($_POST['business_description'] ?? ''); ?></textarea>
                            <div class="form-help">Describe what you sell and what makes your business unique</div>
                        </div>
                        <div class="form-group">
                            <label for="business_address">Business Address</label>
                            <textarea id="business_address" name="business_address" rows="3"><?php echo htmlspecialchars($_POST['business_address'] ?? ''); ?></textarea>
                        </div>
                        <?php if (Session::get('seller_registration_type') === 'business'): ?>
                        <div class="form-group">
                            <label for="tax_id">Tax ID / EIN (Optional)</label>
                            <input type="text" id="tax_id" name="tax_id" value="<?php echo htmlspecialchars($_POST['tax_id'] ?? ''); ?>">
                            <div class="form-help">Required for business sellers in some locations</div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <a href="<?php echo sellerUrl('register?step=1'); ?>" class="btn btn-outline">Back</a>
                        <button type="submit" class="btn btn-primary btn-large">Continue to Verification</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.seller-registration {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
}
.registration-progress { display: flex; justify-content: center; margin-bottom: 3rem; position: relative; }
.registration-progress::before { content: ''; position: absolute; top: 20px; left: 20%; right: 20%; height: 2px; background: #e5e5e5; z-index: 1; }
.step { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; z-index: 2; position: relative; }
.step-number { width: 40px; height: 40px; border-radius: 50%; background: #e5e5e5; color: #666; display: flex; align-items: center; justify-content: center; font-weight: bold; }
.step.active .step-number, .step.completed .step-number { background: #0064d2; color: white; }
.step-label { font-size: 0.9rem; color: #666; }
.step.active .step-label, .step.completed .step-label { color: #333; font-weight: 500; }
.registration-step h1 { text-align: center; margin-bottom: 0.5rem; }
.step-description { text-align: center; color: #666; margin-bottom: 2rem; }
.seller-form { background: #f8f9fa; padding: 2rem; border-radius: 8px; border: 1px solid #e5e5e5; }
.form-section { margin-bottom: 2rem; }
.form-section h3 { margin-bottom: 1rem; color: #333; }
.form-group { margin-bottom: 1rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
.form-group input, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; }
.form-help { font-size: 0.85rem; color: #666; margin-top: 0.25rem; }
.business-type-options { display: grid; gap: 1rem; }
.business-type-option { display: flex; align-items: flex-start; gap: 1rem; padding: 1rem; border: 2px solid #e5e5e5; border-radius: 8px; cursor: pointer; transition: border-color 0.2s; }
.business-type-option:hover { border-color: #0064d2; }
.business-type-option input[type="radio"] { margin-top: 0.25rem; }
.business-type-option input[type="radio"]:checked { accent-color: #0064d2; }
.option-content h4 { margin: 0 0 0.25rem 0; }
.option-content p { margin: 0; color: #666; font-size: 0.9rem; }
.form-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; }
.btn { padding: 0.75rem 2rem; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; transition: background-color 0.2s; }
.btn-primary { background: #0064d2; color: white; }
.btn-primary:hover { background: #0056b3; }
.btn-outline { background: white; color: #0064d2; border: 1px solid #0064d2; }
.btn-outline:hover { background: #0064d2; color: white; }
.btn-large { padding: 1rem 2.5rem; font-size: 1.1rem; }
.existing-account { text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e5e5; }
.alert { padding: 1rem; border-radius: 4px; margin-bottom: 1rem; }
.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<?php includeFooter(); ?>