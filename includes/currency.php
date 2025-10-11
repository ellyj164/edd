<?php
/**
 * Currency Management Class
 * Handles currency detection, conversion, and display
 */

class Currency {
    private $db;
    private static $instance = null;
    
    // Country to currency mapping
    private static $countryToCurrency = [
        // Africa
        'RW' => 'RWF', // Rwanda
        'KE' => 'USD', // Kenya (uses KES but we'll default to USD for now)
        'UG' => 'USD', // Uganda (uses UGX but we'll default to USD for now)
        'TZ' => 'USD', // Tanzania
        'ZA' => 'USD', // South Africa
        'NG' => 'USD', // Nigeria
        'GH' => 'USD', // Ghana
        'EG' => 'USD', // Egypt
        
        // Americas
        'US' => 'USD', // United States
        'CA' => 'USD', // Canada
        'MX' => 'USD', // Mexico
        'BR' => 'USD', // Brazil
        'AR' => 'USD', // Argentina
        
        // EU countries (using EUR)
        'AT' => 'EUR', 'BE' => 'EUR', 'CY' => 'EUR', 'EE' => 'EUR', 'FI' => 'EUR',
        'FR' => 'EUR', 'DE' => 'EUR', 'GR' => 'EUR', 'IE' => 'EUR', 'IT' => 'EUR',
        'LV' => 'EUR', 'LT' => 'EUR', 'LU' => 'EUR', 'MT' => 'EUR', 'NL' => 'EUR',
        'PT' => 'EUR', 'SK' => 'EUR', 'SI' => 'EUR', 'ES' => 'EUR',
        
        // Other European countries
        'GB' => 'USD', // United Kingdom (uses GBP but we'll default to USD for now)
        'CH' => 'USD', // Switzerland
        'NO' => 'USD', // Norway
        'SE' => 'USD', // Sweden
        'DK' => 'USD', // Denmark
        'PL' => 'USD', // Poland
        
        // Asia
        'CN' => 'USD', // China
        'JP' => 'USD', // Japan
        'IN' => 'USD', // India
        'SG' => 'USD', // Singapore
        'HK' => 'USD', // Hong Kong
        'KR' => 'USD', // South Korea
        'TH' => 'USD', // Thailand
        'MY' => 'USD', // Malaysia
        'ID' => 'USD', // Indonesia
        'PH' => 'USD', // Philippines
        'VN' => 'USD', // Vietnam
        
        // Middle East
        'AE' => 'USD', // United Arab Emirates
        'SA' => 'USD', // Saudi Arabia
        'IL' => 'USD', // Israel
        'TR' => 'USD', // Turkey
        
        // Oceania
        'AU' => 'USD', // Australia
        'NZ' => 'USD', // New Zealand
    ];
    
