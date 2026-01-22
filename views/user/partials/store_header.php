<?php
/**
 * Storefront header + primary nav for customer-facing views.
 *
 * Usage:
 *   $page = 'menu' | 'cart' | 'orders' | 'checkout';
 *   $showSearch = true/false;
 *   include __DIR__ . '/../partials/store_header.php';
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page = $page ?? '';
$showSearch = $showSearch ?? false;
$isLoggedIn = isset($_SESSION['id']);
?>

<header class="store-header">
    <div class="store-topbar">
        <a class="store-logo" href="customer_interface.php">Aunt Joy's</a>

        <?php if ($showSearch): ?>
            <form class="store-search" onsubmit="searchMeals(event)">
                <input type="text" id="search-keyword" placeholder="Search for meals..." autocomplete="off">
                <button type="submit">Search</button>
            </form>
        <?php else: ?>
            <div class="store-search store-search--placeholder"></div>
        <?php endif; ?>

        <div class="store-actions">
            <div class="store-account">
                <?php if ($isLoggedIn): ?>
                    <a class="store-link" href="../../../auth/logout.php">Logout</a>
                <?php else: ?>
                    <button type="button" class="store-link store-link--button" onclick="openLoginModal()">Sign in</button>
                <?php endif; ?>
            </div>

            <a class="store-cart" href="cart.php" aria-label="Cart">
                <span class="store-cart-label">Cart</span>
                <span class="store-cart-badge" id="cart-count">0</span>
            </a>
        </div>
    </div>

    <nav class="store-nav">
        <a class="store-nav-link <?php echo $page === 'menu' ? 'active' : ''; ?>" href="customer_interface.php">Menu</a>
        <a class="store-nav-link <?php echo $page === 'orders' ? 'active' : ''; ?>" href="customer_orders.php">My Orders</a>
        <a class="store-nav-link <?php echo $page === 'cart' ? 'active' : ''; ?>" href="cart.php">Cart</a>
    </nav>
</header>

<!-- Login Modal (AJAX) -->
<div class="store-modal-overlay" id="login-modal" style="display:none;">
    <div class="store-modal">
        <button class="store-modal-close" type="button" onclick="closeLoginModal()">&times;</button>
        <h3 class="store-modal-title">Sign in to continue</h3>
        <p class="store-modal-sub">Login to place your order and auto-fill your delivery details.</p>        <form id="login-modal-form">
            <div class="store-field">
                <label>Email</label>
                <input type="email" name="email" required autocomplete="email">
            </div>
            <div class="store-field">
                <label>Password</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <div id="login-modal-error" class="store-modal-error" style="display:none;"></div>
            <button class="store-modal-submit" type="submit">Login</button>
            <div class="store-modal-alt">
                <span>New here?</span> <a href="../../../auth/register.php">Create account</a>
            </div>
        </form>
    </div>
</div>
