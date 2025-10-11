<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Resell at FezaMarket - Turn Your Items into Cash';
includeHeader($page_title);
?>
<div style="background:#f8f9fa;padding:60px 20px;min-height:70vh">
<div style="max-width:1200px;margin:0 auto;text-align:center">
<h1 style="font-size:3rem;margin-bottom:1rem">Resell at FezaMarket</h1>
<p style="font-size:1.3rem;color:#666;margin-bottom:3rem">Turn your gently used items into cash and rewards</p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:30px;margin:40px 0">
<div style="background:white;padding:40px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)">
<i class="fas fa-dollar-sign" style="font-size:3rem;color:#4caf50;margin-bottom:20px"></i>
<h3 style="font-size:1.5rem;margin-bottom:15px">Earn Cash & Rewards</h3>
<p style="color:#666;line-height:1.6">Get up to 65% cash back plus bonus rewards points on your resale items</p>
</div>
<div style="background:white;padding:40px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)">
<i class="fas fa-shipping-fast" style="font-size:3rem;color:#4285f4;margin-bottom:20px"></i>
<h3 style="font-size:1.5rem;margin-bottom:15px">Free Shipping Labels</h3>
<p style="color:#666;line-height:1.6">We provide prepaid shipping labels - just pack and ship your items</p>
</div>
<div style="background:white;padding:40px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08)">
<i class="fas fa-recycle" style="font-size:3rem;color:#8bc34a;margin-bottom:20px"></i>
<h3 style="font-size:1.5rem;margin-bottom:15px">Eco-Friendly</h3>
<p style="color:#666;line-height:1.6">Give items a second life and reduce waste - good for you and the planet</p>
</div>
</div>
<div style="margin-top:50px">
<a href="<?php echo getSellingUrl(); ?>" style="background:#4285f4;color:white;padding:20px 50px;border-radius:8px;font-size:1.2rem;font-weight:600;text-decoration:none;display:inline-block">Start Selling Now</a>
</div>
</div>
</div>
<?php includeFooter(); ?>
