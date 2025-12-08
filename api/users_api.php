<?php
header("Content-Type: application/json");
session_start();
require_once '../includes/Database.php';
require_once '../includes/user.php';
require_once '../includes/AuditLog.php';

// Auth check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$db = (new Database())->getConnection();
$userObj = new User($db);
$auditLog = new AuditLog($db);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET' && $action === 'list') {
        // Get all active users (soft deletion: exclude deactivated)
        $result = $userObj->readAll();
        $data = [];
        while ($row = $result->fetch_assoc()) {
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

        if ($userObj->create($username, $email, $password, $role, $_SESSION['id'], $_SERVER['REMOTE_ADDR'] ?? null)) {
            echo json_encode(['success' => true, 'message' => "User created with role: $role"]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        }

    } elseif ($method === 'POST' && $action === 'delete') {
        // Soft delete user (deactivate instead of hard delete)
        $input = json_decode(file_get_contents('php://input'), true);
        $user_id = $input['id'] ?? null;

        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }

        // Prevent admin from deleting themselves
        if ($user_id == $_SESSION['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot deactivate your own account']);
            exit;
        }

        // Delegate to User class to handle soft-delete and audit logging
        if ($userObj->delete($user_id, $_SESSION['id'], $_SERVER['REMOTE_ADDR'] ?? null)) {
            echo json_encode(['success' => true, 'message' => 'User deactivated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to deactivate user']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
