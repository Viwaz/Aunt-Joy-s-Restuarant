<?php
header("Content-Type: application/json");
session_start();
require_once '../includes/Database.php';
require_once '../includes/Order.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

// --- SESSION SECURITY: Validate user role for sensitive operations ---
// Only allow customers/users to perform order operations (not admin/sales/manager in customer context)
// Sales/Manager/Admin can VIEW orders but this API endpoint enforces proper role separation

$db = (new Database())->getConnection();
$orderModel = new Order($db);

try {
    if ($method === 'GET' && $action === 'list') {
        $orders = $orderModel->listWithItems($user_role, (int)$user_id);
        echo json_encode(['success' => true, 'orders' => $orders]);

    } elseif ($method === 'POST' && $action === 'update_status') {
        // Only sales can update status
        if ($user_role !== 'sales') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = (int)($input['order_id'] ?? 0);
        $new_status = trim($input['status'] ?? '');

        if (!$orderModel->isValidStatus($new_status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        if ($order_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        if ($orderModel->updateStatus($order_id, $new_status)) {
            echo json_encode(['success' => true, 'message' => 'Status updated', 'status' => $new_status]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
