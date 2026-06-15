<?php
session_start();
include('db_config.php'); // Adjust path based on your folder structure (e.g., '../PHP/db_config.php')

// 1. SECURITY CHECK: Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

// Check if required parameters are present
if (isset($_GET['booking_id']) && isset($_GET['action'])) {
    $booking_id = intval($_GET['booking_id']);
    $action = $_GET['action'];

    // 2. FETCH DETAILS of the target booking to read metadata
    $fetch_sql = "SELECT * FROM booking WHERE booking_id = $booking_id LIMIT 1";
    $fetch_res = mysqli_query($conn, $fetch_sql);
    $booking = mysqli_fetch_assoc($fetch_res);

    if (!$booking) {
        echo "<script>alert('Booking not found.'); window.location.href='../prototypes/admin-bookings.php';</script>";
        exit();
    }

    $facility_id = $booking['facility_id'];
    $date = $booking['booking_date'];
    $start = $booking['start_time'];
    $end = $booking['end_time'];
    $msg = "";

    // 3. HANDLE ACTIONS
    if ($action === 'approve') {
        // Approve current booking
        mysqli_query($conn, "UPDATE booking SET status = 'Approved' WHERE booking_id = $booking_id");

        // Resolve conflicts if this is an override priority booking
        if ($booking['is_priority'] == 1) {
            $cancel_conflicts = "
                UPDATE booking b
                JOIN user u ON b.user_id = u.user_id
                SET b.status = 'Cancelled'
                WHERE b.facility_id = $facility_id 
                  AND b.booking_date = '$date'
                  AND b.booking_id != $booking_id
                  AND b.status IN ('Pending', 'Approved')
                  AND u.role = 'Student'
                  AND (
                      ('$start' >= b.start_time AND '$start' < b.end_time) OR
                      ('$end' > b.start_time AND '$end' <= b.end_time) OR
                      (b.start_time >= '$start' AND b.start_time < '$end')
                  )
            ";
            mysqli_query($conn, $cancel_conflicts);
            $msg = "Priority Booking Approved. Overlapping student bookings have been automatically cancelled.";
        } else {
            $msg = "Booking Approved Successfully.";
        }

    } elseif ($action === 'reject') {
        mysqli_query($conn, "UPDATE booking SET status = 'Rejected' WHERE booking_id = $booking_id");
        $msg = "Booking has been Rejected.";

    } elseif ($action === 'cancel') {
        mysqli_query($conn, "UPDATE booking SET status = 'Cancelled' WHERE booking_id = $booking_id");
        $msg = "Booking has been Cancelled by Admin.";
    }

    // 4. DISPLAY MESSAGE AND REDIRECT BACK TO DASHBOARD
    if (!empty($msg)) {
        echo "<script>alert('$msg'); window.location.href='../prototypes/admin-bookings.php';</script>";
    } else {
        header("Location: ../prototypes/admin-bookings.php");
    }
    exit();

} else {
    header("Location: ../prototypes/admin-dashboard.php");
    exit();
}
?>