<?php
session_start();
require_once 'auth/database.php';

// DB Connection
$db = new Database();
$conn = $db->getConnection();

// Fetch meals from DB
$query = $conn->prepare("SELECT * FROM meals WHERE availability='in_stock'");
$query->execute();
$result = $query->get_result();
$meals = $result->fetch_all(MYSQLI_ASSOC);
// while ($row = $result->FETCH_ASSOC()){
//     $meals[] = $row;
// }
// $meals = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">Aunt Joy's Restaurant</a>

        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">

                <?php if (isset($_SESSION['user_id'])): ?>

                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <li class="nav-item">
                            <a href="cart.php" class="nav-link">Cart</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">Logout</a>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a href="login.php" class="nav-link">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="register.php" class="nav-link">Register</a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<div class="container mt-5">

    <h2 class="mb-4">Menu</h2>

    <?php if (count($meals) === 0): ?>
        <div class="alert alert-warning">No meals available at the moment.</div>
    <?php endif; ?>

    <div class="row">

        <?php foreach ($meals as $meal): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">

                    <?php if (!empty($meal['image'])): ?>
                        <img src="<?= htmlspecialchars('menu/'.$meal['image']) ?>" class="card-img-top" style="height:220px; object-fit:cover;">
                    <?php else: ?>
                        <img src="placeholder.jpg" class="card-img-top" style="height:220px; object-fit:cover;">
                    <?php endif; ?>

                    <div class="card-body">
                        <h5><?= htmlspecialchars($meal['name']) ?></h5>
                        <p><?= htmlspecialchars($meal['description']) ?></p>
                        <strong>MWK <?= number_format($meal['price_MWK']) ?></strong><br><br>

                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-primary w-100">Add to Cart</a>
                        <?php else: ?>
                            <a href="add_to_cart.php?id=<?= $meal['id'] ?>" class="btn btn-success w-100">Add to Cart</a>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
