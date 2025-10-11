<?php
/**
 * Privacy Policy Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Privacy Policy';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">Privacy Policy</h1>
                    
                    <div class="privacy-content">
                        <section class="privacy-section">
                            <h2>Information We Collect</h2>
                            <p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support.</p>
                            <ul>
                                <li>Personal information (name, email, address)</li>
                                <li>Payment information</li>
                                <li>Communication preferences</li>
                                <li>Transaction history</li>
                            </ul>
                        </section>
                        
                        <section class="privacy-section">
                            <h2>How We Use Your Information</h2>
                            <p>We use the information we collect to provide, maintain, and improve our services.</p>
                            <ul>
                                <li>Process transactions and payments</li>
                                <li>Send order confirmations and updates</li>
                                <li>Provide customer support</li>
                                <li>Improve our platform and services</li>
                                <li>Send promotional communications (with your consent)</li>
                            </ul>
                        </section>
                        
                        <section class="privacy-section">
                            <h2>Information Sharing</h2>
                            <p>We do not sell, trade, or rent your personal information to third parties without your explicit consent.</p>
                            <ul>
                                <li>Service providers (payment processors, shipping companies)</li>
                                <li>Legal compliance (when required by law)</li>
                                <li>Business transfers (mergers, acquisitions)</li>
                                <li>With your consent for specific purposes</li>
                            </ul>
                        </section>
                        
                        <section class="privacy-section">
                            <h2>Data Security</h2>
                            <p>We implement appropriate security measures to protect your personal information.</p>
                            <ul>
                                <li>Encryption of sensitive data</li>
                                <li>Secure payment processing</li>
                                <li>Regular security audits</li>
                                <li>Employee training on data protection</li>
                            </ul>
                        </section>
                        
                        <section class="privacy-section">
                            <h2>Your Rights</h2>
                            <p>You have certain rights regarding your personal information.</p>
                            <ul>
                                <li>Access your personal data</li>
                                <li>Correct inaccurate information</li>
                                <li>Delete your account and data</li>
                                <li>Opt-out of marketing communications</li>
                                <li>Data portability</li>
                            </ul>
                        </section>
                        
                        <section class="privacy-section">
                            <h2>Cookies and Tracking</h2>
                            <p>We use cookies and similar technologies to enhance your experience on our platform.</p>
                            <ul>
                                <li>Essential cookies for functionality</li>
                                <li>Analytics cookies to improve our service</li>
                                <li>Marketing cookies for personalized ads</li>
                                <li>You can manage cookie preferences in your browser</li>
                            </ul>
                        </section>
                    </div>
                    
                    <div class="policy-footer">
                        <p><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
                        <p>This privacy policy was last updated on <?php echo date('F d, Y'); ?>.</p>
                        <p>For privacy-related questions, email us at privacy@fezamarket.com or <a href="/contact.php">contact us</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>