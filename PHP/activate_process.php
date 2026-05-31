<?php
session_start();
include('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Check if OTP matches
    $query = "SELECT * FROM USER WHERE email = '$email' AND otp_code = '$otp' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Calculate Expiry (10 Minutes)
        $sent_time = strtotime($user['otp_sent_at']);
        $current_time = time();
        $time_difference = ($current_time - $sent_time) / 60; 

        if ($time_difference > 10) {
            echo "<script>alert('OTP has expired! Please request a new one.'); window.location.href='../prototypes/activate.html';</script>";
            exit();
        }

        // If valid and not expired, activate and set password
        $update = "UPDATE USER SET password = '$password', is_activated = 1, otp_code = NULL, otp_sent_at = NULL WHERE email = '$email'";
        if(mysqli_query($conn, $update)) {
            echo "<script>alert('Account Activated Successfully! You can now log in.'); window.location.href='../prototypes/login.html';</script>";
        }
    } else {
        echo "<script>alert('Invalid OTP code. Please try again.'); window.history.back();</script>";
    }
}
?>