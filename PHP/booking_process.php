<?php
session_start();
include('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $user_id = $_SESSION['user_id'];
    $facility_id = mysqli_real_escape_string($conn, $_POST['facility_id']);
    $date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);

    // --- 1. OVERLAP CHECK ---
    // Look for any Approved or Pending booking that overlaps with this time
    $check_overlap = "SELECT * FROM booking 
                      WHERE facility_id = '$facility_id' 
                      AND booking_date = '$date' 
                      AND status != 'Cancelled' 
                      AND status != 'Rejected'
                      AND (
                          ('$start_time' >= start_time AND '$start_time' < end_time) OR 
                          ('$end_time' > start_time AND '$end_time' <= end_time) OR 
                          (start_time >= '$start_time' AND start_time < '$end_time')
                      )";
    $overlap_result = mysqli_query($conn, $check_overlap);

    if (mysqli_num_rows($overlap_result) > 0) {
        echo "<script>alert('Error: This facility is already booked during the selected time.'); window.history.back();</script>";
        exit();
    }

    // --- 2. AUTO-APPROVE LOGIC ---
    // If we reached here, there is no conflict. Set status to Approved immediately.
    $status = 'Approved';

    $sql = "INSERT INTO booking (user_id, facility_id, booking_date, start_time, end_time, purpose, status) 
            VALUES ('$user_id', '$facility_id', '$date', '$start_time', '$end_time', '$purpose', '$status')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Booking Auto-Approved!'); window.location.href='../prototypes/student-dashboard.php';</script>";
    }
}
?>