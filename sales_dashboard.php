<?php
require_once 'auth.php';
$auth = new Auth();

if (!$auth->isLoggedIn() || !$auth->checkRole('sales')) {
    die("Access denied: Sales only.");
}
?>
<h1>Welcome Sales Team Member</h1>