<?php
/**
 * Start Selling - Seller Onboarding
 * E-Commerce Platform
 * This page now redirects to the appropriate URL based on user status
 */

require_once __DIR__ . '/includes/init.php';

// Redirect to the appropriate selling URL based on user status
$redirectUrl = getSellingUrl();

// If the redirect URL is not this page, redirect
if ($redirectUrl !== '/sell.php') {
    header('Location: ' . $redirectUrl);
    exit;
}

// If we reach here, it means there was an issue with the redirect logic
// or the user should see the sell.php page (fallback)
$vendor = new Vendor();
$isVendor = false;

// Check if user is already a vendor
if (Session::isLoggedIn()) {
    $existingVendor = $vendor->findByUserId(Session::getUserId());
    if ($existingVendor) {
        // User is a vendor but somehow didn't get redirected - redirect now
        redirect('/seller-center.php');
    }
}

$page_title = 'Start Selling on FezaMarket';
includeHeader($page_title);
?>

<div class="container">
    <!-- Selling Page Content for Non-Registered Users -->
    <div class="selling-hero">
        <div class="hero-content">
            <h1>Start selling on FezaMarket today</h1>
            <p class="hero-subtitle">Join millions of sellers reaching customers worldwide</p>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">190+</span>
                    <span class="stat-label">Markets worldwide</span>
                </div>
                <div class="stat">
                    <span class="stat-number">1.3B+</span>
                    <span class="stat-label">Active buyers</span>
                </div>
                <div class="stat">
                    <span class="stat-number">$87B+</span>
                    <span class="stat-label">Sold in 2023</span>
                </div>
            </div>
            
            <a href="<?php echo getSellingUrl(); ?>" class="btn btn-large cta-button">Get Started Now</a>
        </div>
        <div class="hero-image">
            <div class="selling-graphic">
                <div class="graphic-element">📦</div>
                <div class="graphic-element">🛒</div>
                <div class="graphic-element">💰</div>
                <div class="graphic-element">🌍</div>
            </div>
        </div>
    </div>

        <!-- Selling Benefits -->
        <section class="selling-benefits">
            <h2>Why sell on FezaMarket?</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">🌐</div>
                    <h3>Global Reach</h3>
                    <p>Access millions of buyers from around the world and expand your market beyond borders.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🛡️</div>
                    <h3>Secure Payments</h3>
                    <p>Get paid safely and on time with our secure payment processing and seller protection.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">📊</div>
                    <h3>Powerful Tools</h3>
                    <p>Use our advanced seller tools to manage inventory, track sales, and grow your business.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">🎯</div>
                    <h3>Marketing Support</h3>
                    <p>Promote your products with our advertising tools and reach the right customers.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">📞</div>
                    <h3>24/7 Support</h3>
                    <p>Get help when you need it with our dedicated seller support team.</p>
                </div>
                <div class="benefit-card">
                    <div class="benefit-icon">📈</div>
                    <h3>Growth Analytics</h3>
                    <p>Track your performance and optimize your listings with detailed analytics.</p>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="how-it-works">
            <h2>How selling works</h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Create your account</h3>
                        <p>Sign up for free and set up your seller profile in minutes.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>List your products</h3>
                        <p>Add photos and descriptions to create compelling product listings.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Start selling</h3>
                        <p>When items sell, we'll handle payment processing and provide shipping labels.</p>
                    </div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Get paid</h3>
                        <p>Receive payments securely and track your earnings in your seller dashboard.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Seller Types -->
        <section class="seller-types">
            <h2>Choose your selling path</h2>
            <div class="seller-types-grid">
                <div class="seller-type-card">
                    <div class="card-header">
                        <h3>Individual Seller</h3>
                        <div class="price">Free</div>
                    </div>
                    <div class="card-content">
                        <ul class="features-list">
                            <li>✓ List up to 50 items per month</li>
                            <li>✓ Basic seller tools</li>
                            <li>✓ Access to FezaMarket marketplace</li>
                            <li>✓ Seller support</li>
                            <li>✓ Payment processing</li>
                        </ul>
                        <p class="seller-fee">3.5% selling fee per transaction</p>
                        <a href="<?php echo getSellingUrl(); ?>" class="btn btn-outline">Start as Individual</a>
                    </div>
                </div>
                
                <div class="seller-type-card featured">
                    <div class="card-header">
                        <div class="popular-badge">Most Popular</div>
                        <h3>Business Seller</h3>
                        <div class="price">$29.99<span>/month</span></div>
                    </div>
                    <div class="card-content">
                        <ul class="features-list">
                            <li>✓ Unlimited listings</li>
                            <li>✓ Advanced seller tools</li>
                            <li>✓ Bulk listing tools</li>
                            <li>✓ Priority support</li>
                            <li>✓ Analytics dashboard</li>
                            <li>✓ Promoted listings</li>
                            <li>✓ Multi-channel selling</li>
                        </ul>
                        <p class="seller-fee">2.5% selling fee per transaction</p>
                        <a href="<?php echo getSellingUrl(); ?>" class="btn">Start as Business</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Success Stories -->
        <section class="success-stories">
            <h2>Success stories from our sellers</h2>
            <div class="stories-grid">
                <div class="story-card">
                    <div class="story-image">👩‍💼</div>
                    <div class="story-content">
                        <blockquote>"I started selling on FezaMarket as a side hustle. Now it's my full-time business with over $100k in annual sales."</blockquote>
                        <div class="story-author">
                            <strong>Sarah M.</strong>
                            <span>Handmade Jewelry Seller</span>
                        </div>
                    </div>
                </div>
                <div class="story-card">
                    <div class="story-image">👨‍💻</div>
                    <div class="story-content">
                        <blockquote>"The seller tools are incredible. I can manage my entire inventory and see exactly what's working."</blockquote>
                        <div class="story-author">
                            <strong>Mike T.</strong>
                            <span>Electronics Retailer</span>
                        </div>
                    </div>
                </div>
                <div class="story-card">
                    <div class="story-image">👩‍🏭</div>
                    <div class="story-content">
                        <blockquote>"FezaMarket helped us reach customers worldwide. Our sales tripled in the first year."</blockquote>
                        <div class="story-author">
                            <strong>Lisa K.</strong>
                            <span>Small Business Owner</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section">
            <h2>Frequently asked questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h3>How much does it cost to sell?</h3>
                    <p>Individual sellers pay no monthly fee and only a 3.5% transaction fee. Business sellers pay $29.99/month plus a 2.5% transaction fee.</p>
                </div>
                <div class="faq-item">
                    <h3>When do I get paid?</h3>
                    <p>Payments are processed within 24-48 hours after the buyer confirms receipt or after the delivery confirmation.</p>
                </div>
                <div class="faq-item">
                    <h3>What can I sell?</h3>
                    <p>You can sell almost anything legal, from handmade crafts to vintage items to new products. Check our prohibited items policy for details.</p>
                </div>
                <div class="faq-item">
                    <h3>Do you provide shipping labels?</h3>
                    <p>Yes! We provide discounted shipping labels and can handle fulfillment for qualifying sellers.</p>
                </div>
                <div class="faq-item">
                    <h3>How do returns work?</h3>
                    <p>We provide a streamlined returns process and seller protection for eligible transactions.</p>
                </div>
                <div class="faq-item">
                    <h3>Can I sell internationally?</h3>
                    <p>Yes! You can sell to buyers in 190+ countries with our global shipping program.</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="final-cta">
            <div class="cta-content">
                <h2>Ready to start your selling journey?</h2>
                <p>Join thousands of successful sellers on FezaMarket</p>
                <div class="cta-buttons">
                    <a href="<?php echo getSellingUrl(); ?>" class="btn btn-large">Start Selling Today</a>
                    <a href="/login.php?redirect=<?php echo urlencode(getSellingUrl()); ?>" class="btn btn-outline">Already have an account?</a>
                </div>
            </div>
        </section>
    </div>

