<?php

 require_once 'auth/database.php';
class Meal {
    private $conn;
    private $table_name = "meals";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new meal
    public function create($name, $description, $price, $category, $image) {
        $query = "INSERT INTO " . $this->table_name . " (name, description, price, category, image, available) VALUES (?, ?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $description, $price, $category, $image]);
    }

    // Read all meals
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Delete a meal
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Toggle Availability
    public function toggleAvailability($id, $currentStatus) {
        $newStatus = $currentStatus == 1 ? 0 : 1;
        $query = "UPDATE " . $this->table_name . " SET available = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$newStatus, $id]);
    }
}
?>