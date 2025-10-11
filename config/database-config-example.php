<?php
/**
 * Database Configuration Examples
 * Copy this file to config/config.php and update with your settings
 */

// === PRODUCTION CONFIGURATION (MySQL/MariaDB) ===
// Use these settings for production deployment

// Database connection settings - MySQL/MariaDB
define('DB_HOST', 'localhost');          // MySQL server host
define('DB_NAME', 'your_database_name'); // Your MySQL database name
define('DB_USER', 'your_mysql_user');    // MySQL username
define('DB_PASS', 'your_secure_password'); // MySQL password
define('DB_CHARSET', 'utf8mb4');

// Use MySQL/MariaDB only (SQLite removed)
define('USE_SQLITE', false);

// === DEVELOPMENT/LOCAL CONFIGURATION EXAMPLES ===

// Example 1: Local MySQL with XAMPP/WAMP
/*
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_dev'); 
define('DB_USER', 'root');
define('DB_PASS', ''); // Usually empty in XAMPP
define('DB_CHARSET', 'utf8mb4');
*/

// Example 2: Local MySQL with custom user
/*
define('DB_HOST', 'localhost');
define('DB_NAME', 'duns1');
define('DB_USER', 'duns1');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');
*/

// Example 3: Remote MySQL server
/*
define('DB_HOST', 'your-mysql-server.com');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_CHARSET', 'utf8mb4');
*/

// Example 4: Docker MySQL container
/*
define('DB_HOST', 'mysql_container'); // Container name
define('DB_NAME', 'ecommerce');
define('DB_USER', 'ecommerce_user');
define('DB_PASS', 'ecommerce_password');
define('DB_CHARSET', 'utf8mb4');
*/

// === CLOUD DATABASE EXAMPLES ===

// Example: AWS RDS MySQL
/*
define('DB_HOST', 'your-instance.region.rds.amazonaws.com');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'admin');
define('DB_PASS', 'your_rds_password');
define('DB_CHARSET', 'utf8mb4');
*/

// Example: Google Cloud SQL
/*
define('DB_HOST', 'your-project:region:instance');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'root');
define('DB_PASS', 'your_cloud_sql_password');
define('DB_CHARSET', 'utf8mb4');
*/

// === INSTRUCTIONS ===
/*
1. Copy this file to config/config.php
2. Choose the appropriate configuration example above
3. Update the credentials with your actual database information
4. Run: php setup_database.php
5. Test with: php test_mysql_connection.php

Default Admin Credentials (after setup):
- Email: admin@ecommerce.com  
- Password: admin123
- Role: admin

SECURITY NOTE: Change the default admin password immediately!
*/

// Rest of application settings...
define('APP_NAME', 'E-Commerce Platform');
define('APP_URL', 'http://localhost');
define('APP_VERSION', '1.0.0');

// Security settings
define('SECRET_KEY', 'your-secret-key-change-this-in-production');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('BCRYPT_COST', 12);

// File upload settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Pagination settings
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 20);
define('REVIEWS_PER_PAGE', 10);

// Email settings (configure for production)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@ecommerce.com');
define('FROM_NAME', APP_NAME);

// Payment gateway settings (sandbox/testing)
define('PAYMENT_GATEWAY', 'mock'); // 'mock', 'stripe', 'paypal'
define('STRIPE_PUBLISHABLE_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('PAYPAL_CLIENT_ID', '');
define('PAYPAL_CLIENT_SECRET', '');

// AI/ML settings
define('AI_RECOMMENDATIONS_ENABLED', true);
define('MIN_RECOMMENDATIONS', 4);
define('MAX_RECOMMENDATIONS', 12);

// Cache settings
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600);

// Debug settings
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', __DIR__ . '/../logs/');

// Timezone
date_default_timezone_set('UTC');

// Error reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>