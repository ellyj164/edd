<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('Partnerships');
?>

<div class="container">
    <div class="page-header"><h1>FezaMarket Partnerships</h1><p>Collaborate with us to grow your business</p></div>
    <div class="content-wrapper">
        <section><h2>Partner With FezaMarket</h2><p>We're always looking for strategic partners who share our vision of creating a thriving marketplace ecosystem. Whether you're a technology provider, logistics company, payment processor, or brand, there are many ways to collaborate with FezaMarket.</p></section>
        <section><h2>Partnership Opportunities</h2>
            <div class="partners-grid">
                <div class="partner-card"><h3>🚚 Logistics Partners</h3><p>Join our network of shipping and fulfillment providers to serve FezaMarket sellers worldwide.</p></div>
                <div class="partner-card"><h3>💳 Payment Partners</h3><p>Integrate your payment solutions to offer more options to our global user base.</p></div>
                <div class="partner-card"><h3>🛍️ Brand Partners</h3><p>Bring your official brand store to FezaMarket and reach millions of engaged shoppers.</p></div>
                <div class="partner-card"><h3>🔧 Technology Partners</h3><p>Build integrations and tools that enhance the FezaMarket experience for buyers and sellers.</p></div>
                <div class="partner-card"><h3>📢 Marketing Partners</h3><p>Collaborate on promotional campaigns and affiliate marketing opportunities.</p></div>
                <div class="partner-card"><h3>🏦 Financial Partners</h3><p>Provide lending, insurance, and other financial services to our seller community.</p></div>
            </div>
        </section>
        <section><h2>Partnership Benefits</h2>
            <ul class="benefits-list">
                <li>Access to 10+ million active users</li>
                <li>API and technical integration support</li>
                <li>Co-marketing opportunities</li>
                <li>Dedicated partnership management</li>
                <li>Revenue sharing models</li>
                <li>Priority support and development</li>
            </ul>
        </section>
        <div class="cta-section"><h2>Interested in Partnering?</h2><p>Let's discuss how we can work together</p><a href="/contact.php?subject=Partnership" class="btn btn-primary btn-lg">Contact Our Partnerships Team</a></div>
    </div>
</div>
<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#8b5cf6 0%,#6d28d9 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #8b5cf6;padding-bottom:10px}
.partners-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:25px;margin-top:30px}
.partner-card{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.partner-card h3{margin:0 0 15px 0;font-size:20px}
.benefits-list{font-size:18px;line-height:2;color:#374151;padding-left:40px}
.cta-section{text-align:center;padding:60px 30px;background:linear-gradient(135deg,#f9fafb 0%,#e5e7eb 100%);border-radius:16px;margin-top:50px}
.btn-lg{padding:16px 48px;font-size:18px}
@media (max-width:768px){.partners-grid{grid-template-columns:1fr}}
</style>
<?php includeFooter(); ?>
