# Database Migrations - Countries Table

## Overview
This migration adds database persistence for country data to replace hardcoded JavaScript arrays in the checkout process. Countries are now stored in MariaDB/MySQL and queried via PHP for better maintainability and scalability.

## New Migrations

### 026_create_countries_table.php
Creates the `countries` table with the following schema:
- `id` - Primary key (auto-increment)
- `name` - Country name (e.g., "United States", "Rwanda")
- `iso2` - ISO 3166-1 alpha-2 code (e.g., "US", "RW") - UNIQUE
- `iso3` - ISO 3166-1 alpha-3 code (e.g., "USA", "RWA") - UNIQUE
- `dial_code` - International dialing code with + prefix (e.g., "+1", "+250")
- `is_eu` - Boolean flag (1 for EU member states, 0 otherwise)
- `currency_code` - ISO 4217 currency code (e.g., "USD", "EUR", "RWF")
- `currency_symbol` - Currency symbol (e.g., "$", "€", "FRw")
- `flag_emoji` - Unicode flag emoji for display (e.g., "🇺🇸", "🇷🇼")
- `created_at` - Timestamp
- `updated_at` - Timestamp (auto-updates)

### 027_seed_countries_data.php
Seeds the `countries` table with 192+ countries including:
- All world countries with accurate ISO codes and dialing codes
- Rwanda with correct RWF currency and +250 dialing code
- All 27 EU member states properly marked with `is_eu = 1`
- Proper currency assignments (EUR for EU, GBP for UK, RWF for Rwanda, USD as default)

## Running the Migrations

### Using the Migration Runner

The database migration runner is located at `database/migrate.php`.

**Check Migration Status:**
```bash
php database/migrate.php status
```

**Run All Pending Migrations:**
```bash
php database/migrate.php up
```

**Rollback Last Batch:**
```bash
php database/migrate.php down
```

### Manual Execution (Alternative)

If the migration runner doesn't work or you prefer manual execution:

```bash
# From project root directory
mysql -u your_username -p your_database < database/migrations/026_create_countries_table.php
mysql -u your_username -p your_database < database/migrations/027_seed_countries_data.php
```

Note: PHP migration files contain SQL in the `up` key. You may need to extract the SQL or use the runner.

## Verification

After running the migrations, verify the data:

```sql
-- Check table was created
SHOW TABLES LIKE 'countries';

-- Count countries
SELECT COUNT(*) FROM countries;
-- Expected: 192+

-- Check Rwanda
SELECT * FROM countries WHERE iso2 = 'RW';
-- Expected: Rwanda, +250, RWF, FRw

-- Check EU countries
SELECT name, iso2 FROM countries WHERE is_eu = 1 ORDER BY name;
-- Expected: 27 EU member states
```

## Migration Features

### Idempotent
Both migrations are safe to run multiple times:
- Table creation uses `CREATE TABLE IF NOT EXISTS`
- Data seeding uses `INSERT ... ON DUPLICATE KEY UPDATE`

### Reversible
Both migrations include proper `down` methods:
- `026` drops the `countries` table
- `027` deletes all seeded country records

### Safe
- Uses transactions to ensure atomicity
- Includes proper error handling
- No breaking changes to existing data

## Integration with Checkout

The checkout page (`checkout.php`) automatically uses the database-backed countries:

1. **PHP Side:** 
   - `CountriesService::getAsJson()` queries the database
   - Falls back to static data if database unavailable
   - Outputs JSON into JavaScript

2. **JavaScript Side:**
   - No changes to existing JavaScript code
   - Same data structure: `{ code, name, flag, phone, currency }`
   - Select2 and intl-tel-input work unchanged

## Troubleshooting

**Error: "Table 'countries' doesn't exist"**
- Run migrations: `php database/migrate.php up`

**Error: "Duplicate entry for key 'unique_iso2'"**
- Migration already ran successfully
- This is expected and safe to ignore

**No countries showing on checkout page**
- Check database connection in `.env`
- Verify migrations ran: `SELECT COUNT(*) FROM countries;`
- Check error logs for fallback messages

**Countries showing but different than expected**
- Re-run seeder: Run migration 027 again (idempotent)
- Check for manual edits to the countries table

## Database Configuration

Ensure your `.env` file has correct database credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ecommerce_platform
DB_USER=your_username
DB_PASS=your_password
DB_CHARSET=utf8mb4
```

## Support

For issues with migrations:
1. Check database connectivity: `php -r "require 'includes/db.php'; var_dump(db_ping());"`
2. Review migration status: `php database/migrate.php status`
3. Check error logs for detailed error messages
4. Verify MariaDB/MySQL version compatibility (requires 5.7+ or MariaDB 10.2+)
