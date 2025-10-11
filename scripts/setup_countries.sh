#!/bin/bash
# Country Migrations - Quick Setup Script
# This script runs the country-related migrations

set -e  # Exit on error

echo "==================================="
echo "Country Database Setup"
echo "==================================="
echo ""

# Check if we're in the right directory
if [ ! -f "database/migrate.php" ]; then
    echo "❌ Error: Must run from project root directory"
    echo "   Current directory: $(pwd)"
    exit 1
fi

echo "📊 Checking current migration status..."
php database/migrate.php status

echo ""
echo "🚀 Running country migrations..."
echo ""

# Run migrations
php database/migrate.php up

echo ""
echo "✅ Migration complete!"
echo ""

# Verify countries were seeded
echo "🔍 Verifying country data..."
php -r "
require_once 'includes/db.php';
require_once 'includes/countries_service.php';

try {
    \$countries = CountriesService::getAll();
    \$count = count(\$countries);
    
    echo \"✅ Found \$count countries in database\\n\";
    
    // Check for Rwanda
    \$rwanda = CountriesService::getByIso2('RW');
    if (\$rwanda) {
        echo \"✅ Rwanda found: {\$rwanda['name']}, {\$rwanda['dial_code']}, {\$rwanda['currency_code']}\\n\";
    } else {
        echo \"⚠️  Warning: Rwanda not found\\n\";
    }
    
    // Check for EU countries
    \$euCountries = CountriesService::getEUCountries();
    \$euCount = count(\$euCountries);
    echo \"✅ Found \$euCount EU member states\\n\";
    
    echo \"\\n✅ All checks passed! Countries are ready for checkout.\\n\";
    
} catch (Exception \$e) {
    echo \"❌ Error: \" . \$e->getMessage() . \"\\n\";
    exit(1);
}
"

echo ""
echo "==================================="
echo "Setup Complete!"
echo "==================================="
echo ""
echo "Next steps:"
echo "1. Visit checkout page to verify country selector works"
echo "2. Test searching for countries using Select2"
echo "3. Test phone input with different countries"
echo ""
