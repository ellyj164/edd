<?php
/**
 * Affiliates Program
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Affiliate Program - FezaMarket';
$metaDescription = 'Earn money by promoting FezaMarket products. Join our affiliate program and start earning commissions today.';

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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        .commission-tiers {
            max-width: 1000px;
            margin: 3rem auto;
            padding: 0 2rem;
        }
        .tier-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 2rem;
            margin: 1rem 0;
            text-align: center;
        }
        .tier-card.featured {
            border-color: #10b981;
            transform: scale(1.05);
        }
        .commission-rate {
            font-size: 3rem;
            font-weight: bold;
            color: #10b981;
        }
        .btn-affiliate {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="hero-section">
        <h1>FezaMarket Affiliate Program</h1>
        <p>Earn up to 8% commission on every sale you refer</p>
        <a href="/register.php?affiliate=1" class="btn-affiliate">Join Now - It's Free!</a>
    </div>
    
    <div class="commission-tiers">
        <h2 style="text-align: center; margin-bottom: 2rem;">Commission Tiers</h2>
        
        <div class="tier-card">
            <h3>Starter</h3>
            <div class="commission-rate">3%</div>
            <p>Perfect for beginners</p>
            <ul style="text-align: left; max-width: 300px; margin: 0 auto;">
                <li>3% commission on all sales</li>
                <li>Basic marketing materials</li>
                <li>Monthly payments</li>
                <li>Email support</li>
            </ul>
        </div>
        
        <div class="tier-card featured">
            <h3>Pro</h3>
            <div class="commission-rate">5%</div>
            <p>Most popular tier</p>
            <ul style="text-align: left; max-width: 300px; margin: 0 auto;">
                <li>5% commission on all sales</li>
                <li>Premium marketing materials</li>
                <li>Weekly payments</li>
                <li>Priority support</li>
                <li>Performance bonuses</li>
            </ul>
        </div>
        
        <div class="tier-card">
            <h3>Elite</h3>
            <div class="commission-rate">8%</div>
            <p>For top performers</p>
            <ul style="text-align: left; max-width: 300px; margin: 0 auto;">
                <li>8% commission on all sales</li>
                <li>Custom marketing materials</li>
                <li>Daily payments</li>
                <li>Dedicated account manager</li>
                <li>Exclusive promotions</li>
            </ul>
        </div>
    </div>
    
    <div style="background: #f8fafc; padding: 4rem 2rem; text-align: center;">
        <h2>How It Works</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 1000px; margin: 2rem auto;">
            <div>
                <div style="font-size: 3rem; margin-bottom: 1rem;">1️⃣</div>
                <h3>Sign Up</h3>
                <p>Join our affiliate program for free and get your unique referral links.</p>
            </div>
            <div>
                <div style="font-size: 3rem; margin-bottom: 1rem;">2️⃣</div>
                <h3>Promote</h3>
                <p>Share products and links with your audience using our marketing materials.</p>
            </div>
            <div>
                <div style="font-size: 3rem; margin-bottom: 1rem;">3️⃣</div>
                <h3>Earn</h3>
                <p>Get paid commissions on every sale made through your referral links.</p>
            </div>
        </div>
        <a href="/register.php?affiliate=1" class="btn-affiliate">Start Earning Today</a>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>