<?php
$content = ob_get_clean();
ob_start();
?>

<h2>Welcome to FezaMarket, <?php echo htmlspecialchars($user['first_name']); ?>!</h2>

<p>Thank you for joining FezaMarket, your trusted online marketplace. We're excited to have you as part of our community!</p>

<div class="info-box">
    <strong>Your account is ready!</strong><br>
    Email: <?php echo htmlspecialchars($user['email']); ?><br>
    Account Type: <?php echo ucfirst(htmlspecialchars($user['role'] ?? 'Customer')); ?>
</div>

<p>Here's what you can do with your new account:</p>

<ul>
    <li><strong>Shop millions of products</strong> from trusted sellers worldwide</li>
    <li><strong>Track your orders</strong> and manage your purchases</li>
    <li><strong>Save your favorites</strong> to your wishlist</li>
    <li><strong>Get personalized recommendations</strong> based on your interests</li>
    <?php if (($user['role'] ?? 'customer') !== 'customer'): ?>
    <li><strong>Start selling</strong> and reach millions of buyers</li>
    <?php endif; ?>
</ul>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?php echo $login_url; ?>" class="btn">Start Shopping</a>
    <?php if (($user['role'] ?? 'customer') === 'customer'): ?>
    <a href="<?php echo sellerUrl('register'); ?>" class="btn btn-outline">Become a Seller</a>
    <?php endif; ?>
</p>

<h3>Need Help?</h3>
<p>If you have any questions, our <a href="<?php echo url('help.php'); ?>">Help Center</a> has answers to common questions, or you can <a href="<?php echo url('contact.php'); ?>">contact our support team</a>.</p>

<p>Welcome aboard!</p>

<p>
    Best regards,<br>
    The FezaMarket Team
</p>

<?php
$content = ob_get_contents();
ob_end_clean();

include __DIR__ . '/base.php';
?>