<?php
session_start();
include('db_config.php');

// 1. SECURITY CHECK: Only allow logged-in Lecturers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Lecturer') {
    die("Unauthorized access.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $user_id = $_SESSION['user_id'];
    $facility_id = mysqli_real_escape_string($conn, $_POST['facility_id']);
    $date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $is_priority = isset($_POST['priority']) ? 1 : 0;

    // --- NEW: LECTURER QUOTA LOGIC (From Handover Notes) ---
    // Rule: Priority Override = No Quota Cost. Standard Booking = Normal Quota Cost.
    if ($is_priority == 0) {
        // Fetch Lecturer's Quota Limit
        $user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1"));
        $max_quota = $user_info['booking_quota'];

        // Count current weekly usage (excluding priority bookings as they don't count towards quota)
        $usage_query = "SELECT COUNT(*) as total FROM booking 
                        WHERE user_id = '$user_id' 
                        AND is_priority = 0
                        AND status NOT IN ('Cancelled', 'Rejected') 
                        AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
        $usage_data = mysqli_fetch_assoc(mysqli_query($conn, $usage_query));
        
        if ($usage_data['total'] >= $max_quota) {
            echo "<script>alert('Error: Weekly quota reached. Please use \"Priority Override\" for official academic purposes only.'); window.history.back();</script>";
            exit();
        }
    }
    // --- END QUOTA LOGIC ---

    // --- DURATION LIMIT LOGIC ---
    $start_ts = strtotime($start_time);
    $end_ts = strtotime($end_time);
    
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

    // Handle File Upload (proof_file)
    $proof_filename = NULL;
    if (isset($_FILES['proofUpload']) && $_FILES['proofUpload']['error'] == 0) {
        $target_dir = "../public/uploads/proofs/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["proofUpload"]["name"], PATHINFO_EXTENSION);
        $proof_filename = "proof_" . time() . "_" . $user_id . "." . $file_ext;
        
        if (!move_uploaded_file($_FILES["proofUpload"]["tmp_name"], $target_dir . $proof_filename)) {
             echo "<script>alert('Error saving file. Check folder permissions.'); window.history.back();</script>";
             exit();
        }
    }

    // Insert into DB using is_priority and proof_file columns
    $sql = "INSERT INTO booking (user_id, facility_id, booking_date, start_time, end_time, purpose, status, is_priority, proof_file) 
            VALUES ('$user_id', '$facility_id', '$date', '$start_time', '$end_time', '$purpose', 'Pending', '$is_priority', '$proof_filename')";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn);
        
        // Handle Add-On Equipment
        if (isset($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equip_id) {
                $qty_field = 'qty_' . $equip_id;
                $qty = isset($_POST[$qty_field]) ? (int)$_POST[$qty_field] : 1;
                
                $equip_id = mysqli_real_escape_string($conn, $equip_id);
                mysqli_query($conn, "INSERT INTO booking_equipment (booking_id, equip_id, quantity) VALUES ('$booking_id', '$equip_id', '$qty')");
            }
        }
        echo "<script>alert('Lecturer Booking Submitted Successfully!'); window.location.href='../prototypes/student-dashboard.php';</script>";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>