<?php
// ... PHP login logic ...
require_once 'auth.php';
$auth = new Auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $auth->login($email, $password);

    if ($role) {
        switch ($role) {
            case 'admin': header("Location: ../admin/dashboard.php"); break;
            case 'sales': header("Location: ../sales/dashboard.php"); break;
            case 'manager': header("Location: ../manager/dashboard.php"); break;
            case 'customer': header("Location: ../user/meals.html"); break;
            default: header("Location: ../index.php");
        }
        exit;
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Food Ordering</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page"> 
    <div class="form-container"> <h2>🍽️ Welcome Back!</h2>
        
        <form method="POST" id="loginForm">
            <input type="email" name="email" placeholder="Email" required>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password" required id="passwordInput">
                <button type="button" class="toggle-password" id="toggleLoginPassword">👁️</button> </div>
            <button type="submit">Login</button>
        </form>

        <?php 
        // Display PHP error message with the styled class
        if (isset($error)) {
            echo "<p class='message error' id='errorMessage'>$error</p>"; 
        }
        ?>

        <div class="link-area"> <p>Don't have an account?</p>
            <a href="register.php">Register Now</a>
        </div>
    </div>
    
    <script>
        // JavaScript for Password Visibility Toggle and Error Clearing (Login)
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleButton = document.getElementById('toggleLoginPassword');
            const errorMessage = document.getElementById('errorMessage');

            if (passwordInput && toggleButton) {
                // Password Toggle
                toggleButton.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '👁️' : '🔒';
                });

                // Error Clearing
                if (errorMessage) {
                    passwordInput.addEventListener('input', function() {
                        errorMessage.style.display = 'none';
                    });
                }
            }
        });
    </script>
</body>
</html>