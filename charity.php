<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('FezaMarket for Charity');
?>
<div class="container">
    <div class="page-header"><h1>FezaMarket for Charity</h1><p>Shop and sell to support causes you care about</p></div>
    <div class="content-wrapper">
        <section><h2>Make a Difference While You Shop</h2><p>FezaMarket for Charity connects our marketplace with charitable organizations worldwide. Whether you're buying or selling, you can contribute to meaningful causes and make a positive impact.</p></section>
        <section><h2>How It Works</h2>
            <div class="steps-grid">
                <div class="step"><div class="step-num">1</div><h3>Browse Charity Items</h3><p>Shop items where a percentage of proceeds goes to charity</p></div>
                <div class="step"><div class="step-num">2</div><h3>Make a Purchase</h3><p>Buy as you normally would - no extra cost to you</p></div>
                <div class="step"><div class="step-num">3</div><h3>Support Causes</h3><p>A portion of your purchase automatically goes to the selected charity</p></div>
            </div>
        </section>
        <section><h2>For Sellers</h2><p>Donate a percentage of your sales to verified charitable organizations:</p>
            <ul><li>Choose from thousands of verified charities</li><li>Set your donation percentage (10-100%)</li><li>Receive tax receipts for donations</li><li>Attract socially-conscious buyers</li><li>Track your charitable impact</li></ul>
        </section>
        <section><h2>Featured Causes</h2>
            <div class="causes-grid">
                <div class="cause-card"><h3>🌍 Environmental Protection</h3><p>Support organizations fighting climate change and protecting our planet</p></div>
                <div class="cause-card"><h3>❤️ Healthcare & Medical</h3><p>Help fund medical research and healthcare access</p></div>
                <div class="cause-card"><h3>📚 Education</h3><p>Support educational programs and literacy initiatives</p></div>
                <div class="cause-card"><h3>🏠 Disaster Relief</h3><p>Aid communities affected by natural disasters</p></div>
            </div>
        </section>
        <div class="cta-section"><h2>Start Making an Impact</h2><a href="/search.php?charity=1" class="btn btn-primary">Shop Charity Items</a> <a href="/seller-center.php" class="btn btn-outline">Sell for Charity</a></div>
    </div>
</div>
<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #10b981;padding-bottom:10px}
.steps-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.step{text-align:center;padding:25px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.step-num{width:50px;height:50px;background:#10b981;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;margin:0 auto 15px}
.causes-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.cause-card{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.cause-card h3{margin:0 0 15px 0;font-size:20px}
.cta-section{text-align:center;padding:50px 30px;background:#f9fafb;border-radius:16px;margin-top:50px}
.cta-section h2{margin-bottom:25px}
@media (max-width:768px){.steps-grid,.causes-grid{grid-template-columns:1fr}}
</style>
<?php includeFooter(); ?>