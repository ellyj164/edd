<?php
/**
 * Money Back Guarantee Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'FezaMarket Money Back Guarantee';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">FezaMarket Money Back Guarantee</h1>
                    
                    <div class="guarantee-content">
                        <section class="guarantee-section">
                            <h2>Our Promise to You</h2>
                            <p>Shop with confidence on FezaMarket. Our Money Back Guarantee ensures you get what you ordered, or we'll make it right.</p>
                            <div class="guarantee-highlights">
                                <div class="highlight-item">
                                    <strong>✓ Get the item you ordered</strong>
                                    <p>Or get your money back</p>
                                </div>
                                <div class="highlight-item">
                                    <strong>✓ Free return shipping</strong>
                                    <p>On eligible items</p>
                                </div>
                                <div class="highlight-item">
                                    <strong>✓ 30-day protection</strong>
                                    <p>Report issues within 30 days</p>
                                </div>
                            </div>
                        </section>
                        
                        <section class="guarantee-section">
                            <h2>What's Covered</h2>
                            <ul>
                                <li><strong>Item not received:</strong> Your item didn't arrive within the expected timeframe</li>
                                <li><strong>Item not as described:</strong> The item is significantly different from the seller's description</li>
                                <li><strong>Damaged items:</strong> Item arrived damaged due to shipping</li>
                                <li><strong>Wrong item:</strong> You received a different item than what you ordered</li>
                            </ul>
                        </section>
                        
                        <section class="guarantee-section">
                            <h2>How to Report an Issue</h2>
                            <ol>
                                <li><strong>Contact the seller first:</strong> Try to resolve the issue directly with the seller</li>
                                <li><strong>Wait for a response:</strong> Give the seller 3 business days to respond</li>
                                <li><strong>Open a case:</strong> If unresolved, open a case in your account</li>
                                <li><strong>We'll step in:</strong> Our team will review and help resolve the issue</li>
                            </ol>
                        </section>
                        
                        <section class="guarantee-section">
                            <h2>Resolution Options</h2>
                            <p>Depending on your situation, we may offer:</p>
                            <ul>
                                <li><strong>Full refund:</strong> Get your money back including original shipping</li>
                                <li><strong>Partial refund:</strong> Keep the item and receive a partial refund</li>
                                <li><strong>Replacement:</strong> Receive a replacement item from the seller</li>
                                <li><strong>Return for refund:</strong> Return the item for a full refund</li>
                            </ul>
                        </section>
                        
                        <section class="guarantee-section">
                            <h2>Time Limits</h2>
                            <ul>
                                <li>Report issues within <strong>30 days</strong> of estimated delivery date</li>
                                <li>For items not received, wait until the latest estimated delivery date has passed</li>
                                <li>Digital items must be reported within 30 days of purchase</li>
                            </ul>
                        </section>
                        
                        <section class="guarantee-section">
                            <h2>Exclusions</h2>
                            <p>Our Money Back Guarantee doesn't cover:</p>
                            <ul>
                                <li>Items clearly described as damaged or defective</li>
                                <li>Changes of mind or buyer's remorse</li>
                                <li>Items damaged after delivery</li>
                                <li>Digital content after access has been provided</li>
                                <li>Custom or personalized items (unless defective)</li>
                            </ul>
                        </section>
                    </div>
                    
                    <div class="guarantee-footer">
                        <p><strong>Questions?</strong> <a href="/contact.php">Contact our customer service team</a> for help with any issues.</p>
                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>