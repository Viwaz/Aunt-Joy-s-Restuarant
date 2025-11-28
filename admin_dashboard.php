<?php
require_once 'auth.php';
$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->checkRole('admin')) {
    die("Access denied: Admin only.");//terminating the session
}
?>
<h1>Welcome Admin</h1>
