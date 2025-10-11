<?php
/**
 * Users Management Entry Point
 * Redirects to the users directory index for user management functionality
 * This file resolves routing conflicts between admin/users/ directory and admin/users.php route
 */

// Redirect to the actual users management page
include_once __DIR__ . '/users/index.php';
?>