<!doctype html>
<body>
    <title>Register</title>
    <?php
    require_once 'database.php';
    require_once 'user.php';
    /**
     * This file is for registering new users of role user
     * the role is modified by the admin only
     */
    // establishing a connection to the database
    $database =new Database();
    $db = $database->getConnection();
    $user = new User($db);

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $username = $_POST['username'];
        $email = $_POST["email"];
        $password = $_POST['password'];

        if($user -> create($username, $email, $password)){
            echo "User Registered Succesfully!!!\n";
        }else{
            echo "There was an error while registering the user\n";
        }
    }


    ?>

    <form method="POST">

        <input type="text" name="username" placeholder="Enter your username" required><br>
        <input type="email" name="email" placeholder="Enter your email" required><br>
        <input type="password" name="password" placeholder="Enter a strong password" required><br>
        <button type="submit"> register</button>
        <p>Already have an account?</p>
        <a href = "login.php">Login</a>
    </form>
<body>
</doctype>