<?php
/**
 * User Logout
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

Session::destroy();

// Redirect to homepage with success message
redirect('/?logout=1');
?>