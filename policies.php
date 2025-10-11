<?php
/**
 * Policies Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Policies';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">FezaMarket Policies</h1>
                    
                    <div class="policies-content">
                        <section class="policy-section">
                            <h2>User Agreement</h2>
                            <p>By using FezaMarket, you agree to our terms and conditions. These policies govern your use of our platform and services.</p>
                            <ul>
                                <li>Account registration and security</li>
                                <li>Buying and selling guidelines</li>
                                <li>Payment processing rules</li>
                                <li>Dispute resolution procedures</li>
                            </ul>
                        </section>
                        
                        <section class="policy-section">
                            <h2>Privacy Policy</h2>
                            <p>We are committed to protecting your privacy and personal information. Our privacy policy explains how we collect, use, and protect your data.</p>
                            <ul>
                                <li>Data collection practices</li>
                                <li>Information sharing policies</li>
                                <li>Cookie usage</li>
                                <li>Your privacy rights</li>
                            </ul>
                        </section>
                        
                        <section class="policy-section">
                            <h2>Prohibited and Restricted Items</h2>
                            <p>Certain items are prohibited or restricted on FezaMarket to ensure safety and compliance with laws.</p>
                            <ul>
                                <li>Counterfeit goods</li>
                                <li>Hazardous materials</li>
                                <li>Adult content</li>
                                <li>Weapons and firearms</li>
                                <li>Intellectual property violations</li>
                            </ul>
                        </section>
                        
                        <section class="policy-section">
                            <h2>Payment and Billing</h2>
                            <p>Understanding our payment policies helps ensure smooth transactions.</p>
                            <ul>
                                <li>Accepted payment methods</li>
                                <li>Transaction fees</li>
                                <li>Refund procedures</li>
                                <li>Billing dispute resolution</li>
                            </ul>
                        </section>
                        
                        <section class="policy-section">
                            <h2>Seller Policies</h2>
                            <p>Guidelines for sellers to maintain high standards and trust on our platform.</p>
                            <ul>
                                <li>Listing requirements</li>
                                <li>Shipping obligations</li>
                                <li>Customer service standards</li>
                                <li>Performance metrics</li>
                            </ul>
                        </section>
                    </div>
                    
                    <div class="policy-footer">
                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                        <p>For questions about these policies, please <a href="/contact.php">contact us</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>