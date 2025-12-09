<?php
require_once '../../../auth/auth.php';
require_once '../../../includes/Database.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    // If user has a session cart (legacy), preserve it
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart'])>0) {
        $_SESSION['pending_cart'] = $_SESSION['cart'];
    }
    $_SESSION['redirect_after_login'] = 'user/checkout.php';
    header('Location: ../../../auth/login.php');
    exit;
}

$user_id = $_SESSION['id'];
$db = (new Database())->getConnection();

// Fetch cart items
$query = "SELECT c.id as cart_id, c.menu_item_id, c.quantity, m.name, m.price_MWK, m.image
          FROM cart c
          JOIN menu_items m ON c.menu_item_id = m.id
          WHERE c.user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
$total = 0;
while ($r = $result->fetch_assoc()) {
    $r['subtotal'] = $r['price_MWK'] * $r['quantity'];
    $total += $r['subtotal'];
    $cart_items[] = $r;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['delivery_address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($address === '') $errors[] = 'Delivery address is required.';
    if ($phone === '') $errors[] = 'Phone number is required.';

    if (empty($cart_items)) $errors[] = 'Your cart is empty.';

    if (empty($errors)) {
        // begin transaction
        $db->begin_transaction();
        try {
            
            ;
            $insertOrder = $db->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, phone_num) VALUES (?, ?, ?, ?)");
            $insertOrder->bind_param('idss', $user_id, $total, $address, $phone);
            $insertOrder->execute();

            $order_id = $db->insert_id;

            // insert order items
            $insItem = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cart_items as $it) {
                $mid = $it['menu_item_id'];
                $qty = $it['quantity'];
                $price = $it['price_MWK'];
                $insItem->bind_param('iiid', $order_id, $mid, $qty, $price);
                $insItem->execute();
            }

            // clear cart for user
            $del = $db->prepare("DELETE FROM cart WHERE user_id = ?");
            $del->bind_param('i', $user_id);
            $del->execute();

            $db->commit();

            // redirect to orders page or confirmation
            header('Location: customer_orders.php?order_created=1&order_id=' . $order_id);
            exit;

        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Failed to create order: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/cart.css">
</head>
<body>
<div class="cart-container">
    <div class="sidebar">
        <a href="customer_interface.php" class="logo"> Aunt Joy's</a>
        <ul>
            <li><a href="customer_interface.php"> Menu</a></li>
            <li><a href="cart.php">Cart</a></li>
            <li><a href="customer_orders.php">Orders</a></li>
        </ul>
    </div>

    <div class="main-content">
        <header>
            <h1>Checkout</h1>
        </header>
        <div class="content-area">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <div class="checkout-form">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Delivery Address</label>
                            <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Contact Phone</label>
                            <input id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <button class="btn-checkout" type="submit">Place Order (MWK <?php echo number_format($total + 500); ?>)</button>
                    </form>
                </div>

                <div class="checkout-summary">
                    <h4>Order Summary</h4>
                    <?php if (empty($cart_items)): ?>
                        <p>No items in cart.</p>
                    <?php else: ?>
                        <ul class="list-unstyled">
                            <?php foreach ($cart_items as $it): ?>
                                <li><?php echo htmlspecialchars($it['name']); ?> x <?php echo (int)$it['quantity']; ?> — MWK <?php echo number_format($it['subtotal']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <hr>
                        <p>Subtotal: MWK <?php echo number_format($total); ?></p>
                        <p>Delivery: MWK 500</p>
                        <p><strong>Total: MWK <?php echo number_format($total + 500); ?></strong></p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
