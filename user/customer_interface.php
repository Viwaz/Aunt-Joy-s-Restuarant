




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="customer.css">
</head>
<body>

<div class="customer-container">
    <!-- Sidebar -->
    <div class="sidebar">
    <a href="customer_interface.php" class="logo"> Aunt Joy's</a>
        <ul>
            <li><a href="customer_interface.php"> Menu</a></li>
            <li><a href="cart.php"> Cart</a>
            <div class="header-right">
                <a class="cart-badge" id="cart-count">0</a></div>
            </li>
            <li><a href="customer_orders.php"> My Orders</a></li>
        </ul>
        
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <h1>Menu</h1>
            <div class="header-right"></div>
        </header>

        <div class="content-area">
            <!-- Search -->
            <div class="search-section">
                <form class="search-form" onsubmit="searchMeals(event)">
                    <input type="text" id="search-keyword" placeholder="Search for meals...">
                    <button type="submit">Search</button>
                </form>
            </div>

            <!-- Categories -->
            <div class="categories-section">
                <h3>Filter by Category</h3>
                <div id="category-list"></div>
            </div>

            <!-- Meals Grid -->
            <div id="meals-grid"></div>
        </div>

        <footer>
            <p>&copy; 2025 Aunt Joy's Restaurant. All Rights Reserved.</p>
        </footer>
    </div>
</div>

<script src="customer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>