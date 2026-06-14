<?php
session_start();
include('db_config.php');

if (isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 

    // Look for activated users only
    $query = "SELECT * FROM user WHERE email = '$email' AND password = '$password' AND is_activated = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Store user info in Session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // ROLE-BASED REDIRECTION
        if ($user['role'] === 'Admin') {
            header("Location: ../prototypes/admin-dashboard.php");
        } elseif ($user['role'] === 'Lecturer') {
            header("Location: ../prototypes/student-dashboard.php");
        } else {
            // Default for Students
            header("Location: ../prototypes/student-dashboard.php");
        }
        exit();
    } else {
        echo "<script>alert('Invalid Email/Password or Account not activated.'); window.location.href='../prototypes/login.html';</script>";
    }
}
?>