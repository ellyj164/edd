<?php
/**
 * Currency Service
 * Handles multi-currency conversion with caching
 */

class CurrencyService {
    private $db;
    private $baseCurrency = 'USD';
    private $cacheLifetime = 3600; // 1 hour
    
    // Country to currency mapping
    private $countryToCurrency = [
        'US' => 'USD', 'CA' => 'USD', 'GB' => 'GBP', 'EU' => 'EUR',
        'FR' => 'EUR', 'DE' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
        'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR',
        'IE' => 'EUR', 'GR' => 'EUR', 'FI' => 'EUR', 'LU' => 'EUR',
        'RW' => 'RWF', // Rwanda
        'KE' => 'KES', 'UG' => 'UGX', 'TZ' => 'TZS', // East Africa
        'AU' => 'AUD', 'NZ' => 'NZD', 'JP' => 'JPY', 'CN' => 'CNY',
        'IN' => 'INR', 'MX' => 'MXN', 'BR' => 'BRL', 'ZA' => 'ZAR',
    ];
    
    // Supported currencies for Stripe
    private $supportedCurrencies = ['USD', 'EUR', 'GBP', 'RWF', 'KES', 'UGX', 'AUD', 'CAD'];
    
    public function __construct($db = null) {
        $this->db = $db ?? db();
        $this->baseCurrency = strtoupper(env('CURRENCY_BASE', 'USD'));
    }
    
    /**
     * Detect currency from country code
     * Rwanda: RWF, EU countries: EUR, All others: USD
     */
    public function detectCurrency($countryCode) {
        $countryCode = strtoupper($countryCode);
        
        // Rwanda gets RWF
        if ($countryCode === 'RW') {
            return 'RWF';
        }
        
        // EU countries get EUR
        $euCountries = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 
                        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 
                        'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];
        if (in_array($countryCode, $euCountries)) {
            return 'EUR';
        }
        
        // All other countries get USD
        return 'USD';
    }
    
    /**
     * Get exchange rate from cache or API
     */
    public function getRate($from, $to) {
        $from = strtoupper($from);
        $to = strtoupper($to);
        
        // Same currency
        if ($from === $to) {
            return 1.0;
        }
        
        // Check cache
        $stmt = $this->db->prepare("
            SELECT rate, updated_at 
            FROM currency_rates 
            WHERE base = ? AND quote = ?
        ");
        $stmt->execute([$from, $to]);
        $cached = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cached) {
            $age = time() - strtotime($cached['updated_at']);
            if ($age < $this->cacheLifetime) {
                return (float)$cached['rate'];
            }
        }
        
        // Fetch fresh rate (simplified - you can integrate real API)
        $rate = $this->fetchExchangeRate($from, $to);
        
        if ($rate) {
            // Update cache
            $stmt = $this->db->prepare("
                INSERT INTO currency_rates (base, quote, rate, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE rate = ?, updated_at = NOW()
            ");
            $stmt->execute([$from, $to, $rate, $rate]);
            return $rate;
        }
        
        // Fallback rates if API unavailable
        return $this->getFallbackRate($from, $to);
    }
    
    /**
     * Convert amount from one currency to another
     */
    public function convert($amount, $from, $to) {
        $rate = $this->getRate($from, $to);
        return round($amount * $rate, 2);
    }
    
    /**
     * Fetch exchange rate from external API
     * This is a simplified implementation - integrate with a real service
     */
    private function fetchExchangeRate($from, $to) {
        // Mock implementation - replace with actual API call
        // Example: https://api.exchangerate-api.com/v4/latest/{base}
        // Or use your preferred forex API
        
        return $this->getFallbackRate($from, $to);
    }
    
    /**
     * Fallback static rates (used when API unavailable)
     */
    private function getFallbackRate($from, $to) {
        // Static exchange rates (update periodically or via admin)
        $rates = [
            'USD_EUR' => 0.92,
            'USD_GBP' => 0.79,
            'USD_RWF' => 1320.00,
            'USD_KES' => 129.00,
            'USD_UGX' => 3700.00,
            'USD_AUD' => 1.52,
            'USD_CAD' => 1.36,
            'EUR_USD' => 1.09,
            'EUR_GBP' => 0.86,
            'EUR_RWF' => 1435.00,
            'GBP_USD' => 1.27,
            'GBP_EUR' => 1.16,
            'RWF_USD' => 0.00076,
            'RWF_EUR' => 0.00070,
        ];
        
        $key = "{$from}_{$to}";
        if (isset($rates[$key])) {
            return $rates[$key];
        }
        
        // Try inverse
        $inverseKey = "{$to}_{$from}";
        if (isset($rates[$inverseKey])) {
            return 1.0 / $rates[$inverseKey];
        }
        
        // Default to 1:1 if not found
        return 1.0;
    }
    
    /**
     * Format amount with currency symbol
     */
    public function format($amount, $currency = 'USD') {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'RWF' => 'FRw',
            'KES' => 'KSh',
            'UGX' => 'USh',
            'AUD' => 'A$',
            'CAD' => 'C$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        $formatted = number_format($amount, 2);
        
        // Some currencies use suffix
        if (in_array($currency, ['RWF', 'KES', 'UGX'])) {
            return $formatted . ' ' . $symbol;
        }
        
        return $symbol . $formatted;
    }
}
