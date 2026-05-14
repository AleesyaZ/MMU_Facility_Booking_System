<?php
$host = "localhost";
$db_user = "root";      // Default XAMPP username
$db_pass = "";          // Default XAMPP password is empty
$db_name = "online_booking_system"; // Replace with your actual database name

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>