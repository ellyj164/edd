<?php
$content = ob_get_clean();
ob_start();
?>

<h2>Verify Your Email Address</h2>

<p>Hello <?php echo htmlspecialchars($user['first_name']); ?>,</p>

<p>Thank you for signing up with FezaMarket! To complete your account setup, please verify your email address.</p>

<div class="info-box">
    <strong>Account Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
</div>

<p>Click the button below to verify your email address:</p>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?php echo $verification_url; ?>" class="btn">Verify Email Address</a>
</p>

<p>If the button above doesn't work, you can copy and paste this link into your browser:</p>
<p style="word-break: break-all; background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace;">
    <?php echo $verification_url; ?>
</p>

<div class="warning-box">
    <strong>Security Note:</strong> This verification link will expire in 24 hours and can only be used once. If you didn't create this account, you can safely ignore this email.
</div>

<h3>Why verify your email?</h3>
<ul>
    <li>Secure your account and enable password recovery</li>
    <li>Receive important order updates and notifications</li>
    <li>Access all FezaMarket features and services</li>
    <li>Get personalized recommendations and deals</li>
</ul>

<h3>Need Help?</h3>
<p>If you're having trouble with verification, visit our <a href="<?php echo url('help/account/verification'); ?>">Help Center</a> or <a href="<?php echo url('contact.php'); ?>">contact support</a>.</p>

<p>
    Best regards,<br>
    The FezaMarket Team
</p>

<?php
$content = ob_get_contents();
ob_end_clean();

include __DIR__ . '/base.php';
?>