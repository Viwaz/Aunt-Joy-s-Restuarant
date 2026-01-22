
<?php
require_once __DIR__ . '/../../../auth/auth.php';

$auth = new Auth();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/storefront.css">
</head>
<body>

<div id="notification-popup" class="notification-popup" style="display:none;"></div>

<div class="store-page">
    <?php
        $page = 'menu';
        $showSearch = true;
        include __DIR__ . '/../partials/store_header.php';
    ?>

    <main class="store-main">
        <section class="store-hero">
            <div>
                <h2>Today's Deals</h2>
                <p>Fresh meals, fast delivery, and great value — shop your favorites.</p>
            </div>
            <div class="store-hero-cta">Free delivery over MWK 20,000</div>
        </section>

        <section class="store-panel">
            <h3 class="store-panel-title">Shop by category</h3>
            <div id="category-list"></div>
        </section>

        <section class="store-panel">
            <h3 class="store-panel-title">Recommended for you</h3>
            <div id="meals-grid"></div>
        </section>
    </main>

    <footer class="store-footer">
        <p>&copy; 2025 Aunt Joy's Restaurant. All Rights Reserved.</p>
    </footer>
</div>

<script src="../api-client.js"></script>
<script src="../scripts/customer.js"></script>
<script src="../scripts/storefront.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>