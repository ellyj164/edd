<?php
// Get the current script's path to determine the active page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="col-md-2 sidebar p-3" style="background-color: #2c3e50; min-height: 100vh;">
    <h4 class="text-white mb-4">Admin Panel</h4>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link text-white" href="/admin/index.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="/admin/products/index.php">
                <i class="fas fa-cube"></i> Products
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="/admin/inventory/index.php">
                <i class="fas fa-boxes"></i> Inventory
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="/admin/orders/index.php">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white active" href="/admin/payouts/index.php">
                <i class="fas fa-money-bill-wave"></i> Payouts
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="/admin/vendors/index.php">
                <i class="fas fa-store"></i> Vendors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="/admin/finance/index.php">
                <i class="fas fa-chart-line"></i> Finance
            </a>
        </li>
    </ul>
</div>