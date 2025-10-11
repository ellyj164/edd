# 🚀 Deployment Checklist

## Pre-Deployment Steps

### 1. Code Review
- [ ] Review all file changes in the PR
- [ ] Check for any merge conflicts
- [ ] Verify no sensitive data committed
- [ ] Confirm all documentation is complete

### 2. Testing
- [ ] Run automated tests: `php test_enhancements.php`
  - Expected: All 41 tests should pass ✅
- [ ] Open browser test page: `/test_checkout_enhancements.php`
  - Verify all sections show green ✅
- [ ] Test checkout page: `/checkout.php`
  - Try selecting different countries
  - Verify search functionality
  - Check phone input updates
  - Confirm currency note displays

### 3. Database Migration
```bash
# Backup database first!
mysqldump -u username -p database_name > backup_before_migration.sql

# Run migration
mysql -u username -p database_name < migrations/20251011_currency_rates_table.sql

# Verify tables created
mysql -u username -p database_name -e "SHOW TABLES LIKE '%currency%';"
mysql -u username -p database_name -e "DESCRIBE currency_rates;"
mysql -u username -p database_name -e "SELECT COUNT(*) FROM currency_rates;"
```

Expected output:
```
+-------------------+
| Tables_like_%currency% |
+-------------------+
| currency_rates    |
+-------------------+

COUNT(*): Should be > 0 (initial rates inserted)
```

## Deployment to Staging

### Step 1: Deploy Code
```bash
# On staging server
cd /path/to/edd
git fetch origin
git checkout copilot/fix-mobile-header-enhance-checkout
git pull origin copilot/fix-mobile-header-enhance-checkout
```

### Step 2: Run Migration (if not already done)
```bash
mysql -u staging_user -p staging_db < migrations/20251011_currency_rates_table.sql
```

### Step 3: Verify Libraries Load
Open checkout page and check browser console:
- [ ] No JavaScript errors
- [ ] jQuery loaded
- [ ] Select2 loaded
- [ ] intl-tel-input loaded

### Step 4: Functional Testing

#### Test 1: Country Selector
1. Navigate to `/checkout.php`
2. Click on "Country" dropdown
3. Type "rwa" in search box
4. Expected: Rwanda appears in filtered list with 🇷🇼 flag

#### Test 2: Phone Input
1. Select Rwanda from country dropdown
2. Expected: Phone field shows ��🇼 +250
3. Change to France
4. Expected: Phone field shows 🇫🇷 +33

#### Test 3: Currency Display
1. Select Rwanda
2. Expected: "Prices will be shown in RWF (FRw)" message appears
3. Select France
4. Expected: "Prices will be shown in EUR (€)" message appears
5. Select USA
6. Expected: "Prices will be shown in USD ($)" message appears

#### Test 4: Mobile Header
1. Open homepage on mobile device or browser dev tools (mobile view)
2. Scroll down slowly
3. Expected: Header smoothly hides
4. Scroll up
5. Expected: Header smoothly shows
6. Hold phone still (no scrolling)
7. Expected: Header remains completely stable (no "dancing")

### Step 5: Cross-Browser Testing
Test on:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile Chrome (Android)
- [ ] Mobile Safari (iOS)

## Production Deployment

### Pre-Production Checklist
- [ ] All staging tests passed
- [ ] No issues reported from staging
- [ ] Database backup created
- [ ] Rollback plan prepared

### Production Steps

#### 1. Backup Everything
```bash
# Backup database
mysqldump -u prod_user -p prod_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup current code
cd /path/to/edd
git branch backup_before_checkout_enhancements_$(date +%Y%m%d)
```

#### 2. Deploy Code
```bash
git fetch origin
git checkout copilot/fix-mobile-header-enhance-checkout
git pull origin copilot/fix-mobile-header-enhance-checkout
```

#### 3. Run Migration
```bash
mysql -u prod_user -p prod_db < migrations/20251011_currency_rates_table.sql
```

#### 4. Verify Deployment
- [ ] Homepage loads correctly
- [ ] Checkout page loads correctly
- [ ] No JavaScript console errors
- [ ] Countries dropdown works
- [ ] Search functionality works
- [ ] Phone input works
- [ ] Currency display works
- [ ] Mobile header behaves correctly

#### 5. Monitor
- [ ] Check error logs for first 30 minutes
- [ ] Monitor checkout conversion rates
- [ ] Watch for any user reports
- [ ] Check database query performance

## Rollback Plan

If issues are detected:

```bash
# 1. Restore database
mysql -u prod_user -p prod_db < backup_TIMESTAMP.sql

# 2. Revert code
git checkout main  # or previous stable branch
git pull origin main

# 3. Clear caches if applicable
# (specific to your setup)
```

## Post-Deployment

### Monitoring Checklist (First 24 Hours)
- [ ] Check error logs every 2 hours
- [ ] Monitor checkout completion rate
- [ ] Watch for support tickets related to:
  - Country selection issues
  - Phone input problems
  - Currency display issues
  - Mobile header behavior

### Success Metrics
- [ ] No increase in error rates
- [ ] No decrease in checkout conversion
- [ ] Positive user feedback on new features
- [ ] No critical bugs reported

### Exchange Rate Updates (Ongoing)
The currency_rates table includes fallback rates. For production:

1. **Manual Updates:**
```sql
UPDATE currency_rates 
SET rate = NEW_RATE, updated_at = NOW() 
WHERE base = 'USD' AND quote = 'EUR';
```

2. **Automated Updates (Future):**
- Consider integrating with forex API
- Set up cron job for daily updates
- Monitor rate accuracy

## Verification Commands

### Check Database Structure
```sql
-- Verify currency_rates table exists
SHOW CREATE TABLE currency_rates;

-- Check for exchange rates
SELECT * FROM currency_rates LIMIT 10;

-- Verify orders table updated
SHOW COLUMNS FROM orders LIKE '%currency%';
```

### Check File Changes
```bash
# View changed files
git diff main..copilot/fix-mobile-header-enhance-checkout --name-only

# Check specific file
git show copilot/fix-mobile-header-enhance-checkout:templates/header.php
```

### Test API Endpoints
```bash
# If you have currency API endpoints
curl -X GET "https://yoursite.com/api/currency/rates"
```

## Troubleshooting

### Issue: Countries not appearing
**Check:**
- Browser console for JavaScript errors
- Verify js/checkout-stripe.js loaded correctly
- Check jQuery and Select2 libraries loaded

### Issue: Search not working
**Check:**
- Select2 library loaded
- jQuery version compatible (3.6.0)
- No JavaScript conflicts in console

### Issue: Phone codes not updating
**Check:**
- intl-tel-input library loaded
- Country change event listeners attached
- Browser console for errors

### Issue: Currency not displaying
**Check:**
- Currency note element exists in HTML
- updateCurrency function exists in JS
- Country code properly passed to function

### Issue: Mobile header still "dancing"
**Check:**
- Latest templates/header.php deployed
- No browser cache issues (hard refresh)
- Correct branch deployed

## Support Contacts

- **Development Team:** [Your team contact]
- **Database Admin:** [DBA contact]
- **DevOps:** [DevOps contact]

## Additional Resources

- Full documentation: `CHECKOUT_ENHANCEMENTS_README.md`
- Technical details: `CHANGES_SUMMARY.md`
- Visual guide: `BEFORE_AFTER_VISUAL.md`
- Test results: Run `php test_enhancements.php`

---

**Last Updated:** 2025-10-11
**Version:** 1.0
**Branch:** copilot/fix-mobile-header-enhance-checkout
