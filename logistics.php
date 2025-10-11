<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('Feza Logistics');
?>

<div class="container">
    <div class="page-header">
        <h1>Feza Logistics</h1>
        <p>Streamlined shipping and fulfillment for FezaMarket sellers</p>
    </div>

    <div class="content-wrapper">
        <section>
            <h2>Shipping Made Simple</h2>
            <p>Feza Logistics is our integrated shipping solution designed to make fulfillment easy and affordable for sellers of all sizes. From printing labels to tracking deliveries, we handle the complexities so you can focus on growing your business.</p>
        </section>

        <section>
            <h2>Features & Benefits</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>📦 Discounted Rates</h3>
                    <p>Save up to 60% on shipping with our negotiated carrier rates from USPS, FedEx, UPS, and DHL.</p>
                </div>
                <div class="feature-card">
                    <h3>🖨️ Easy Label Printing</h3>
                    <p>Print shipping labels directly from your seller dashboard in seconds.</p>
                </div>
                <div class="feature-card">
                    <h3>📍 Real-Time Tracking</h3>
                    <p>Provide customers with tracking updates automatically throughout delivery.</p>
                </div>
                <div class="feature-card">
                    <h3>📊 Analytics Dashboard</h3>
                    <p>Monitor shipping performance, costs, and delivery times with detailed analytics.</p>
                </div>
                <div class="feature-card">
                    <h3>🌍 International Shipping</h3>
                    <p>Ship globally with simplified customs documentation and international rates.</p>
                </div>
                <div class="feature-card">
                    <h3>🛡️ Insurance Options</h3>
                    <p>Protect your shipments with affordable insurance coverage up to $5,000.</p>
                </div>
            </div>
        </section>

        <section>
            <h2>How It Works</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-num">1</div>
                    <h3>Sell a Product</h3>
                    <p>When an order comes in, our system automatically calculates shipping.</p>
                </div>
                <div class="step">
                    <div class="step-num">2</div>
                    <h3>Print Label</h3>
                    <p>Print your discounted shipping label from the order page.</p>
                </div>
                <div class="step">
                    <div class="step-num">3</div>
                    <h3>Pack & Ship</h3>
                    <p>Pack your item, attach the label, and drop off at any carrier location.</p>
                </div>
                <div class="step">
                    <div class="step-num">4</div>
                    <h3>Track Delivery</h3>
                    <p>Both you and your customer get real-time tracking updates.</p>
                </div>
            </div>
        </section>

        <section>
            <h2>Pricing</h2>
            <p>No monthly fees, no hidden charges. You only pay for shipping when you ship:</p>
            <ul class="pricing-list">
                <li>Domestic shipping starting at $3.50</li>
                <li>Flat-rate boxes available</li>
                <li>Volume discounts for high-volume sellers</li>
                <li>Free packaging supplies program for qualified sellers</li>
            </ul>
        </section>

        <div class="cta-section">
            <h2>Ready to Get Started?</h2>
            <p>Enable Feza Logistics in your seller settings today</p>
            <a href="/seller-center.php" class="btn btn-primary btn-lg">Go to Seller Center</a>
        </div>
    </div>
</div>

<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}
section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #f59e0b;padding-bottom:10px}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:25px;margin-top:30px}
.feature-card{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.feature-card h3{margin:0 0 15px 0;font-size:20px;color:#1f2937}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-top:30px}
.step{text-align:center;padding:25px;background:#f9fafb;border-radius:12px}
.step-num{width:50px;height:50px;background:#f59e0b;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:bold;margin:0 auto 15px}
.pricing-list{font-size:18px;line-height:2;color:#374151}
.cta-section{text-align:center;padding:60px 30px;background:linear-gradient(135deg,#f9fafb 0%,#e5e7eb 100%);border-radius:16px;margin-top:50px}
.btn-lg{padding:16px 48px;font-size:18px}
@media (max-width:768px){.features-grid,.steps{grid-template-columns:1fr}}
</style>

<?php includeFooter(); ?>
