<?php
header("Content-Type: application/json");
session_start();
require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/includes/Cart.php';

// Read POST data
$input = json_decode(file_get_contents("php://input"), true);
$menu_item_id = (int)($input['menu_item_id'] ?? 0);
$quantity = (int)($input['quantity'] ?? 1);

if ($menu_item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid menu item']);
    exit;
}
if ($quantity <= 0) $quantity = 1;

// Guest cart (session-based) if not logged in
if (!isset($_SESSION['guest_cart']) || !is_array($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}
$current = (int)($_SESSION['guest_cart'][$menu_item_id] ?? 0);
$_SESSION['guest_cart'][$menu_item_id] = $current + $quantity;
echo json_encode(['success' => true]);