<style>
.seller-dashboard-preview {
    text-align: center;
    padding: 60px 0;
}

.seller-dashboard-preview h1 {
    font-size: 36px;
    color: #1f2937;
    margin-bottom: 15px;
}

.dashboard-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.selling-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    padding: 60px 0;
    margin-bottom: 80px;
}

.hero-content h1 {
    font-size: 42px;
    color: #1f2937;
    margin-bottom: 15px;
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 20px;
    color: #6b7280;
    margin-bottom: 40px;
}

.hero-stats {
    display: flex;
    gap: 40px;
    margin-bottom: 40px;
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: bold;
    color: #0654ba;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
}

.cta-button {
    font-size: 18px !important;
    padding: 15px 30px !important;
    background: #0654ba !important;
}

.selling-graphic {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    max-width: 300px;
    margin: 0 auto;
}

.graphic-element {
    background: #f3f4f6;
    padding: 40px;
    border-radius: 12px;
    font-size: 48px;
    text-align: center;
    transition: transform 0.3s ease;
}

.graphic-element:hover {
    transform: scale(1.1);
}

.selling-benefits {
    margin-bottom: 80px;
}

.selling-benefits h2 {
    text-align: center;
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 50px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.benefit-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.benefit-card:hover {
    transform: translateY(-5px);
}

.benefit-icon {
    font-size: 48px;
    margin-bottom: 20px;
}

.benefit-card h3 {
    color: #1f2937;
    margin-bottom: 15px;
    font-size: 20px;
}

.benefit-card p {
    color: #6b7280;
    line-height: 1.6;
}

.how-it-works {
    margin-bottom: 80px;
    background: #f9fafb;
    padding: 60px 0;
    border-radius: 12px;
}

.how-it-works h2 {
    text-align: center;
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 50px;
}

.steps-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 40px;
    max-width: 1000px;
    margin: 0 auto;
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
    font-size: 18px;
}

