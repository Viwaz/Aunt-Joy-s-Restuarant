<?php
// ... PHP login logic ...
require_once 'auth.php';
require_once dirname(__DIR__) . '../includes/Database.php';

$auth = new Auth();
$warning = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // --- SESSION SECURITY: Check for role switch warning ---
    if (isset($_SESSION['id']) && isset($_SESSION['role'])) {
        // User is already logged in - check if they're trying to login as different role
        $db = (new Database())->getConnection();
        $query = "SELECT role FROM users WHERE email = ? LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if ($user_data['role'] !== $_SESSION['role']) {
                // Different role - show warning
                $warning = "You are currently logged in as a <strong>" . htmlspecialchars($_SESSION['role']) . 
                          "</strong>. Logging in as a <strong>" . htmlspecialchars($user_data['role']) . 
                          "</strong> will log you out from other browser tabs.";
            }
        }
    }
    
    $role = $auth->login($email, $password);

    if ($role) {
        // Merge any pending cart saved in session into the user's DB cart
        if (isset($_SESSION['pending_cart']) && is_array($_SESSION['pending_cart'])) {
            require_once dirname(__DIR__) . '../includes/Database.php';
            $db = (new Database())->getConnection();
            $user_id = $_SESSION['id'];

            foreach ($_SESSION['pending_cart'] as $menu_item_id => $qty) {
                $menu_item_id = (int)$menu_item_id;
                $qty = (int)$qty;
                if ($menu_item_id <= 0 || $qty <= 0) continue;

                // Check if exists
                $check = $db->prepare("SELECT quantity FROM cart WHERE user_id = ? AND menu_item_id = ?");
                $check->bind_param("ii", $user_id, $menu_item_id);
                $check->execute();
                $res = $check->get_result();
                if ($row = $res->fetch_assoc()) {
                    $newQty = $row['quantity'] + $qty;
                    $upd = $db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND menu_item_id = ?");
                    $upd->bind_param("iii", $newQty, $user_id, $menu_item_id);
                    $upd->execute();
                } else {
                    $ins = $db->prepare("INSERT INTO cart (user_id, menu_item_id, quantity) VALUES (?, ?, ?)");
                    $ins->bind_param("iii", $user_id, $menu_item_id, $qty);
                    $ins->execute();
                }
            }

            unset($_SESSION['pending_cart']);
        }

        // If there is a requested redirect after login (e.g., checkout), honor it
        if (!empty($_SESSION['redirect_after_login'])) {
            $redirect = $_SESSION['redirect_after_login'];
            unset($_SESSION['redirect_after_login']);
            header("Location: ../" . ltrim($redirect, '/'));
            exit;
        }

        switch ($role) {
            case 'admin': header("Location: ../views/admin/dashboard.php"); break;
            case 'sales': header("Location: ../views/sales/dashboard.php"); break;
            case 'manager': header("Location: ../views/manager/dashboard.php"); break;
            case 'customer': header("Location: ../views/user/sections/customer_interface.php"); break;
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
    <div class="form-container"> <h2> <strong>Welcome Back!</strong></h2>
        
        <!-- <?php 
        // Display warning about role switch
        if (!empty($warning)) {
            echo "<div class='message warning' style='color : #ff9800; background: #fff3e0; padding: 10px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #ff9800;'>";
            echo "⚠️ " . $warning;
            echo "</div>";
        }
        ?>
         -->
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
            echo "<p class='message error'id='errorMessage'>$error</p>"; 
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