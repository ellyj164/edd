<?php
/**
 * FezaMarket Membership Program
 * Premium membership with exclusive benefits and rewards
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'FezaMarket Membership - Premium Benefits & Rewards';
$meta_description = 'Join FezaMarket Premium membership for exclusive benefits, cashback rewards, free shipping, and special deals. Save more on every purchase!';

includeHeader($page_title);
?>

<div class="membership-page">
    <!-- Hero Section -->
    <section class="membership-hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>FezaMarket <span class="premium-badge">Premium</span></h1>
                    <p class="hero-subtitle">Save more, earn more, get exclusive benefits</p>
                    <div class="hero-features">
                        <div class="feature-item">
                            <i class="fas fa-shipping-fast"></i>
                            <span>Free 2-day shipping</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-percentage"></i>
                            <span>5% cashback</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <span>Exclusive deals</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-headset"></i>
                            <span>Priority support</span>
                        </div>
                    </div>
                    <div class="cta-buttons">
                        <a href="/register.php?membership=premium" class="btn btn-primary btn-large">Join Premium - $9.99/month</a>
                        <a href="#plans" class="btn btn-secondary btn-large">View All Plans</a>
                    </div>
                    <p class="trial-info">✨ Start with a 30-day free trial • Cancel anytime</p>
                </div>
                <div class="hero-image">
                    <div class="membership-card-preview">
                        <div class="card-front">
                            <div class="card-logo">FezaMarket Premium</div>
                            <div class="card-chip"></div>
                            <div class="card-number">•••• •••• •••• 5678</div>
                            <div class="card-holder">PREMIUM MEMBER</div>
                            <div class="card-badge">★</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Overview -->
    <section class="benefits-section">
        <div class="container">
            <h2 class="section-title">Premium Membership Benefits</h2>
            <p class="section-subtitle">Everything you need to shop smarter and save more</p>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3>Free 2-Day Shipping</h3>
                    <p>Get unlimited free 2-day shipping on millions of eligible items. No minimum order required.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3>5% Cashback Rewards</h3>
                    <p>Earn 5% cashback on all FezaMarket purchases. Plus, get additional rewards at partner stores.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Exclusive Deals</h3>
                    <p>Access member-only deals, early bird sales, and special pricing on thousands of products daily.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3>Early Access to Sales</h3>
                    <p>Shop major sales events 24 hours before everyone else. Never miss out on limited inventory.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Extended Returns</h3>
                    <p>Enjoy extended 90-day returns on all purchases, with free return shipping on most items.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-headphones-alt"></i>
                    </div>
                    <h3>Priority Customer Support</h3>
                    <p>Get 24/7 priority support with dedicated phone line and instant chat assistance.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <h3>Birthday Bonus</h3>
                    <p>Receive a special $20 birthday credit to celebrate your special day every year.</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Mobile App Perks</h3>
                    <p>Exclusive app-only deals, price alerts, and one-tap checkout for faster shopping.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Membership Plans -->
    <section class="plans-section" id="plans">
        <div class="container">
            <h2 class="section-title">Choose Your Plan</h2>
            <p class="section-subtitle">Flexible membership options to fit your lifestyle</p>
            
            <div class="plans-grid">
                <!-- Monthly Plan -->
                <div class="plan-card">
                    <div class="plan-header">
                        <h3>Premium Monthly</h3>
                        <div class="plan-price">
                            <span class="price-amount">$9.99</span>
                            <span class="price-period">/month</span>
                        </div>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Free 2-day shipping</li>
                        <li><i class="fas fa-check"></i> 5% cashback rewards</li>
                        <li><i class="fas fa-check"></i> Exclusive deals</li>
                        <li><i class="fas fa-check"></i> Early access to sales</li>
                        <li><i class="fas fa-check"></i> Extended returns (90 days)</li>
                        <li><i class="fas fa-check"></i> Priority support</li>
                        <li><i class="fas fa-check"></i> Mobile app perks</li>
                    </ul>
                    <a href="/register.php?plan=monthly" class="btn btn-outline">Start Free Trial</a>
                    <p class="plan-note">30-day free trial • Cancel anytime</p>
                </div>
                
                <!-- Annual Plan (Most Popular) -->
                <div class="plan-card featured">
                    <div class="popular-badge">Most Popular</div>
                    <div class="plan-header">
                        <h3>Premium Annual</h3>
                        <div class="plan-price">
                            <span class="price-amount">$99</span>
                            <span class="price-period">/year</span>
                        </div>
                        <div class="plan-savings">Save $20 vs. monthly</div>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Everything in Monthly</li>
                        <li><i class="fas fa-star"></i> <strong>10% cashback</strong> (double rewards)</li>
                        <li><i class="fas fa-star"></i> <strong>$50 welcome bonus</strong></li>
                        <li><i class="fas fa-star"></i> <strong>Birthday $50 credit</strong></li>
                        <li><i class="fas fa-star"></i> <strong>Exclusive annual events</strong></li>
                        <li><i class="fas fa-star"></i> <strong>Partner discounts</strong></li>
                        <li><i class="fas fa-star"></i> <strong>Free gift wrapping</strong></li>
                    </ul>
                    <a href="/register.php?plan=annual" class="btn btn-primary">Start Free Trial</a>
                    <p class="plan-note">30-day free trial • Best value guarantee</p>
                </div>
                
                <!-- Family Plan -->
                <div class="plan-card">
                    <div class="plan-header">
                        <h3>Premium Family</h3>
                        <div class="plan-price">
                            <span class="price-amount">$149</span>
                            <span class="price-period">/year</span>
                        </div>
                        <div class="plan-savings">Up to 5 family members</div>
                    </div>
                    <ul class="plan-features">
                        <li><i class="fas fa-check"></i> Everything in Annual</li>
                        <li><i class="fas fa-users"></i> <strong>5 member accounts</strong></li>
                        <li><i class="fas fa-users"></i> <strong>Shared rewards pool</strong></li>
                        <li><i class="fas fa-users"></i> <strong>Family calendar</strong></li>
                        <li><i class="fas fa-users"></i> <strong>Parental controls</strong></li>
                        <li><i class="fas fa-users"></i> <strong>Group gift lists</strong></li>
                        <li><i class="fas fa-users"></i> <strong>Volume discounts</strong></li>
                    </ul>
                    <a href="/register.php?plan=family" class="btn btn-outline">Start Free Trial</a>
                    <p class="plan-note">30-day free trial • Best for families</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Partner Benefits -->
    <section class="partners-section">
        <div class="container">
            <h2 class="section-title">Exclusive Partner Benefits</h2>
            <p class="section-subtitle">Get special discounts at top brands and restaurants</p>
            
            <div class="partners-grid">
                <div class="partner-card">
                    <div class="partner-logo">
                        <i class="fas fa-hamburger"></i>
                    </div>
                    <h4>Burger King</h4>
                    <p>25% off all orders</p>
                </div>
                
                <div class="partner-card">
                    <div class="partner-logo">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h4>Starbucks</h4>
                    <p>10% off + bonus stars</p>
                </div>
                
                <div class="partner-card">
                    <div class="partner-logo">
                        <i class="fas fa-film"></i>
                    </div>
                    <h4>Movie Theaters</h4>
                    <p>$5 off tickets</p>
                </div>
                
                <div class="partner-card">
                    <div class="partner-logo">
                        <i class="fas fa-gas-pump"></i>
                    </div>
                    <h4>Gas Stations</h4>
                    <p>5¢/gallon savings</p>
                </div>
                
                <div class="partner-card">
                    <div class="partner-logo">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h4>Gyms & Fitness</h4>
                    <p>15% off memberships</p>
                </div>
                
                <div class="partner-card">
                    <div class="partner-logo">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h4>Retail Stores</h4>
                    <p>Additional discounts</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <h4>How does the free trial work?</h4>
                    <p>Start with a 30-day free trial of FezaMarket Premium. Enjoy all membership benefits at no cost. If you don't cancel before the trial ends, you'll be automatically charged for your selected plan.</p>
                </div>
                
                <div class="faq-item">
                    <h4>Can I cancel anytime?</h4>
                    <p>Yes! You can cancel your membership at any time with no penalties or fees. Simply go to your account settings and select "Cancel Membership." You'll continue to have access until the end of your billing period.</p>
                </div>
                
                <div class="faq-item">
                    <h4>How do I earn and use cashback?</h4>
                    <p>Cashback is automatically earned on eligible purchases and added to your FezaMarket wallet. You can use it for future purchases, transfer to your bank account, or donate to charity.</p>
                </div>
                
                <div class="faq-item">
                    <h4>Is there a minimum spend for free shipping?</h4>
                    <p>No! Premium members get unlimited free 2-day shipping on millions of eligible items with no minimum order amount. Shop as often as you like!</p>
                </div>
                
                <div class="faq-item">
                    <h4>Can I share my membership?</h4>
                    <p>Monthly and Annual plans are for individual use. If you want to share benefits with family members, upgrade to our Family plan which supports up to 5 member accounts.</p>
                </div>
                
                <div class="faq-item">
                    <h4>What happens to my rewards if I cancel?</h4>
                    <p>Your earned cashback and rewards remain in your account even after cancellation. You have 12 months to use them before they expire.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="final-cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Saving?</h2>
                <p>Join millions of satisfied Premium members and unlock exclusive benefits today!</p>
                <a href="/register.php?membership=premium" class="btn btn-primary btn-xl">Start Your Free Trial</a>
                <p class="cta-note">No commitment • Cancel anytime • 30-day free trial</p>
            </div>
        </div>
    </section>
</div>

<style>
/* Membership Page Styles */
.membership-page {
    background-color: #ffffff;
}

