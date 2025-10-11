#!/usr/bin/env php
<?php
/**
 * Test Countries Implementation
 * Validates the countries service and checkout integration without requiring database
 */

declare(strict_types=1);

echo "🧪 Testing Countries Implementation\n";
echo str_repeat("=", 50) . "\n\n";

$errors = 0;
$warnings = 0;
$passed = 0;

// Test 1: Verify countries_service.php file exists and is valid
echo "Test 1: Checking countries_service.php...\n";
$serviceFile = __DIR__ . '/../includes/countries_service.php';
if (!file_exists($serviceFile)) {
    echo "  ❌ FAIL: countries_service.php not found\n";
    $errors++;
} else {
    echo "  ✅ PASS: countries_service.php exists\n";
    $passed++;
    
    // Check class definition
    $content = file_get_contents($serviceFile);
    if (strpos($content, 'class CountriesService') !== false) {
        echo "  ✅ PASS: CountriesService class defined\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: CountriesService class not found\n";
        $errors++;
    }
    
    // Check key methods
    $methods = ['getAll', 'getByIso2', 'getEUCountries', 'getAsJson', 'isAvailable'];
    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "  ✅ PASS: Method $method exists\n";
            $passed++;
        } else {
            echo "  ❌ FAIL: Method $method not found\n";
            $errors++;
        }
    }
}

echo "\n";

// Test 2: Verify migration files
echo "Test 2: Checking migration files...\n";
$migration1 = __DIR__ . '/../database/migrations/026_create_countries_table.php';
$migration2 = __DIR__ . '/../database/migrations/027_seed_countries_data.php';

foreach ([$migration1, $migration2] as $migrationFile) {
    $name = basename($migrationFile);
    if (!file_exists($migrationFile)) {
        echo "  ❌ FAIL: $name not found\n";
        $errors++;
        continue;
    }
    
    echo "  ✅ PASS: $name exists\n";
    $passed++;
    
    // Check structure
    $migration = require $migrationFile;
    if (is_array($migration) && isset($migration['up']) && isset($migration['down'])) {
        echo "  ✅ PASS: $name has up/down structure\n";
        $passed++;
        
        // Check SQL content
        if (strpos($migration['up'], 'CREATE TABLE') !== false || 
            strpos($migration['up'], 'INSERT INTO') !== false) {
            echo "  ✅ PASS: $name has valid SQL\n";
            $passed++;
        } else {
            echo "  ⚠️  WARN: $name SQL might be incomplete\n";
            $warnings++;
        }
    } else {
        echo "  ❌ FAIL: $name missing up/down structure\n";
        $errors++;
    }
}

echo "\n";

// Test 3: Check countries table schema
echo "Test 3: Validating countries table schema...\n";
$migration = require $migration1;
$sql = $migration['up'];

$requiredFields = [
    'id' => true,
    'name' => true,
    'iso2' => true,
    'iso3' => true,
    'dial_code' => true,
    'is_eu' => true,
    'currency_code' => true,
    'currency_symbol' => true,
    'flag_emoji' => true,
    'created_at' => true,
    'updated_at' => true
];

foreach ($requiredFields as $field => $required) {
    if (strpos($sql, "`$field`") !== false || strpos($sql, "$field ") !== false) {
        echo "  ✅ PASS: Field '$field' defined\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Field '$field' missing\n";
        $errors++;
    }
}

// Check for unique constraints
if (strpos($sql, 'UNIQUE') !== false) {
    echo "  ✅ PASS: Unique constraints defined\n";
    $passed++;
} else {
    echo "  ⚠️  WARN: No unique constraints found\n";
    $warnings++;
}

echo "\n";

// Test 4: Check seed data
echo "Test 4: Validating seed data...\n";
$seedMigration = require $migration2;
$seedSql = $seedMigration['up'];

// Check for Rwanda
if (strpos($seedSql, "'RW'") !== false && strpos($seedSql, "'Rwanda'") !== false) {
    echo "  ✅ PASS: Rwanda found in seed data\n";
    $passed++;
    
    // Check Rwanda has RWF currency
    if (strpos($seedSql, "'RWF'") !== false) {
        echo "  ✅ PASS: Rwanda has RWF currency\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Rwanda missing RWF currency\n";
        $errors++;
    }
} else {
    echo "  ❌ FAIL: Rwanda not found in seed data\n";
    $errors++;
}

