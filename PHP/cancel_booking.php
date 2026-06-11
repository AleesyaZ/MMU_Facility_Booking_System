<?php
session_start();
include('db_config.php');

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $booking_id = mysqli_real_escape_string($conn, $_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Check if the request came from the dashboard or my-bookings
    $redirect_url = "../prototypes/my-bookings.php";
    if (isset($_GET['source']) && $_GET['source'] == 'dashboard') {
        $redirect_url = "../prototypes/student-dashboard.php";
    }

    // Verify the booking belongs to this user before cancelling
    $check = mysqli_query($conn, "SELECT * FROM booking WHERE booking_id = '$booking_id' AND user_id = '$user_id' LIMIT 1");

    if (mysqli_num_rows($check) > 0) {
        $update = "UPDATE booking SET status = 'Cancelled' WHERE booking_id = '$booking_id'";
        if (mysqli_query($conn, $update)) {
            echo "<script>alert('Booking cancelled successfully.'); window.location.href='$redirect_url';</script>";
        }
    } else {
        echo "<script>alert('Error: Unauthorized action.'); window.location.href='$redirect_url';</script>";
    }
} else {
    header("Location: ../prototypes/student-dashboard.php");
}
?>