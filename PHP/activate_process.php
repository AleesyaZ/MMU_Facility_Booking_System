<?php
session_start();
include('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get and sanitize the inputs from activate.html
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $new_password = mysqli_real_escape_string($conn, $_POST['password']);

    // 2. Search for the user with this email and OTP
    // We use LOWER() and TRIM() again to avoid the "not found" issues we had earlier
    $query = "SELECT * FROM user WHERE LOWER(TRIM(email)) = LOWER(TRIM('$email')) AND otp_code = '$otp' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // 3. Check for OTP Expiry (10 Minutes)
        $sent_time = strtotime($user['otp_sent_at']);
        $current_time = time();
        $time_difference = ($current_time - $sent_time) / 60; 

        if ($time_difference > 10) {
            echo "<script>alert('Your OTP has expired. Please request a new one.'); window.location.href='../prototypes/activate.html';</script>";
            exit();
        }

        // 4. THE UPDATE: Save the new password and set is_activated to 1
        // We also clear the otp_code so it cannot be used again
        $user_id = $user['user_id'];
        $update_query = "UPDATE user SET 
                         password = '$new_password', 
                         is_activated = 1, 
                         otp_code = NULL, 
                         otp_sent_at = NULL 
                         WHERE user_id = '$user_id'";

        if (mysqli_query($conn, $update_query)) {
            // Success! The database is now updated.
            echo "<script>
                    alert('Account activated! Your password has been set. You can now log in.'); 
                    window.location.href='../prototypes/login.html';
                  </script>";
        } else {
            echo "Error updating database: " . mysqli_error($conn);
        }

    } else {
        // If the email and OTP combination doesn't match
        echo "<script>alert('Invalid OTP or Email. Please check your database for the correct code.'); window.history.back();</script>";
    }
}
?>