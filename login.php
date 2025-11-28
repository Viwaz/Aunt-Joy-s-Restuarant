<?php
require_once 'auth.php';
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $auth->login($email,$password);

    if ($role) {
        switch ($role) {
            case 'admin': header("Location: admin_dashboard.php"); break;
            case 'sales': header("Location: sales_dashboard.php"); break;
            case 'manager': header("Location: manager_dashboard.php"); break;
            case 'customer': header("Location: ./user/meals.html"); break;
        }
        exit;
    } else {
        $error = "Invalid email or password!";
        echo $error;
    }
}
?>
<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>
    <p>Don't have an account?</p>
    <a href="register.php">Register</a>
</form>
<?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>