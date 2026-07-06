<?php
session_start(); // 1. Start session to enable the security gate
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
include('db_config.php');
date_default_timezone_set("Asia/Kuala_Lumpur");

if (isset($_POST['request_otp_btn'])) {
    // Sanitize email input
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    
    // Search for user (using LOWER/TRIM for better matching)
    $query = "SELECT * FROM user WHERE LOWER(TRIM(email)) = LOWER('$email') LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $otp = rand(100000, 999999);
        $now = date("Y-m-d H:i:s");
        
        // Update the user record with the OTP
        mysqli_query($conn, "UPDATE user SET otp_code = '$otp', otp_sent_at = '$now' WHERE LOWER(TRIM(email)) = LOWER('$email')");

        $mail = new PHPMailer(true);
        try {
            // SMTP Settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'verychill911@gmail.com'; 
            $mail->Password   = 'nmnr jsut eyne lawl'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465; 

            $mail->setFrom('system@mmu.edu.my', 'MMU Facility Booking');
            $mail->addAddress($email); 

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; border: 1px solid #eee; padding: 20px;'>
                    <h2 style='color: #003d7c;'>Password Reset Request</h2>
                    <p>You requested an OTP to reset your password for the MMU Facility Booking System.</p>
                    <div style='background: #f4f4f4; padding: 10px; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 5px;'>
                        $otp
                    </div>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you did not request this, please ignore this email.</p>
                </div>";

            if($mail->send()) {
                // Set session flags to allow entry to reset-password.php
                $_SESSION['reset_allowed'] = true;
                $_SESSION['reset_email_target'] = $email;

                header("Location: ../prototypes/reset-password.php?email=" . urlencode($email));
                exit();
            }
        } catch (Exception $e) {
            echo "<script>alert('Mailer Error: {$mail->ErrorInfo}'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email not found in our records.'); window.history.back();</script>";
    }
} else {
    header("Location: ../prototypes/forgot-password.php");
    exit();
}