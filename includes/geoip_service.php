<?php
/**
 * GeoIP Service
 * Detects user country from IP address
 */

class GeoIPService {
    
    /**
     * Detect country from IP address
     */
    public static function detectCountry($ip = null) {
        // Use provided IP or get from request
        if ($ip === null) {
            $ip = self::getClientIP();
        }
        
        // Check if we have a GeoIP provider configured
        $provider = env('GEOIP_PROVIDER', '');
        
        if ($provider === 'maxmind' && function_exists('geoip_country_code_by_name')) {
            try {
                $country = geoip_country_code_by_name($ip);
                if ($country) {
                    return $country;
                }
            } catch (Exception $e) {
                error_log("GeoIP detection error: " . $e->getMessage());
            }
        }
        
        // Fallback: Use IP-API.com (free tier, 45 req/min)
        if (!self::isLocalIP($ip)) {
            $country = self::detectViaIPAPI($ip);
            if ($country) {
                return $country;
            }
        }
        
        // Default fallback
        return 'US';
    }
    
    /**
     * Detect country using IP-API.com
     */
    private static function detectViaIPAPI($ip) {
        try {
            $url = "http://ip-api.com/json/{$ip}?fields=countryCode";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['countryCode']) && !empty($data['countryCode'])) {
                    return $data['countryCode'];
                }
            }
        } catch (Exception $e) {
            error_log("IP-API detection error: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Check if IP is local/private
     */
    private static function isLocalIP($ip) {
        if (in_array($ip, ['127.0.0.1', '::1', 'localhost'])) {
            return true;
        }
        
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get phone country code from country
     */
    public static function getPhoneCode($countryCode) {
        $phoneCodes = [
            'US' => '+1', 'CA' => '+1', 'GB' => '+44', 'AU' => '+61',
            'FR' => '+33', 'DE' => '+49', 'IT' => '+39', 'ES' => '+34',
            'RW' => '+250', 'KE' => '+254', 'UG' => '+256', 'TZ' => '+255',
            'IN' => '+91', 'CN' => '+86', 'JP' => '+81', 'MX' => '+52',
            'BR' => '+55', 'ZA' => '+27', 'NL' => '+31', 'BE' => '+32',
        ];
        
        return $phoneCodes[strtoupper($countryCode)] ?? '+1';
    }
}
