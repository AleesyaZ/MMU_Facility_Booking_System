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

    // --- NEW: BACKEND QUOTA SECURITY CHECK ---
    // 1. Get the user's maximum allowed quota
    $user_info_query = mysqli_query($conn, "SELECT booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1");
    $user_info = mysqli_fetch_assoc($user_info_query);
    $max_quota = $user_info['booking_quota'];

    // 2. Count active bookings in the current week (Sunday-Saturday logic)
    $usage_query = "SELECT COUNT(*) as total FROM booking 
                    WHERE user_id = '$user_id' 
                    AND status NOT IN ('Cancelled', 'Rejected') 
                    AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
    $usage_res = mysqli_query($conn, $usage_query);
    $usage_data = mysqli_fetch_assoc($usage_res);
    $current_usage = $usage_data['total'];

    // 3. Block if quota is reached
    if ($current_usage >= $max_quota) {
        echo "<script>alert('Error: You have reached your weekly booking limit ($max_quota). Please try again next week.'); window.location.href='../prototypes/facilities.php';</script>";
        exit();
    }
    // --- END QUOTA CHECK ---


    // --- DURATION LIMIT LOGIC ---
    $start_ts = strtotime($start_time);
    $end_ts = strtotime($end_time);
    
    // Check if end time is before start time
    if ($end_ts <= $start_ts) {
        echo "<script>alert('Error: End time must be after start time.'); window.history.back();</script>";
        exit();
    }

    $duration_hours = ($end_ts - $start_ts) / 3600;

    if ($duration_hours < 1) {
        echo "<script>alert('Error: Minimum booking duration is 1 hour.'); window.history.back();</script>";
        exit();
    }

    if ($duration_hours > 2) {
        echo "<script>alert('Error: Maximum booking duration is 2 hours.'); window.history.back();</script>";
        exit();
    }
    // --- END OF DURATION LOGIC ---

    // Proceed to insert if both Quota and Duration are valid
    $sql = "INSERT INTO booking (user_id, facility_id, booking_date, start_time, end_time, purpose, status) 
            VALUES ('$user_id', '$facility_id', '$date', '$start_time', '$end_time', '$purpose', 'Pending')";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn);

        if (isset($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equip_id) {
                $qty_field = 'qty_' . $equip_id;
                $qty = isset($_POST[$qty_field]) ? (int)$_POST[$qty_field] : 1;
                
                $equip_id = mysqli_real_escape_string($conn, $equip_id);
                mysqli_query($conn, "INSERT INTO booking_equipment (booking_id, equip_id, quantity) VALUES ('$booking_id', '$equip_id', '$qty')");
            }
        }

        echo "<script>alert('Booking Successful!'); window.location.href='../prototypes/student-dashboard.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>