# Deployment Guide - Database-Backed Countries

## Overview
This deployment replaces hardcoded JavaScript country arrays with database-backed country data for the checkout page. All changes are backward compatible with graceful fallbacks.

## Pre-Deployment Checklist

- [ ] Backup database before running migrations
- [ ] Verify database credentials in `.env` file
- [ ] Ensure MariaDB/MySQL is running (version 5.7+ or MariaDB 10.2+)
- [ ] Test database connectivity: `php -r "require 'includes/db.php'; var_dump(db_ping());"`

## Deployment Steps

### Step 1: Backup (CRITICAL)

```bash
# Backup database
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup current checkout.php
cp checkout.php checkout.php.backup
```

### Step 2: Deploy Code Changes

```bash
# Pull latest code
git pull origin main

# Verify file changes
git diff HEAD~1 checkout.php
git diff HEAD~1 includes/countries_service.php
```

### Step 3: Run Migrations

**Option A: Using Automated Script (Recommended)**
```bash
./scripts/setup_countries.sh
```

**Option B: Using Migration Runner**
```bash
# Check current status
php database/migrate.php status

# Run migrations
php database/migrate.php up

# Verify
php -r "
require 'includes/db.php';
\$stmt = db()->query('SELECT COUNT(*) FROM countries');
echo 'Countries in DB: ' . \$stmt->fetchColumn() . PHP_EOL;
"
```

**Option C: Manual SQL Execution**
```bash
# Extract SQL from migrations (if needed)
# Note: PHP files contain SQL in arrays, use migration runner instead
mysql -u username -p database_name < database/migrations/026_create_countries_table.sql
mysql -u username -p database_name < database/migrations/027_seed_countries_data.sql
```

### Step 4: Verify Deployment

```bash
# Run automated tests
php tests/test_countries_implementation.php

# Should output: "🎉 All tests passed!"
```

### Step 5: Test in Browser

1. **Navigate to checkout page**
   - URL: `https://yourdomain.com/checkout.php`
   - Add items to cart first if required

2. **Test Country Selector**
   - [ ] Dropdown shows all countries (192+)
   - [ ] Search functionality works (type "Rwanda" or "Germany")
   - [ ] Countries have flag emojis
   - [ ] Selecting country updates phone code
   - [ ] Can select Rwanda and see +250 prefix

3. **Test Phone Input**
   - [ ] Phone field has country flag dropdown
   - [ ] Changing phone country updates country selector
   - [ ] Can select Rwanda from phone dropdown

4. **Test Form Submission**
   - [ ] Fill out checkout form completely
   - [ ] Submit form
   - [ ] Verify order processes successfully
   - [ ] Check order contains correct country code

### Step 6: Monitor Logs

```bash
# Check for any errors
tail -f /var/log/php/error.log

# Look for these messages:
# - "[CHECKOUT] Countries table not available" (should NOT appear if DB is working)
# - "CountriesService::" errors (investigate if present)
```

## Verification Queries

Run these SQL queries to verify data:

```sql
-- Check table exists
SHOW TABLES LIKE 'countries';

-- Count countries
SELECT COUNT(*) as total_countries FROM countries;
-- Expected: 192+

-- Check Rwanda
SELECT * FROM countries WHERE iso2 = 'RW';
-- Expected: name=Rwanda, dial_code=+250, currency_code=RWF

-- Check EU countries
SELECT COUNT(*) as eu_count FROM countries WHERE is_eu = 1;
-- Expected: 27

-- Sample of countries
SELECT iso2, name, dial_code, currency_code, is_eu 
FROM countries 
WHERE iso2 IN ('US', 'RW', 'DE', 'FR', 'GB', 'AU', 'CN', 'JP')
ORDER BY name;
```

## Rollback Procedure

If issues occur, rollback using these steps:

### Option 1: Rollback Migrations Only
```bash
# Rollback last migration batch
php database/migrate.php down

# Restore from backup if needed
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

### Option 2: Rollback Code Changes
```bash
# Restore previous version of checkout.php
cp checkout.php.backup checkout.php

# Or revert git commit
git revert HEAD
git push origin main
```

### Option 3: Complete Rollback
```bash
# 1. Restore database
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql

# 2. Restore code
git reset --hard HEAD~1
# Or: git revert HEAD

# 3. Clear any caches
php artisan cache:clear  # If using Laravel
# Or: Clear opcache/APCu if configured
```

## Troubleshooting

### Issue: "Countries table doesn't exist"
**Solution:**
```bash
php database/migrate.php up
```

### Issue: "No countries showing on checkout"
**Diagnosis:**
```bash
php -r "
require 'includes/db.php';
require 'includes/countries_service.php';
var_dump(CountriesService::isAvailable());
echo 'Countries: ' . count(CountriesService::getAll()) . PHP_EOL;
"
```

**Solution:**
- If `isAvailable()` returns `false`: Run migrations
- If count is 0: Run seeder migration (027)
- If errors: Check database credentials in `.env`

### Issue: "Country selector empty or shows only USA"
**Diagnosis:**
- Check browser console for JavaScript errors
- Check if `$countriesJson` is empty in page source
- Check PHP error logs

**Solution:**
```bash
# Check fallback is working
grep "CHECKOUT.*Countries table not available" /var/log/php/error.log

# If fallback active, database isn't available - run migrations
php database/migrate.php up
```

### Issue: "Search not working in country selector"
**Diagnosis:**
- Check if Select2 library is loaded
- Check browser console for errors

**Solution:**
- No code changes needed - Select2 is already included
- Clear browser cache
- Check jQuery and Select2 CDN URLs are accessible

### Issue: "Phone input not showing country flags"
**Diagnosis:**
- Check if intl-tel-input library is loaded
- Check browser console for errors

**Solution:**
- No code changes needed - intl-tel-input already included
- Clear browser cache
- Verify CDN accessibility

## Performance Notes

### Database Impact
- **Table size**: ~50KB for 192 countries
- **Query time**: <10ms for SELECT * FROM countries
- **Index usage**: Efficient with iso2/iso3 unique indexes

### Caching (Optional Enhancement)
Consider adding caching for production:

```php
// In includes/countries_service.php
public static function getAsJson(): string {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    
    $countries = self::getAll();
    $simplified = array_map(/* ... */);
    $cache = json_encode($simplified, JSON_UNESCAPED_UNICODE);
    return $cache;
}
```

## Post-Deployment Verification

Run this checklist after deployment:

- [ ] Database has 192+ countries
- [ ] Rwanda appears with +250 and RWF
- [ ] 27 EU countries marked with is_eu=1
- [ ] Checkout page loads without errors
- [ ] Country selector is populated
- [ ] Search works in country dropdown
- [ ] Phone input updates when country changes
- [ ] Form submission works correctly
- [ ] No errors in PHP error log
- [ ] No JavaScript errors in browser console

## Success Criteria

✅ Deployment is successful when:
1. All automated tests pass
2. All 192+ countries visible in checkout
3. Search functionality works
4. Phone integration works
5. Form submits successfully
6. No errors in logs
7. User experience unchanged (same UX/design)

## Support Contacts

For deployment issues:
- Review logs: `/var/log/php/error.log`
- Check migration status: `php database/migrate.php status`
- Run tests: `php tests/test_countries_implementation.php`
- Consult documentation: `database/migrations/README_COUNTRIES.md`
