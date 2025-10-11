<?php
require_once __DIR__ . '/../includes/init.php';
includeHeader('Bidding & Buying Help');
?>

<div class="container">
    <div class="help-header">
        <h1>Bidding & Buying Help</h1>
        <p>Everything you need to know about buying on FezaMarket</p>
    </div>

    <div class="help-content">
        <section class="help-section">
            <h2>Getting Started</h2>
            <div class="help-grid">
                <div class="help-item">
                    <h3>🛍️ How to Buy</h3>
                    <p>Browse products, add to cart, and check out securely. It's that simple!</p>
                    <ul>
                        <li>Search or browse for items</li>
                        <li>Click "Add to Cart" or "Buy Now"</li>
                        <li>Review your cart and proceed to checkout</li>
                        <li>Enter shipping and payment information</li>
                        <li>Confirm your order</li>
                    </ul>
                </div>
                <div class="help-item">
                    <h3>💳 Payment Methods</h3>
                    <p>We accept multiple secure payment options:</p>
                    <ul>
                        <li>Credit/Debit Cards (Visa, Mastercard, Amex)</li>
                        <li>PayPal</li>
                        <li>Apple Pay & Google Pay</li>
                        <li>Bank Transfer</li>
                        <li>FezaMarket Gift Cards</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="help-section">
            <h2>Bidding on Items</h2>
            <div class="help-item">
                <h3>How Bidding Works</h3>
                <p>Many items on FezaMarket are available through auction-style bidding:</p>
                <ol>
                    <li><strong>Find an Auction:</strong> Look for items marked with "Bid" or auction end times</li>
                    <li><strong>Place Your Bid:</strong> Enter your maximum bid amount</li>
                    <li><strong>Automatic Bidding:</strong> Our system bids on your behalf up to your maximum</li>
                    <li><strong>Watch the Auction:</strong> Monitor your bid status in real-time</li>
                    <li><strong>Win & Pay:</strong> If you're the highest bidder when time expires, you win!</li>
                </ol>
                <p class="tip"><strong>Pro Tip:</strong> Bid your true maximum to increase your chances of winning without constant monitoring.</p>
            </div>
        </section>

        <section class="help-section">
            <h2>Shopping Tips</h2>
            <div class="help-grid">
                <div class="help-item">
                    <h3>📝 Read Descriptions Carefully</h3>
                    <p>Review item conditions, specifications, and seller policies before purchasing.</p>
                </div>
                <div class="help-item">
                    <h3>⭐ Check Seller Ratings</h3>
                    <p>Look for sellers with high ratings and positive feedback from other buyers.</p>
                </div>
                <div class="help-item">
                    <h3>📸 Review Photos</h3>
                    <p>Examine all product photos carefully. Zoom in to check details and condition.</p>
                </div>
                <div class="help-item">
                    <h3>💬 Ask Questions</h3>
                    <p>Contact sellers directly if you need clarification before making a purchase.</p>
                </div>
            </div>
        </section>

        <section class="help-section">
            <h2>After Your Purchase</h2>
            <div class="help-item">
                <h3>Track Your Order</h3>
                <p>Monitor your shipment from seller to your door:</p>
                <ul>
                    <li>View order status in your account</li>
                    <li>Receive email notifications at each step</li>
                    <li>Track package with carrier's tracking number</li>
                    <li>Confirm delivery upon receipt</li>
                </ul>
            </div>
            <div class="help-item">
                <h3>Leave Feedback</h3>
                <p>Share your experience to help other buyers and sellers:</p>
                <ul>
                    <li>Rate your purchase and seller</li>
                    <li>Write a detailed review</li>
                    <li>Upload photos of the item received</li>
                    <li>Help build a trusted marketplace community</li>
                </ul>
            </div>
        </section>

        <section class="help-section">
            <h2>Need More Help?</h2>
            <div class="help-grid">
                <div class="help-item">
                    <h3>📧 Contact Support</h3>
                    <p>Our team is here to help with any questions or issues.</p>
                    <a href="/contact.php" class="btn btn-primary">Contact Us</a>
                </div>
                <div class="help-item">
                    <h3>💰 Money Back Guarantee</h3>
                    <p>Shop with confidence knowing you're protected.</p>
                    <a href="/money-back.php" class="btn btn-outline">Learn More</a>
                </div>
            </div>
        </section>
    </div>
</div>

<style>
.help-header{text-align:center;padding:40px 0;background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);color:white;margin-bottom:40px}
.help-header h1{margin:0 0 10px 0;font-size:36px}
.help-header p{margin:0;font-size:18px;opacity:0.9}
.help-content{max-width:1200px;margin:0 auto;padding:0 20px 40px}
.help-section{margin-bottom:50px}
.help-section h2{font-size:28px;margin-bottom:25px;color:#1f2937;border-bottom:2px solid #3b82f6;padding-bottom:10px}
.help-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:20px}
.help-item{background:white;padding:25px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
.help-item h3{margin-top:0;margin-bottom:15px;color:#1f2937;font-size:20px}
.help-item ul,.help-item ol{margin:15px 0;padding-left:25px}
.help-item li{margin-bottom:8px;color:#374151}
.tip{background:#fef3c7;border-left:4px solid #f59e0b;padding:15px;margin-top:15px;border-radius:4px}
@media (max-width:768px){.help-grid{grid-template-columns:1fr}}
</style>

<?php includeFooter(); ?>
