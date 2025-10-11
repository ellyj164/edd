<?php
/**
 * URL Helpers and Routing Functions
 * E-Commerce Platform
 */

/**
 * Generate clean URLs for the application
 */
function url($path = '') {
    $baseUrl = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $baseUrl . '/' . $path;
}

/**
 * Generate URL for seller routes
 */
if (!function_exists('sellerUrl')) {
    function sellerUrl($path = '') {
        return url('seller/' . ltrim($path, '/'));
    }
}

/**
 * Get the appropriate selling URL based on user status
 * This function handles the selling link redirect logic:
 * - If user is a registered vendor: redirect to seller-center.php
 * - If user is logged in but not a vendor: redirect to seller-register.php
 * - If user is not logged in: redirect to register.php with seller parameter
 */
if (!function_exists('getSellingUrl')) {
    function getSellingUrl() {
        if (!Session::isLoggedIn()) {
            // Not logged in - redirect to registration with seller flag
            return '/register.php?seller=1';
        }
        
        // User is logged in - check if they're a vendor
        try {
            $vendor = new Vendor();
            $existingVendor = $vendor->findByUserId(Session::getUserId());
            
            if ($existingVendor) {
                // User is already a vendor - go to seller center
                return '/seller-center.php';
            } else {
                // User is logged in but not a vendor - go to seller registration
                return '/seller-register.php';
            }
        } catch (Exception $e) {
            // If there's an error checking vendor status, default to seller registration
            return '/seller-register.php';
        }
    }
}

/**
 * Generate URL for account routes
 */
if (!function_exists('accountUrl')) {
    function accountUrl($path = '') {
        return url('account/' . ltrim($path, '/'));
    }
}

/**
 * Generate URL for admin routes
 */
if (!function_exists('adminUrl')) {
    function adminUrl($path = '') {
        return url('admin/' . ltrim($path, '/'));
    }
}

/**
 * Check if current page matches given path
 */
function isCurrentPage($path) {
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $currentPath = rtrim($currentPath, '/');
    $path = '/' . ltrim($path, '/');
    $path = rtrim($path, '/');
    
    return $currentPath === $path || $currentPath === $path . '.php';
}

/**
 * Generate navigation class for active states
 */
function navClass($path, $baseClass = '', $activeClass = 'active') {
    $classes = [$baseClass];
    
    if (isCurrentPage($path)) {
        $classes[] = $activeClass;
    }
    
    return implode(' ', array_filter($classes));
}

/**
 * Store intended URL for post-login redirect
 */
function setIntendedUrl($url = null) {
    if ($url === null) {
        $url = $_SERVER['REQUEST_URI'] ?? '/';
    }
    Session::set('intended_url', $url);
}

/**
 * Get and clear intended URL
 */
function getIntendedUrl($default = '/') {
    $url = Session::get('intended_url', $default);
    Session::remove('intended_url');
    return $url;
}

/**
 * 404 error handler
 */
function show404($message = 'Page Not Found') {
    if (!headers_sent()) {
        http_response_code(404);
    }
    
    $page_title = '404 - Page Not Found';
    includeHeader($page_title);
    ?>
    <div class="container">
        <div class="error-page">
            <div class="error-content">
                <h1 class="error-code">404</h1>
                <h2 class="error-title">Page Not Found</h2>
                <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
                <div class="error-actions">
                    <a href="/" class="btn btn-primary">Go Home</a>
                    <a href="javascript:history.back()" class="btn btn-outline">Go Back</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    includeFooter();
    exit;
}

/**
 * Get default dashboard URL based on user role
 */
function getDashboardUrl($role) {
    switch ($role) {
        case 'admin':
            return '/admin/index.php';
        case 'vendor':
        case 'seller':
            return '/seller-center.php';
        case 'customer':
        default:
            return '/account.php';
    }
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    if (!Session::isLoggedIn()) {
        return false;
    }
    
    $userRole = Session::getUserRole();
    
    // Admin has access to everything
    if ($userRole === 'admin') {
        return true;
    }
    
    // Handle vendor/seller role aliases
    if ($role === 'vendor' || $role === 'seller') {
        return in_array($userRole, ['vendor', 'seller', 'admin']);
    }
    
    // Exact role match
    return $userRole === $role;
}

/**
 * Get user avatar URL or generate initials
 * - Safe against missing/null fields
 * - Deterministic color based on available seed (email/name) with fallback
 */
function getUserAvatar($user, $size = 40) {
    $u = is_array($user) ? $user : [];

    // Use uploaded avatar if present
    $avatar = $u['avatar'] ?? '';
    if (is_string($avatar) && $avatar !== '') {
        return url('uploads/avatars/' . ltrim($avatar, '/'));
    }

    // Helpers for multibyte-safe substr/upper with fallback
    $mb_substr = function (string $s, int $start, int $len = null): string {
        if ($s === '') return '';
        if (function_exists('mb_substr')) {
            return $len === null ? mb_substr($s, $start) : mb_substr($s, $start, $len);
        }
        return $len === null ? substr($s, $start) : substr($s, $start, $len);
    };
    $mb_upper = function (string $s): string {
        if ($s === '') return '';
        return function_exists('mb_strtoupper') ? mb_strtoupper($s) : strtoupper($s);
    };

    // Build initials from first/last name, else from username or email, else fallback to '?'
    $first = trim((string)($u['first_name'] ?? ''));
    $last  = trim((string)($u['last_name'] ?? ''));
    $initials = '';
    if ($first !== '') $initials .= $mb_upper($mb_substr($first, 0, 1));
    if ($last !== '')  $initials .= $mb_upper($mb_substr($last, 0, 1));

    if ($initials === '') {
        $username = trim((string)($u['username'] ?? ''));
        $email    = trim((string)($u['email'] ?? ''));
        if ($username !== '') {
            $initials = $mb_upper($mb_substr($username, 0, 1));
        } elseif ($email !== '') {
            $local = explode('@', $email)[0] ?? '';
            $initials = $local !== '' ? $mb_upper($mb_substr($local, 0, 1)) : '?';
        } else {
            $initials = '?';
        }
    }

    $colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b'];
    $seed = (string)($u['email'] ?? ($first . $last));
    if ($seed === '') $seed = 'user';
    $colorIndex = abs((int)crc32($seed)) % count($colors);
    $color = $colors[$colorIndex];

    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='{$size}' height='{$size}' viewBox='0 0 100 100'>"
         . "<rect width='100' height='100' fill='{$color}'/>"
         . "<text x='50' y='50' font-family='Arial' font-size='40' fill='white' text-anchor='middle' dominant-baseline='central'>{$initials}</text>"
         . "</svg>";

    return 'data:image/svg+xml,' . rawurlencode($svg);
}

/**
 * Format date for display (only if formatDate doesn't exist)
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'M j, Y') {
        if (empty($date)) return '';
        
        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (Exception $e) {
            return $date;
        }
    }
}

/**
 * Format currency for display
 */
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = 'USD') {
        return '$' . number_format($amount, 2);
    }
}