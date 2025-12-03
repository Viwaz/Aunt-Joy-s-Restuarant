<?php
require_once __DIR__.'/../includes/Database.php';

class Auth {
    private $conn;

    public function __construct() {
        session_start();
        $this->conn = (new Database())->getConnection();// setting up a connection to the database
    }

    // handling user authentication for role based access
    public function login($email, $password) {
        // checking a query before execution to prevent SQL injection
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        // parsing the results of the query
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            return $user['role'];  // return role for redirection
        }
        return false;
    }

    public function isLoggedIn() {
    return isset($_SESSION['id']);
    }

    public function checkRole($requiredRole) {
        return isset($_SESSION['role']) && $_SESSION['role'] === $requiredRole;
    }

    public function logout() {
        session_destroy();
    }
}
