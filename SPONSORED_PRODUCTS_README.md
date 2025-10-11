# Seller Ad Promotion & Sponsored Products System

Complete implementation of a seller advertising and sponsored products system with coupon functionality.

## 🎯 Overview

This system allows sellers to promote their products through paid sponsorships, with admin controls for pricing, approval, and management. It also includes enhanced coupon functionality for discount promotions.

## 📋 Features Implemented

### 1. Seller Ad Creation (`seller/marketing.php`)
- ✅ "Create Ads" option in marketing dashboard
- ✅ Multi-product selection for promotion
- ✅ Fixed 7-day sponsorship duration
- ✅ Admin-configurable pricing per product
- ✅ Automatic wallet deduction for payment
- ✅ Active and expired ads tracking with tabs
- ✅ Performance metrics (impressions, clicks, CTR)

### 2. Sponsored Products Display (`product.php`)
- ✅ Displays sponsored products above "Similar Items" section
- ✅ Responsive card layout matching existing design
- ✅ Dynamic rotation based on category relevance
- ✅ "SPONSORED" tag on sponsored products
- ✅ Automatic impression tracking
- ✅ Mobile-responsive design

### 3. Admin Dashboard Control (`admin/sponsored-products/`)
- ✅ Dedicated management interface
- ✅ Price configuration for 7-day sponsorships
- ✅ Approve/reject ad submissions workflow
- ✅ Manual product sponsorship capability
- ✅ Active/expired/rejected ads view
- ✅ Advanced filtering and search
- ✅ Statistics dashboard with revenue tracking

### 4. Payment & Duration Logic
- ✅ Wallet integration for ad payments
- ✅ Automatic balance deduction
- ✅ Transaction logging
- ✅ Cron job for automatic expiration
- ✅ 7-day duration enforcement

### 5. Coupon Functionality
- ✅ Coupon creation interface
- ✅ Percentage and fixed amount discounts
- ✅ Validity period configuration
- ✅ Product-specific application
- ✅ Usage limits (total and per-customer)
- ✅ Validation API at checkout
- ✅ Redemption tracking

## 🗄️ Database Schema

### Tables Created

#### `sponsored_products`
Tracks individual product sponsorships:
- `id` - Primary key
- `product_id` - FK to products
- `vendor_id` - FK to vendors
- `seller_id` - FK to users
- `cost` - Sponsorship cost
- `payment_status` - pending/paid/failed/refunded
- `status` - pending/active/expired/rejected/cancelled
- `sponsored_from` - Start date
- `sponsored_until` - End date (auto-calculated as +7 days)
- `impressions` - View count
- `clicks` - Click count
- `approved_by` - Admin who approved
- `rejected_reason` - Reason if rejected

#### `sponsored_product_analytics`
Detailed tracking of sponsored product interactions:
- `sponsored_product_id` - FK to sponsored_products
- `event_type` - impression/click/view
- `user_id` - Optional user reference
- `session_id` - Session tracking
- `ip_address` - IP tracking
- `created_at` - Timestamp

#### `sponsored_product_settings`
Admin configuration:
- `setting_key` - Configuration key (e.g., 'price_per_7_days')
- `setting_value` - Configuration value
- `description` - Setting description

## 📁 Files Modified/Created

### Created Files
1. `database/migrations/035_create_sponsored_products_table.php` - Database schema
2. `admin/sponsored-products/index.php` - Admin management interface
3. `scripts/expire_sponsored_products.php` - Cron job for expiration
4. `scripts/CRON_SETUP.md` - Cron job setup instructions

### Modified Files
1. `seller/marketing.php` - Added sponsored ads creation and tracking
2. `product.php` - Updated to display sponsored products with tags
3. `api/track-sponsored-click.php` - Updated click tracking

## 🚀 Installation & Setup

### Step 1: Run Database Migration
```bash
# The migration will be automatically run by the system
# Or run manually:
php database/migrate.php
```

### Step 2: Configure Admin Pricing
1. Log in as admin
2. Navigate to `/admin/sponsored-products/`
3. Click "Update Pricing"
4. Set price per product for 7 days (default: $50.00)

### Step 3: Setup Cron Job
See `scripts/CRON_SETUP.md` for detailed instructions.

Quick setup:
```bash
crontab -e
```
Add:
```
0 0 * * * cd /path/to/edd && php scripts/expire_sponsored_products.php >> /var/log/edd/sponsored_expiry.log 2>&1
```

### Step 4: Test the System
1. Create a test seller account with wallet balance
2. Navigate to `/seller/marketing.php`
3. Click "Create Ad" and select products
4. Submit for approval
5. As admin, approve the ad in `/admin/sponsored-products/`
6. Verify sponsored products appear on product pages

## 💰 Payment Flow

### Seller Creates Sponsored Ad
1. Seller selects products to sponsor
2. System calculates total cost: `num_products × price_per_7_days`
3. System checks seller's wallet balance
4. If sufficient, deducts amount from wallet
5. Creates sponsored_products records with status='pending'
6. Records wallet transaction

