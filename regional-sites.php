<?php
require_once __DIR__ . '/includes/init.php';
includeHeader('FezaMarket Regional Sites');
?>

<div class="container">
    <div class="page-header"><h1>FezaMarket Around the World</h1><p>Shop in your local language and currency</p></div>
    <div class="content-wrapper">
        <section><h2>Global Marketplace, Local Experience</h2><p>FezaMarket operates regional sites to provide you with the best shopping experience in your country. Browse products in your local language, pay in your currency, and enjoy localized customer support.</p></section>
        <section><h2>Select Your Region</h2>
            <div class="regions-grid">
                <div class="region-card"><h3>🇺🇸 United States</h3><p>www.fezamarket.com</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇬🇧 United Kingdom</h3><p>www.fezamarket.co.uk</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇨🇦 Canada</h3><p>www.fezamarket.ca</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇦🇺 Australia</h3><p>www.fezamarket.com.au</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇩🇪 Germany</h3><p>www.fezamarket.de</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇫🇷 France</h3><p>www.fezamarket.fr</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇮🇹 Italy</h3><p>www.fezamarket.it</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇪🇸 Spain</h3><p>www.fezamarket.es</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇯🇵 Japan</h3><p>www.fezamarket.co.jp</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇮🇳 India</h3><p>www.fezamarket.in</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇧🇷 Brazil</h3><p>www.fezamarket.com.br</p><a href="/" class="btn btn-outline">Visit Site</a></div>
                <div class="region-card"><h3>🇲🇽 Mexico</h3><p>www.fezamarket.com.mx</p><a href="/" class="btn btn-outline">Visit Site</a></div>
            </div>
        </section>
        <section><h2>Benefits of Regional Sites</h2>
            <div class="benefits-grid">
                <div class="benefit"><h3>💱 Local Currency</h3><p>Shop and sell in your local currency with transparent pricing</p></div>
                <div class="benefit"><h3>🌐 Local Language</h3><p>Full site translation in your preferred language</p></div>
                <div class="benefit"><h3>📦 Local Shipping</h3><p>Faster delivery times and lower shipping costs</p></div>
                <div class="benefit"><h3>🆘 Local Support</h3><p>Customer service in your time zone and language</p></div>
            </div>
        </section>
    </div>
</div>
<style>
.page-header{text-align:center;padding:50px 20px;background:linear-gradient(135deg,#06b6d4 0%,#0891b2 100%);color:white;margin-bottom:50px}
.page-header h1{margin:0 0 10px 0;font-size:42px}
.content-wrapper{max-width:1200px;margin:0 auto;padding:0 20px 60px}
section{margin-bottom:50px}section h2{font-size:32px;margin-bottom:20px;color:#1f2937;border-bottom:2px solid #06b6d4;padding-bottom:10px}
.regions-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:20px;margin-top:30px}
.region-card{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);text-align:center}
.region-card h3{margin:0 0 10px 0;font-size:24px}
.region-card p{color:#6b7280;margin-bottom:15px}
.benefits-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.benefit{background:#f9fafb;padding:25px;border-radius:12px}
.benefit h3{margin:0 0 10px 0}
@media (max-width:768px){.regions-grid{grid-template-columns:1fr}}
</style>
<?php includeFooter(); ?>
