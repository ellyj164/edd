<?php
require_once __DIR__ . '/includes/init.php';
$page_title = 'Developers';
includeHeader($page_title);
?>

<div class="container">
    <div class="page-header"><h1>FezaMarket Developer Resources</h1><p>Build with our APIs and grow the ecosystem</p></div>
    <div class="content-wrapper">
        <section><h2>Welcome Developers</h2><p>FezaMarket provides powerful APIs and tools to help you integrate our marketplace into your applications, build new features, and create innovative solutions for buyers and sellers.</p></section>
        <section><h2>APIs & SDKs</h2>
            <div class="api-grid">
                <div class="api-card"><h3>🛍️ Shopping API</h3><p>Search products, retrieve listings, and access product data</p><ul><li>RESTful JSON API</li><li>Real-time product data</li><li>Advanced search & filtering</li></ul></div>
                <div class="api-card"><h3>💼 Seller API</h3><p>Manage inventory, orders, and fulfillment programmatically</p><ul><li>Bulk listing management</li><li>Order processing automation</li><li>Inventory synchronization</li></ul></div>
                <div class="api-card"><h3>💳 Checkout API</h3><p>Integrate FezaMarket checkout into your website or app</p><ul><li>Secure payment processing</li><li>Multiple payment methods</li><li>Fraud protection</li></ul></div>
                <div class="api-card"><h3>📊 Analytics API</h3><p>Access sales data, trends, and marketplace insights</p><ul><li>Sales analytics</li><li>Market trends</li><li>Performance metrics</li></ul></div>
            </div>
        </section>
        <section><h2>Developer Tools</h2>
            <div class="tools-grid">
                <div class="tool-card"><h3>🔑 Developer Portal</h3><p>Manage API keys and access your dashboard</p><a href="/developer-portal.php" class="btn btn-outline">Open Portal</a></div>
                <div class="tool-card"><h3>📝 Documentation</h3><p>Comprehensive API reference and guides</p><a href="/developer-portal.php#docs" class="btn btn-outline">View Docs</a></div>
                <div class="tool-card"><h3>🧪 Sandbox Environment</h3><p>Test your integrations safely</p><a href="/developer-portal.php" class="btn btn-outline">Access Sandbox</a></div>
                <div class="tool-card"><h3>📊 Usage Analytics</h3><p>Monitor your API usage and performance</p><a href="/developer-portal.php#usage" class="btn btn-outline">View Analytics</a></div>
            </div>
        </section>
        <section><h2>Use Cases</h2>
            <ul class="use-cases"><li><strong>Inventory Management:</strong> Sync your inventory across multiple sales channels</li><li><strong>Price Monitoring:</strong> Track competitor prices and market trends</li><li><strong>Affiliate Marketing:</strong> Build product comparison and affiliate sites</li><li><strong>Mobile Apps:</strong> Create custom shopping experiences</li><li><strong>Business Tools:</strong> Build tools for sellers to manage their business</li><li><strong>Data Analysis:</strong> Analyze marketplace trends and opportunities</li></ul>
        </section>
        <div class="cta-section"><h2>Ready to Build?</h2><p>Get your API credentials and start developing today</p><?php if (Session::isLoggedIn()): ?><a href="/developer-portal.php" class="btn btn-primary btn-lg">Go to Developer Portal</a><?php else: ?><a href="/login.php?redirect=/developer-portal.php" class="btn btn-primary btn-lg">Login to Get Started</a><?php endif; ?></div>
    </div>
</div>

<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#6366f1 0%,#4338ca 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #6366f1;padding-bottom:10px}
.api-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:25px}
.api-card{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.api-card h3{margin:0 0 15px 0;font-size:20px}
.api-card ul{margin-top:15px;padding-left:20px}
.api-card li{margin-bottom:8px;color:#6b7280}
.tools-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.tool-card{background:#f9fafb;padding:30px;border-radius:12px;text-align:center}
.tool-card h3{margin:0 0 15px 0}
.tool-card .btn{margin-top:15px}
.use-cases{font-size:18px;line-height:2;padding-left:40px}
.cta-section{text-align:center;padding:50px 30px;background:#f9fafb;border-radius:16px;margin-top:50px}
@media (max-width:768px){.api-grid,.tools-grid{grid-template-columns:1fr}}
</style>

<?php includeFooter(); ?>