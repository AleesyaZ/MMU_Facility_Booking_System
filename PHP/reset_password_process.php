<?php
session_start();
include('db_config.php');
date_default_timezone_set("Asia/Kuala_Lumpur");

if (isset($_POST['reset_btn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $password = $_POST['password'];

    $query = "SELECT * FROM user WHERE email = '$email' AND otp_code = '$otp' LIMIT 1";
    $res = mysqli_query($conn, $query);

    if (mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
        $sent_time = strtotime($user['otp_sent_at']);
        
        if ((time() - $sent_time) / 60 > 10) {
            echo "<script>alert('OTP Expired.'); window.location.href='../prototypes/forgot-password.php';</script>";
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE user SET password = '$hashed_password', otp_code = NULL, otp_sent_at = NULL WHERE email = '$email'");

        echo "<script>alert('Password reset successful!'); window.location.href='../prototypes/login.html';</script>";
    } else {
        echo "<script>alert('Invalid OTP.'); window.history.back();</script>";
    }
}