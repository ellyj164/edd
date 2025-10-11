<?php
require_once __DIR__ . '/../includes/init.php';
includeHeader('How to Sell');
?>

<div class="container">
    <div class="help-header">
        <h1>How to Sell on FezaMarket</h1>
        <p>Your complete guide to successful selling</p>
    </div>

    <div class="help-content">
        <section class="help-section">
            <h2>Getting Started as a Seller</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Create Your Seller Account</h3>
                    <p>Sign up and complete your seller profile with business information.</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Set Up Payment</h3>
                    <p>Configure how you want to receive payments from sales.</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>List Your Products</h3>
                    <p>Create detailed listings with photos, descriptions, and pricing.</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Start Selling</h3>
                    <p>Manage orders, ship products, and grow your business.</p>
                </div>
            </div>
        </section>

        <section class="help-section">
            <h2>Creating Great Listings</h2>
            <div class="help-grid">
                <div class="help-item">
                    <h3>📸 Professional Photos</h3>
                    <ul>
                        <li>Use high-quality images (at least 1000x1000px)</li>
                        <li>Show product from multiple angles</li>
                        <li>Use good lighting and clean background</li>
                        <li>Include close-ups of important details</li>
                    </ul>
                </div>
                <div class="help-item">
                    <h3>📝 Detailed Descriptions</h3>
                    <ul>
                        <li>Describe condition accurately</li>
                        <li>List all specifications and features</li>
                        <li>Mention any flaws or defects</li>
                        <li>Include measurements and dimensions</li>
                    </ul>
                </div>
                <div class="help-item">
                    <h3>💰 Competitive Pricing</h3>
                    <ul>
                        <li>Research similar items</li>
                        <li>Consider condition and rarity</li>
                        <li>Factor in shipping costs</li>
                        <li>Offer promotional pricing for new sellers</li>
                    </ul>
                </div>
                <div class="help-item">
                    <h3>🏷️ Categories & Tags</h3>
                    <ul>
                        <li>Choose the most specific category</li>
                        <li>Add relevant keywords and tags</li>
                        <li>Fill in all item attributes</li>
                        <li>Make products easy to find</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="help-section">
            <h2>Seller Fees</h2>
            <div class="help-item">
                <p><strong>Our seller fees are simple and transparent:</strong></p>
                <ul>
                    <li><strong>Listing Fee:</strong> FREE - List as many items as you want</li>
                    <li><strong>Commission:</strong> 10% on each sale (may vary by category)</li>
                    <li><strong>Payment Processing:</strong> 2.9% + $0.30 per transaction</li>
                    <li><strong>Optional Services:</strong> Featured listings, promotions (extra fees apply)</li>
                </ul>
                <p class="tip">New sellers get their first 10 sales commission-free!</p>
            </div>
        </section>

        <section class="help-section">
            <h2>Shipping & Fulfillment</h2>
            <div class="help-grid">
                <div class="help-item">
                    <h3>Ship It Yourself</h3>
                    <p>Handle your own shipping with your preferred carrier. Print labels, pack items, and drop off at your convenience.</p>
                </div>
                <div class="help-item">
                    <h3>Feza Logistics</h3>
                    <p>Use our integrated shipping solution for discounted rates and streamlined fulfillment.</p>
                </div>
            </div>
        </section>

        <section class="help-section">
            <h2>Growing Your Business</h2>
            <div class="help-item">
                <h3>Success Tips</h3>
                <ul>
                    <li>Respond quickly to customer inquiries</li>
                    <li>Ship orders promptly</li>
                    <li>Maintain accurate inventory</li>
                    <li>Provide excellent customer service</li>
                    <li>Build positive seller ratings</li>
                    <li>Use promotions and sales strategically</li>
                    <li>Expand your product offerings</li>
                    <li>Engage with the FezaMarket community</li>
                </ul>
            </div>
        </section>

        <div class="cta-section">
            <h2>Ready to Start Selling?</h2>
            <a href="/seller-register.php" class="btn btn-primary btn-lg">Become a Seller Today</a>
        </div>
    </div>
</div>

<style>
.help-header{text-align:center;padding:40px 0;background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;margin-bottom:40px}
.help-header h1{margin:0 0 10px 0;font-size:36px}
.help-header p{margin:0;font-size:18px;opacity:0.9}
.help-content{max-width:1200px;margin:0 auto;padding:0 20px 40px}
.help-section{margin-bottom:50px}
.help-section h2{font-size:28px;margin-bottom:25px;color:#1f2937;border-bottom:2px solid #10b981;padding-bottom:10px}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px;margin-top:30px}
.step{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);text-align:center}
.step-number{width:50px;height:50px;background:#10b981;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;margin:0 auto 15px}
.step h3{margin:10px 0;color:#1f2937}
.help-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px}
.help-item{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.help-item h3{margin-top:0;margin-bottom:15px;color:#1f2937;font-size:20px}
.help-item ul{margin:15px 0;padding-left:25px}
.help-item li{margin-bottom:8px;color:#374151}
.tip{background:#dcfce7;border-left:4px solid #10b981;padding:15px;margin-top:15px;border-radius:4px;color:#166534}
.cta-section{text-align:center;padding:40px;background:linear-gradient(135deg,#f9fafb 0%,#e5e7eb 100%);border-radius:12px;margin-top:40px}
.cta-section h2{margin-bottom:20px}
.btn-lg{padding:15px 40px;font-size:18px}
@media (max-width:768px){.help-grid,.steps{grid-template-columns:1fr}}
</style>

<?php includeFooter(); ?>
