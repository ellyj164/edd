<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('Charity Shop');
?>
<div class="container">
    <div class="page-header"><h1>FezaMarket Charity Shop</h1><p>100% of proceeds go to charitable causes</p></div>
    <div class="content-wrapper">
        <section><h2>Shop for Good</h2><p>The FezaMarket Charity Shop features items where 100% of the proceeds are donated to charitable organizations. Every purchase makes a direct impact on communities in need.</p></section>
        <section><h2>How to Donate Items</h2><p>Have items to donate? List them in the Charity Shop:</p>
            <ol><li>Register as a seller (free)</li><li>List your items and select "100% Charity Donation"</li><li>Choose a verified charitable organization</li><li>Ship sold items to buyers</li><li>We handle the donation on your behalf</li></ol>
        </section>
        <section><h2>Tax Benefits</h2><p>Donations through the Charity Shop may be tax-deductible. We provide:</p>
            <ul><li>Official donation receipts</li><li>IRS-compliant documentation</li><li>Annual donation summaries</li><li>Direct charity payments with tracking</li></ul>
        </section>
        <div class="cta-section"><h2>Start Shopping or Donating</h2><a href="/search.php?charity_shop=1" class="btn btn-primary">Browse Charity Shop</a> <a href="/seller-register.php" class="btn btn-outline">Donate Items</a></div>
    </div>
</div>
<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#ec4899 0%,#be185d 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:900px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #ec4899;padding-bottom:10px}
section ol,section ul{font-size:18px;line-height:2;color:#374151;padding-left:40px}
.cta-section{text-align:center;padding:50px 30px;background:#f9fafb;border-radius:16px;margin-top:50px}
.cta-section h2{margin-bottom:25px}
</style>
<?php includeFooter(); ?>