<?php
/**
 * Order "model" / data-access layer.
 *
 * Encapsulates common order queries used by api/orders_api.php and dashboards.
 */
class Order {
    private mysqli $conn;

    public function __construct(mysqli $db) {
        $this->conn = $db;
    }

    /**
     * List orders. Customers only see their own orders; staff can see all.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listWithItems(?string $role, int $user_id): array {
        $query = "SELECT o.*, u.email, u.username
                  FROM orders o
                  JOIN users u ON o.user_id = u.id";

        $params = null;
        if ($role === 'customer') {
            $query .= " WHERE o.user_id = ?";
            $params = $user_id;
        }
        $query .= " ORDER BY o.order_date DESC";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare orders query');
        }
        if ($params !== null) {
            $stmt->bind_param('i', $params);
        }
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $itemStmt = $this->conn->prepare(
            "SELECT order_id, menu_item_id, quantity, price, (quantity * price) as subtotal
             FROM order_items
             WHERE order_id = ?"
        );
        if (!$itemStmt) {
            throw new Exception('Failed to prepare order items query');
        }

        foreach ($orders as &$order) {
            $order_id = (int)($order['order_id'] ?? 0);
            $itemStmt->bind_param('i', $order_id);
            $itemStmt->execute();
            $order['items'] = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        unset($order);
        $itemStmt->close();

        return $orders;
    }

    public function isValidStatus(string $status): bool {
        return in_array($status, ['pending', 'en route', 'delivered'], true);
    }

    /**
     * @return bool true if an order row existed and was updated (or already had that status)
     */
    public function updateStatus(int $order_id, string $new_status): bool {
        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare update status query');
        }
        $stmt->bind_param('si', $new_status, $order_id);
        $stmt->execute();

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected > 0) return true;

        // If status is unchanged, affected_rows can be 0; confirm order exists.
        $check = $this->conn->prepare("SELECT order_id FROM orders WHERE order_id = ? LIMIT 1");
        if (!$check) {
            throw new Exception('Failed to prepare order existence query');
        }
        $check->bind_param('i', $order_id);
        $check->execute();
        $exists = (bool)$check->get_result()->fetch_assoc();
        $check->close();

        return $exists;
    }
}