### Admin Approval
1. Admin reviews pending ads
2. Approves or rejects with reason
3. Approved ads: status → 'active'
4. Rejected ads: status → 'rejected', no refund (already paid)

### Automatic Expiration
1. Cron job runs daily
2. Finds ads where `sponsored_until <= NOW()` and `status = 'active'`
3. Updates status to 'expired'
4. Products no longer appear as sponsored

## 📊 Analytics & Tracking

### Impressions
- Automatically tracked when sponsored products appear on pages
- Updated in real-time via SQL
- Displayed in seller and admin dashboards

### Clicks
- Tracked via `/api/track-sponsored-click.php`
- JavaScript on product cards sends POST request
- Stored in sponsored_products.clicks and analytics table

### Click-Through Rate (CTR)
- Calculated as: `(clicks / impressions) × 100`
- Displayed in dashboards for performance analysis

## 🎨 UI Components

### Seller Marketing Dashboard
- Clean, modern design with card layouts
- Color-coded status badges
- Performance metrics display
- Tabbed interface for filtering
- Modal-based creation workflow

### Admin Management Interface
- Statistics dashboard with key metrics
- Filterable table view
- Action buttons for approve/reject
- Modal-based configuration
- Responsive design

### Product Page Sponsored Section
- Matches existing "Similar Items" design
- Clear "SPONSORED" badge
- Product thumbnails, names, prices
- Hover effects for better UX
- Responsive grid layout

## 🔒 Security Considerations

### Payment Validation
- Server-side wallet balance checking
- Transaction atomicity with database transactions
- Prevents negative balances

### Access Control
- Seller authentication required
- Admin permission checking
- CSRF token protection on all forms

### Data Validation
- Input sanitization
- SQL injection prevention (prepared statements)
- XSS protection (htmlspecialchars)

## 🧪 Testing Checklist

### Seller Workflow
- [ ] Create sponsored ad with wallet balance
- [ ] Create sponsored ad without sufficient balance
- [ ] View active sponsored ads
- [ ] View expired sponsored ads
- [ ] Check performance metrics update

### Admin Workflow
- [ ] Update pricing configuration
- [ ] Approve pending ads
- [ ] Reject pending ads with reason
- [ ] Manually sponsor a product
- [ ] Filter and search ads

### Frontend Display
- [ ] Sponsored products appear on product pages
- [ ] SPONSORED badge displays correctly
- [ ] Mobile responsive design
- [ ] Click tracking works
- [ ] Impression tracking works

### Automatic Expiration
- [ ] Cron job runs successfully
- [ ] Products expire after 7 days
- [ ] Status updates correctly
- [ ] No longer displayed as sponsored

### Coupon System
- [ ] Create coupon with percentage discount
- [ ] Create coupon with fixed discount
- [ ] Apply valid coupon at checkout
- [ ] Reject invalid/expired coupon
- [ ] Track coupon usage

## 📝 API Endpoints

### `/api/coupons/validate.php`
Validate and retrieve coupon details
- Method: GET
- Parameters: `code` (coupon code)
- Returns: Coupon details or error

### `/api/track-sponsored-click.php`
Track clicks on sponsored products
- Method: POST
- Body: `{ "product_id": 123 }`
- Returns: Success status

## 🛠️ Maintenance

### Database Cleanup
Periodically archive old expired ads:
```sql
-- Archive ads older than 90 days
INSERT INTO sponsored_products_archive 
SELECT * FROM sponsored_products 
WHERE status = 'expired' 
AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete archived records
DELETE FROM sponsored_products 
WHERE status = 'expired' 
AND updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Performance Optimization
- Add indexes on frequently queried columns (already included in migration)
- Monitor analytics table size
- Consider partitioning for large datasets

## 🐛 Troubleshooting

### Sponsored Products Not Appearing
1. Check product status: `SELECT * FROM sponsored_products WHERE status = 'active'`
2. Verify expiration date: `sponsored_until > NOW()`
3. Check product is active: `products.status = 'active'`

### Payment Not Deducting
1. Verify wallet exists for seller
2. Check wallet balance is sufficient
3. Review transaction logs in `wallet_transactions`

### Cron Job Not Running
1. Check cron service status
2. Verify PHP path in crontab
3. Check file permissions
4. Review log files

## 📞 Support

For issues or questions:
1. Check error logs: `error_log()` calls throughout code
2. Review database migration status
3. Verify configuration settings
4. Test with demo data

## 🔄 Future Enhancements

Potential improvements:
- [ ] Multi-duration options (3, 7, 14, 30 days)
- [ ] Targeted advertising by location/demographics
- [ ] A/B testing for ad performance
- [ ] Bulk ad creation
- [ ] Scheduled future ads
- [ ] Email notifications for approval/expiration
- [ ] Advanced analytics dashboard
- [ ] Budget caps and auto-renewal
- [ ] Bidding system for premium placements

## 📄 License

Part of the EDD E-Commerce Platform.