// Check for EU countries (should have is_eu = 1)
$euCountries = ['DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'IE', 'PT', 'GR'];
$euFound = 0;
foreach ($euCountries as $code) {
    if (preg_match("/'$code'.*1.*'EUR'/s", $seedSql) || 
        preg_match("/'$code'.*,\s*1\s*,/", $seedSql)) {
        $euFound++;
    }
}

if ($euFound >= 8) {
    echo "  ✅ PASS: EU countries properly marked (found $euFound/10 checked)\n";
    $passed++;
} else {
    echo "  ⚠️  WARN: Only $euFound/10 EU countries found with proper flags\n";
    $warnings++;
}

// Count approximate number of countries
$countryCount = substr_count($seedSql, "('");
if ($countryCount >= 180) {
    echo "  ✅ PASS: Approximately $countryCount countries in seed data\n";
    $passed++;
} else {
    echo "  ⚠️  WARN: Only $countryCount countries found (expected 192+)\n";
    $warnings++;
}

// Check for ON DUPLICATE KEY (idempotent)
if (strpos($seedSql, 'ON DUPLICATE KEY UPDATE') !== false) {
    echo "  ✅ PASS: Seed is idempotent (ON DUPLICATE KEY UPDATE)\n";
    $passed++;
} else {
    echo "  ❌ FAIL: Seed is not idempotent\n";
    $errors++;
}

echo "\n";

// Test 5: Check checkout.php integration
echo "Test 5: Checking checkout.php integration...\n";
$checkoutFile = __DIR__ . '/../checkout.php';

if (!file_exists($checkoutFile)) {
    echo "  ❌ FAIL: checkout.php not found\n";
    $errors++;
} else {
    $checkoutContent = file_get_contents($checkoutFile);
    
    // Check for countries_service include
    if (strpos($checkoutContent, "require_once __DIR__ . '/includes/countries_service.php'") !== false ||
        strpos($checkoutContent, 'countries_service.php') !== false) {
        echo "  ✅ PASS: countries_service.php included\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: countries_service.php not included\n";
        $errors++;
    }
    
    // Check for CountriesService usage
    if (strpos($checkoutContent, 'CountriesService::') !== false) {
        echo "  ✅ PASS: CountriesService used in checkout\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: CountriesService not used\n";
        $errors++;
    }
    
    // Check for countriesJson variable
    if (strpos($checkoutContent, '$countriesJson') !== false) {
        echo "  ✅ PASS: \$countriesJson variable defined\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: \$countriesJson variable not found\n";
        $errors++;
    }
    
    // Check JavaScript uses PHP variable
    if (strpos($checkoutContent, '<?php echo $countriesJson; ?>') !== false) {
        echo "  ✅ PASS: JavaScript uses PHP-generated JSON\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: JavaScript not using PHP variable\n";
        $errors++;
    }
    
    // Check for fallback mechanism
    if (strpos($checkoutContent, 'CountriesService::isAvailable()') !== false) {
        echo "  ✅ PASS: Fallback mechanism implemented\n";
        $passed++;
    } else {
        echo "  ⚠️  WARN: No fallback check found\n";
        $warnings++;
    }
    
    // Make sure old hardcoded array is gone
    $hardcodedPattern = "/const countries = \[\s*\{\s*code:\s*['\"]AF['\"]/";
    if (!preg_match($hardcodedPattern, $checkoutContent)) {
        echo "  ✅ PASS: Hardcoded country array removed\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: Hardcoded country array still present\n";
        $errors++;
    }
}

echo "\n";

// Test 6: Verify fallback to static data works
echo "Test 6: Testing static data fallback...\n";
$staticDataFile = __DIR__ . '/../includes/countries_data.php';
if (file_exists($staticDataFile)) {
    echo "  ✅ PASS: Static countries_data.php exists for fallback\n";
    $passed++;
    
    require_once $staticDataFile;
    if (class_exists('CountriesData')) {
        echo "  ✅ PASS: CountriesData class exists\n";
        $passed++;
        
        if (method_exists('CountriesData', 'getAll')) {
            $staticCountries = CountriesData::getAll();
            if (is_array($staticCountries) && count($staticCountries) > 0) {
                echo "  ✅ PASS: Static data has " . count($staticCountries) . " countries\n";
                $passed++;
            } else {
                echo "  ⚠️  WARN: Static data appears empty\n";
                $warnings++;
            }
        } else {
            echo "  ❌ FAIL: CountriesData::getAll() method not found\n";
            $errors++;
        }
    } else {
        echo "  ❌ FAIL: CountriesData class not found\n";
        $errors++;
    }
} else {
    echo "  ⚠️  WARN: Static countries_data.php not found (fallback won't work)\n";
    $warnings++;
}

echo "\n";

// Summary
echo str_repeat("=", 50) . "\n";
echo "📊 Test Summary\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Passed: $passed\n";
echo "⚠️  Warnings: $warnings\n";
echo "❌ Failed: $errors\n";
echo "\n";

if ($errors === 0) {
    if ($warnings === 0) {
        echo "🎉 All tests passed! Implementation is ready.\n";
        exit(0);
    } else {
        echo "✅ Tests passed with $warnings warning(s). Review warnings above.\n";
        exit(0);
    }
} else {
    echo "❌ $errors test(s) failed. Please fix the errors above.\n";
    exit(1);
}
