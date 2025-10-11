const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

/**
 * Admin Panel Screenshot Automation
 * Generates screenshots of all admin pages for documentation
 */

// Configuration
const config = {
    baseUrl: process.env.BASE_URL || 'http://localhost:8000',
    adminBypass: true,
    screenshotDir: './docs/screenshots',
    viewport: { width: 1920, height: 1080 },
    timeout: 30000
};

// Admin pages to screenshot - ALL 28 modules
const adminPages = [
    { name: 'dashboard', url: '/admin/', title: 'Admin Dashboard' },
    { name: 'analytics', url: '/admin/analytics/', title: 'Analytics & Reports' },
    { name: 'campaigns', url: '/admin/campaigns/', title: 'Marketing Campaigns' },
    { name: 'categories', url: '/admin/categories/', title: 'Categories & SEO' },
    { name: 'cms', url: '/admin/cms/', title: 'Content Management' },
    { name: 'communications', url: '/admin/communications/', title: 'Communications' },
    { name: 'coupons', url: '/admin/coupons/', title: 'Coupons & Discounts' },
    { name: 'dashboards', url: '/admin/dashboards/', title: 'Custom Dashboards' },
    { name: 'disputes', url: '/admin/disputes/', title: 'Dispute Resolution' },
    { name: 'finance', url: '/admin/finance/', title: 'Finance Management' },
    { name: 'integrations', url: '/admin/integrations/', title: 'API & Integrations' },
    { name: 'inventory', url: '/admin/inventory/', title: 'Inventory Management' },
    { name: 'kyc', url: '/admin/kyc/', title: 'KYC & Verification' },
    { name: 'loyalty', url: '/admin/loyalty/', title: 'Loyalty & Rewards' },
    { name: 'maintenance', url: '/admin/maintenance/', title: 'System Maintenance' },
    { name: 'orders', url: '/admin/orders/', title: 'Order Management' },
    { name: 'payments', url: '/admin/payments/', title: 'Payment Tracking' },
    { name: 'payouts', url: '/admin/payouts/', title: 'Payout Management' },
    { name: 'products', url: '/admin/products/', title: 'Product Management' },
    { name: 'returns', url: '/admin/returns/', title: 'Returns & Refunds' },
    { name: 'roles', url: '/admin/roles/', title: 'Roles & Permissions' },
    { name: 'security', url: '/admin/security/', title: 'Security & Audit' },
    { name: 'settings', url: '/admin/settings/', title: 'System Settings' },
    { name: 'shipping', url: '/admin/shipping/', title: 'Shipping Management' },
    { name: 'streaming', url: '/admin/streaming/', title: 'Live Streaming' },
    { name: 'support', url: '/admin/support/', title: 'Support Management' },
    { name: 'users', url: '/admin/users/', title: 'User Management' },
    { name: 'vendors', url: '/admin/vendors/', title: 'Vendor Management' }
];

async function createScreenshotDir() {
    if (!fs.existsSync(config.screenshotDir)) {
        fs.mkdirSync(config.screenshotDir, { recursive: true });
        console.log(`✓ Created screenshot directory: ${config.screenshotDir}`);
    }
}

async function takeScreenshot(page, pageName, pageTitle, url) {
    try {
        console.log(`📸 Taking screenshot: ${pageTitle}`);
        
        // Navigate to the page
        await page.goto(`${config.baseUrl}${url}`, { 
            waitUntil: 'networkidle',
            timeout: config.timeout 
        });
        
        // Wait for page to be fully loaded
        await page.waitForTimeout(2000);
        
        // Check if page loaded successfully by looking for admin header or main content
        const pageLoaded = await page.locator('.admin-header, .container-fluid, .card').first().isVisible();
        
        if (!pageLoaded) {
            console.log(`⚠️  Warning: Page may not have loaded correctly for ${pageTitle}`);
        }
        
        // Take screenshot
        const screenshotPath = path.join(config.screenshotDir, `${pageName}.png`);
        await page.screenshot({
            path: screenshotPath,
            fullPage: true,
            type: 'png'
        });
        
        console.log(`✅ Screenshot saved: ${screenshotPath}`);
        return true;
        
    } catch (error) {
        console.error(`❌ Failed to screenshot ${pageTitle}: ${error.message}`);
        return false;
    }
}

async function setupAdminSession(page) {
    try {
        // If admin bypass is enabled, session should be set automatically
        // Otherwise, implement login logic here
        console.log('🔓 Admin Bypass enabled - session will be set automatically');
        return true;
    } catch (error) {
        console.error(`❌ Failed to setup admin session: ${error.message}`);
        return false;
    }
}

