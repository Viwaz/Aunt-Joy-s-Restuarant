<?php
header("Content-Type: application/json");
session_start();
require_once dirname(__DIR__) . '/includes/Database.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['id'];
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
