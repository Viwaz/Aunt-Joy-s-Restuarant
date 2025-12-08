<?php
require_once 'AuditLog.php';

class Meal {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll($include_inactive = false) {
        if ($include_inactive) {
            $query = "SELECT * FROM menu_items ORDER BY id DESC";
            return $this->conn->query($query);
        }
        $stmt = $this->conn->prepare("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY id DESC");
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        return $res;
    }

    public function read($id, $include_inactive = false) {
        if ($include_inactive) {
            $stmt = $this->conn->prepare("SELECT * FROM menu_items WHERE id = ? LIMIT 1");
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM menu_items WHERE id = ? AND is_active = 1 LIMIT 1");
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        return $res;
    }

    public function create($name, $description, $price, $category_id, $image = null, $performed_by = null, $ip_address = null) {
        // Use category_id directly
        $query = "INSERT INTO menu_items (name, description, price_MWK, category, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;
        $img = $image ?? 'default_food.png';
        $stmt->bind_param('ssdis', $name, $description, $price, $category_id, $img);
        $res = $stmt->execute();
        if ($res) {
            $new_id = $this->conn->insert_id;
            $audit = new AuditLog($this->conn);
            $new_values = ['name' => $name, 'description' => $description, 'price_MWK' => $price, 'category' => $category_id, 'image' => $img];
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('CREATE', 'meal', $new_id, $performed_by, "Meal created: $name", null, $new_values, $ip);
        }
        $stmt->close();
        return (bool)$res;
    }

    public function update($id, $name, $description, $price, $category_name, $image = null, $performed_by = null, $ip_address = null) {
        // Resolve category id
        $q = "SELECT id FROM categories WHERE category_name = ? LIMIT 1";
        $s = $this->conn->prepare($q);
        $s->bind_param('s', $category_name);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        $s->close();
        $category = $r['id'] ?? null;

        // fetch old values
        $stmt = $this->conn->prepare("SELECT name, description, price_MWK, category, image FROM menu_items WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($image !== null) {
            $query = "UPDATE menu_items SET name = ?, description = ?, price_MWK = ?, category = ?, image = ? WHERE id = ?";
            $upd = $this->conn->prepare($query);
            $upd->bind_param('ssdisi', $name, $description, $price, $category, $image, $id);
        } else {
            $query = "UPDATE menu_items SET name = ?, description = ?, price_MWK = ?, category = ? WHERE id = ?";
            $upd = $this->conn->prepare($query);
            $upd->bind_param('ssdii', $name, $description, $price, $category, $id);
        }

        if (!$upd) return false;
        $res = $upd->execute();
        if ($res) {
            $audit = new AuditLog($this->conn);
            $new_values = ['name' => $name, 'description' => $description, 'price_MWK' => $price, 'category' => $category, 'image' => $image];
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('UPDATE', 'meal', $id, $performed_by, "Meal updated: $name", $old, $new_values, $ip);
        }
        $upd->close();
        return (bool)$res;
    }

    public function toggleAvailability($id, $current_status, $performed_by = null, $ip_address = null) {
        $new_status = ($current_status === 'in_stock') ? 'out_of_stock' : 'in_stock';
        $query = "UPDATE menu_items SET availability = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('si', $new_status, $id);
        $res = $stmt->execute();
        if ($res) {
            $audit = new AuditLog($this->conn);
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('UPDATE', 'meal', $id, $performed_by, "Availability toggled to $new_status", ['availability' => $current_status], ['availability' => $new_status], $ip);
        }
        $stmt->close();
        return (bool)$res;
    }

    public function delete($id, $performed_by = null, $ip_address = null) {
        // Soft delete: set is_active = 0
        $stmt = $this->conn->prepare("SELECT name, is_active FROM menu_items WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) return false;
        if (isset($row['is_active']) && $row['is_active'] == 0) return true;

        $query = "UPDATE menu_items SET is_active = 0 WHERE id = ?";
        $upd = $this->conn->prepare($query);
        $upd->bind_param('i', $id);
        $res = $upd->execute();
        if ($res) {
            $audit = new AuditLog($this->conn);
            $old_values = ['is_active' => 1];
            $new_values = ['is_active' => 0];
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('DEACTIVATE', 'meal', $id, $performed_by, "Meal deactivated: " . ($row['name'] ?? $id), $old_values, $new_values, $ip);
        }
        $upd->close();
        return (bool)$res;
    }
}
