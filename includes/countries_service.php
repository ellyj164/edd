<?php
/**
 * Countries Service - Database-backed country data access
 * Provides methods to query country information from the database
 */
declare(strict_types=1);

class CountriesService {
    
    /**
     * Get all countries from database
     * @return array List of all countries with all fields
     */
    public static function getAll(): array {
        try {
            $pdo = db();
            $stmt = $pdo->query("
                SELECT 
                    id, name, iso2, iso3, dial_code, is_eu, 
                    currency_code, currency_symbol, flag_emoji,
                    created_at, updated_at
                FROM countries
                ORDER BY name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("CountriesService::getAll failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get country by ISO2 code
     * @param string $iso2 Two-letter ISO code (e.g., 'US', 'RW')
     * @return array|null Country data or null if not found
     */
    public static function getByIso2(string $iso2): ?array {
        try {
            $pdo = db();
            $stmt = $pdo->prepare("
                SELECT 
                    id, name, iso2, iso3, dial_code, is_eu, 
                    currency_code, currency_symbol, flag_emoji,
                    created_at, updated_at
                FROM countries
                WHERE iso2 = ?
                LIMIT 1
            ");
            $stmt->execute([strtoupper($iso2)]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("CountriesService::getByIso2 failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get EU member countries
     * @return array List of EU countries
     */
    public static function getEUCountries(): array {
        try {
            $pdo = db();
            $stmt = $pdo->query("
                SELECT 
                    id, name, iso2, iso3, dial_code, is_eu, 
                    currency_code, currency_symbol, flag_emoji
                FROM countries
                WHERE is_eu = 1
                ORDER BY name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("CountriesService::getEUCountries failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get countries as JSON for JavaScript
     * Returns simplified format matching existing JavaScript structure
     * @return string JSON-encoded country list
     */
    public static function getAsJson(): string {
        $countries = self::getAll();
        $simplified = array_map(function($country) {
            return [
                'code' => $country['iso2'],
                'name' => $country['name'],
                'flag' => $country['flag_emoji'],
                'phone' => $country['dial_code'],
                'currency' => $country['currency_code']
            ];
        }, $countries);
        
        return json_encode($simplified, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Check if database has country data
     * @return bool True if countries table exists and has data
     */
    public static function isAvailable(): bool {
        try {
            $pdo = db();
            $stmt = $pdo->query("SELECT COUNT(*) FROM countries");
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
