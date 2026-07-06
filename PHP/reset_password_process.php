<?php
session_start();
include('db_config.php');
date_default_timezone_set("Asia/Kuala_Lumpur");

if (isset($_POST['reset_btn'])) {
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $password = $_POST['password']; // Get raw password for hashing

    // Open for this specific email
    if (!isset($_SESSION['reset_allowed']) || $_SESSION['reset_allowed'] !== true || $_SESSION['reset_email_target'] !== $_POST['email']) {
        echo "<script>alert('Security session expired. Please request a new OTP.'); window.location.href='../prototypes/forgot-password.php';</script>";
        exit();
    }

    // Search for the user with matching Email and OTP
    $query = "SELECT * FROM user WHERE email = '$email' AND otp_code = '$otp' LIMIT 1";
    $res = mysqli_query($conn, $query);

    if (mysqli_num_rows($res) > 0) {
        $user = mysqli_fetch_assoc($res);
        $sent_time = strtotime($user['otp_sent_at']);
        
        // 4. Expiry Check (10 Minutes)
        if ((time() - $sent_time) / 60 > 10) {
            echo "<script>alert('OTP Expired. Please request a new one.'); window.location.href='../prototypes/forgot-password.php';</script>";
            exit();
        }

        // Hash the new password and update the record
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update_query = "UPDATE user SET 
                         password = '$hashed_password', 
                         otp_code = NULL, 
                         otp_sent_at = NULL 
                         WHERE email = '$email'";

        if (mysqli_query($conn, $update_query)) {
            
            // Close the security gate (Crucial)
            unset($_SESSION['reset_allowed']);
            unset($_SESSION['reset_email_target']);

            echo "<script>alert('Password reset successful! You can now log in with your new password.'); window.location.href='../prototypes/login.html';</script>";
        } else {
            echo "Error updating database: " . mysqli_error($conn);
        }
    } else {
        // If OTP or Email doesn't match
        echo "<script>alert('Invalid OTP. Please check the code in your email.'); window.history.back();</script>";
    }
} else {
    header("Location: ../prototypes/forgot-password.php");
    exit();
}
?>