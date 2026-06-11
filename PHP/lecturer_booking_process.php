<?php
session_start();
include('db_config.php');

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

    // --- 1. OVERLAP CHECK ---
    $check_overlap = "SELECT * FROM booking 
                      WHERE facility_id = '$facility_id' 
                      AND booking_date = '$date' 
                      AND status NOT IN ('Cancelled', 'Rejected') 
                      AND (
                          ('$start_time' >= start_time AND '$start_time' < end_time) OR 
                          ('$end_time' > start_time AND '$end_time' <= end_time) OR 
                          (start_time >= '$start_time' AND start_time < '$end_time')
                      )";
    $overlap_result = mysqli_query($conn, $check_overlap);
    $has_conflict = (mysqli_num_rows($overlap_result) > 0);

    // --- 2. ADVANCED PRIORITY LOGIC ---
    if ($has_conflict) {
        // Check if any of the existing bookings are already "Priority"
        $priority_conflict = false;
        while($row = mysqli_fetch_assoc($overlap_result)) {
            if ($row['is_priority'] == 1) {
                $priority_conflict = true;
                break;
            }
        }

        if ($priority_conflict) {
            // RULE: You cannot override another priority booking
            echo "<script>alert('Error: This slot is held by another priority user and cannot be overridden.'); window.history.back();</script>";
            exit();
        }

        if ($is_priority == 0) {
            // RULE: Standard conflict and you didn't turn on override
            echo "<script>alert('Error: Slot occupied. Use Priority Override for academic emergencies.'); window.history.back();</script>";
            exit();
        }

        // If we are here, it means there is a conflict but it's NOT priority, and current user HAS priority ON.
        $status = 'Pending'; // Needs Admin review to "bump" the standard user
    } else {
        // RULE: Slot is open! Auto-approve regardless of priority toggle
        $status = 'Approved';
    }

    // --- 3. FILE UPLOAD LOGIC ---
    $proof_filename = NULL;
    if (isset($_FILES['proofUpload']) && $_FILES['proofUpload']['error'] == 0) {
        $target_dir = "../public/uploads/proofs/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_ext = pathinfo($_FILES["proofUpload"]["name"], PATHINFO_EXTENSION);
        $proof_filename = "proof_" . time() . "_" . $user_id . "." . $file_ext;
        move_uploaded_file($_FILES["proofUpload"]["tmp_name"], $target_dir . $proof_filename);
    }

    // --- 4. INSERT DATA ---
    $sql = "INSERT INTO booking (user_id, facility_id, booking_date, start_time, end_time, purpose, status, is_priority, proof_file) 
            VALUES ('$user_id', '$facility_id', '$date', '$start_time', '$end_time', '$purpose', '$status', '$is_priority', '$proof_filename')";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn);
        // Handle Equipment (Optional)
        if (isset($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equip_id) {
                $qty = (int)$_POST['qty_' . $equip_id];
                mysqli_query($conn, "INSERT INTO booking_equipment (booking_id, equip_id, quantity) VALUES ('$booking_id', '$equip_id', '$qty')");
            }
        }
        $msg = ($status == 'Approved') ? "Booking Successful and Auto-Approved!" : "Priority request submitted. Status: Pending Admin Review.";
        echo "<script>alert('$msg'); window.location.href='../prototypes/student-dashboard.php';</script>";
    }
}
?>