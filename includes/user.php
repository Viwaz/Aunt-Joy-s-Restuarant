<?php
require_once 'database.php';
require_once 'AuditLog.php';

class User {
    private $conn;
    private $table = 'users';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($username, $email, $password, $role = 'customer', $performed_by = null, $ip_address = null, $delivery_address = null, $phone_num = null) {
        $query = "INSERT INTO {$this->table} (username, email, password, role, delivery_address, phone_num) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('ssssss', $username, $email, $password_hash, $role, $delivery_address, $phone_num);
        $res = $stmt->execute();
        if ($res) {
            $new_id = $this->conn->insert_id;
            // Log creation
            $audit = new AuditLog($this->conn);
            $new_values = ['username' => $username, 'email' => $email, 'role' => $role, 'delivery_address' => $delivery_address, 'phone_num' => $phone_num];
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('CREATE', 'user', $new_id, $performed_by, "User created: $username", null, $new_values, $ip);
        }
        $stmt->close();
        return (bool)$res;
    }

    public function readAll($include_inactive = false) {
        if ($include_inactive) {
            $query = "SELECT * FROM $this->table ORDER BY id DESC";
            $result = $this->conn->query($query);
            return $result;
        }
        $stmt = $this->conn->prepare("SELECT * FROM $this->table WHERE is_active = 1 ORDER BY id DESC");
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        return $res;
    }

    public function read($id, $include_inactive = false) {
        if ($include_inactive) {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ? AND is_active = 1 LIMIT 1");
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        return $res;
    }

    public function update($username, $email, $role, $id, $performed_by = null, $ip_address = null) {
        // fetch old values for audit
        $stmt = $this->conn->prepare("SELECT username, email, role FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $query = "UPDATE {$this->table} SET username = ?, email = ?, role = ? WHERE id = ?";
        $update = $this->conn->prepare($query);
        if (!$update) return false;
        $update->bind_param('sssi', $username, $email, $role, $id);
        $res = $update->execute();
        if ($res) {
            $audit = new AuditLog($this->conn);
            $old_values = $old;
            $new_values = ['username' => $username, 'email' => $email, 'role' => $role];
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('UPDATE', 'user', $id, $performed_by, "User updated: {$username}", $old_values, $new_values, $ip);
        }
        $update->close();
        return (bool)$res;
    }

    public function delete($id, $performed_by = null, $ip_address = null) {
        // Soft delete: set is_active = 0
        $stmt = $this->conn->prepare("SELECT username, email, role, is_active FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) return false;
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            // already deactivated
            return true;
        }

        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
        $upd = $this->conn->prepare($query);
        if (!$upd) return false;
        $upd->bind_param('i', $id);
        $res = $upd->execute();
        if ($res) {
            $audit = new AuditLog($this->conn);
            $old_values = ['is_active' => 1];
            $new_values = ['is_active' => 0];
            $ip = $ip_address ?? ($_SERVER['REMOTE_ADDR'] ?? null);
            $audit->log('DEACTIVATE', 'user', $id, $performed_by, "User deactivated: " . ($user['username'] ?? $id), $old_values, $new_values, $ip);
        }
        $upd->close();
        return (bool)$res;
    }
}