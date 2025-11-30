<?php
session_start();
echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
    