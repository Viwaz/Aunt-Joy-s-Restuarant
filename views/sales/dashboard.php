<?php
require_once __DIR__.'/../../auth/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'sales') {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sales.css">
</head>
<body>
<div class="sales-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="dashboard.php" class="logo"><h3>Aunt Joy's Sales Management</h3></a>
        <ul>
            <li><a href="dashboard.php" class="active">Orders</a></li>
            <li><a href="../../auth/logout.php" class="logout">Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header>
            <h1>Order Management</h1>
            <div class="header-right">
                <span class="filter-label">Filter:</span>
                <select id="status-filter" onchange="filterOrders()">
                    <option value="">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="en route">En route</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <div id="orders-table" class="orders-grid">
                <p>Loading orders...</p>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-detail-modal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal()">&times;</button>
        <h3>Order Details</h3>
        
        <div id="modal-body">
            <!-- Populated by JS -->
        </div>

        <div class="modal-actions">
            <label for="status-dropdown">Update Status:</label>
            <select id="status-dropdown">
                <option value="pending">Pending</option>
                <option value="en route">En route</option>
                <option value="delivered">Delivered</option>
            </select>
            <button id="update-btn" class="btn-update-status" onclick="updateOrderStatus()">Update Status</button>
        </div>
    </div>
</div>

<script src="api-client.js"></script>
<script src="sales.js"></script>
</body>
</html>