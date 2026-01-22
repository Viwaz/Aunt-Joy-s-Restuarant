<?php
header("Content-Type: application/json");
session_start();
require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/includes/Cart.php';

// Session-only cart count
$guest = $_SESSION['guest_cart'] ?? [];
$count = 0;
if (is_array($guest)) {
    foreach ($guest as $qty) $count += (int)$qty;
}
echo json_encode(['success' => true, 'count' => $count]);
?>
