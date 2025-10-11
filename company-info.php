<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('Company Information');
?>

<div class="container">
    <div class="page-header">
        <h1>About FezaMarket</h1>
        <p>Connecting buyers and sellers worldwide since 2024</p>
    </div>

    <div class="content-wrapper">
        <section class="company-section">
            <h2>Our Story</h2>
            <p>FezaMarket was founded with a simple mission: to create a trusted marketplace where anyone can buy and sell with confidence. Today, we're proud to serve millions of users worldwide, facilitating billions of dollars in transactions annually.</p>
            <p>Our platform brings together individual sellers, small businesses, and established brands, creating a vibrant ecosystem of commerce that benefits everyone.</p>
        </section>

        <section class="company-section">
            <h2>Our Mission</h2>
            <p>To empower people and create economic opportunity for all by providing a world-class marketplace that is simple, safe, and accessible to everyone.</p>
        </section>

        <section class="company-section">
            <h2>Our Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <h3>🤝 Trust</h3>
                    <p>Building trust between buyers and sellers through transparency and security</p>
                </div>
                <div class="value-card">
                    <h3>💡 Innovation</h3>
                    <p>Continuously improving and innovating to serve our community better</p>
                </div>
                <div class="value-card">
                    <h3>🌍 Inclusivity</h3>
                    <p>Creating opportunities for everyone, regardless of background or location</p>
                </div>
                <div class="value-card">
                    <h3>🎯 Excellence</h3>
                    <p>Striving for excellence in everything we do, from technology to customer service</p>
                </div>
            </div>
        </section>

        <section class="company-section">
            <h2>By the Numbers</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">10M+</div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">50M+</div>
                    <div class="stat-label">Products Listed</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">190+</div>
                    <div class="stat-label">Countries</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$5B+</div>
                    <div class="stat-label">Annual GMV</div>
                </div>
            </div>
        </section>

        <section class="company-section">
            <h2>Leadership</h2>
            <p>FezaMarket is led by a team of experienced professionals passionate about e-commerce, technology, and building community. Our leadership brings decades of combined experience from top technology and retail companies.</p>
        </section>

        <section class="company-section">
            <h2>Contact Us</h2>
            <div class="contact-info">
                <p><strong>Headquarters:</strong> 123 Commerce Street, San Francisco, CA 94105</p>
                <p><strong>Customer Support:</strong> <a href="/contact.php">Contact Form</a></p>
                <p><strong>Media Inquiries:</strong> <a href="mailto:press@fezamarket.com">press@fezamarket.com</a></p>
                <p><strong>Investor Relations:</strong> <a href="/investors.php">Investor Portal</a></p>
            </div>
        </section>
    </div>
</div>

<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.page-header p{margin:0;font-size:20px;opacity:0.9}
.content-wrapper{max-width:1000px;margin:0 auto;padding:0 20px 60px}
.company-section{margin-bottom:50px}
.company-section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #3b82f6;padding-bottom:10px}
.company-section p{font-size:16px;line-height:1.8;color:#374151;margin-bottom:15px}
.values-grid,.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;margin-top:30px}
.value-card,.stat-card{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);text-align:center}
.value-card h3{margin:0 0 15px 0;font-size:24px}
.stat-number{font-size:48px;font-weight:bold;color:#3b82f6;margin-bottom:10px}
.stat-label{font-size:16px;color:#6b7280;text-transform:uppercase;letter-spacing:1px}
.contact-info{background:#f9fafb;padding:30px;border-radius:12px;border-left:4px solid #3b82f6}
.contact-info p{margin-bottom:15px}
@media (max-width:768px){.values-grid,.stats-grid{grid-template-columns:1fr}.page-header h1{font-size:32px}}
</style>

<?php includeFooter(); ?>
