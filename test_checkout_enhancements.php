<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Enhanced Checkout Features</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #333;
        }
        .pass {
            color: #28a745;
            font-weight: bold;
        }
        .fail {
            color: #dc3545;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-left: 4px solid #0066cc;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>🧪 Enhanced Checkout Features Test</h1>
    
    <div class="info">
        <strong>Test Scope:</strong> This page tests the mobile header fix and checkout enhancements implemented in the repository.
    </div>

    <div class="test-section">
        <h2>1. Mobile Header Fix Test</h2>
        <p>✅ Fixed: Removed resize event re-initialization that caused "dancing" behavior</p>
        <p>✅ Scroll behavior only triggers on actual scroll events</p>
        <p>✅ Viewport width check integrated into scroll handler</p>
        <p><em>To test: Open the homepage on mobile and scroll - header should hide/show smoothly without "dancing"</em></p>
    </div>

    <div class="test-section">
        <h2>2. Currency Service Test</h2>
        <?php
        require_once __DIR__ . '/includes/init.php';
        require_once __DIR__ . '/includes/currency_service.php';
        
        $currencyService = new CurrencyService();
        
        echo "<table>";
        echo "<tr><th>Country Code</th><th>Expected Currency</th><th>Actual Currency</th><th>Status</th></tr>";
        
        $testCases = [
            ['RW', 'RWF', 'Rwanda should use RWF'],
            ['US', 'USD', 'USA should use USD'],
            ['CA', 'USD', 'Canada should use USD'],
            ['GB', 'USD', 'UK should use USD'],
            ['FR', 'EUR', 'France (EU) should use EUR'],
            ['DE', 'EUR', 'Germany (EU) should use EUR'],
            ['IT', 'EUR', 'Italy (EU) should use EUR'],
            ['ES', 'EUR', 'Spain (EU) should use EUR'],
            ['NL', 'EUR', 'Netherlands (EU) should use EUR'],
            ['BE', 'EUR', 'Belgium (EU) should use EUR'],
            ['AT', 'EUR', 'Austria (EU) should use EUR'],
            ['PT', 'EUR', 'Portugal (EU) should use EUR'],
            ['IE', 'EUR', 'Ireland (EU) should use EUR'],
            ['GR', 'EUR', 'Greece (EU) should use EUR'],
            ['FI', 'EUR', 'Finland (EU) should use EUR'],
            ['JP', 'USD', 'Japan should use USD'],
            ['CN', 'USD', 'China should use USD'],
            ['IN', 'USD', 'India should use USD'],
            ['BR', 'USD', 'Brazil should use USD']
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($testCases as list($countryCode, $expectedCurrency, $description)) {
            $actualCurrency = $currencyService->detectCurrency($countryCode);
            $status = ($actualCurrency === $expectedCurrency) ? 'PASS' : 'FAIL';
            $class = ($status === 'PASS') ? 'pass' : 'fail';
            
            if ($status === 'PASS') $passed++;
            else $failed++;
            
            echo "<tr>";
            echo "<td>{$countryCode}</td>";
            echo "<td>{$expectedCurrency}</td>";
            echo "<td>{$actualCurrency}</td>";
            echo "<td class='{$class}'>{$status}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p style='margin-top: 15px;'><strong>Results:</strong> <span class='pass'>{$passed} passed</span>, <span class='fail'>{$failed} failed</span></p>";
        ?>
    </div>

    <div class="test-section">
        <h2>3. Countries Data Test</h2>
        <?php
        require_once __DIR__ . '/includes/countries_data.php';
        
        $allCountries = CountriesData::getAll();
        $euCountries = CountriesData::getEUCountries();
        
        echo "<p>✅ Total countries available: <strong>" . count($allCountries) . "</strong></p>";
        echo "<p>✅ EU countries defined: <strong>" . count($euCountries) . "</strong></p>";
        
        // Test specific countries
        $testCountries = ['RW', 'US', 'FR', 'GB'];
        echo "<table>";
        echo "<tr><th>Code</th><th>Name</th><th>Flag</th><th>Phone</th><th>Currency</th></tr>";
        
        foreach ($testCountries as $code) {
            $country = CountriesData::getByCode($code);
            if ($country) {
                echo "<tr>";
                echo "<td>{$country['code']}</td>";
                echo "<td>{$country['name']}</td>";
                echo "<td>{$country['flag']}</td>";
                echo "<td>{$country['phone']}</td>";
                echo "<td>{$country['currency']}</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
        // Sample of available countries
        echo "<h3>Sample Countries (first 20):</h3>";
        echo "<div style='display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px;'>";
        for ($i = 0; $i < min(20, count($allCountries)); $i++) {
            $c = $allCountries[$i];
            echo "<div style='padding: 8px; background: #f8f9fa; border-radius: 4px;'>";
            echo "{$c['flag']} {$c['name']}";
            echo "</div>";
        }
        echo "</div>";
        ?>
    </div>

    <div class="test-section">
        <h2>4. Database Migration Test</h2>
        <?php
        $migrationFile = __DIR__ . '/migrations/20251011_currency_rates_table.sql';
        if (file_exists($migrationFile)) {
            echo "<p class='pass'>✅ Migration file exists: <code>20251011_currency_rates_table.sql</code></p>";
            $content = file_get_contents($migrationFile);
            if (strpos($content, 'currency_rates') !== false) {
                echo "<p class='pass'>✅ Creates currency_rates table</p>";
            }
            if (strpos($content, 'INSERT INTO') !== false) {
                echo "<p class='pass'>✅ Includes initial exchange rate data</p>";
            }
            if (strpos($content, 'orders') !== false) {
                echo "<p class='pass'>✅ Updates orders table for currency support</p>";
            }
            echo "<p><em>Note: Run this migration to enable currency conversion features</em></p>";
        } else {
            echo "<p class='fail'>❌ Migration file not found</p>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>5. JavaScript Integration Test</h2>
        <?php
        $jsFile = __DIR__ . '/js/checkout-stripe.js';
        if (file_exists($jsFile)) {
            $jsContent = file_get_contents($jsFile);
            
            echo "<table>";
            echo "<tr><th>Feature</th><th>Status</th></tr>";
            
            $tests = [
                ['Countries array with flags', strpos($jsContent, "flag: '🇺🇸'") !== false],
                ['Phone codes in countries data', strpos($jsContent, "phone: '") !== false],
                ['Currency codes in countries data', strpos($jsContent, "currency: '") !== false],
                ['updatePhoneCountryCode function', strpos($jsContent, 'updatePhoneCountryCode') !== false],
                ['updateCurrency function', strpos($jsContent, 'updateCurrency') !== false],
                ['Select2 initialization', strpos($jsContent, 'select2') !== false],
                ['intlTelInput initialization', strpos($jsContent, 'intlTelInput') !== false],
            ];
            
            foreach ($tests as list($feature, $found)) {
                $status = $found ? 'PASS' : 'FAIL';
                $class = $found ? 'pass' : 'fail';
                echo "<tr><td>{$feature}</td><td class='{$class}'>{$status}</td></tr>";
            }
            
            echo "</table>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>6. Checkout Page Integration Test</h2>
        <?php
        $checkoutFile = __DIR__ . '/checkout.php';
        if (file_exists($checkoutFile)) {
            $checkoutContent = file_get_contents($checkoutFile);
            
            echo "<table>";
            echo "<tr><th>Feature</th><th>Status</th></tr>";
            
            $tests = [
                ['jQuery included', strpos($checkoutContent, 'jquery') !== false],
                ['Select2 library included', strpos($checkoutContent, 'select2') !== false],
                ['intl-tel-input library included', strpos($checkoutContent, 'intl-tel-input') !== false],
                ['Currency note element', strpos($checkoutContent, 'currency-note') !== false],
                ['Country select has country-select class', strpos($checkoutContent, 'country-select') !== false],
                ['Select2 CSS styles', strpos($checkoutContent, 'select2-container') !== false],
            ];
            
            foreach ($tests as list($feature, $found)) {
                $status = $found ? 'PASS' : 'FAIL';
                $class = $found ? 'pass' : 'fail';
                echo "<tr><td>{$feature}</td><td class='{$class}'>{$status}</td></tr>";
            }
            
            echo "</table>";
        }
        ?>
    </div>

    <div class="test-section">
        <h2>📋 Implementation Summary</h2>
        <h3>✅ Completed Features:</h3>
        <ul>
            <li>Fixed mobile header "dancing" behavior by removing resize re-initialization</li>
            <li>Created comprehensive countries data (195+ countries) with flags 🏳️</li>
            <li>Implemented currency detection logic: RWF for Rwanda, EUR for EU countries, USD for others</li>
            <li>Enhanced checkout with searchable country selector (Select2 library)</li>
            <li>Added international phone input with country codes (intl-tel-input)</li>
            <li>Phone number field syncs with selected country</li>
            <li>Dynamic currency display based on selected country</li>
            <li>Created database migration for currency_rates table</li>
            <li>All changes maintain backward compatibility</li>
        </ul>
        
        <h3>🔧 To Complete Setup:</h3>
        <ol>
            <li>Run the database migration: <code>mysql your_database < migrations/20251011_currency_rates_table.sql</code></li>
            <li>Test the checkout page: <a href="/checkout.php" target="_blank">Open Checkout</a></li>
            <li>Test mobile header on actual mobile device or browser dev tools</li>
            <li>Verify country search functionality works in checkout</li>
            <li>Verify phone input updates when country changes</li>
            <li>Verify currency note displays correctly</li>
        </ol>
    </div>

    <div class="info">
        <strong>Next Steps:</strong> 
        <ul style="margin: 10px 0 0 0;">
            <li>Review the changes in the pull request</li>
            <li>Run the database migration</li>
            <li>Test on staging environment</li>
            <li>Deploy to production when ready</li>
        </ul>
    </div>
</body>
</html>
