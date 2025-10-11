// EPD Platform Enhanced Screenshot Automation
// Extended to capture Admin, Seller, and Buyer dashboards

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// Screenshot configuration
const screenshots = [
    // Admin pages
    { url: '/admin/', name: 'admin-dashboard', description: 'Admin Dashboard' },
    { url: '/admin/integrations/', name: 'admin-integrations', description: 'Admin API & Integrations' },
    { url: '/admin/coupons/', name: 'admin-coupons', description: 'Admin Coupons Management' },
    { url: '/admin/dashboards/', name: 'admin-custom-dashboards', description: 'Admin Custom Dashboards' },
    { url: '/admin/streaming/', name: 'admin-streaming', description: 'Admin Live Streaming' },
    { url: '/admin/users/', name: 'admin-users', description: 'Admin User Management' },
    
    // Seller pages (requires auth, will capture login redirect or auth bypass)
    { url: '/seller/dashboard.php', name: 'seller-dashboard', description: 'Seller Dashboard', requiresAuth: true },
    { url: '/seller/products.php', name: 'seller-products', description: 'Seller Products Management', requiresAuth: true },
    { url: '/seller/orders.php', name: 'seller-orders', description: 'Seller Orders Management', requiresAuth: true },
    
    // Buyer pages (requires auth, will capture login redirect or auth bypass)  
    { url: '/buyer/dashboard.php', name: 'buyer-dashboard', description: 'Buyer Dashboard', requiresAuth: true },
    { url: '/buyer/orders.php', name: 'buyer-orders', description: 'Buyer Orders', requiresAuth: true },
    { url: '/buyer/profile.php', name: 'buyer-profile', description: 'Buyer Profile', requiresAuth: true },
    { url: '/buyer/wallet.php', name: 'buyer-wallet', description: 'Buyer Wallet', requiresAuth: true },
    { url: '/buyer/wishlist.php', name: 'buyer-wishlist', description: 'Buyer Wishlist', requiresAuth: true },
    { url: '/buyer/support.php', name: 'buyer-support', description: 'Buyer Support', requiresAuth: true },
    
    // Public pages
    { url: '/', name: 'homepage', description: 'Homepage' },
    { url: '/products.php', name: 'products', description: 'Products Page' },
    { url: '/login.php', name: 'login', description: 'Login Page' },
    { url: '/register.php', name: 'register', description: 'Registration Page' },
];

async function captureScreenshots() {
    // Ensure docs/screenshots directory exists
    const screenshotDir = path.join(__dirname, 'docs', 'screenshots');
    if (!fs.existsSync(screenshotDir)) {
        fs.mkdirSync(screenshotDir, { recursive: true });
    }

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1200, height: 800 }
    });
    const page = await context.newPage();

    // Base URL - adjust based on environment
    const baseUrl = process.env.BASE_URL || 'http://localhost:8000';
    
    console.log(`📸 Starting screenshot automation for ${screenshots.length} pages...`);
    console.log(`🌐 Base URL: ${baseUrl}\n`);

    const results = [];

    for (const screenshot of screenshots) {
        try {
            const url = `${baseUrl}${screenshot.url}`;
            console.log(`📷 Capturing: ${screenshot.description} (${url})`);
            
            await page.goto(url, { waitUntil: 'networkidle' });
            
            // Wait a bit for any dynamic content to load
            await page.waitForTimeout(2000);
            
            // Check if we hit a login redirect for auth-required pages
            if (screenshot.requiresAuth && page.url().includes('/login')) {
                console.log(`   ⚠️  Auth required - captured login redirect`);
            }
            
            const screenshotPath = path.join(screenshotDir, `${screenshot.name}.png`);
            await page.screenshot({ 
                path: screenshotPath, 
                fullPage: true 
            });
            
            results.push({
                name: screenshot.name,
                description: screenshot.description,
                url: screenshot.url,
                status: 'success',
                path: screenshotPath
            });
            
            console.log(`   ✅ Saved: ${screenshotPath}`);
            
        } catch (error) {
            console.log(`   ❌ Error: ${error.message}`);
            results.push({
                name: screenshot.name,
                description: screenshot.description,
                url: screenshot.url,
                status: 'error',
                error: error.message
            });
        }
    }

    await browser.close();

    // Generate results summary
    const summary = {
        timestamp: new Date().toISOString(),
        total: screenshots.length,
        successful: results.filter(r => r.status === 'success').length,
        failed: results.filter(r => r.status === 'error').length,
        results: results
    };

    // Save summary JSON
    const summaryPath = path.join(screenshotDir, 'screenshot-summary.json');
    fs.writeFileSync(summaryPath, JSON.stringify(summary, null, 2));

    console.log(`\n📊 Screenshot Summary:`);
    console.log(`   ✅ Successful: ${summary.successful}`);
    console.log(`   ❌ Failed: ${summary.failed}`);
    console.log(`   📁 Saved to: ${screenshotDir}`);
    console.log(`   📄 Summary: ${summaryPath}`);

    return summary;
}

// Run if called directly
if (require.main === module) {
    captureScreenshots()
        .then(() => {
            console.log('\n🎉 Screenshot automation completed!');
            process.exit(0);
        })
        .catch(error => {
            console.error('\n💥 Screenshot automation failed:', error);
            process.exit(1);
        });
}

module.exports = { captureScreenshots };