async function generateScreenshots() {
    console.log('🚀 Starting Admin Panel Screenshot Generation');
    console.log(`📍 Base URL: ${config.baseUrl}`);
    console.log(`📁 Screenshot Directory: ${config.screenshotDir}`);
    console.log(`📄 Pages to capture: ${adminPages.length}`);
    
    // Create screenshot directory
    await createScreenshotDir();
    
    // Launch browser
    const browser = await chromium.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    const context = await browser.newContext({
        viewport: config.viewport,
        ignoreHTTPSErrors: true
    });
    
    const page = await context.newPage();
    
    // Set up admin session
    const sessionSetup = await setupAdminSession(page);
    if (!sessionSetup) {
        console.error('❌ Failed to setup admin session. Exiting.');
        await browser.close();
        return;
    }
    
    // Track results
    const results = {
        success: 0,
        failed: 0,
        total: adminPages.length
    };
    
    // Generate screenshots for each admin page
    for (const pageConfig of adminPages) {
        const success = await takeScreenshot(
            page, 
            pageConfig.name, 
            pageConfig.title, 
            pageConfig.url
        );
        
        if (success) {
            results.success++;
        } else {
            results.failed++;
        }
        
        // Small delay between screenshots
        await page.waitForTimeout(1000);
    }
    
    await browser.close();
    
    // Generate summary
    console.log('\n📊 Screenshot Generation Summary:');
    console.log(`✅ Successful: ${results.success}/${results.total}`);
    console.log(`❌ Failed: ${results.failed}/${results.total}`);
    console.log(`📁 Screenshots saved to: ${config.screenshotDir}`);
    
    // Generate index file
    await generateScreenshotIndex(results);
    
    console.log('\n🎉 Screenshot generation completed!');
}

async function generateScreenshotIndex(results) {
    const indexContent = `# Admin Panel Screenshots

Generated on: ${new Date().toISOString()}

## Summary
- **Total Pages**: ${results.total}
- **Successful**: ${results.success}
- **Failed**: ${results.failed}

## Screenshots

${adminPages.map(page => 
    `### ${page.title}
![${page.title}](${page.name}.png)
**URL**: \`${page.url}\`

`).join('')}

## Usage

These screenshots are automatically generated using Playwright automation. To regenerate:

\`\`\`bash
npm run admin:screenshots
\`\`\`

## Notes

- Screenshots are taken at 1920x1080 resolution
- Full page screenshots are captured
- Admin Bypass mode must be enabled for automated capture
`;

    const indexPath = path.join(config.screenshotDir, 'README.md');
    fs.writeFileSync(indexPath, indexContent);
    console.log(`📄 Generated screenshot index: ${indexPath}`);
}

// Basic smoke tests
async function runSmokeTests() {
    console.log('🧪 Running basic smoke tests...');
    
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: config.viewport });
    const page = await context.newPage();
    
    let testsPassed = 0;
    let testsFailed = 0;
    
    for (const pageConfig of adminPages) {
        try {
            await page.goto(`${config.baseUrl}${pageConfig.url}`, { 
                waitUntil: 'networkidle',
                timeout: config.timeout 
            });
            
            // Check if page loaded and has expected elements
            const hasHeader = await page.locator('h1, .admin-header h1, .h3').first().isVisible();
            const hasContent = await page.locator('.container-fluid, .card, .table').first().isVisible();
            
            if (hasHeader && hasContent) {
                console.log(`✅ ${pageConfig.title}: Page loads correctly`);
                testsPassed++;
            } else {
                console.log(`❌ ${pageConfig.title}: Missing expected elements`);
                testsFailed++;
            }
            
        } catch (error) {
            console.log(`❌ ${pageConfig.title}: ${error.message}`);
            testsFailed++;
        }
    }
    
    await browser.close();
    
    console.log(`\n🧪 Smoke Test Results: ${testsPassed} passed, ${testsFailed} failed`);
    return testsFailed === 0;
}

// Main execution
async function main() {
    try {
        // Check if we should run smoke tests
        const runTests = process.argv.includes('--test') || process.argv.includes('--smoke-test');
        
        if (runTests) {
            const testsPass = await runSmokeTests();
            if (!testsPass) {
                console.error('❌ Smoke tests failed. Screenshots may not be generated correctly.');
                process.exit(1);
            }
        }
        
        // Generate screenshots
        await generateScreenshots();
        
    } catch (error) {
        console.error(`❌ Error: ${error.message}`);
        process.exit(1);
    }
}

// Export for testing
module.exports = {
    generateScreenshots,
    runSmokeTests,
    adminPages,
    config
};

// Run if called directly
if (require.main === module) {
    main();
}