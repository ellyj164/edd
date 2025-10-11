<?php
/**
 * Formatting and Helper Functions
 * E-Commerce Platform
 */

if (!function_exists('formatCurrency')) {
    /**
     * Formats a number as a currency string.
     *
     * @param float|null $amount The number to format.
     * @param string $currencySymbol The currency symbol to prepend.
     * @return string The formatted currency string.
     */
    function formatCurrency($amount, $currencySymbol = '$') {
        $amount = $amount ?? 0; // Ensure amount is not null
        return $currencySymbol . number_format((float)$amount, 2, '.', ',');
    }
}

if (!function_exists('getStatusBadgeClass')) {
    /**
     * Gets the Bootstrap badge class for a given status.
     *
     * @param string $status The status string.
     * @return string The corresponding Bootstrap background class.
     */
    function getStatusBadgeClass($status) {
        switch (strtolower($status)) {
            case 'pending':
                return 'warning';
            case 'approved':
                return 'info';
            case 'rejected':
                return 'danger';
            case 'paid':
                return 'success';
            case 'shipped':
                return 'primary';
            case 'delivered':
                return 'success';
            case 'cancelled':
                return 'secondary';
            default:
                return 'secondary';
        }
    }
}
?>