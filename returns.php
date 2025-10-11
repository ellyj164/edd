<?php
/**
 * Returns & Refunds Policy
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Returns & Refunds - FezaMarket';
includeHeader($page_title);
?>

<div class="container">
    <!-- Returns Header -->
    <div class="returns-header">
        <h1>Returns & Refunds</h1>
        <p>Easy returns and hassle-free refunds</p>
    </div>

    <!-- Quick Actions -->
    <section class="quick-actions">
        <div class="actions-grid">
            <div class="action-card">
                <div class="action-icon">↩️</div>
                <h3>Start a Return</h3>
                <p>Return an item from a recent order</p>
                <?php if (Session::isLoggedIn()): ?>
                    <a href="/account/returns.php" class="btn">Start Return</a>
                <?php else: ?>
                    <a href="/login.php?return=/returns.php" class="btn">Sign In to Return</a>
                <?php endif; ?>
            </div>
            <div class="action-card">
                <div class="action-icon">📋</div>
                <h3>Check Return Status</h3>
                <p>Track the progress of your return</p>
                <a href="/help.php" class="btn btn-outline">Check Status</a>
            </div>
            <div class="action-card">
                <div class="action-icon">📞</div>
                <h3>Need Help?</h3>
                <p>Contact our customer service team</p>
                <a href="/contact.php" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Return Policy -->
    <section class="return-policy">
        <h2>Our Return Policy</h2>
        <div class="policy-content">
            <div class="policy-highlight">
                <h3>🕐 30-Day Return Window</h3>
                <p>Most items can be returned within 30 days of delivery for a full refund. Items must be in their original condition with tags attached.</p>
            </div>
            
            <div class="policy-details">
                <div class="policy-section">
                    <h4>What Can Be Returned</h4>
                    <ul class="policy-list">
                        <li>✅ Items in original condition with tags</li>
                        <li>✅ Unopened electronics with original packaging</li>
                        <li>✅ Books in resellable condition</li>
                        <li>✅ Clothing and accessories (unworn, with tags)</li>
                        <li>✅ Home goods and appliances (unused)</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h4>What Cannot Be Returned</h4>
                    <ul class="policy-list">
                        <li>❌ Personalized or custom-made items</li>
                        <li>❌ Perishable goods (food, flowers)</li>
                        <li>❌ Digital downloads and software</li>
                        <li>❌ Intimate apparel and swimwear</li>
                        <li>❌ Items damaged by misuse</li>
                        <li>❌ Gift cards</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Return Process -->
    <section class="return-process">
        <h2>How to Return an Item</h2>
        <div class="process-steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Initiate Return</h3>
                    <p>Go to "My Orders" and select the item you want to return. Choose your reason and submit the return request.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Print Return Label</h3>
                    <p>We'll email you a prepaid return shipping label. Print it and attach it to your package.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Ship the Item</h3>
                    <p>Package the item securely and drop it off at any authorized shipping location or schedule a pickup.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3>Get Your Refund</h3>
                    <p>Once we receive and inspect your item, we'll process your refund within 3-5 business days.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Refund Information -->
    <section class="refund-info">
        <h2>Refund Information</h2>
        <div class="refund-details">
            <div class="refund-card">
                <h3>💳 Refund Methods</h3>
                <ul>
                    <li>Original payment method (credit card)</li>
                    <li>FezaMarket account credit</li>
                    <li>Store credit for exchanges</li>
                </ul>
            </div>
            <div class="refund-card">
                <h3>⏰ Refund Timeline</h3>
                <ul>
                    <li>Processing: 1-2 business days after receipt</li>
                    <li>Credit/Debit cards: 3-5 business days</li>
                    <li>Bank transfers: 5-7 business days</li>
                </ul>
            </div>
            <div class="refund-card">
                <h3>💰 Refund Amount</h3>
                <ul>
                    <li>Full purchase price for eligible returns</li>
                    <li>Original shipping costs (if item was defective)</li>
                    <li>Return shipping is free with our prepaid label</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Special Circumstances -->
    <section class="special-circumstances">
        <h2>Special Circumstances</h2>
        <div class="circumstances-grid">
            <div class="circumstance-card">
                <h3>🔧 Defective Items</h3>
                <p>If you receive a defective item, contact us immediately. We'll provide a prepaid return label and full refund including original shipping costs.</p>
                <a href="/contact.php?issue=defective" class="report-link">Report Defective Item</a>
            </div>
            <div class="circumstance-card">
                <h3>📦 Wrong Item Received</h3>
                <p>If you receive the wrong item, we'll send you the correct item and provide a prepaid return label for the incorrect one.</p>
                <a href="/contact.php?issue=wrong-item" class="report-link">Report Wrong Item</a>
            </div>
            <div class="circumstance-card">
                <h3>📋 Missing Items</h3>
                <p>If your order is missing items, contact us within 7 days of delivery. We'll investigate and send the missing items or provide a refund.</p>
                <a href="/contact.php?issue=missing-item" class="report-link">Report Missing Item</a>
            </div>
            <div class="circumstance-card">
                <h3>🚚 Damaged in Shipping</h3>
                <p>Items damaged during shipping are eligible for full refund or replacement. Report damage within 48 hours of delivery.</p>
                <a href="/contact.php?issue=shipping-damage" class="report-link">Report Shipping Damage</a>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="returns-faq">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How long do I have to return an item?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    You have 30 days from the delivery date to initiate a return for most items. Some categories may have different return windows - check the product page for specific information.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Do I have to pay for return shipping?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    No, we provide free prepaid return labels for most returns. You'll receive the label via email after initiating your return.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Can I return items without original packaging?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    Items should be returned in their original packaging when possible. However, we may accept returns without original packaging depending on the item condition and category.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    What if I lost my receipt or order number?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    If you're signed in to your account, you can find all your orders in "My Orders." If you checked out as a guest, contact our customer service with your email address.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Can I exchange an item for a different size or color?
                    <span class="faq-toggle">+</span>
                </div>
                <div class="faq-answer">
                    Yes, you can return the original item and place a new order for the size or color you want. This ensures you get the item you need as quickly as possible.
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Support -->
    <section class="contact-support">
        <div class="support-banner">
            <h2>Still Need Help?</h2>
            <p>Our customer service team is here to help with any questions about returns or refunds.</p>
            <div class="support-options">
                <a href="/contact.php" class="btn">Contact Support</a>
                <a href="/help.php" class="btn btn-outline">Visit Help Center</a>
            </div>
        </div>
    </section>
</div>

<style>
.returns-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 40px 0;
}

.returns-header h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 10px;
}

.returns-header p {
    font-size: 18px;
    color: #6b7280;
}

.quick-actions {
    margin-bottom: 50px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.action-card {
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.action-card:hover {
    transform: translateY(-3px);
}

.action-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.action-card h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.action-card p {
    color: #6b7280;
    margin-bottom: 20px;
}

.return-policy {
    margin-bottom: 50px;
}

.return-policy h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.policy-highlight {
    background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
    padding: 30px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 30px;
    border-left: 4px solid #0654ba;
}

.policy-highlight h3 {
    color: #0654ba;
    margin-bottom: 10px;
    font-size: 20px;
}

.policy-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
}

.policy-section h4 {
    color: #1f2937;
    margin-bottom: 15px;
}

.policy-list {
    list-style: none;
    padding: 0;
}

.policy-list li {
    padding: 8px 0;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

.return-process {
    margin-bottom: 50px;
}

.return-process h2 {
    color: #1f2937;
    margin-bottom: 40px;
    text-align: center;
}

.process-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.step {
    text-align: center;
    position: relative;
}

.step-number {
    width: 60px;
    height: 60px;
    background: #0654ba;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: bold;
    margin: 0 auto 20px auto;
}

.step h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.step p {
    color: #6b7280;
    line-height: 1.6;
}

.refund-info {
    margin-bottom: 50px;
}

.refund-info h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.refund-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
}

.refund-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.refund-card h3 {
    color: #1f2937;
    margin-bottom: 15px;
}

.refund-card ul {
    list-style: none;
    padding: 0;
}

.refund-card li {
    padding: 5px 0;
    color: #374151;
}

.special-circumstances {
    margin-bottom: 50px;
}

.special-circumstances h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.circumstances-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.circumstance-card {
    background: #f9fafb;
    padding: 25px;
    border-radius: 8px;
    border-left: 4px solid #0654ba;
}

.circumstance-card h3 {
    color: #1f2937;
    margin-bottom: 10px;
}

.circumstance-card p {
    color: #6b7280;
    margin-bottom: 15px;
    line-height: 1.6;
}

.report-link {
    color: #0654ba;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
}

.returns-faq {
    margin-bottom: 50px;
}

.returns-faq h2 {
    color: #1f2937;
    margin-bottom: 30px;
    text-align: center;
}

.faq-container {
    max-width: 800px;
    margin: 0 auto;
}

.faq-item {
    border-bottom: 1px solid #e5e7eb;
    padding: 20px 0;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-weight: 600;
    color: #1f2937;
}

.faq-toggle {
    font-size: 20px;
    color: #0654ba;
    font-weight: bold;
}

.faq-answer {
    display: none;
    color: #6b7280;
    margin-top: 15px;
    line-height: 1.6;
}

.faq-answer.active {
    display: block;
}

.contact-support {
    background: linear-gradient(135deg, #0654ba, #1e40af);
    color: white;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
}

.support-banner h2 {
    margin-bottom: 10px;
}

.support-banner p {
    margin-bottom: 25px;
    opacity: 0.9;
}

.support-options {
    display: flex;
    gap: 15px;
    justify-content: center;
}

@media (max-width: 768px) {
    .policy-details {
        grid-template-columns: 1fr;
    }
    
    .process-steps {
        grid-template-columns: 1fr;
    }
    
    .support-options {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
function toggleFAQ(element) {
    const answer = element.nextElementSibling;
    const toggle = element.querySelector('.faq-toggle');
    
    if (answer.classList.contains('active')) {
        answer.classList.remove('active');
        toggle.textContent = '+';
    } else {
        // Close all other FAQs
        document.querySelectorAll('.faq-answer').forEach(ans => {
            ans.classList.remove('active');
        });
        document.querySelectorAll('.faq-toggle').forEach(tog => {
            tog.textContent = '+';
        });
        
        // Open this FAQ
        answer.classList.add('active');
        toggle.textContent = '−';
    }
}
</script>

<?php includeFooter(); ?>