<?php
session_start();
include('db_config.php'); // No change here since they are in the same folder

if (isset($_POST['login_btn'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); 

    $query = "SELECT * FROM USER WHERE email = '$email' AND password = '$password' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        // PATH UPDATE: Go up one level to find the prototypes folder
        if ($user['role'] == 'Admin') {
            header("Location: ../prototypes/admin-dashboard.html");
        } elseif ($user['role'] == 'Lecturer') {
            header("Location: ../prototypes/lecturer-dashboard.html");
        } else {
            header("Location: ../prototypes/student-dashboard.html");
        }
        exit();
    } else {
        // PATH UPDATE: Go up one level to go back to login.html
        echo "<script>
                alert('Invalid MMU Email or Password'); 
                window.location.href='../prototypes/login.html';
              </script>";
    }
} else {
    header("Location: ../prototypes/login.html");
    exit();
}
?>