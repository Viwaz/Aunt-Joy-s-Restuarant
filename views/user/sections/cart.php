<?php
require_once '../../../includes/Database.php';
session_start();

$isLoggedIn = isset($_SESSION['id']) && (($_SESSION['role'] ?? null) === 'customer');
$db = (new Database())->getConnection();

$cart_items = [];
$total_price = 0;

// --- Guest cart handlers (session) ---
if (!$isLoggedIn) {
    if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) {
        $_SESSION['guest_cart'] = [];
    }

    // Handle remove/update (guest)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_remove_mid'])) {
        $mid = (int)$_POST['guest_remove_mid'];
        unset($_SESSION['guest_cart'][$mid]);
        header("Location: cart.php");
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guest_update_mid'])) {
        $mid = (int)$_POST['guest_update_mid'];
        $new_qty = max(1, (int)($_POST['quantity'] ?? 1));
        $_SESSION['guest_cart'][$mid] = $new_qty;
        header("Location: cart.php");
        exit;
    }

    // Fetch menu info for guest cart ids
    $ids = array_keys($_SESSION['guest_cart']);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $sql = "SELECT m.id as menu_item_id, m.name, m.price_MWK, m.image, m.description, cat.category_name
                FROM menu_items m
                LEFT JOIN categories cat ON m.category = cat.id
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
            $row = $byId[$mid];
            $qty = (int)($_SESSION['guest_cart'][$mid] ?? 1);
            $row['quantity'] = $qty;
            $row['id'] = $mid; // reuse id slot for template forms
            $row['subtotal'] = $row['price_MWK'] * $qty;
            $total_price += $row['subtotal'];
            $cart_items[] = $row;
        }
    }
} else {
    // --- Logged-in cart (DB) ---
    $user_id = (int)$_SESSION['id'];

    // Handle remove/update (DB)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
        $remove_id = (int)$_POST['remove_id'];
        $deleteQuery = "DELETE FROM cart WHERE id = ? AND user_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bind_param("ii", $remove_id, $user_id);
        $deleteStmt->execute();
        header("Location: cart.php");
        exit;
    }
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Aunt Joy's Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/storefront.css">
    <link rel="stylesheet" href="../styles/cart.css">
</head>
<body>

<div class="store-page">
    <?php
        $page = 'cart';
        $showSearch = false;
        include __DIR__ . '/../partials/store_header.php';
    ?>

    <main class="store-main">
        <section class="store-panel">
            <h3 class="store-panel-title">Your cart (<?php echo count($cart_items); ?> item(s))</h3>

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
                                <?php if ($isLoggedIn): ?>
                                    <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="update_qty" value="1">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>"
                                               min="1" max="50" class="qty-field">
                                        <button type="submit" class="btn-update">Update</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                                        <input type="hidden" name="guest_update_mid" value="<?php echo (int)$item['menu_item_id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>"
                                               min="1" max="50" class="qty-field">
                                        <button type="submit" class="btn-update">Update</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <div class="item-subtotal">
                                <p class="subtotal">MWK <?php echo number_format($item['subtotal']); ?></p>
                            </div>

                            <div class="item-actions">
                                <?php if ($isLoggedIn): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="remove_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-remove">Remove</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="guest_remove_mid" value="<?php echo (int)$item['menu_item_id']; ?>">
                                        <button type="submit" class="btn-remove">Remove</button>
                                    </form>
                                <?php endif; ?>
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
        </section>
    </main>

    <footer class="store-footer">
        <p>&copy; 2025 Aunt Joy's Restaurant. All Rights Reserved.</p>
    </footer>
</div>

<script src="../scripts/storefront.js"></script>
</body>
</html>