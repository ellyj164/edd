<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Your Privacy Choices - FezaMarket';
includeHeader($page_title);
?>
<div style="max-width:900px;margin:60px auto;padding:0 20px">
<h1 style="font-size:2.5rem;margin-bottom:2rem">Your Privacy Choices</h1>
<div style="background:white;padding:40px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)">
<div style="background:#e3f2fd;padding:20px;border-radius:8px;margin-bottom:2rem;border-left:4px solid #2196f3">
<p style="color:#1976d2;margin:0;font-weight:600"><i class="fas fa-shield-alt"></i> Your privacy matters to us. Control how your data is used.</p>
</div>
<h2>Manage Your Privacy Preferences</h2>
<p style="color:#666;line-height:1.8;margin-bottom:2rem">You have control over your personal information. Use the options below to manage your privacy settings.</p>
<div style="border:1px solid #e0e0e0;border-radius:8px;margin-bottom:2rem">
<div style="padding:25px;border-bottom:1px solid #e0e0e0">
<h3 style="margin:0 0 10px 0">Do Not Sell My Personal Information</h3>
<p style="color:#666;margin-bottom:15px">California residents can opt-out of the sale of personal information under CCPA.</p>
<button onclick="alert('Your request has been submitted')" style="background:#4285f4;color:white;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-weight:600">Opt Out of Sale</button>
</div>
<div style="padding:25px;border-bottom:1px solid #e0e0e0">
<h3 style="margin:0 0 10px 0">Marketing Communications</h3>
<p style="color:#666;margin-bottom:15px">Control promotional emails, SMS messages, and push notifications.</p>
<a href="/account.php?section=notifications" style="background:#4285f4;color:white;border:none;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600">Manage Preferences</a>
</div>
<div style="padding:25px;border-bottom:1px solid #e0e0e0">
<h3 style="margin:0 0 10px 0">Targeted Advertising</h3>
<p style="color:#666;margin-bottom:15px">Opt-out of personalized advertising based on your browsing behavior.</p>
<a href="/ad-choice.php" style="background:#4285f4;color:white;border:none;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600">AdChoice Settings</a>
</div>
<div style="padding:25px">
<h3 style="margin:0 0 10px 0">Cookies and Tracking</h3>
<p style="color:#666;margin-bottom:15px">Manage cookie preferences and tracking technologies.</p>
<a href="/cookies.php" style="background:#4285f4;color:white;border:none;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:600">Cookie Settings</a>
</div>
</div>
<h2 style="margin-top:3rem">Your Rights Under State Privacy Laws</h2>
<p style="color:#666;line-height:1.8">Depending on your location, you may have the right to:</p>
<ul style="color:#666;line-height:1.8;padding-left:30px">
<li>Know what personal information we collect and how we use it</li>
<li>Request deletion of your personal information</li>
<li>Opt-out of the sale or sharing of personal information</li>
<li>Correct inaccurate personal information</li>
<li>Limit use of sensitive personal information</li>
<li>Non-discrimination for exercising your privacy rights</li>
</ul>
<h2 style="margin-top:2rem">Submit a Privacy Request</h2>
<p style="color:#666;line-height:1.8;margin-bottom:2rem">To exercise your privacy rights, please submit a request through our <a href="/contact.php?subject=Privacy+Request" style="color:#4285f4">Privacy Request form</a> or email us at privacy@fezamarket.com.</p>
<div style="background:#fff3cd;padding:20px;border-radius:8px;border-left:4px solid #ffc107">
<p style="margin:0;color:#856404"><strong>Note:</strong> We will verify your identity before processing privacy requests to protect your information.</p>
</div>
<h2 style="margin-top:3rem">Additional Resources</h2>
<ul style="list-style:none;padding:0">
<li style="padding:10px 0;border-bottom:1px solid #f0f0f0"><a href="/privacy.php" style="color:#4285f4;text-decoration:none"><i class="fas fa-file-alt"></i> Privacy Policy</a></li>
<li style="padding:10px 0;border-bottom:1px solid #f0f0f0"><a href="/ca-privacy.php" style="color:#4285f4;text-decoration:none"><i class="fas fa-shield-alt"></i> California Privacy Notice</a></li>
<li style="padding:10px 0;border-bottom:1px solid #f0f0f0"><a href="/cookies.php" style="color:#4285f4;text-decoration:none"><i class="fas fa-cookie"></i> Cookie Policy</a></li>
<li style="padding:10px 0"><a href="/contact.php" style="color:#4285f4;text-decoration:none"><i class="fas fa-envelope"></i> Contact Privacy Team</a></li>
</ul>
</div>
</div>
<?php includeFooter(); ?>
