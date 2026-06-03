<?php
// 1. Include PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Require the PHPMailer files (Ensure these paths match your folder)
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 3. Include your database connection
include('db_config.php');

// Set timezone to Malaysia (important for OTP expiry tracking)
date_default_timezone_set("Asia/Kuala_Lumpur");

if (isset($_POST['email'])) {
    // Sanitize and clean user input
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    
    // 4. Search for the user in the database (Case-insensitive check)
    $query = "SELECT * FROM user WHERE LOWER(TRIM(email)) = LOWER('$email') LIMIT 1";
    $check = mysqli_query($conn, $query);

    if (mysqli_num_rows($check) > 0) {
        $user = mysqli_fetch_assoc($check);
        
        // 5. If account is already activated, stop here
        if ($user['is_activated'] == 1) {
            echo "already_active";
        } else {
            // 6. Generate a 6-digit OTP and set current time
            $otp = rand(100000, 999999);
            $now = date("Y-m-d H:i:s");
            
            // 7. Update the database with the generated code
            $user_id = $user['user_id'];
            $update = mysqli_query($conn, "UPDATE user SET otp_code = '$otp', otp_sent_at = '$now' WHERE user_id = '$user_id'");

            if($update) {
                // 8. Prepare PHPMailer to send the real email via Mailtrap
                $mail = new PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = '69b575351e511b'; // Your Mailtrap Username
                    $mail->Password   = 'dd48a002b4d47d'; // Your Mailtrap Password
                    $mail->Port       = 2525;

                    // Recipients
                    $mail->setFrom('system@mmu.edu.my', 'MMU Facility Booking');
                    $mail->addAddress($email); 

                    // HTML Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Your Account Activation OTP';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; border: 1px solid #ddd; padding: 20px; max-width: 500px; margin: auto;'>
                            <h2 style='color: #bb0013; text-align: center;'>MMU Facility Booking</h2>
                            <p>Hello <b>" . htmlspecialchars($user['name']) . "</b>,</p>
                            <p>You have requested to activate your account. Please use the following One-Time Password (OTP) to complete the process:</p>
                            <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 10px; border-radius: 8px;'>
                                $otp
                            </div>
                            <p style='color: #666; font-size: 13px; margin-top: 20px;'>
                                <b>Note:</b> This code will expire in 10 minutes. If you did not request this, please ignore this email.
                            </p>
                            <hr style='border: none; border-top: 1px solid #eee; margin-top: 20px;'>
                            <p style='font-size: 11px; color: #999; text-align: center;'>This is an automated system message for MMU students. Please do not reply.</p>
                        </div>";

                    // Send the email
                    if($mail->send()) {
                        echo "success";
                    }
                } catch (Exception $e) {
                    echo "Email Error: {$mail->ErrorInfo}";
                }
            } else {
                echo "Database Error: Failed to save OTP.";
            }
        }
    } else {
        echo "not_found";
    }
} else {
    echo "invalid_request";
}
?>