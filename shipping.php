<?php
/**
 * Shipping Information Page
 * FezaMarket shipping options, rates, and policies
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Shipping & Delivery - FezaMarket';
$meta_description = 'Learn about FezaMarket shipping options, delivery times, rates, and tracking. Free shipping available on eligible orders!';

includeHeader($page_title);
?>

<div class="shipping-page">
    <!-- Hero Section -->
    <section class="shipping-hero">
        <div class="container">
            <h1>Shipping & Delivery</h1>
            <p class="hero-subtitle">Fast, reliable shipping to your door</p>
        </div>
    </section>

    <!-- Shipping Options -->
    <section class="shipping-options-section">
        <div class="container">
            <h2 class="section-title">Shipping Options</h2>
            
            <div class="options-grid">
                <div class="option-card">
                    <div class="option-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Standard Shipping</h3>
                    <p class="delivery-time">3-5 business days</p>
                    <p class="shipping-cost">FREE on orders $35+</p>
                    <p class="description">Our most popular option. Reliable delivery within 3-5 business days for most locations.</p>
                </div>
                
                <div class="option-card featured">
                    <div class="popular-badge">Most Popular</div>
                    <div class="option-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Express Shipping</h3>
                    <p class="delivery-time">2-3 business days</p>
                    <p class="shipping-cost">$7.99</p>
                    <p class="description">Faster delivery for when you need it sooner. Available for most items and locations.</p>
                </div>
                
                <div class="option-card premium">
                    <div class="option-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Premium 2-Day</h3>
                    <p class="delivery-time">2 business days</p>
                    <p class="shipping-cost">$12.99 or FREE for Premium members</p>
                    <p class="description">Guaranteed 2-day delivery. Free for FezaMarket Premium members on all orders.</p>
                </div>
                
                <div class="option-card">
                    <div class="option-icon">
                        <i class="fas fa-plane"></i>
                    </div>
                    <h3>Overnight Shipping</h3>
                    <p class="delivery-time">Next business day</p>
                    <p class="shipping-cost">$24.99</p>
                    <p class="description">Next-day delivery for urgent orders. Order before 2 PM for next-day delivery.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Free Shipping Info -->
    <section class="free-shipping-section">
        <div class="container">
            <div class="free-shipping-card">
                <div class="free-shipping-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="free-shipping-content">
                    <h2>Free Standard Shipping</h2>
                    <p>Get free standard shipping on orders of $35 or more. No code needed - discount automatically applied at checkout!</p>
                    <ul class="benefits-list">
                        <li><i class="fas fa-check"></i> No minimum on Premium member orders</li>
                        <li><i class="fas fa-check"></i> Applies to millions of eligible items</li>
                        <li><i class="fas fa-check"></i> Ships within 1-2 business days</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Shipping Rates -->
    <section class="shipping-rates-section">
        <div class="container">
            <h2 class="section-title">Shipping Rates by Order Value</h2>
            
            <div class="rates-table">
                <table>
                    <thead>
                        <tr>
                            <th>Order Value</th>
                            <th>Standard</th>
                            <th>Express</th>
                            <th>2-Day</th>
                            <th>Overnight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Under $35</td>
                            <td>$5.99</td>
                            <td>$7.99</td>
                            <td>$12.99</td>
                            <td>$24.99</td>
                        </tr>
                        <tr class="highlighted">
                            <td>$35 - $99</td>
                            <td><strong>FREE</strong></td>
                            <td>$7.99</td>
                            <td>$12.99</td>
                            <td>$24.99</td>
                        </tr>
                        <tr>
                            <td>$100+</td>
                            <td><strong>FREE</strong></td>
                            <td>$5.99</td>
                            <td>$9.99</td>
                            <td>$19.99</td>
                        </tr>
                        <tr class="premium-row">
                            <td>Premium Members</td>
                            <td><strong>FREE</strong></td>
                            <td><strong>FREE</strong></td>
                            <td><strong>FREE</strong></td>
                            <td>$14.99</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <p class="rates-note">* Rates shown for contiguous United States. Alaska, Hawaii, and territories may have different rates.</p>
        </div>
    </section>

    <!-- International Shipping -->
    <section class="international-section">
        <div class="container">
            <h2 class="section-title">International Shipping</h2>
            
            <div class="international-grid">
                <div class="international-card">
                    <h3><i class="fas fa-globe-americas"></i> Available Countries</h3>
                    <p>We ship to over 100 countries worldwide. Check our <a href="/international-shipping.php">international shipping page</a> for a complete list and rates.</p>
                </div>
                
                <div class="international-card">
                    <h3><i class="fas fa-file-invoice-dollar"></i> Customs & Duties</h3>
                    <p>International orders may be subject to customs duties and taxes. These fees are the responsibility of the buyer and are not included in our shipping rates.</p>
                </div>
                
                <div class="international-card">
                    <h3><i class="fas fa-clock"></i> Delivery Times</h3>
                    <p>International delivery typically takes 7-21 business days depending on destination. Express international shipping available for select countries.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tracking -->
    <section class="tracking-section">
        <div class="container">
            <div class="tracking-content">
                <div class="tracking-text">
                    <h2>Track Your Order</h2>
                    <p>Get real-time updates on your shipment's location and estimated delivery date.</p>
                    <ul class="tracking-features">
                        <li><i class="fas fa-envelope"></i> Email notifications at each shipping milestone</li>
                        <li><i class="fas fa-mobile-alt"></i> SMS text updates (optional)</li>
                        <li><i class="fas fa-map-marker-alt"></i> Live map tracking</li>
                        <li><i class="fas fa-calendar-check"></i> Accurate delivery estimates</li>
                    </ul>
                </div>
                <div class="tracking-form">
                    <h3>Track Your Package</h3>
                    <form action="/track-order.php" method="GET">
                        <div class="form-group">
                            <label for="tracking-number">Tracking Number</label>
                            <input type="text" id="tracking-number" name="tracking" placeholder="Enter your tracking number" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Track Package</button>
                    </form>
                    <p class="form-note">You can find your tracking number in your order confirmation email or in your <a href="/account.php?section=orders">order history</a>.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="faq-section">
        <div class="container">
            <h2 class="section-title">Shipping FAQs</h2>
            
            <div class="faq-list">
                <div class="faq-item">
                    <h4>When will my order ship?</h4>
                    <p>Most orders ship within 1-2 business days. You'll receive a shipping confirmation email with tracking information once your order ships.</p>
                </div>
                
                <div class="faq-item">
                    <h4>Can I change my shipping address after ordering?</h4>
                    <p>If your order hasn't shipped yet, you can change the address in your order details. Once shipped, contact our <a href="/contact.php">customer service</a> for assistance.</p>
                </div>
                
                <div class="faq-item">
                    <h4>What if my package is lost or damaged?</h4>
                    <p>We're sorry if this happens! Contact us immediately and we'll work with the carrier to locate your package or send a replacement. All shipments are insured.</p>
                </div>
                
                <div class="faq-item">
                    <h4>Do you offer P.O. Box delivery?</h4>
                    <p>Yes, we ship to P.O. Boxes via USPS for most items. Some large or restricted items cannot be delivered to P.O. Boxes.</p>
                </div>
                
                <div class="faq-item">
                    <h4>Can I pick up my order locally?</h4>
                    <p>Local pickup is available for select items and locations. Look for the "Local Pickup" option at checkout where available.</p>
                </div>
                
                <div class="faq-item">
                    <h4>What carriers do you use?</h4>
                    <p>We partner with USPS, UPS, FedEx, and DHL to ensure the fastest and most reliable delivery. The carrier will be selected based on your location and the items ordered.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Need Help with Shipping?</h2>
                <p>Our customer service team is here to help with any shipping questions or concerns.</p>
                <div class="cta-buttons">
                    <a href="/contact.php" class="btn btn-primary">Contact Support</a>
                    <a href="/returns.php" class="btn btn-secondary">Returns & Exchanges</a>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.shipping-page {
    background-color: #ffffff;
}

.shipping-hero {
    background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
}

.shipping-hero h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.3rem;
    opacity: 0.95;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 3rem;
    color: #333;
}

/* Shipping Options */
.shipping-options-section {
    padding: 60px 20px;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.option-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 16px;
    padding: 40px 30px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.option-card:hover {
    border-color: #4285f4;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(66, 133, 244, 0.2);
}

.option-card.featured {
    border-color: #4285f4;
    border-width: 3px;
}

.option-card.premium {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    border-color: #ffd700;
}

.popular-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: #4285f4;
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.option-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    color: white;
}

