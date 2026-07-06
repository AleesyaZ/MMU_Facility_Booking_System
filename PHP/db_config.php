<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "online_booking_system"; 

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set Timezone
date_default_timezone_set("Asia/Kuala_Lumpur");

// Checks if 30 days (2592000 seconds) have passed since suspension_start
$lift_query = "UPDATE user u
               LEFT JOIN penalty p ON u.user_id = p.user_id
               SET u.status = 'Active', 
                   u.suspension_start = NULL, 
                   p.strike_count = 0
               WHERE u.status = 'Suspended' 
               AND u.suspension_start <= DATE_SUB(NOW(), INTERVAL 30 DAY)";

mysqli_query($conn, $lift_query);
?>