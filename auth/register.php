<?php
// ... PHP register logic ...
require_once __DIR__.'/../includes/Database.php';
require_once __DIR__.'/../includes/user.php';

$success_message = '';
$error_message = '';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST['username'], $_POST['email'], $_POST['password'])) {
        $username = $_POST['username'];
        $email = $_POST["email"];
        $password = $_POST['password'];

        if($user->create($username, $email, $password)){
            $success_message = "Registration successful! You can now log in.";
        } else {
            $error_message = "There was an error while registering the user. The email or username might already be in use.";
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Food Ordering</title>
    <link rel="stylesheet" href="style.css"> 
</head>
<body class="register-page"> 
    <div class="form-container"> <h2>📝 Create Your Account</h2>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Choose a Username" required>
            <input type="email" name="email" placeholder="Enter your Email" required>
            
            <div class="input-group">
                <input type="password" name="password" placeholder="Enter a Strong Password" required id="regPasswordInput"> <button type="button" class="toggle-password" id="toggleRegPassword">👁️</button> </div>
            
            <button type="submit">Register</button>
        </form>

        <?php 
        // Display PHP messages with styled classes
        if (!empty($success_message)) {
            echo "<p class='message success'>$success_message</p>"; 
        }
        if (!empty($error_message)) {
            echo "<p class='message error'>$error_message</p>"; 
        }
        ?>

        <div class="link-area"> <p>Already have an account?</p>
            <a href="login.php">Login Here</a>
        </div>
    </div>
    
    <script>
        // JavaScript for Password Visibility Toggle (Register)
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('regPasswordInput');
            const toggleButton = document.getElementById('toggleRegPassword');

            if (passwordInput && toggleButton) {
                toggleButton.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '👁️' : '🔒';
                });
            }
        });
    </script>
</body>
</html>