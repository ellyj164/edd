<?php
/**
 * Accessibility Statement Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Accessibility';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">Accessibility Statement</h1>
                    
                    <div class="accessibility-content">
                        <section class="accessibility-section">
                            <h2>Our Commitment to Accessibility</h2>
                            <p>FezaMarket is committed to ensuring digital accessibility for people with disabilities. We are continually improving the user experience for everyone and applying the relevant accessibility standards.</p>
                        </section>
                        
                        <section class="accessibility-section">
                            <h2>Measures to Support Accessibility</h2>
                            <p>FezaMarket takes the following measures to ensure accessibility:</p>
                            <ul>
                                <li>Include accessibility as part of our mission statement</li>
                                <li>Integrate accessibility into our procurement practices</li>
                                <li>Provide continual accessibility training for our staff</li>
                                <li>Include people with disabilities in our design personas</li>
                            </ul>
                        </section>
                        
                        <section class="accessibility-section">
                            <h2>Conformance Status</h2>
                            <p>The Web Content Accessibility Guidelines (WCAG) defines requirements for designers and developers to improve accessibility for people with disabilities. It defines three levels of conformance: Level A, Level AA, and Level AAA.</p>
                            <p>FezaMarket is partially conformant with WCAG 2.1 level AA. Partially conformant means that some parts of the content do not fully conform to the accessibility standard.</p>
                        </section>
                        
                        <section class="accessibility-section">
                            <h2>Feedback</h2>
                            <p>We welcome your feedback on the accessibility of FezaMarket. Please let us know if you encounter accessibility barriers:</p>
                            <ul>
                                <li>Email: accessibility@fezamarket.com</li>
                                <li>Phone: 1-800-FEZAMARKET</li>
                                <li><a href="/contact.php">Contact form</a></li>
                            </ul>
                        </section>
                        
                        <section class="accessibility-section">
                            <h2>Technical Specifications</h2>
                            <p>Accessibility of FezaMarket relies on the following technologies to work with the particular combination of web browser and any assistive technologies or plugins installed on your computer:</p>
                            <ul>
                                <li>HTML</li>
                                <li>WAI-ARIA</li>
                                <li>CSS</li>
                                <li>JavaScript</li>
                            </ul>
                        </section>
                    </div>
                    
                    <div class="accessibility-footer">
                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                        <p>This statement was created on <?php echo date('F d, Y'); ?>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>