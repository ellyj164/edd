<!-- Buyer Sidebar Component -->
<div class="col-md-3 col-lg-2 sidebar">
    <div class="sidebar-sticky">
        <h5 class="sidebar-heading">Buyer Center</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="/buyer/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>" href="/buyer/profile.php">
                    <i class="fas fa-user"></i> Profile & Account
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>" href="/buyer/orders.php">
                    <i class="fas fa-box"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'wallet.php' ? 'active' : ''; ?>" href="/buyer/wallet.php">
                    <i class="fas fa-wallet"></i> Payments & Wallet
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'returns.php' ? 'active' : ''; ?>" href="/buyer/returns.php">
                    <i class="fas fa-undo"></i> Returns & Refunds
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'disputes.php' ? 'active' : ''; ?>" href="/buyer/disputes.php">
                    <i class="fas fa-gavel"></i> Disputes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'loyalty.php' ? 'active' : ''; ?>" href="/buyer/loyalty.php">
                    <i class="fas fa-star"></i> Loyalty & Rewards
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'wishlist.php' ? 'active' : ''; ?>" href="/buyer/wishlist.php">
                    <i class="fas fa-heart"></i> Wishlist
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : ''; ?>" href="/buyer/messages.php">
                    <i class="fas fa-envelope"></i> Messages
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'support.php' ? 'active' : ''; ?>" href="/buyer/support.php">
                    <i class="fas fa-headset"></i> Support
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'privacy.php' ? 'active' : ''; ?>" href="/buyer/privacy.php">
                    <i class="fas fa-shield-alt"></i> Privacy & Compliance
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.sidebar {
    background-color: #ffffff;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.sidebar-heading {
    font-size: 0.75rem;
    font-weight: 800;
    color: #5a5c69;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    padding: 1.5rem 1rem 0.5rem;
}

.nav-link {
    color: #858796;
    padding: 0.75rem 1rem;
    border-radius: 0.35rem;
    margin: 0.125rem 1rem;
}

.nav-link:hover,
.nav-link.active {
    color: #5a5c69;
    background-color: #eaecf4;
}

.nav-link i {
    margin-right: 0.5rem;
    width: 1rem;
    text-align: center;
}
</style>