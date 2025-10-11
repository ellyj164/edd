<?php
/**
 * Email Verification Page - Link Based
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$token = $_GET['token'] ?? '';
$errors = [];
$success_message = '';

// Redirect to register if no token provided
if (empty($token)) {
    redirect('/register.php');
}

// Get current request info for security check
$currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
$currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $db = Database::getInstance()->getConnection();
    
    // Fetch verification record
    $stmt = $db->prepare("
        SELECT * FROM email_verifications 
        WHERE token = ? 
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    
    if (!$row) {
        $errors[] = 'Invalid or expired verification link.';
    } elseif ($row['verified_at']) {
        // Already verified
        $success_message = 'This email has already been verified. You can now log in.';
    } else {
        // Check if link is expired (24 hours TTL)
        $createdTime = strtotime($row['created_at']);
        $currentTime = time();
        if (($currentTime - $createdTime) > 86400) {
            $errors[] = 'This verification link has expired. Please request a new verification email.';
        }
        // Security Check: IP and User Agent must match
        elseif ($row['ip_address'] !== $currentIp || $row['user_agent'] !== $currentAgent) {
            $errors[] = 'Verification must be done from the same device and network you used to register. For security reasons, this link can only be used from the original device.';
            Logger::warning("Verification attempt with mismatched IP/Agent. Token: {$token}, Expected IP: {$row['ip_address']}, Got: {$currentIp}");
        } else {
            // All checks passed - verify the account
            $db->beginTransaction();
            
            try {
                // Update user status
                $updateUser = $db->prepare("
                    UPDATE users 
                    SET is_verified = 1, status = 'active', verified_at = NOW(), email_verified_at = NOW() 
                    WHERE id = ?
                ");
                $updateUser->execute([$row['user_id']]);
                
                // Mark verification as completed
                $updateVerification = $db->prepare("
                    UPDATE email_verifications 
                    SET verified_at = NOW() 
                    WHERE id = ?
                ");
                $updateVerification->execute([$row['id']]);
                
                $db->commit();
                
                $success_message = 'You have successfully verified your account ✅';
                Logger::info("Email verified for user ID {$row['user_id']} via link token");
                
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to verify your email. Please try again.';
                Logger::error("Email verification error: " . $e->getMessage());
            }
        }
    }
    
} catch (Exception $e) {
    $errors[] = 'Database error. Please try again.';
    Logger::error("Email verification error: " . $e->getMessage());
}

$page_title = 'Verify Email';
includeHeader($page_title);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        :root { 
            --primary-color: #0052cc; 
            --primary-hover: #0041a3; 
            --secondary-color: #f4f7f6; 
            --text-color: #333; 
            --light-text-color: #777; 
            --border-color: #ddd; 
            --error-bg: #f8d7da; 
            --error-text: #721c24; 
            --success-bg: #d4edda; 
            --success-text: #155724; 
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            background-color: var(--secondary-color); 
        }
        main.auth-container { 
            flex-grow: 1; 
            display: flex; 
            width: 100%; 
        }
        .auth-panel { 
            flex: 1; 
            background: linear-gradient(135deg, #0052cc, #007bff); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            padding: 50px; 
            text-align: center; 
        }
        .auth-panel h2 { 
            font-size: 2rem; 
            margin-bottom: 15px; 
        }
        .auth-panel p { 
            font-size: 1.1rem; 
            line-height: 1.6; 
            max-width: 350px; 
        }
        .auth-form-section { 
            flex: 1; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 50px; 
            background: #fff; 
        }
        .form-box { 
            width: 100%; 
            max-width: 500px; 
            text-align: center; 
        }
        .form-box h1 { 
            color: var(--text-color); 
            margin-bottom: 10px; 
            font-size: 2.2rem; 
        }
        .form-box .form-subtitle { 
            color: var(--light-text-color); 
            margin-bottom: 30px; 
            font-size: 1.1rem;
        }
        .message-area { 
            margin-bottom: 20px; 
        }
        .message { 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center;
            font-size: 1.1rem;
        }
        .error-message { 
            color: var(--error-text); 
            background-color: var(--error-bg); 
            border: 1px solid #f5c6cb;
        }
        .success-message { 
            color: var(--success-text); 
            background-color: var(--success-bg); 
            border: 1px solid #c3e6cb;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .auth-button { 
            width: 100%; 
            padding: 14px; 
            background-color: var(--primary-color); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 1.1rem; 
            font-weight: 700; 
            transition: background-color 0.3s; 
            margin-top: 20px; 
            text-decoration: none;
            display: inline-block;
        }
        .auth-button:hover { 
            background-color: var(--primary-hover); 
        }
        .bottom-link { 
            margin-top: 25px; 
        }
        .bottom-link a { 
            color: var(--primary-color); 
            text-decoration: none; 
            font-weight: 600; 
        }
        @media (max-width: 992px) { 
            .auth-panel { 
                display: none; 
            } 
            .auth-form-section { 
                padding: 30px; 
            } 
        }
    </style>
</head>
<body>
    <main class="auth-container">
        <div class="auth-panel">
            <h2>Email Verification</h2>
            <p>Verify your email address to activate your account and unlock all features.</p>
        </div>
        <div class="auth-form-section">
            <div class="form-box">
                <?php if ($success_message): ?>
                    <div class="success-icon">✅</div>
                    <h1>Account Verified!</h1>
                    <div class="message-area">
                        <div class="message success-message">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    </div>
                    <a href="/login.php" class="auth-button">Proceed to Login</a>
                <?php elseif (!empty($errors)): ?>
                    <h1>Verification Failed</h1>
                    <div class="message-area">
                        <div class="message error-message">
                            <?php echo htmlspecialchars($errors[0]); ?>
                        </div>
                    </div>
                    <div class="bottom-link">
                        <a href="/register.php">Create a New Account</a><br>
                        <a href="/login.php">Back to Login</a>
                    </div>
                <?php else: ?>
                    <h1>Verifying...</h1>
                    <p class="form-subtitle">Please wait while we verify your email address.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>

<?php includeFooter(); ?>