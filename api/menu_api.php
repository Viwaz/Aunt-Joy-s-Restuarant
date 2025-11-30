<?php
header("Content-Type: application/json");
session_start();
require_once dirname(__DIR__) . '/includes/Database.php';

$db = (new Database())->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET' && $action === 'categories') {
        // Get all categories
        $query = "SELECT * FROM categories ORDER BY category_name";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'categories' => $categories]);

    } elseif ($method === 'GET' && $action === 'meals') {
        // Get meals by category or all
        // accept either ?category_id= or ?id= for backward compatibility
        $category_id = $_GET['category_id'] ?? $_GET['id'] ?? null;

        $query = "SELECT m.*, c.category_name as category_name 
                  FROM menu_items m 
                  LEFT JOIN categories c ON m.category = c.id 
                  WHERE m.availability = 'in_stock'";

        if ($category_id) {
            $query .= " AND m.category = ?";
        }

        $query .= " ORDER BY m.name";

        $stmt = $db->prepare($query);
        if ($category_id) {
            $stmt->bind_param("i", $category_id);
        }
        $stmt->execute();
        $meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'meals' => $meals]);

    } elseif ($method === 'GET' && $action === 'search') {
        // Search meals by keyword
        $keyword = $_GET['keyword'] ?? '';

        // if no keyword provided, return empty results (UI can decide to fetch all instead)
        if ($keyword === '') {
            echo json_encode(['success' => true, 'meals' => []]);
            exit;
        }

        $query = "SELECT m.*, c.category_name as category_name 
                  FROM menu_items m 
                  LEFT JOIN categories c ON m.category = c.id 
                  WHERE m.availability = 'in_stock' 
                  AND (m.name LIKE ? OR m.description LIKE ?) 
                  ORDER BY m.name";

        $stmt = $db->prepare($query);
        $keyword = "%$keyword%";
        $stmt->bind_param("ss", $keyword, $keyword);
        $stmt->execute();
        $meals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['success' => true, 'meals' => $meals]);

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
