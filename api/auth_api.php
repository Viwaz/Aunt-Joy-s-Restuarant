<?php
header("Content-Type: application/json");
session_start();

require_once dirname(__DIR__) . '/auth/auth.php';
require_once dirname(__DIR__) . '/includes/Database.php';
require_once dirname(__DIR__) . '/includes/Cart.php';
require_once dirname(__DIR__) . '/includes/user.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? null;

try {
    if ($method !== 'POST') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $email = trim($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');

        if ($email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password are required']);
            exit;
        }

        $auth = new Auth();
        $res = $auth->login($email, $password);

        if ($res === 'deactivated') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Account is deactivated']);
            exit;
        }

        if ($res === false) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit;
        }

        // With session-only cart we don't need to merge; guest cart stays in session.
        echo json_encode(['success' => true, 'role' => $_SESSION['role'] ?? null]);
        exit;
    }

    if ($action === 'register') {
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = (string)($input['password'] ?? '');
        $delivery_address = $input['delivery_address'] ?? null;
        $phone_num = $input['phone_num'] ?? null;

        if ($username === '' || $email === '' || $password === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Username, email and password are required']);
            exit;
        }

        $db = (new Database())->getConnection();
        $userModel = new User($db);
        if (!$userModel->create($username, $email, $password, 'customer', null, null, $delivery_address, $phone_num)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Registration failed - email or username may already exist']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

