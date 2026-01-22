<?php
/**
 * Cart model used by api/add_to_cart.php and api/cart_count.php.
 */
class Cart {
    private mysqli $conn;

    public function __construct(mysqli $db) {
        $this->conn = $db;
    }

    public function addItem(int $user_id, int $menu_item_id, int $quantity): void {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be > 0');
        }

        $stmt = $this->conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND menu_item_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare cart lookup query');
        }
        $stmt->bind_param("ii", $user_id, $menu_item_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $existing = (int)$row['quantity'];
            $newQty = $existing + $quantity;
            $stmt->close();

            $upd = $this->conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND menu_item_id = ?");
            if (!$upd) {
                throw new Exception('Failed to prepare cart update query');
            }
            $upd->bind_param("iii", $newQty, $user_id, $menu_item_id);
            $upd->execute();
            $upd->close();
            return;
        }

        $stmt->close();

        $ins = $this->conn->prepare("INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
        if (!$ins) {
            throw new Exception('Failed to prepare cart insert query');
        }
        $ins->bind_param("iii", $user_id, $menu_item_id, $quantity);
        $ins->execute();
        $ins->close();
    }

    public function countItems(int $user_id): int {
        $stmt = $this->conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare cart count query');
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }

    /**
     * Merge a session-based guest cart into the DB cart for a user.
     *
     * @param array<int,int> $guest_cart [menu_item_id => quantity]
     */
    public function mergeGuestCart(int $user_id, array $guest_cart): void {
        foreach ($guest_cart as $menu_item_id => $qty) {
            $mid = (int)$menu_item_id;
            $q = (int)$qty;
            if ($mid <= 0 || $q <= 0) continue;
            $this->addItem($user_id, $mid, $q);
        }
    }
}