/* Hero Section */
.membership-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 20px;
    margin-bottom: 60px;
}

.hero-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

.hero-text h1 {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 15px;
}

.premium-badge {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #333;
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 1.5rem;
    font-weight: 700;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
}

.hero-subtitle {
    font-size: 1.5rem;
    margin-bottom: 2rem;
    opacity: 0.95;
}

.hero-features {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 2rem;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.1rem;
}

.feature-item i {
    font-size: 1.3rem;
    color: #ffd700;
}

.cta-buttons {
    display: flex;
    gap: 15px;
    margin-bottom: 1rem;
}

.btn {
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    cursor: pointer;
}

.btn-primary {
    background: linear-gradient(135deg, #4285f4, #1a73e8);
    color: white;
    border-color: #1a73e8;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #3367d6, #1557b0);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(26, 115, 232, 0.3);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border-color: white;
}

.btn-secondary:hover {
    background: transparent;
    color: white;
    border-color: white;
}

.btn-outline {
    background: transparent;
    color: #0654ba;
    border-color: #0654ba;
}

.btn-outline:hover {
    background: #0654ba;
    color: white;
}

.btn-large {
    padding: 15px 30px;
    font-size: 1.1rem;
}

.btn-xl {
    padding: 20px 50px;
    font-size: 1.3rem;
}

.trial-info {
    font-size: 1rem;
    opacity: 0.9;
}

/* Membership Card Preview */
.membership-card-preview {
    perspective: 1000px;
}

.card-front {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    position: relative;
    height: 250px;
    color: white;
}

.card-logo {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 30px;
}

.card-chip {
    width: 50px;
    height: 40px;
    background: linear-gradient(135deg, #d4af37, #ffdf00);
    border-radius: 8px;
    margin-bottom: 30px;
}

.card-number {
    font-size: 1.5rem;
    letter-spacing: 3px;
    margin-bottom: 20px;
    font-family: 'Courier New', monospace;
}

.card-holder {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 2px;
}

.card-badge {
    position: absolute;
    top: 30px;
    right: 30px;
    font-size: 3rem;
    color: #ffd700;
}

/* Benefits Section */
.benefits-section {
    padding: 60px 20px;
    background: #f8f9fa;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    text-align: center;
    margin-bottom: 1rem;
    color: #333;
}

.section-subtitle {
    font-size: 1.2rem;
    text-align: center;
    color: #666;
    margin-bottom: 3rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
}

.benefit-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    text-align: center;
    transition: all 0.3s ease;
}

.benefit-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.benefit-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2rem;
    color: white;
}

