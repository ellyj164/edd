<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Payments Terms of Use - FezaMarket';
includeHeader($page_title);
?>
<div style="max-width:900px;margin:60px auto;padding:0 20px">
<h1 style="font-size:2.5rem;margin-bottom:2rem">Payments Terms of Use</h1>
<div style="background:white;padding:40px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)">
<p style="color:#666;margin-bottom:1.5rem;line-height:1.8">Last Updated: <?php echo date('F Y'); ?></p>
<h2 style="margin-top:2rem">Payment Methods</h2>
<p style="color:#666;line-height:1.8">FezaMarket accepts credit cards and debit cards securely processed through Stripe. All payments are encrypted and processed through our secure payment gateway.</p>
<h2 style="margin-top:2rem">Payment Processing</h2>
<p style="color:#666;line-height:1.8">Payments are processed at the time of order placement. Authorization holds may appear on your account immediately, with the charge posting once your order ships. For digital products, charges post immediately upon purchase.</p>
<h2 style="margin-top:2rem">Refunds and Returns</h2>
<p style="color:#666;line-height:1.8">Refunds are processed to the original payment method within 5-10 business days after we receive your returned item. For more information, please see our <a href="/returns.php" style="color:#4285f4">Returns Policy</a>.</p>
<h2 style="margin-top:2rem">Payment Security</h2>
<p style="color:#666;line-height:1.8">We use industry-standard encryption (SSL/TLS) to protect your payment information. We do not store complete credit card numbers on our servers. All payment processing is PCI-DSS compliant.</p>
<h2 style="margin-top:2rem">Disputed Charges</h2>
<p style="color:#666;line-height:1.8">If you believe there is an error with a charge, please contact us immediately at <a href="/contact.php" style="color:#4285f4">support@fezamarket.com</a>. We will work with you to resolve any payment disputes promptly.</p>
<h2 style="margin-top:2rem">Contact Us</h2>
<p style="color:#666;line-height:1.8">For questions about payments, please visit our <a href="/contact.php" style="color:#4285f4">Contact page</a> or call our customer service team.</p>
</div>
</div>
<?php includeFooter(); ?>
