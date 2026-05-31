<?php
// Turn on error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('db_config.php');

if (isset($_POST['email'])) {
    // 1. Trim spaces from the user input and convert to lowercase
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    
    // 2. Search using TRIM and LOWER to match exactly even if DB has spaces or caps
    // Using LOWER(email) makes the search case-insensitive
    $query = "SELECT * FROM user WHERE LOWER(TRIM(email)) = LOWER('$email') LIMIT 1";
    $check = mysqli_query($conn, $query);

    if (!$check) {
        // If the SQL itself has an error, tell us
        echo "SQL Error: " . mysqli_error($conn);
        exit();
    }

    if (mysqli_num_rows($check) > 0) {
        $user = mysqli_fetch_assoc($check);
        
        if ($user['is_activated'] == 1) {
            echo "already_active";
        } else {
            $otp = rand(100000, 999999);
            $now = date("Y-m-d H:i:s");
            
            // Update the record using the ID we just found
            $user_id = $user['user_id'];
            $update = mysqli_query($conn, "UPDATE user SET otp_code = '$otp', otp_sent_at = '$now' WHERE user_id = '$user_id'");
            
            if($update) {
                echo "success";
            } else {
                echo "Update failed: " . mysqli_error($conn);
            }
        }
    } else {
        echo "not_found";
    }
} else {
    echo "no_email_received";
}
?>