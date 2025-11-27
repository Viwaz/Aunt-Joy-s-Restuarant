<?php

class Meal {
    private $conn;
    private $table = "meals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT * FROM meals ORDER BY id DESC";
        $fetch = $this->conn->query($query);
        return $fetch;
    }

    public function read($id) {
        $query = "SELECT * FROM meals WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function create($name, $description, $price, $category, $image) {
        $query = "INSERT INTO meals(name, description, price_MWK, category, image)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssdss", $name, $description, $price, $category, $image);

        return $stmt->execute();
    }

    public function update($id, $name, $description, $price, $category, $image=null) {
        if ($image) {
            $query = "UPDATE meals SET name=?, description=?, price_MWK=?, category=?, image=? WHERE id=?";
        } else {
            $query = "UPDATE meals SET name=?, description=?, price_MWK=?, category=? WHERE id=?";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssdsi", $name, $description, $price, $category, $id);
        if ($image) $stmt->bind_param("s", $image);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM meals WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