    public function __construct() {
        $this->db = db();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Detect user's country from IP address using GeoIP service with fallback
     */
    public function detectCountryFromIP($ipAddress = null) {
        if ($ipAddress === null) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        // Don't detect for local IPs
        if (in_array($ipAddress, ['127.0.0.1', '::1', 'localhost']) || empty($ipAddress)) {
            error_log("Currency: Local IP detected ({$ipAddress}), defaulting to US");
            return 'US'; // Default to US for local development
        }
        
        // Log the IP being detected
        error_log("Currency: Detecting country for IP: {$ipAddress}");
        
        // Try primary service: ip-api.com
        $countryCode = $this->detectFromIpApi($ipAddress);
        if ($countryCode) {
            error_log("Currency: Detected country '{$countryCode}' from ip-api.com");
            return $countryCode;
        }
        
        // Try fallback service: ipapi.co
        $countryCode = $this->detectFromIpapiCo($ipAddress);
        if ($countryCode) {
            error_log("Currency: Detected country '{$countryCode}' from ipapi.co (fallback)");
            return $countryCode;
        }
        
        // Try second fallback: ipinfo.io
        $countryCode = $this->detectFromIpInfo($ipAddress);
        if ($countryCode) {
            error_log("Currency: Detected country '{$countryCode}' from ipinfo.io (fallback 2)");
            return $countryCode;
        }
        
        error_log("Currency: All GeoIP services failed for IP {$ipAddress}, defaulting to US");
        return 'US'; // Default to US if all services fail
    }
    
    /**
     * Try to detect country from ip-api.com
     */
    private function detectFromIpApi($ipAddress) {
        try {
            $url = "http://ip-api.com/json/{$ipAddress}?fields=status,countryCode";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true,
                    'method' => 'GET',
                    'header' => "User-Agent: FezaMarket/1.0\r\n"
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                error_log("Currency: ip-api.com request failed");
                return null;
            }
            
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success' && isset($data['countryCode'])) {
                return $data['countryCode'];
            }
            
            error_log("Currency: ip-api.com returned invalid data: " . $response);
        } catch (Exception $e) {
            error_log("Currency: ip-api.com exception: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Try to detect country from ipapi.co (fallback)
     */
    private function detectFromIpapiCo($ipAddress) {
        try {
            $url = "https://ipapi.co/{$ipAddress}/country/";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true,
                    'method' => 'GET',
                    'header' => "User-Agent: FezaMarket/1.0\r\n"
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                error_log("Currency: ipapi.co request failed");
                return null;
            }
            
            $countryCode = trim($response);
            if (strlen($countryCode) === 2 && ctype_alpha($countryCode)) {
                return strtoupper($countryCode);
            }
            
            error_log("Currency: ipapi.co returned invalid data: " . $response);
        } catch (Exception $e) {
            error_log("Currency: ipapi.co exception: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Try to detect country from ipinfo.io (second fallback)
     */
    private function detectFromIpInfo($ipAddress) {
        try {
            $url = "https://ipinfo.io/{$ipAddress}/country";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'ignore_errors' => true,
                    'method' => 'GET',
                    'header' => "User-Agent: FezaMarket/1.0\r\n"
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                error_log("Currency: ipinfo.io request failed");
                return null;
            }
            
            $countryCode = trim($response);
            if (strlen($countryCode) === 2 && ctype_alpha($countryCode)) {
                return strtoupper($countryCode);
            }
            
            error_log("Currency: ipinfo.io returned invalid data: " . $response);
        } catch (Exception $e) {
            error_log("Currency: ipinfo.io exception: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get currency code for a country
     */
    public function getCurrencyForCountry($countryCode) {
        return self::$countryToCurrency[$countryCode] ?? 'USD';
    }
    
    /**
     * Detect and set user's currency
     */
    public function detectAndSetCurrency() {
        Session::start();
        
        // Check if currency already set in session (don't override manual selection)
        if (Session::get('currency_code')) {
            error_log("Currency: Using existing session currency: " . Session::get('currency_code'));
            return Session::get('currency_code');
        }
        
        // Check if manually overridden
        if (Session::get('currency_manual_override')) {
            error_log("Currency: Manual override detected, not auto-detecting");
            return Session::get('currency_code', 'USD');
        }
        
        // Detect country from IP
        $countryCode = $this->detectCountryFromIP();
        error_log("Currency: Detected country code: {$countryCode}");
        
        // Get currency for country
        $currencyCode = $this->getCurrencyForCountry($countryCode);
        error_log("Currency: Mapped to currency: {$currencyCode}");
        
        // Store in session
        Session::set('currency_code', $currencyCode);
        Session::set('country_code', $countryCode);
        
        return $currencyCode;
    }
    
    /**
     * Get current currency code
     */
    public function getCurrentCurrency() {
        Session::start();
        return Session::get('currency_code', 'USD');
    }
    
    /**
     * Set user's preferred currency (manual override)
     */
    public function setCurrency($currencyCode) {
        Session::start();
        Session::set('currency_code', $currencyCode);
        Session::set('currency_manual_override', true);
    }
    
    /**
     * Get exchange rate for currency
     */
    public function getRate($currencyCode) {
        try {
            $stmt = $this->db->prepare("
                SELECT rate_to_usd 
                FROM currency_rates 
                WHERE currency_code = ?
            ");
            $stmt->execute([$currencyCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (float)$result['rate_to_usd'] : 1.0;
        } catch (Exception $e) {
            error_log("Error getting rate: " . $e->getMessage());
            return 1.0;
        }
    }
    
    /**
     * Get currency symbol
     */
    public function getSymbol($currencyCode) {
        try {
            $stmt = $this->db->prepare("
                SELECT currency_symbol 
                FROM currency_rates 
                WHERE currency_code = ?
            ");
            $stmt->execute([$currencyCode]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['currency_symbol'] : '$';
        } catch (Exception $e) {
            error_log("Error getting symbol: " . $e->getMessage());
            return '$';
        }
    }
    
    /**
     * Convert price from USD to target currency
     */
    public function convertPrice($priceUSD, $targetCurrency = null) {
        if ($targetCurrency === null) {
            $targetCurrency = $this->getCurrentCurrency();
        }
        
        if ($targetCurrency === 'USD') {
            return $priceUSD;
        }
        
        $rate = $this->getRate($targetCurrency);
        return $priceUSD * $rate;
    }
    
    /**
     * Format price with currency symbol
     */
    public function formatPrice($priceUSD, $targetCurrency = null) {
        if ($targetCurrency === null) {
            $targetCurrency = $this->getCurrentCurrency();
        }
        
        $convertedPrice = $this->convertPrice($priceUSD, $targetCurrency);
        $symbol = $this->getSymbol($targetCurrency);
        
        // Format based on currency
        if ($targetCurrency === 'RWF') {
            // No decimals for RWF
            return $symbol . ' ' . number_format($convertedPrice, 0, '.', ',');
        } else {
            return $symbol . number_format($convertedPrice, 2, '.', ',');
        }
    }
    
    /**
     * Fetch and update exchange rates from API
     */
    public function updateExchangeRates() {
        try {
            // Use exchangerate-api.com free tier (no API key needed for basic access)
            // Alternative: use fixer.io, currencylayer.com, or openexchangerates.org
            $url = "https://api.exchangerate-api.com/v4/latest/USD";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                error_log("Failed to fetch exchange rates");
                return false;
            }
            
            $data = json_decode($response, true);
            if (!$data || !isset($data['rates'])) {
                error_log("Invalid exchange rate data");
                return false;
            }
            
            // Update rates in database
            $rates = $data['rates'];
            
            // Update EUR
            if (isset($rates['EUR'])) {
                $this->updateRate('EUR', 1.0 / $rates['EUR']); // Convert to USD base
            }
            
            // Update RWF
            if (isset($rates['RWF'])) {
                $this->updateRate('RWF', $rates['RWF']); // RWF per USD
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error updating exchange rates: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update individual rate in database
     */
    private function updateRate($currencyCode, $rate) {
        try {
            $stmt = $this->db->prepare("
                UPDATE currency_rates 
                SET rate_to_usd = ?, last_updated = CURRENT_TIMESTAMP 
                WHERE currency_code = ?
            ");
            $stmt->execute([$rate, $currencyCode]);
        } catch (Exception $e) {
            error_log("Error updating rate for {$currencyCode}: " . $e->getMessage());
        }
    }
    
    /**
     * Check if rates need updating (older than 24 hours)
     */
    public function shouldUpdateRates() {
        try {
            $stmt = $this->db->prepare("
                SELECT MAX(last_updated) as last_update 
                FROM currency_rates
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !$result['last_update']) {
                return true;
            }
            
            $lastUpdate = strtotime($result['last_update']);
            $now = time();
            
            // Update if older than 24 hours
            return ($now - $lastUpdate) > 86400;
        } catch (Exception $e) {
            error_log("Error checking rate update status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all supported currencies
     */
    public function getSupportedCurrencies() {
        try {
            $stmt = $this->db->prepare("
                SELECT currency_code, currency_name, currency_symbol 
                FROM currency_rates 
                ORDER BY currency_code
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting currencies: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Helper function to format price with current currency
 */
function formatPrice($priceUSD) {
    static $currency = null;
    if ($currency === null) {
        $currency = Currency::getInstance();
    }
    return $currency->formatPrice($priceUSD);
}

/**
 * Helper function to get current currency code
 */
function getCurrentCurrency() {
    static $currency = null;
    if ($currency === null) {
        $currency = Currency::getInstance();
    }
    return $currency->getCurrentCurrency();
}
