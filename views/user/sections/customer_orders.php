<?php
require_once __DIR__ . '/../../../auth/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header("Location: " . __DIR__ . "/../../../auth/login.php");
    exit;
}

// --- SECURITY: Only customers can view their own orders ---
if ($_SESSION['role'] !== 'customer') {
    echo "Access Denied. Redirecting to login page...";
    sleep(3);
    header("Location: ../../../auth/login.php");
    exit;
}

$user_id = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href=__DIR__ ."../styles/storefront.css">
    <link rel="stylesheet" href=__DIR__ ."../styles/cart.css">
    <link rel="stylesheet" href=__DIR__ ."../styles/orders.css">
</head>
<body>
<div class="store-page">
    <?php
        $page = 'orders';
        $showSearch = false;
        include __DIR__ . '/../partials/store_header.php';
    ?>

    <main class="store-main">
        <section class="store-panel">
            <h3 class="store-panel-title">Current orders</h3>
            <div id="current-orders" class="orders-list">
                <p>Loading...</p>
            </div>
        </section>

        <section class="store-panel">
            <h3 class="store-panel-title">Order history</h3>
            <div id="history-orders" class="orders-list">
                <p>Loading...</p>
            </div>
        </section>
    </main>

    <footer class="store-footer">
        <p>&copy; 2025 Aunt Joy's Restaurant. All Rights Reserved.</p>
    </footer>
</div>

<!-- Order Details Modal -->
<div id="order-modal" class="modal-overlay">
    <div class="modal-content order-modal">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h3>Order Details</h3>
        <div id="modal-body">
            <!-- Populated by JS -->
        </div>
    </div>
</div>

<script src=__DIR__ ."../scripts/orders.js"></script>
<script src=__DIR__ ."../scripts/storefront.js"></script>
</body>
</html>