.option-card.premium .option-icon {
    background: linear-gradient(135deg, #333 0%, #555 100%);
}

.option-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.delivery-time {
    font-size: 1.1rem;
    font-weight: 600;
    color: #4285f4;
    margin-bottom: 10px;
}

.shipping-cost {
    font-size: 1.3rem;
    font-weight: 700;
    color: #2e7d32;
    margin-bottom: 15px;
}

.description {
    color: #666;
    line-height: 1.6;
}

/* Free Shipping Card */
.free-shipping-section {
    padding: 60px 20px;
    background: #f8f9fa;
}

.free-shipping-card {
    background: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
    color: white;
    border-radius: 16px;
    padding: 50px;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 40px;
    align-items: center;
}

.free-shipping-icon {
    font-size: 5rem;
}

.free-shipping-content h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.free-shipping-content p {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
    opacity: 0.95;
}

.benefits-list {
    list-style: none;
    padding: 0;
}

.benefits-list li {
    padding: 8px 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.benefits-list i {
    color: #ffd700;
}

/* Rates Table */
.shipping-rates-section {
    padding: 60px 20px;
}

.rates-table {
    overflow-x: auto;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    overflow: hidden;
}

thead {
    background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
    color: white;
}

th {
    padding: 20px;
    text-align: left;
    font-weight: 600;
    font-size: 1rem;
}

td {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

tr:hover {
    background: #f8f9fa;
}

.highlighted {
    background: #e8f5e9;
}

.premium-row {
    background: #fff8e1;
    font-weight: 600;
}

.rates-note {
    text-align: center;
    color: #666;
    font-size: 0.9rem;
    font-style: italic;
}

/* International */
.international-section {
    padding: 60px 20px;
    background: #f8f9fa;
}

.international-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.international-card {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
}

.international-card h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.international-card i {
    color: #4285f4;
}

.international-card p {
    color: #666;
    line-height: 1.6;
}

.international-card a {
    color: #4285f4;
    text-decoration: none;
    font-weight: 600;
}

.international-card a:hover {
    text-decoration: underline;
}

/* Tracking */
.tracking-section {
    padding: 60px 20px;
}

.tracking-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.tracking-text h2 {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #333;
}

.tracking-text p {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.tracking-features {
    list-style: none;
    padding: 0;
}

.tracking-features li {
    padding: 12px 0;
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 1rem;
    color: #333;
}

.tracking-features i {
    color: #4285f4;
    font-size: 1.2rem;
}

.tracking-form {
    background: #f8f9fa;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.tracking-form h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 25px;
    color: #333;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-group input {
    width: 100%;
    padding: 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #4285f4;
}

.btn {
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    width: 100%;
}

.btn-primary {
    background: linear-gradient(135deg, #4285f4, #1a73e8);
    color: white;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #3367d6, #1557b0);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(26, 115, 232, 0.3);
}

.btn-secondary {
    background: white;
    color: #4285f4;
    border: 2px solid #4285f4;
}

.btn-secondary:hover {
    background: #4285f4;
    color: white;
}

.form-note {
    margin-top: 15px;
    font-size: 0.9rem;
    color: #666;
    text-align: center;
}

.form-note a {
    color: #4285f4;
    text-decoration: none;
}

.form-note a:hover {
    text-decoration: underline;
}

/* FAQ */
.faq-section {
    padding: 60px 20px;
    background: #f8f9fa;
}

.faq-list {
    max-width: 900px;
    margin: 0 auto;
}

.faq-item {
    background: white;
    padding: 30px;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    border-left: 4px solid #4285f4;
}

.faq-item h4 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.faq-item p {
    color: #666;
    line-height: 1.6;
}

.faq-item a {
    color: #4285f4;
    text-decoration: none;
    font-weight: 600;
}

.faq-item a:hover {
    text-decoration: underline;
}

/* CTA Section */
.cta-section {
    padding: 80px 20px;
    background: linear-gradient(135deg, #4285f4 0%, #1a73e8 100%);
    color: white;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    opacity: 0.95;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 20px;
}

.cta-buttons .btn {
    width: auto;
    padding: 15px 40px;
}

.cta-buttons .btn-secondary {
    background: white;
    color: #4285f4;
    border: 2px solid white;
}

.cta-buttons .btn-secondary:hover {
    background: transparent;
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .shipping-hero h1 {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .options-grid,
    .international-grid {
        grid-template-columns: 1fr;
    }
    
    .free-shipping-card {
        grid-template-columns: 1fr;
        text-align: center;
        padding: 30px;
    }
    
    .tracking-content {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    
    table {
        font-size: 0.85rem;
    }
    
    th, td {
        padding: 12px 8px;
    }
}
</style>

<?php includeFooter(); ?>
