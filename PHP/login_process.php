<?php
session_start();
include('db_config.php');

if (isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 

    // Added is_activated = 1 to the check
    $query = "SELECT * FROM USER WHERE email = '$email' AND password = '$password' AND is_activated = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        if ($user['role'] == 'Admin') {
            header("Location: ../prototypes/admin-dashboard.html");
        } elseif ($user['role'] == 'Lecturer') {
            header("Location: ../prototypes/lecturer-dashboard.html");
        } else {
            header("Location: ../prototypes/student-dashboard.html");
        }
        exit();
    } else {
        echo "<script>alert('Account not activated or invalid credentials.'); window.location.href='../prototypes/login.html';</script>";
    }
}
?>