<?php
/**
 * AuditLog Helper Class
 * Handles all audit logging operations for CRUD actions
 */

class AuditLog {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Log an action (CREATE, UPDATE, DELETE, DEACTIVATE, etc.)
     * @param string $action
     * @param string $entity_type
     * @param int $entity_id
     * @param int|null $performed_by
     * @param string|null $description
     * @param array|null $old_values
     * @param array|null $new_values
     * @param string|null $ip_address
     * @return bool
     */
    public function log($action, $entity_type, $entity_id, $performed_by = null, $description = null, $old_values = null, $new_values = null, $ip_address = null) {
        try {
            $old_json = $old_values !== null ? json_encode($old_values) : null;
            $new_json = $new_values !== null ? json_encode($new_values) : null;

            $query = "INSERT INTO audit_log
                      (action, entity_type, entity_id, performed_by, description, old_values, new_values, ip_address)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log('AuditLog prepare failed: ' . $this->conn->error);
                return false;
            }

            // Bind params (use strings for nullable JSON fields)
            $action_v = $action;
            $entity_type_v = $entity_type;
            $entity_id_v = (int)$entity_id;
            $performed_by_v = $performed_by !== null ? (int)$performed_by : null;
            $description_v = $description;
            $old_json_v = $old_json;
            $new_json_v = $new_json;
            $ip_v = $ip_address;

            $stmt->bind_param(
                'ssiissss',
                $action_v,
                $entity_type_v,
                $entity_id_v,
                $performed_by_v,
                $description_v,
                $old_json_v,
                $new_json_v,
                $ip_v
            );

            $result = $stmt->execute();
            if (!$result) {
                error_log('AuditLog execute failed: ' . $stmt->error);
            }
            $stmt->close();
            return (bool)$result;

        } catch (Exception $e) {
            error_log("AuditLog error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve audit log entries
     */
    public function getLog($entity_type = null, $entity_id = null, $limit = 50) {
        try {
            $query = "SELECT * FROM audit_log";
            $params = [];
            $types = '';

            if ($entity_type !== null) {
                $query .= " WHERE entity_type = ?";
                $params[] = $entity_type;
                $types .= 's';

                if ($entity_id !== null) {
                    $query .= " AND entity_id = ?";
                    $params[] = (int)$entity_id;
                    $types .= 'i';
                }
            }

            $query .= " ORDER BY created_at DESC LIMIT " . (int)$limit;

            if (!empty($params)) {
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($query);
            }

            $logs = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['old_values']) $row['old_values'] = json_decode($row['old_values'], true);
                if ($row['new_values']) $row['new_values'] = json_decode($row['new_values'], true);
                $logs[] = $row;
            }

            return $logs;

        } catch (Exception $e) {
            error_log("AuditLog getLog error: " . $e->getMessage());
            return [];
        }
    }
}
