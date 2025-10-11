<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'AdChoice - FezaMarket';
includeHeader($page_title);
?>
<div style="max-width:900px;margin:60px auto;padding:0 20px">
<h1 style="font-size:2.5rem;margin-bottom:2rem"><i class="fas fa-info-circle"></i> AdChoice</h1>
<div style="background:white;padding:40px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)">
<h2>About Interest-Based Advertising</h2>
<p style="color:#666;line-height:1.8">FezaMarket and our advertising partners use cookies and similar technologies to deliver advertisements that are relevant to your interests. These ads may appear on FezaMarket or on other websites and apps.</p>
<h2 style="margin-top:2rem">How Interest-Based Advertising Works</h2>
<p style="color:#666;line-height:1.8">When you visit FezaMarket or use our mobile app, we and our partners may collect information about your browsing activities to show you ads for products and services you might like. This information may include:</p>
<ul style="color:#666;line-height:1.8;padding-left:30px">
<li>Pages you view and links you click</li>
<li>Products you search for or purchase</li>
<li>Your general location (based on IP address)</li>
<li>Device and browser information</li>
</ul>
<h2 style="margin-top:2rem">Your AdChoices</h2>
<div style="background:#f8f9fa;padding:30px;border-radius:8px;margin:2rem 0">
<h3 style="margin-top:0">Opt-Out Options</h3>
<p style="color:#666;line-height:1.8;margin-bottom:20px">You can control interest-based advertising through the following methods:</p>
<div style="margin:20px 0">
<a href="/your-privacy-choices.php" style="background:#4285f4;color:white;padding:12px 25px;border-radius:6px;text-decoration:none;display:inline-block;margin-right:10px;margin-bottom:10px">Your Privacy Choices</a>
<a href="https://optout.aboutads.info" target="_blank" style="background:#4caf50;color:white;padding:12px 25px;border-radius:6px;text-decoration:none;display:inline-block;margin-right:10px;margin-bottom:10px">DAA Opt-Out <i class="fas fa-external-link-alt"></i></a>
<a href="https://optout.networkadvertising.org" target="_blank" style="background:#ff9800;color:white;padding:12px 25px;border-radius:6px;text-decoration:none;display:inline-block;margin-bottom:10px">NAI Opt-Out <i class="fas fa-external-link-alt"></i></a>
</div>
</div>
<h2 style="margin-top:2rem">Browser and Device Controls</h2>
<p style="color:#666;line-height:1.8">You can also control advertising through your browser or device settings:</p>
<ul style="color:#666;line-height:1.8;padding-left:30px">
<li><strong>Browser Settings:</strong> Most browsers allow you to block or delete cookies</li>
<li><strong>Mobile Devices:</strong> iOS and Android devices have settings to limit ad tracking</li>
<li><strong>Do Not Track:</strong> Some browsers support "Do Not Track" signals</li>
</ul>
<div style="background:#fff3cd;padding:20px;border-radius:8px;border-left:4px solid #ffc107;margin:2rem 0">
<p style="margin:0;color:#856404"><strong>Note:</strong> Opting out does not mean you will no longer see ads. You will still see the same number of advertisements, but they may be less relevant to your interests.</p>
</div>
<h2 style="margin-top:2rem">Our Advertising Partners</h2>
<p style="color:#666;line-height:1.8">We work with trusted advertising partners including Google, Facebook, and other ad networks. Each partner has their own privacy policies and opt-out mechanisms.</p>
<h2 style="margin-top:2rem">Questions?</h2>
<p style="color:#666;line-height:1.8">For more information about our advertising practices, please review our <a href="/privacy.php" style="color:#4285f4">Privacy Policy</a> or <a href="/contact.php?subject=Advertising" style="color:#4285f4">contact us</a>.</p>
</div>
</div>
<?php includeFooter(); ?>
