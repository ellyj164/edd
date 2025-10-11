<?php
/**
 * Security Center
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Security Center - FezaMarket';
$metaDescription = 'Learn about our security measures and best practices to keep your account safe on FezaMarket.';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .security-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .security-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid #dc2626;
        }
        .security-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dc2626;
        }
        .alert-box {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 1rem;
            margin: 2rem auto;
            max-width: 800px;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="hero-section">
        <h1>Security Center</h1>
        <p>Your safety and security are our top priorities</p>
    </div>
    
    <div class="alert-box">
        <h3>🚨 Security Alert</h3>
        <p>Always verify URLs, never share your password, and report suspicious activity immediately. FezaMarket will never ask for your password via email or phone.</p>
    </div>
    
    <div class="security-grid">
        <div class="security-card">
            <div class="security-icon">🔐</div>
            <h3>Account Security</h3>
            <p>Use strong, unique passwords and enable two-factor authentication to protect your account.</p>
            <ul>
                <li>Enable 2FA authentication</li>
                <li>Use a password manager</li>
                <li>Regular password updates</li>
                <li>Monitor account activity</li>
            </ul>
        </div>
        
        <div class="security-card">
            <div class="security-icon">💳</div>
            <h3>Payment Security</h3>
            <p>Your payment information is encrypted and protected with industry-standard security measures.</p>
            <ul>
                <li>PCI DSS compliance</li>
                <li>SSL encryption</li>
                <li>Fraud detection systems</li>
                <li>Secure payment processing</li>
            </ul>
        </div>
        
        <div class="security-card">
            <div class="security-icon">🛡️</div>
            <h3>Data Protection</h3>
            <p>We protect your personal information with advanced security technologies and strict policies.</p>
            <ul>
                <li>Data encryption at rest</li>
                <li>Regular security audits</li>
                <li>GDPR compliance</li>
                <li>Limited data access</li>
            </ul>
        </div>
        
        <div class="security-card">
            <div class="security-icon">🕵️</div>
            <h3>Fraud Prevention</h3>
            <p>Our advanced systems detect and prevent fraudulent activities to keep our marketplace safe.</p>
            <ul>
                <li>AI-powered fraud detection</li>
                <li>Identity verification</li>
                <li>Transaction monitoring</li>
                <li>Seller screening</li>
            </ul>
        </div>
        
        <div class="security-card">
            <div class="security-icon">📞</div>
            <h3>Report Security Issues</h3>
            <p>If you encounter any security concerns, report them immediately to our security team.</p>
            <ul>
                <li>Security hotline: 1-800-SECURE</li>
                <li>Email: security@fezamarket.com</li>
                <li>24/7 monitoring</li>
                <li>Rapid response team</li>
            </ul>
        </div>
        
        <div class="security-card">
            <div class="security-icon">📚</div>
            <h3>Security Tips</h3>
            <p>Stay informed about the latest security best practices and threats.</p>
            <ul>
                <li>Phishing awareness</li>
                <li>Safe browsing habits</li>
                <li>Software updates</li>
                <li>Secure networks only</li>
            </ul>
        </div>
    </div>
    
    <div style="background: #f8fafc; padding: 4rem 2rem; text-align: center;">
        <h2>Security Certifications</h2>
        <p>FezaMarket is certified and compliant with industry security standards</p>
        <div style="display: flex; justify-content: center; gap: 2rem; margin: 2rem 0; flex-wrap: wrap;">
            <div style="padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h4>PCI DSS</h4>
                <p>Level 1 Certified</p>
            </div>
            <div style="padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h4>ISO 27001</h4>
                <p>Information Security</p>
            </div>
            <div style="padding: 1rem; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h4>GDPR</h4>
                <p>Privacy Compliant</p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>