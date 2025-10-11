<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('Verified Rights Owner Program');
?>

<div class="container">
    <div class="page-header"><h1>Verified Rights Owner (VeRO) Program</h1><p>Protecting intellectual property on FezaMarket</p></div>
    <div class="content-wrapper">
        <section><h2>About the VeRO Program</h2><p>The Verified Rights Owner (VeRO) Program helps rights owners protect their intellectual property on FezaMarket. Members of this program can quickly identify and report listings that infringe on their copyrights, trademarks, and other intellectual property rights.</p></section>
        
        <section><h2>How It Works</h2>
            <div class="steps-grid">
                <div class="step"><div class="step-num">1</div><h3>Join the Program</h3><p>Register as a VeRO member and verify your rights ownership</p></div>
                <div class="step"><div class="step-num">2</div><h3>Monitor Listings</h3><p>Use our tools to search for potentially infringing listings</p></div>
                <div class="step"><div class="step-num">3</div><h3>Report Infringement</h3><p>Submit takedown notices through our streamlined process</p></div>
                <div class="step"><div class="step-num">4</div><h3>We Take Action</h3><p>We review reports and remove infringing items within 24-48 hours</p></div>
            </div>
        </section>

        <section><h2>Who Should Join?</h2><p>The VeRO Program is open to:</p>
            <ul><li>Brand owners and manufacturers</li><li>Copyright holders (authors, artists, content creators)</li><li>Trademark owners</li><li>Patent holders</li><li>Authorized representatives of rights owners</li><li>Industry associations</li></ul>
        </section>

        <section><h2>Program Benefits</h2>
            <ul><li><strong>Fast Response:</strong> Priority review and removal of infringing items</li><li><strong>Dedicated Support:</strong> Direct line to our IP protection team</li><li><strong>Batch Reporting:</strong> Submit multiple infringement reports at once</li><li><strong>Prevention Tools:</strong> Set up filters to catch potential infringements</li><li><strong>Education:</strong> Help educate sellers about your IP rights</li><li><strong>No Cost:</strong> The program is completely free to join</li></ul>
        </section>

        <section><h2>For Sellers</h2><p>As a seller, it's important to respect intellectual property rights:</p>
            <ul><li>Don't sell counterfeit or replica items</li><li>Ensure you have the right to sell branded products</li><li>Don't use copyrighted images without permission</li><li>Respect trademark rights in your listings</li><li>If you receive a VeRO notice, respond promptly</li></ul>
        </section>

        <div class="cta-section"><h2>Join the VeRO Program</h2><p>Protect your intellectual property on FezaMarket</p><a href="/contact.php?subject=VeRO" class="btn btn-primary btn-lg">Apply Now</a></div>
    </div>
</div>

<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#dc2626 0%,#991b1b 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1000px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #dc2626;padding-bottom:10px}
section ul{font-size:16px;line-height:2;padding-left:40px}
.steps-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px}
.step{text-align:center;padding:25px;background:white;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.step-num{width:50px;height:50px;background:#dc2626;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;margin:0 auto 15px}
.cta-section{text-align:center;padding:50px 30px;background:#f9fafb;border-radius:16px;margin-top:50px}
@media (max-width:768px){.steps-grid{grid-template-columns:1fr}}
</style>

<?php includeFooter(); ?>