.step p {
    color: #6b7280;
    line-height: 1.6;
}

.seller-types {
    margin-bottom: 80px;
}

.seller-types h2 {
    text-align: center;
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 50px;
}

.seller-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    max-width: 800px;
    margin: 0 auto;
}

.seller-type-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    position: relative;
}

.seller-type-card.featured {
    border: 2px solid #0654ba;
    transform: scale(1.05);
}

.popular-badge {
    background: #0654ba;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
}

.card-header {
    background: #f9fafb;
    padding: 30px;
    text-align: center;
    position: relative;
}

.card-header h3 {
    color: #1f2937;
    margin-bottom: 10px;
    font-size: 24px;
}

.price {
    font-size: 36px;
    font-weight: bold;
    color: #0654ba;
}

.price span {
    font-size: 16px;
    color: #6b7280;
}

.card-content {
    padding: 30px;
}

.features-list {
    list-style: none;
    padding: 0;
    margin-bottom: 20px;
}

.features-list li {
    padding: 8px 0;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}

.features-list li:last-child {
    border-bottom: none;
}

.seller-fee {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 20px;
    text-align: center;
    font-style: italic;
}

.success-stories {
    margin-bottom: 80px;
}

.success-stories h2 {
    text-align: center;
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 50px;
}

.stories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.story-card {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.story-image {
    font-size: 48px;
    text-align: center;
    margin-bottom: 20px;
}

.story-content blockquote {
    color: #374151;
    font-style: italic;
    margin-bottom: 20px;
    font-size: 16px;
    line-height: 1.6;
}

.story-author strong {
    color: #1f2937;
    display: block;
}

.story-author span {
    color: #6b7280;
    font-size: 14px;
}

.faq-section {
    margin-bottom: 80px;
    background: #f9fafb;
    padding: 60px 0;
    border-radius: 12px;
}

.faq-section h2 {
    text-align: center;
    font-size: 32px;
    color: #1f2937;
    margin-bottom: 50px;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}

.faq-item {
    background: white;
    padding: 25px;
    border-radius: 8px;
}

.faq-item h3 {
    color: #1f2937;
    margin-bottom: 10px;
    font-size: 18px;
}

.faq-item p {
    color: #6b7280;
    line-height: 1.6;
}

.final-cta {
    background: linear-gradient(135deg, #0654ba, #1e40af);
    color: white;
    padding: 60px 0;
    text-align: center;
    border-radius: 12px;
    margin-bottom: 40px;
}

.cta-content h2 {
    font-size: 36px;
    margin-bottom: 15px;
}

.cta-content p {
    font-size: 18px;
    margin-bottom: 30px;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
}

@media (max-width: 768px) {
    .selling-hero {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 40px;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 20px;
    }
    
    .steps-container {
        grid-template-columns: 1fr;
    }
    
    .seller-types-grid {
        grid-template-columns: 1fr;
    }
    
    .seller-type-card.featured {
        transform: none;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<?php includeFooter(); ?>