.benefit-card h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.benefit-card p {
    color: #666;
    line-height: 1.6;
    font-size: 1rem;
}

/* Plans Section */
.plans-section {
    padding: 60px 20px;
    background: white;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.plan-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 16px;
    padding: 40px 30px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.plan-card:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
}

.plan-card.featured {
    border-color: #667eea;
    border-width: 3px;
    box-shadow: 0 10px 40px rgba(102, 126, 234, 0.15);
}

.popular-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    color: #333;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
}

.plan-header h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
    color: #333;
}

.plan-price {
    margin-bottom: 10px;
}

.price-amount {
    font-size: 3rem;
    font-weight: 700;
    color: #667eea;
}

.price-period {
    font-size: 1.2rem;
    color: #666;
}

.plan-savings {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    display: inline-block;
    margin-bottom: 20px;
}

.plan-features {
    list-style: none;
    padding: 0;
    margin: 30px 0;
    text-align: left;
}

.plan-features li {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.plan-features li:last-child {
    border-bottom: none;
}

.plan-features i {
    color: #4caf50;
    font-size: 1.1rem;
}

.plan-features i.fa-star {
    color: #ffd700;
}

.plan-note {
    margin-top: 15px;
    font-size: 0.9rem;
    color: #666;
}

/* Partners Section */
.partners-section {
    padding: 60px 20px;
    background: #f8f9fa;
}

.partners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.partner-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    text-align: center;
    transition: all 0.3s ease;
}

.partner-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
}

.partner-logo {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 1.8rem;
    color: white;
}

.partner-card h4 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.partner-card p {
    color: #4caf50;
    font-weight: 600;
}

/* FAQ Section */
.faq-section {
    padding: 60px 20px;
    background: white;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
}

.faq-item {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
    border-left: 4px solid #667eea;
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

/* Final CTA */
.final-cta-section {
    padding: 80px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.cta-note {
    font-size: 0.9rem;
    margin-top: 1rem;
    opacity: 0.9;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .hero-text h1 {
        font-size: 2rem;
    }
    
    .premium-badge {
        font-size: 1.2rem;
    }
    
    .hero-features {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
    }
    
    .membership-card-preview {
        display: none;
    }
    
    .section-title {
        font-size: 2rem;
    }
    
    .benefits-grid,
    .plans-grid,
    .partners-grid,
    .faq-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php includeFooter(); ?>
