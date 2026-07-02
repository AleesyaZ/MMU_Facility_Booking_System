<?php
session_start();
include('db_config.php');

// Set Timezone
date_default_timezone_set("Asia/Kuala_Lumpur");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $user_id = $_SESSION['user_id'];
    $facility_id = mysqli_real_escape_string($conn, $_POST['facility_id']);
    $date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);

    // 1. BACKEND QUOTA SECURITY CHECK
    $user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1"));
    $max_quota = $user_info['booking_quota'];

    $usage_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking 
                    WHERE user_id = '$user_id' 
                    AND status NOT IN ('Cancelled', 'Rejected') 
                    AND (is_priority = 0 OR is_priority IS NULL)
                    AND YEARWEEK(booking_date, 1) >= YEARWEEK(CURDATE(), 1)");
    $current_usage = mysqli_fetch_assoc($usage_res)['total'];

    if ($current_usage >= $max_quota) {
        echo "<script>alert('Error: Weekly quota reached ($max_quota).'); window.location.href='../prototypes/facilities.php';</script>";
        exit();
    }

    // 2. DURATION LIMIT CHECK (1-2 Hours)
    $start_ts = strtotime($start_time);
    $end_ts = strtotime($end_time);
    $duration = ($end_ts - $start_ts) / 3600;

    if ($end_ts <= $start_ts || $duration < 1 || $duration > 2) {
        echo "<script>alert('Error: Booking must be between 1 and 2 hours.'); window.history.back();</script>";
        exit();
    }

    // 3. OVERLAP CHECK
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

    if (mysqli_num_rows($overlap_result) > 0) {
        echo "<script>alert('Error: This time slot is already booked.'); window.history.back();</script>";
        exit();
    }

    // 4. MAIN INSERT (Auto-Approved for free slots)
    $sql = "INSERT INTO booking (user_id, facility_id, booking_date, start_time, end_time, purpose, status, is_priority) 
            VALUES ('$user_id', '$facility_id', '$date', '$start_time', '$end_time', '$purpose', 'Approved', 0)";

    if (mysqli_query($conn, $sql)) {
        $booking_id = mysqli_insert_id($conn);

        // --- UPDATED: 5. ADD-ON EQUIPMENT LOGIC WITH QUANTITY UPDATE ---
        if (isset($_POST['equipment']) && is_array($_POST['equipment'])) {
            foreach ($_POST['equipment'] as $equip_id) {
                $equip_id = mysqli_real_escape_string($conn, $equip_id);
                $qty_field = 'qty_' . $equip_id;
                $requested_qty = isset($_POST[$qty_field]) ? (int)$_POST[$qty_field] : 1;

                // A. Verify availability again on backend before deducting
                $check_stock = mysqli_query($conn, "SELECT avail_qty FROM equipment WHERE equip_id = '$equip_id'");
                $stock_data = mysqli_fetch_assoc($check_stock);
                
                if ($stock_data && $stock_data['avail_qty'] >= $requested_qty) {
                    // B. Insert relationship record
                    $sql_equip = "INSERT INTO booking_equipment (booking_id, equip_id, quantity) 
                                  VALUES ('$booking_id', '$equip_id', '$requested_qty')";
                    mysqli_query($conn, $sql_equip);

                    // C. UPDATE THE EQUIPMENT TABLE (Subtract from available quantity)
                    $update_stock = "UPDATE equipment SET avail_qty = avail_qty - $requested_qty WHERE equip_id = '$equip_id'";
                    mysqli_query($conn, $update_stock);
                } else {
                    // Optional: You could alert if some items were out of stock, 
                    // but usually, the form prevents this via the 'max' attribute.
                }
            }
        }
        echo "<script>alert('Booking successful and Auto-Approved!'); window.location.href='../prototypes/student-dashboard.php';</script>";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>