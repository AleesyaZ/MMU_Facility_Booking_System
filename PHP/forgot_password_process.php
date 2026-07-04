<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
include('db_config.php');
date_default_timezone_set("Asia/Kuala_Lumpur");

if (isset($_POST['request_otp_btn'])) {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    
    $query = "SELECT * FROM user WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $otp = rand(100000, 999999);
        $now = date("Y-m-d H:i:s");
        
        mysqli_query($conn, "UPDATE user SET otp_code = '$otp', otp_sent_at = '$now' WHERE email = '$email'");

        $mail = new PHPMailer(true);
        try {
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
            $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. It expires in 10 minutes.";

            $mail->send();
            header("Location: ../prototypes/reset-password.php?email=" . urlencode($email));
        } catch (Exception $e) {
            echo "<script>alert('Mailer Error: {$mail->ErrorInfo}'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Email not found in our records.'); window.history.back();</script>";
    }
}