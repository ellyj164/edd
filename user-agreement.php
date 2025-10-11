<?php
/**
 * User Agreement Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'User Agreement';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">FezaMarket User Agreement</h1>
                    
                    <div class="agreement-content">
                        <section class="agreement-section">
                            <h2>Acceptance of Terms</h2>
                            <p>By accessing or using FezaMarket services, you agree to be bound by this User Agreement and all applicable laws and regulations.</p>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Account Registration</h2>
                            <p>To use certain features of our service, you must register for an account.</p>
                            <ul>
                                <li>You must provide accurate and complete information</li>
                                <li>You are responsible for maintaining account security</li>
                                <li>You must be at least 18 years old to create an account</li>
                                <li>One person or business entity may maintain only one account</li>
                            </ul>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Buying on FezaMarket</h2>
                            <p>When you purchase items through FezaMarket, you enter into a contract with the seller.</p>
                            <ul>
                                <li>All purchases are binding contracts</li>
                                <li>Payment is due immediately upon purchase</li>
                                <li>Shipping and handling charges may apply</li>
                                <li>Returns and refunds are subject to seller policies</li>
                            </ul>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Selling on FezaMarket</h2>
                            <p>Sellers must comply with all applicable laws and FezaMarket policies.</p>
                            <ul>
                                <li>Accurate item descriptions and photos required</li>
                                <li>Prompt shipping and communication expected</li>
                                <li>Compliance with prohibited items list</li>
                                <li>Payment of applicable fees and taxes</li>
                            </ul>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Prohibited Activities</h2>
                            <p>Users may not engage in activities that violate this agreement or applicable laws.</p>
                            <ul>
                                <li>Fraud, misrepresentation, or deceptive practices</li>
                                <li>Violation of intellectual property rights</li>
                                <li>Circumventing FezaMarket fees</li>
                                <li>Manipulating search results or feedback</li>
                                <li>Harassment or abusive behavior</li>
                            </ul>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Intellectual Property</h2>
                            <p>FezaMarket and its content are protected by copyright, trademark, and other intellectual property laws.</p>
                            <ul>
                                <li>FezaMarket retains ownership of platform content</li>
                                <li>Users retain rights to their own content</li>
                                <li>License granted to FezaMarket for platform operation</li>
                                <li>Respect for third-party intellectual property</li>
                            </ul>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Limitation of Liability</h2>
                            <p>FezaMarket's liability is limited to the maximum extent permitted by law.</p>
                            <ul>
                                <li>Platform provided "as is" without warranties</li>
                                <li>No liability for user-generated content</li>
                                <li>Limited liability for service interruptions</li>
                                <li>Exclusion of consequential damages</li>
                            </ul>
                        </section>
                        
                        <section class="agreement-section">
                            <h2>Dispute Resolution</h2>
                            <p>Disputes should be resolved through our resolution center first, followed by binding arbitration if necessary.</p>
                        </section>
                    </div>
                    
                    <div class="agreement-footer">
                        <p><strong>Effective Date:</strong> <?php echo date('F d, Y'); ?></p>
                        <p>This user agreement was last updated on <?php echo date('F d, Y'); ?>.</p>
                        <p>Questions about this agreement? <a href="/contact.php">Contact us</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>