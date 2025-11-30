<?php
header("Content-Type: application/json");
session_start();
require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/admin/user.php';

// Auth check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = (new Database())->getConnection();
$userObj = new User($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET' && $action === 'list') {
        // Get all users
        $users = $userObj->readAll();
        $data = [];
        while ($row = $users->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $data]);

    } elseif ($method === 'POST' && $action === 'create') {
        // Create new user
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? null;
        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;
        $role = $input['role'] ?? null;

        if (!$username || !$email || !$password || !$role) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit;
        }

        if ($userObj->create($username, $email, $password, $role)) {
            echo json_encode(['success' => true, 'message' => "User created with role: $role"]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
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
