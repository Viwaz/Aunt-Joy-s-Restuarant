<?php
require_once __DIR__ . '/../../../auth/auth.php';
require_once __DIR__ . '/../../../includes/Database.php';

$auth = new Auth();
$isLoggedIn = $auth->isLoggedIn() && (($_SESSION['role'] ?? null) === 'customer');
$user_id = $isLoggedIn ? (int)$_SESSION['id'] : 0;
$db = (new Database())->getConnection();

$cart_items = [];
$total = 0;

if ($isLoggedIn) {
    // Fetch DB cart items
    $query = "SELECT c.id as cart_id, c.menu_item_id, c.quantity, m.name, m.price_MWK, m.image
              FROM cart c
              JOIN menu_items m ON c.menu_item_id = m.id
              WHERE c.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($r = $result->fetch_assoc()) {
        $r['subtotal'] = $r['price_MWK'] * $r['quantity'];
        $total += $r['subtotal'];
        $cart_items[] = $r;
    }
    $stmt->close();
} else {
    // Fetch session guest cart items
    $guest = $_SESSION['guest_cart'] ?? [];
    if (is_array($guest) && !empty($guest)) {
        $ids = array_keys($guest);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sql = "SELECT m.id as menu_item_id, m.name, m.price_MWK, m.image
                FROM menu_items m
                WHERE m.id IN ($placeholders) AND m.is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $byId = [];
        foreach ($rows as $r) $byId[(int)$r['menu_item_id']] = $r;

        foreach ($ids as $mid) {
            $mid = (int)$mid;
            if (!isset($byId[$mid])) continue;
            $r = $byId[$mid];
            $qty = max(1, (int)($guest[$mid] ?? 1));
            $r['quantity'] = $qty;
            $r['subtotal'] = $r['price_MWK'] * $qty;
            $total += $r['subtotal'];
            $cart_items[] = $r;
        }
    }
}

$errors = [];

// Prefill from user profile (if available) when logged in
$profile_address = '';
$profile_phone = '';
if ($isLoggedIn) {
    $stmt = $db->prepare("SELECT delivery_address, phone_num FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $profile_address = (string)($row['delivery_address'] ?? '');
    $profile_phone = (string)($row['phone_num'] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isLoggedIn) {
        // Checkout form submission requires login; frontend will show modal.
        $errors[] = 'Please login to place your order.';
    }

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

            // store latest delivery details back to user profile
            $up = $db->prepare("UPDATE users SET delivery_address = ?, phone_num = ? WHERE id = ?");
            $up->bind_param('ssi', $address, $phone, $user_id);
            $up->execute();

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
    <link rel="stylesheet" href="../styles/storefront.css">
    <link rel="stylesheet" href="../styles/cart.css">
</head>
<body>
<div class="store-page">
    <?php
        $page = 'checkout';
        $showSearch = false;
        include __DIR__ . '/../partials/store_header.php';
    ?>

    <main class="store-main">
        <section class="store-panel">
            <h3 class="store-panel-title">Checkout</h3>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
                </div>
            <?php endif; ?>

            <div class="checkout-grid">
                <div class="checkout-form">
                    <form method="POST" id="checkout-form" data-logged-in="<?php echo $isLoggedIn ? '1' : '0'; ?>">
                        <div class="mb-3">
                            <label for="delivery_address" class="form-label">Delivery Address</label>
                            <textarea id="delivery_address" name="delivery_address" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['delivery_address'] ?? ($profile_address ?? '')); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Contact Phone</label>
                            <input id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? ($profile_phone ?? '')); ?>">
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
        </section>
    </main>

    <footer class="store-footer">
        <p>&copy; 2025 Aunt Joy's Restaurant. All Rights Reserved.</p>
    </footer>
</div>

<script src="../scripts/storefront.js"></script>
</body>
</html>
