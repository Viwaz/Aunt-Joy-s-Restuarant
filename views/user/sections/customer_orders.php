<?php
require_once '../../../auth/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
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
    <link rel="stylesheet" href="../styles/cart.css">
    <link rel="stylesheet" href="../styles/orders.css">
</head>
<body>
<div class="cart-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="customer_interface.php" class="logo"><h3>Aunt Joy's Customer orders</h3></a>
        <ul>
            <li><a href="customer_interface.php">Menu</a></li>
            <li><a href="cart.php">Cart</a></li>
            <li><a href="customer_orders.php" class="active">Orders</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header>
            <h1>My Orders</h1>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Current Orders Section -->
            <div class="orders-section">
                <h2>Current Orders</h2>
                <div id="current-orders" class="orders-list">
                    <p>Loading...</p>
                </div>
            </div>

            <!-- Order History Section -->
            <div class="orders-section">
                <h2>Order History</h2>
                <div id="history-orders" class="orders-list">
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
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

<script src="../scripts/orders.js"></script>
</body>
</html>
