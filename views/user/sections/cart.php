<?php
require_once '../../../includes/Database.php';
session_start();
// Session and role check
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../../../auth/login.php');
    exit;
}
$user_id = $_SESSION['id'];
$db = (new Database())->getConnection();

// Fetch cart items for the logged-in user
$cart_items = [];
$total_price = 0;

$query = "SELECT c.id, c.menu_item_id, c.quantity, m.name, m.price_MWK, m.image, m.description, cat.category_name
          FROM cart c
          JOIN menu_items m ON c.menu_item_id = m.id
          LEFT JOIN categories cat ON m.category = cat.id
          WHERE c.user_id = ?
          ORDER BY c.id DESC";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['subtotal'] = $row['price_MWK'] * $row['quantity'];
    $total_price += $row['subtotal'];
    $cart_items[] = $row;
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    $remove_id = (int)$_POST['remove_id'];
    $deleteQuery = "DELETE FROM cart WHERE id = ? AND user_id = ?";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $remove_id, $user_id);
    $deleteStmt->execute();
    header("Location: cart.php");
    exit;
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $cart_id = (int)$_POST['cart_id'];
    $new_qty = (int)$_POST['quantity'];
    
    if ($new_qty > 0) {
        $updateQuery = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bind_param("iii", $new_qty, $cart_id, $user_id);
        $updateStmt->execute();
    }
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/cart.css">
</head>
<body>

<div class="cart-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <a href="customer_interface.php" class="logo">Aunt Joy's</a>
        <ul>
            <li><a href="customer_interface.php"> Menu</a></li>
            <li><a href="customer_orders.php">Orders</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header>
            <h1>Your Cart</h1>
            <div class="header-right">
                <span class="items-count"><?php echo count($cart_items); ?> item(s)</span>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <p>Your cart is empty</p>
                    <a href="customer_interface.php" class="btn-continue-shopping">Continue Shopping</a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                               <img src="../../../menu/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-img">
                            
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="item-category"><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></p>
                                <p class="item-desc"><?php echo htmlspecialchars($item['description']); ?></p>
                            </div>

                            <div class="item-price">
                                <p class="unit-price">MWK <?php echo number_format($item['price_MWK']); ?></p>
                            </div>

                            <div class="item-qty">
                                <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="update_qty" value="1">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="50" class="qty-field">
                                    <button type="submit" class="btn-update">Update</button>
                                </form>
                            </div>

                            <div class="item-subtotal">
                                <p class="subtotal">MWK <?php echo number_format($item['subtotal']); ?></p>
                            </div>

                            <div class="item-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="remove_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>MWK <?php echo number_format($total_price); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>MWK 500</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>MWK <?php echo number_format($total_price + 500); ?></span>
                    </div>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                    <a href="customer_interface.php" class="btn-continue-shopping">Continue Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>