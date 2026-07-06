<?php
session_start();
include('db_config.php'); 

// Set Timezone
date_default_timezone_set("Asia/Kuala_Lumpur");

// SECURITY CHECK: Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if (isset($_GET['booking_id']) && isset($_GET['action'])) {
    $booking_id = intval($_GET['booking_id']);
    $action = $_GET['action'];

    // FETCH DETAILS of the target booking
    $fetch_sql = "SELECT b.*, f.facility_name FROM booking b 
                  JOIN facility f ON b.facility_id = f.facility_id 
                  WHERE b.booking_id = $booking_id LIMIT 1";
    $fetch_res = mysqli_query($conn, $fetch_sql);
    $booking = mysqli_fetch_assoc($fetch_res);

    if (!$booking) {
        echo "<script>alert('Booking not found.'); window.location.href='../prototypes/admin-bookings.php';</script>";
        exit();
    }

    $target_user_id = $booking['user_id'];
    $facility_name = $booking['facility_name'];
    $facility_id = $booking['facility_id'];
    $date = $booking['booking_date'];
    $start = $booking['start_time'];
    $end = $booking['end_time'];
    $msg = "";

    // HANDLE ACTIONS
    if ($action === 'approve') {
        // Approve current booking
        mysqli_query($conn, "UPDATE booking SET status = 'Approved' WHERE booking_id = $booking_id");

        // Inform the Lecturer/Requester that their specific request was approved 
        $notif_title = "Booking Request Approved";
        $notif_msg = "Your reservation for $facility_name on $date (" . date("g:i A", strtotime($start)) . ") has been officially approved by the Admin.";
        mysqli_query($conn, "INSERT INTO notification (user_id, title, message, is_read, date_sent) 
                             VALUES ('$target_user_id', '$notif_title', '$notif_msg', 0, NOW())");

        // Handle Priority Overrides (Notify bumped students) 
        if ($booking['is_priority'] == 1) {
            $find_conflicts = "SELECT b.booking_id, b.user_id FROM booking b
                               JOIN user u ON b.user_id = u.user_id
                               WHERE b.facility_id = $facility_id 
                               AND b.booking_date = '$date'
                               AND b.booking_id != $booking_id
                               AND b.status IN ('Pending', 'Approved')
                               AND u.role = 'Student'
                               AND (('$start' >= b.start_time AND '$start' < b.end_time) OR 
                                    ('$end' > b.start_time AND '$end' <= b.end_time) OR 
                                    (b.start_time >= '$start' AND b.start_time < '$end'))";
            
            $conflicts_res = mysqli_query($conn, $find_conflicts);

            while ($conflict = mysqli_fetch_assoc($conflicts_res)) {
                $c_bid = $conflict['booking_id'];
                $c_uid = $conflict['user_id'];

                // Restore stock for student
                $eq_res = mysqli_query($conn, "SELECT equip_id, quantity FROM booking_equipment WHERE booking_id = $c_bid");
                while ($eq = mysqli_fetch_assoc($eq_res)) {
                    $eid = $eq['equip_id']; $eqy = $eq['quantity'];
                    mysqli_query($conn, "UPDATE equipment SET avail_qty = avail_qty + $eqy WHERE equip_id = $eid");
                }
                mysqli_query($conn, "DELETE FROM booking_equipment WHERE booking_id = $c_bid");

                // Notify bumped student
                $st_title = "Booking Overridden by Admin";
                $st_msg = "Your booking for $facility_name on $date has been cancelled as the slot was needed for an academic priority session.";
                mysqli_query($conn, "INSERT INTO notification (user_id, title, message, is_read, date_sent) 
                                     VALUES ('$c_uid', '$st_title', '$st_msg', 0, NOW())");
                
                mysqli_query($conn, "UPDATE booking SET status = 'Cancelled' WHERE booking_id = $c_bid");
            }
            $msg = "Priority Request Approved. Affected users have been notified.";
        } else {
            $msg = "Standard Booking Approved. User has been notified.";
        }

    } elseif ($action === 'reject' || $action === 'cancel') {
        $new_status = ($action === 'reject') ? 'Rejected' : 'Cancelled';
        
        // Restore Equipment stock
        $eq_res = mysqli_query($conn, "SELECT equip_id, quantity FROM booking_equipment WHERE booking_id = $booking_id");
        while ($eq = mysqli_fetch_assoc($eq_res)) {
            $eid = $eq['equip_id']; $eqy = $eq['quantity'];
            mysqli_query($conn, "UPDATE equipment SET avail_qty = avail_qty + $eqy WHERE equip_id = $eid");
        }
        mysqli_query($conn, "DELETE FROM booking_equipment WHERE booking_id = $booking_id");

        // Update status
        mysqli_query($conn, "UPDATE booking SET status = '$new_status' WHERE booking_id = $booking_id");

        // Inform the Lecturer/Requester of Disapproval
        $notif_title = "Booking Request " . $new_status;
        $notif_msg = "We regret to inform you that your booking for $facility_name on $date has been $new_status by the Administration.";
        mysqli_query($conn, "INSERT INTO notification (user_id, title, message, is_read, date_sent) 
                             VALUES ('$target_user_id', '$notif_title', '$notif_msg', 0, NOW())");

        $msg = "Booking has been $new_status. Requester notified.";
    }

    // REDIRECT
    echo "<script>alert('$msg'); window.location.href='../prototypes/admin-bookings.php';</script>";
    exit();

} else {
    header("Location: ../prototypes/admin-dashboard.php");
    exit();
}
?>