<?php
header("Content-Type: application/json");
session_start();
require_once dirname(__DIR__) . '/includes/Database.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Not logged in']);
    exit;
}

// --- SESSION SECURITY: Only customer/user roles should access cart ---
$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? null;

// Validate that this is a customer/user accessing their own cart
// (Admin/Sales/Manager should not have carts)
if ($user_role !== 'customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'count' => 0, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();

$query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$count = $row['total'] ?? 0;
echo json_encode(['success' => true, 'count' => (int)$count]);
?>
