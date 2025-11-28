<?php
header("Content-Type: application/json");
session_start();
require_once "../database.php"; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

// Read POST data
$input = json_decode(file_get_contents("php://input"), true);
$menu_item_id = $input['menu_item_id'] ?? 0;
$quantity = $input['quantity'] ?? 1;

if (!$menu_item_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid menu item']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if item already exists in cart
$stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND menu_item_id = ?");
$stmt->bind_param("ii", $user_id, $menu_item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Update quantity
    $newQty = $row['quantity'] + $quantity;
    $stmtUpdate = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND menu_item_id = ?");
    $stmtUpdate->bind_param("iii", $newQty, $user_id, $menu_item_id);
    $stmtUpdate->execute();
} else {
    // Insert new item
    $stmtInsert = $conn->prepare("INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
    $stmtInsert->bind_param("iii", $user_id, $menu_item_id, $quantity);
    $stmtInsert->execute();
}

echo json_encode(['success' => true]);
