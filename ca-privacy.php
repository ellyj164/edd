<?php
/**
 * California Privacy Notice Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'California Privacy Notice';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">California Privacy Notice</h1>
                    
                    <div class="page-content">
                        <p>Privacy rights for California residents under CCPA.</p>
                        
                        <section>
                            <h2>Overview</h2>
                            <p>This page contains important information about our California Privacy Notice. We are committed to transparency and providing clear information to our users.</p>
                        </section>
                        
                        <section>
                            <h2>Key Points</h2>
                            <ul>
                                <li>Clear and comprehensive information</li>
                                <li>Regular updates to reflect current practices</li>
                                <li>User-friendly explanations</li>
                                <li>Compliance with applicable regulations</li>
                            </ul>
                        </section>
                        
                        <section>
                            <h2>Contact Information</h2>
                            <p>If you have questions about this content, please <a href="/contact.php">contact us</a> for more information.</p>
                        </section>
                    </div>
                    
                    <div class="page-footer">
                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>