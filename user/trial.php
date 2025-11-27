<?php
require_once '../auth/database.php';
require_once '../admin/user.php';

$database = new Database();
$conn = $database->getConnection();

$query = "SELECT * FROM meals ORDER BY id DESC";
$fetch = $conn->query($query);
// echo $fetch->num_rows;

while($row = $fetch->fetch_assoc()){
    echo $row['name'] . "<br>";
    echo $row['description'] . "<br>";
}
$userObj = new User($conn);
$users = $userObj->readAll();

while($row = $users){
    echo $row['username'] . "<br>";
    echo $row['email'] . "<br>";
}
?>


