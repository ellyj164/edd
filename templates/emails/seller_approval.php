<?php
$content = ob_get_clean();
ob_start();
?>

<h2>Congratulations! Your Seller Account is Approved</h2>

<p>Hello <?php echo htmlspecialchars($user['first_name']); ?>,</p>

<p>Great news! Your seller account has been approved and you can now start selling on FezaMarket.</p>

<div class="info-box">
    <strong>Seller Account Details:</strong><br>
    Business Name: <?php echo htmlspecialchars($vendor['business_name']); ?><br>
    Account Type: <?php echo ucfirst($vendor['business_type'] ?? 'Individual'); ?><br>
    Status: <span style="color: #28a745; font-weight: bold;">Approved</span>
</div>

<h3>What's Next?</h3>

<ol>
    <li><strong>Set up your store</strong> - Add your business information and policies</li>
    <li><strong>List your first products</strong> - Start adding items to sell</li>
    <li><strong>Configure shipping</strong> - Set up shipping rates and options</li>
    <li><strong>Review seller resources</strong> - Learn best practices for success</li>
</ol>

<p style="text-align: center; margin: 30px 0;">
    <a href="<?php echo $seller_center_url; ?>" class="btn">Go to Seller Center</a>
</p>

<h3>Seller Resources</h3>

<ul>
    <li><a href="<?php echo url('help/selling/getting-started'); ?>">Getting Started Guide</a></li>
    <li><a href="<?php echo url('help/selling/best-practices'); ?>">Listing Best Practices</a></li>
    <li><a href="<?php echo url('help/selling/fees'); ?>">Seller Fees & Pricing</a></li>
    <li><a href="<?php echo url('help/selling/policies'); ?>">Seller Policies</a></li>
</ul>

<div class="warning-box">
    <strong>Important:</strong> Please review our seller policies and ensure your listings comply with FezaMarket guidelines. Non-compliant listings may be removed without notice.
</div>

<h3>Need Support?</h3>
<p>Our seller support team is here to help! Visit the <a href="<?php echo url('help/selling'); ?>">Seller Help Center</a> or <a href="<?php echo url('contact.php'); ?>">contact us directly</a>.</p>

<p>We're excited to see your business grow on FezaMarket!</p>

<p>
    Best regards,<br>
    The FezaMarket Seller Team
</p>

<?php
$content = ob_get_contents();
ob_end_clean();

include __DIR__ . '/base.php';
?>