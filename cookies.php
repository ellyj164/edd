<?php
/**
 * Cookie Policy Page
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$page_title = 'Cookie Policy';
includeHeader($page_title);
?>

<div class="container">
    <div class="row justify-center">
        <div class="col-10">
            <div class="card mt-4">
                <div class="card-body">
                    <h1 class="card-title">Cookie Policy</h1>
                    
                    <div class="cookie-content">
                        <section class="cookie-section">
                            <h2>What Are Cookies?</h2>
                            <p>Cookies are small text files that are stored on your computer or mobile device when you visit a website. They help us provide you with a better experience by remembering your preferences and improving site performance.</p>
                        </section>
                        
                        <section class="cookie-section">
                            <h2>How We Use Cookies</h2>
                            <p>FezaMarket uses cookies for several purposes:</p>
                            <ul>
                                <li><strong>Essential Cookies:</strong> Required for basic site functionality</li>
                                <li><strong>Performance Cookies:</strong> Help us understand how visitors use our site</li>
                                <li><strong>Functionality Cookies:</strong> Remember your preferences and settings</li>
                                <li><strong>Marketing Cookies:</strong> Deliver personalized advertisements</li>
                            </ul>
                        </section>
                        
                        <section class="cookie-section">
                            <h2>Types of Cookies We Use</h2>
                            <div class="cookie-types">
                                <h3>Essential Cookies</h3>
                                <p>These cookies are necessary for the website to function properly. They enable core functionality such as security, network management, and accessibility.</p>
                                
                                <h3>Analytics Cookies</h3>
                                <p>These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.</p>
                                
                                <h3>Preference Cookies</h3>
                                <p>These cookies allow the website to remember information that changes the way it behaves or looks, such as your preferred language or region.</p>
                                
                                <h3>Marketing Cookies</h3>
                                <p>These cookies track visitors across websites to display relevant and engaging advertisements for individual users.</p>
                            </div>
                        </section>
                        
                        <section class="cookie-section">
                            <h2>Managing Cookies</h2>
                            <p>You have the right to decide whether to accept or reject cookies. You can manage your cookie preferences through:</p>
                            <ul>
                                <li>Your web browser settings</li>
                                <li>Our cookie preference center (coming soon)</li>
                                <li>Third-party opt-out tools</li>
                            </ul>
                            
                            <h3>Browser Settings</h3>
                            <p>Most web browsers allow you to control cookies through their settings. To find out more about cookies, including how to see what cookies have been set, visit <a href="https://www.aboutcookies.org" target="_blank" rel="noopener">www.aboutcookies.org</a>.</p>
                        </section>
                        
                        <section class="cookie-section">
                            <h2>Third-Party Cookies</h2>
                            <p>Some cookies on our site are set by third-party services that appear on our pages. We have no control over these cookies, and they are set directly by the providers of those services.</p>
                        </section>
                    </div>
                    
                    <div class="cookie-footer">
                        <p><strong>Last Updated:</strong> <?php echo date('F d, Y'); ?></p>
                        <p>If you have questions about our use of cookies, please <a href="/contact.php">contact us</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php includeFooter(); ?>