<?php
header("Content-Type: application/json");
require_once '../includes/Database.php';
require_once '../includes/Menu.php';

$db = (new Database())->getConnection();
$menuModel = new Menu($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET' && $action === 'categories') {
        $categories = $menuModel->listCategories();
        echo json_encode(['success' => true, 'categories' => $categories]);

    } elseif ($method === 'GET' && $action === 'meals') {
        // Get meals by category or all
        // accept either ?category_id= or ?id= for backward compatibility
        $category_id = $_GET['category_id'] ?? $_GET['id'] ?? null;
        $meals = $menuModel->listMeals($category_id ? (int)$category_id : null);

        echo json_encode(['success' => true, 'meals' => $meals]);

    } elseif ($method === 'GET' && $action === 'search') {
        // Search meals by keyword
        $keyword = $_GET['keyword'] ?? '';

        // if no keyword provided, return empty results (UI can decide to fetch all instead)
        if ($keyword === '') {
            echo json_encode(['success' => true, 'meals' => []]);
            exit;
        }
        $meals = $menuModel->searchMeals($keyword);

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
