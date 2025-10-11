# Sponsored Products System - Cron Job Setup

## Overview
The sponsored products system requires a cron job to automatically expire sponsored ads after their 7-day duration.

## Cron Job Script
The script is located at: `/scripts/expire_sponsored_products.php`

## Setup Instructions

### Option 1: Using Crontab (Linux/Unix)

1. Open your crontab:
```bash
crontab -e
```

2. Add the following line to run the script daily at midnight:
```bash
0 0 * * * cd /path/to/edd && php scripts/expire_sponsored_products.php >> /var/log/edd/sponsored_expiry.log 2>&1
```

Replace `/path/to/edd` with the actual path to your installation.

### Option 2: Using Plesk/cPanel

1. Log into your hosting control panel
2. Navigate to "Scheduled Tasks" or "Cron Jobs"
3. Create a new cron job with:
   - Command: `php /path/to/edd/scripts/expire_sponsored_products.php`
   - Schedule: Daily at 00:00 (midnight)

### Option 3: Using Laravel Scheduler (if applicable)

Add to your `app/Console/Kernel.php`:
```php
$schedule->exec('php ' . base_path('../scripts/expire_sponsored_products.php'))
         ->daily()
         ->at('00:00');
```

## Testing the Script

Run manually to test:
```bash
cd /path/to/edd
php scripts/expire_sponsored_products.php
```

Expected output:
```
Successfully expired N sponsored products.
```

## What the Script Does

1. Finds all sponsored products with `status = 'active'` and `sponsored_until <= NOW()`
2. Updates their status to `'expired'`
3. Logs the number of expired products
4. Commits the changes to the database

## Monitoring

Check the logs to ensure the cron job is running:
```bash
tail -f /var/log/edd/sponsored_expiry.log
```

Or check system logs:
```bash
grep "Sponsored Products Cron" /var/log/syslog
```

## Troubleshooting

### Script Not Running
- Verify PHP path: `which php`
- Check cron is running: `service cron status`
- Verify permissions: `chmod +x scripts/expire_sponsored_products.php`

### Database Errors
- Check database connection in `includes/init.php`
- Verify table exists: Run migration `035_create_sponsored_products_table.php`

### No Products Expiring
- Verify products have expired: `SELECT * FROM sponsored_products WHERE status = 'active' AND sponsored_until <= NOW()`
- Check script logs for errors

## Manual Expiration

If needed, you can manually expire products via SQL:
```sql
UPDATE sponsored_products 
SET status = 'expired', updated_at = NOW()
WHERE status = 'active' 
AND sponsored_until <= NOW();
```

## Frequency Recommendations

- **Daily (Recommended)**: Good balance between accuracy and server load
- **Hourly**: Better accuracy, use if you have high-volume sponsored ads
- **Every 6 hours**: Middle ground option

To change frequency, modify the cron schedule:
- Hourly: `0 * * * *`
- Every 6 hours: `0 */6 * * *`
- Daily: `0 0 * * *`
