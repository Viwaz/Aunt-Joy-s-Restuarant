<?php
header("Content-Type: application/json");

require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/includes/meal.php';
session_start();
// Auth check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = (new Database())->getConnection();
$mealObj = new Meal($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET' && $action === 'list') {
        // Get all meals
        $meals = $mealObj->readAll();
        $data = [];
        while ($row = $meals->fetch_assoc()) {
            // Fetch category name
            $stmt = $db->prepare("SELECT category_name FROM categories WHERE id = ?");
            $stmt->bind_param("i", $row['category']);
            $stmt->execute();
            $cat = $stmt->get_result()->fetch_assoc();
            $row['category_name'] = $cat['category_name'] ?? 'Unknown';
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'meals' => $data]);

    } elseif ($method === 'POST' && $action === 'create') {
        // Add new meal
        // Accept JSON body or form-encoded POST (for file uploads)
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (empty($input) && !empty($_POST)) {
            $input = $_POST;
        }

        $name = $input['name'] ?? null;
        $description = $input['description'] ?? null;
        $price = $input['price'] ?? null;
        $category_id = $input['category'] ?? null;

        if (!$name || !$price || !$category_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Handle image upload (FormData) or fallback to provided image name
        $image = 'default_food.png';
        if (!empty($_FILES['image']) && $_FILES['image']['size'] > 0) {
            // Handle file upload from FormData
            $target_dir = '../menu/';
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $image = basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
        } elseif (!empty($input['image'])) {
            // allow clients to provide an image filename
            $image = $input['image'];
        }

        if ($mealObj->create($name, $description, $price, $category_id, $image, $_SESSION['id'], $_SERVER['REMOTE_ADDR'] ?? null)) {
            echo json_encode(['success' => true, 'message' => 'Meal created successfully', 'image' => $image]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create meal']);
        }

    } elseif ($method === 'POST' && $action === 'delete') {
        // Delete meal
        $input = json_decode(file_get_contents('php://input'), true);
        $meal_id = $input['id'] ?? null;

        if (!$meal_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Meal ID required']);
            exit;
        }

        if ($mealObj->delete($meal_id, $_SESSION['id'], $_SERVER['REMOTE_ADDR'] ?? null)) {
            echo json_encode(['success' => true, 'message' => 'Meal deactivated']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete meal']);
        }

    } elseif ($method === 'POST' && $action === 'update') {
        // Update meal details
        // Accept JSON body or form-encoded POST (for file uploads)
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (empty($input) && !empty($_POST)) {
            $input = $_POST;
        }

        $meal_id = $input['id'] ?? null;
        $name = $input['name'] ?? null;
        $description = $input['description'] ?? null;
        $price = $input['price'] ?? null;
        $category_id = $input['category'] ?? null;

        if (!$meal_id || !$name || !$price || !$category_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        // Handle image upload (optional)
        $image = null;
        if (!empty($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $target_dir = '../menu/';
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
            $image = basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
        }

        if ($mealObj->update($meal_id, $name, $description, $price, $category_id, $image, $_SESSION['id'], $_SERVER['REMOTE_ADDR'] ?? null)) {
            echo json_encode(['success' => true, 'message' => 'Meal updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update meal']);
        }

    } elseif ($method === 'POST' && $action === 'toggle') {
        // Toggle availability
        $input = json_decode(file_get_contents('php://input'), true);
        $meal_id = $input['id'] ?? null;
        $current_status = $input['status'] ?? null;

        if (!$meal_id || !$current_status) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID and status required']);
            exit;
        }

        $new_status = ($current_status === 'in_stock') ? 'out_of_stock' : 'in_stock';
        if ($mealObj->toggleAvailability($meal_id, $current_status, $_SESSION['id'], $_SERVER['REMOTE_ADDR'] ?? null)) {
            echo json_encode(['success' => true, 'status' => $new_status]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
