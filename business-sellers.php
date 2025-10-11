<?php
/**
 * Business Sellers Program
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Business Sellers Program - FezaMarket';
$metaDescription = 'Join our Business Sellers Program for enhanced selling features, bulk tools, and dedicated support.';

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .feature-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .cta-section {
            background: #f8fafc;
            padding: 4rem 2rem;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-block;
            margin: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="hero-section">
        <h1>Business Sellers Program</h1>
        <p>Scale your business with advanced selling tools and dedicated support</p>
        <a href="/seller-register.php?type=business" class="btn-primary">Get Started</a>
    </div>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Advanced Analytics</h3>
            <p>Get detailed insights into your sales performance, customer behavior, and market trends.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🛠️</div>
            <h3>Bulk Management Tools</h3>
            <p>Upload and manage thousands of products with our powerful bulk editing tools.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Priority Support</h3>
            <p>Get dedicated account management and priority customer support for your business.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">💳</div>
            <h3>Better Commission Rates</h3>
            <p>Enjoy reduced commission rates based on your sales volume and performance.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🚀</div>
            <h3>Marketing Tools</h3>
            <p>Access advanced marketing features including promoted listings and advertising tools.</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🔗</div>
            <h3>API Integration</h3>
            <p>Connect your existing systems with our robust API for seamless operations.</p>
        </div>
    </div>
    
    <div class="cta-section">
        <h2>Ready to Grow Your Business?</h2>
        <p>Join thousands of successful business sellers on FezaMarket</p>
        <a href="/seller-register.php?type=business" class="btn-primary">Start Selling</a>
        <a href="/contact.php" class="btn-primary" style="background: #6b7280;">Contact Sales</a>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>