<?php
/**
 * 403 Forbidden Error Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

http_response_code(403);

$page_title = 'Access Forbidden';
includeHeader($page_title);
?>

<div class="container">
    <div style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 120px; color: #dc3545; margin-bottom: 20px;">🔒</div>
        <h1 style="font-size: 48px; color: #333; margin-bottom: 20px;">403</h1>
        <h2 style="color: #666; margin-bottom: 30px;">Access Forbidden</h2>
        <p style="font-size: 18px; color: #666; margin-bottom: 40px; max-width: 600px; margin-left: auto; margin-right: auto;">
            You don't have permission to access this resource. This could be because:
        </p>
        
        <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 30px; margin: 30px auto; max-width: 500px; text-align: left;">
            <ul style="margin: 0; padding-left: 20px; color: #666;">
                <li>You need to log in with appropriate credentials</li>
                <li>Your account doesn't have the required role or permissions</li>
                <li>The resource is restricted to certain user types</li>
                <li>Your session may have expired</li>
            </ul>
        </div>

        <div style="margin-top: 40px;">
            <?php if (Session::isLoggedIn()): ?>
                <a href="/account.php" class="btn btn-primary" style="margin-right: 10px;">
                    Go to My Account
                </a>
                <a href="/" class="btn btn-outline">
                    Return to Home
                </a>
            <?php else: ?>
                <a href="/login.php" class="btn btn-primary" style="margin-right: 10px;">
                    Login to Continue
                </a>
                <a href="/register.php" class="btn btn-outline" style="margin-right: 10px;">
                    Create Account
                </a>
                <a href="/" class="btn btn-secondary">
                    Return to Home
                </a>
            <?php endif; ?>
        </div>

        <?php if (Session::isLoggedIn()): ?>
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; max-width: 500px; margin-left: auto; margin-right: auto;">
            <p style="margin: 0; color: #856404;">
                <strong>Logged in as:</strong> <?php echo htmlspecialchars(Session::get('user_email', 'Unknown')); ?><br>
                <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst(Session::getUserRole())); ?>
            </p>
        </div>
        <?php endif; ?>

        <div style="margin-top: 40px; font-size: 14px; color: #999;">
            <p>If you believe this is an error, please <a href="/contact.php">contact support</a>.</p>
        </div>
    </div>
</div>

<?php includeFooter(); ?>