<?php
session_start();

// Ensure cart exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['meal_id'])) {
    $meal_id = $_POST['meal_id'];

    // Increase quantity if item already in cart
    if (isset($_SESSION['cart'][$meal_id])) {
        $_SESSION['cart'][$meal_id] += 1;
    } else {
        $_SESSION['cart'][$meal_id] = 1;
    }

    echo "SUCCESS"; // This is very important for JS handling
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Aunt Joy's Restaurant</title>
   
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">Aunt Joy's Restaurant</a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a href="customer_interface.php" class="nav-link">Menu</a>
                </li>
                <li class="nav-item">
                    <a href="customer_cart.php" class="nav-link active">Cart</a>
                </li>
                <li class="nav-item">
                    <a href="customer_orders.php" class="nav-link">My Orders</a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">Logout</a>
                </li>

            </ul>
        </div>
    </div>