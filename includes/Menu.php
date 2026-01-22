<?php
/**
 * Read-only menu model used by api/menu_api.php (public browsing).
 */
class Menu {
    private mysqli $conn;

    public function __construct(mysqli $db) {
        $this->conn = $db;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listCategories(): array {
        $stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY category_name");
        if (!$stmt) {
            throw new Exception('Failed to prepare categories query');
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMeals(?int $category_id = null): array {
        $query = "SELECT m.*, c.category_name as category_name
                  FROM menu_items m
                  LEFT JOIN categories c ON m.category = c.id
                  WHERE m.availability = 'in_stock' AND m.is_active = 1";

        if ($category_id !== null) {
            $query .= " AND m.category = ?";
        }
        $query .= " ORDER BY m.name";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare meals query');
        }
        if ($category_id !== null) {
            $stmt->bind_param("i", $category_id);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function searchMeals(string $keyword): array {
        $query = "SELECT m.*, c.category_name as category_name
                  FROM menu_items m
                  LEFT JOIN categories c ON m.category = c.id
                  WHERE m.availability = 'in_stock' AND m.is_active = 1
                  AND (m.name LIKE ? OR m.description LIKE ?)
                  ORDER BY m.name";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare search query');
        }
        $like = '%' . $keyword . '%';
        $stmt->bind_param("ss", $like, $like);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}

