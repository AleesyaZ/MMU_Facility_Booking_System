<?php
session_start();
include('db_config.php');

date_default_timezone_set("Asia/Kuala_Lumpur");

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

    // QUOTA CHECK (Only for non-priority bookings)
    if ($is_priority == 0) {
        $user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1"));
        $max_quota = $user_info['booking_quota'];
        // Reset on Sunday
        $usage_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE user_id = '$user_id' AND status != 'Cancelled' AND is_priority = 0 AND YEARWEEK(booking_date, 0) >= YEARWEEK(CURDATE(), 0)");
        if (mysqli_fetch_assoc($usage_res)['total'] >= $max_quota) {
            echo "<script>alert('Quota reached. Use Priority Override for academic reasons.'); window.history.back();</script>";
            exit();
        }
    }

    // DURATION LIMIT CHECK
    $duration = (strtotime($end_time) - strtotime($start_time)) / 3600;
    if ($duration < 1 || $duration > 2) {
        echo "<script>alert('Error: Duration must be 1-2 hours.'); window.history.back();</script>";
        exit();
    }

    // OVERLAP & PRIORITY CONFLICT CHECK
    $check_overlap = "SELECT * FROM booking WHERE facility_id = '$facility_id' AND booking_date = '$date' AND status NOT IN ('Cancelled', 'Rejected') 
                      AND (('$start_time' >= start_time AND '$start_time' < end_time) OR ('$end_time' > start_time AND '$end_time' <= end_time) OR (start_time >= '$start_time' AND start_time < '$end_time'))";
    $overlap_result = mysqli_query($conn, $check_overlap);
    
    $status = 'Approved'; // Default
    if (mysqli_num_rows($overlap_result) > 0) {
        while($row = mysqli_fetch_assoc($overlap_result)) {
            if ($row['is_priority'] == 1) {
                echo "<script>alert('Error: This slot is locked by another Priority User.'); window.history.back();</script>";
                exit();
            }
        }
        if ($is_priority == 0) {
            echo "<script>alert('Slot occupied. Enable Priority Override to proceed.'); window.history.back();</script>";
            exit();
        }
        $status = 'Pending'; // Conflict exists + current user used priority
    }

    // TIMETABLE OVERLAP CHECK (Fixed Classes)
    $day_name = date('l', strtotime($date));
    $check_timetable = "SELECT * FROM timetable 
                        WHERE facility_id = '$facility_id' 
                        AND day_of_week = '$day_name' 
                        AND expiry_date >= '$date'
                        AND (
                            ('$start_time' >= start_time AND '$start_time' < end_time) OR 
                            ('$end_time' > start_time AND '$end_time' <= end_time) OR 
                            (start_time >= '$start_time' AND start_time < '$end_time')
                        )";
    $tt_result = mysqli_query($conn, $check_timetable);

    if (mysqli_num_rows($tt_result) > 0) {
        echo "<script>alert('Error: This slot is reserved for a fixed academic class.'); window.history.back();</script>";
        exit();
    }

    // FILE UPLOAD (proof_file)
    $proof_filename = NULL;
    if (isset($_FILES['proofUpload']) && $_FILES['proofUpload']['error'] == 0) {
        $target_dir = "../public/uploads/proofs/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_ext = pathinfo($_FILES["proofUpload"]["name"], PATHINFO_EXTENSION);
        $proof_filename = "proof_" . time() . "_" . $user_id . "." . $file_ext;
        move_uploaded_file($_FILES["proofUpload"]["tmp_name"], $target_dir . $proof_filename);
    }

    // INSERT DATA & UPDATE EQUIPMENT STOCK
    $sql = "INSERT INTO booking (user_id, facility_id, booking_date, start_time, end_time, purpose, status, is_priority, proof_file) 
            VALUES ('$user_id', '$facility_id', '$date', '$start_time', '$end_time', '$purpose', '$status', '$is_priority', '$proof_filename')";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn);

        // EQUIPMENT LOGIC WITH STOCK DEDUCTION 
        if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equip_id) {
                $equip_id = mysqli_real_escape_string($conn, $equip_id);
                $qty_field = 'qty_' . $equip_id;
                $requested_qty = isset($_POST[$qty_field]) ? (int)$_POST[$qty_field] : 1;

                // Check current stock availability
                $check_stock = mysqli_query($conn, "SELECT avail_qty FROM equipment WHERE equip_id = '$equip_id'");
                $stock_row = mysqli_fetch_assoc($check_stock);

                if ($stock_row && $stock_row['avail_qty'] >= $requested_qty) {
                    // Record the equipment used for this booking
                    mysqli_query($conn, "INSERT INTO booking_equipment (booking_id, equip_id, quantity) VALUES ('$booking_id', '$equip_id', '$requested_qty')");

                    // Deduct from the main equipment table
                    mysqli_query($conn, "UPDATE equipment SET avail_qty = avail_qty - $requested_qty WHERE equip_id = '$equip_id'");
                }
            }
        }

        $msg = ($status == 'Approved') ? "Auto-Approved!" : "Pending Admin Review.";
        echo "<script>alert('Booking Successful! $msg'); window.location.href='../prototypes/user-dashboard.php';</script>";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>```