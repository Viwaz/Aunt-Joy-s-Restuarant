<?php
header("Content-Type: application/json");
session_start();
require_once '../includes/Database.php';

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

try {
    if ($method === 'GET' && $action === 'list') {
        // Customer sees only their orders; sales see all
        $query = "SELECT o.*, u.email, u.username 
                  FROM orders o 
                  JOIN users u ON o.user_id = u.id";
        
        if ($user_role === 'customer') {
            $query .= " WHERE o.user_id = ?";
        }
        
        $query .= " ORDER BY o.order_date DESC";

        $stmt = $db->prepare($query);
        if ($user_role === 'customer') {
            $stmt->bind_param('i', $user_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);

        // Fetch items for each order
        $itemQuery = "SELECT order_id, menu_item_id, quantity, price, (quantity * price) as subtotal FROM order_items WHERE order_id = ?";
        $itemStmt = $db->prepare($itemQuery);

        foreach ($orders as &$order) {
            $order_id = $order['order_id'];
            $itemStmt->bind_param('i', $order_id);
            $itemStmt->execute();
            $order['items'] = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

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

        $valid_statuses = ['pending','en route','delivered'];
        if (!in_array($new_status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }

        if ($order_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }

        $updateQuery = "UPDATE orders SET status = ? WHERE order_id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bind_param('si', $new_status, $order_id);
        $updateStmt->execute();

        if ($updateStmt->affected_rows > 0) {
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
