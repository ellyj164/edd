<?php
/**
 * UI Components Autoloader
 * Load all reusable UI components
 */

// Define component directory
define('COMPONENTS_DIR', __DIR__);

// Autoload all component classes
spl_autoload_register(function ($className) {
    $componentFile = COMPONENTS_DIR . '/' . $className . '.php';
    if (file_exists($componentFile)) {
        require_once $componentFile;
    }
});

// Include all component files for immediate availability
$componentFiles = [
    'ProductCard.php',
    'PriceBadge.php', 
    'RatingStars.php',
    'AddToCartButton.php',
    'Toast.php'
];

foreach ($componentFiles as $file) {
    $filePath = COMPONENTS_DIR . '/' . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}
?>