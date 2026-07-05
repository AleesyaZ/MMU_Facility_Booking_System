<?php
session_start();
include('db_config.php');

if (isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Do not escape the password if using password_verify

    // 1. Look for the user by email only first
    $query = "SELECT * FROM user WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // 2. Check if the account is activated
        if ($user['is_activated'] == 0) {
            echo "<script>alert('Account not activated. Please verify your email.'); window.location.href='../prototypes/activate.html';</script>";
            exit();
        }
        // 4. Verify the hashed password
        // This function compares the typed password with the $2y$10... string in the DB
        if (password_verify($password, $user['password'])) {
            
            // Store user info in Session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // ROLE-BASED REDIRECTION
            if ($user['role'] === 'Admin') {
                header("Location: ../prototypes/admin-dashboard.php");
            } else {
                // Both Lecturers and Students go here
                header("Location: ../prototypes/user-dashboard.php");
            }
            exit();
            
        } else {
            // Password did not match the hash
            echo "<script>alert('Invalid Email or Password.'); window.location.href='../prototypes/login.html';</script>";
        }
    } else {
        // Email not found
        echo "<script>alert('Invalid Email or Password.'); window.location.href='../prototypes/login.html';</script>";
    }
}
?>