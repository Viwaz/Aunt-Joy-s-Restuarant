<?php
require_once 'auth.php';
$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->checkRole('manager')) {
    die("Access denied: Manager only.");
}
?>
<h1>Welcome Manager</h1>