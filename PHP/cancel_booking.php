<?php
session_start();
include('db_config.php');

if (isset($_GET['id']) && isset($_SESSION['user_id'])) {
    $booking_id = mysqli_real_escape_string($conn, $_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    $redirect_url = "../prototypes/my-bookings.php";
    if (isset($_GET['source']) && $_GET['source'] == 'dashboard') {
        $redirect_url = "../prototypes/user-dashboard.php";
    }

    $check = mysqli_query($conn, "SELECT * FROM booking WHERE booking_id = '$booking_id' AND user_id = '$user_id' LIMIT 1");

    if (mysqli_num_rows($check) > 0) {
        $booking_data = mysqli_fetch_assoc($check);
        
        if ($booking_data['status'] !== 'Cancelled' && $booking_data['status'] !== 'Rejected') {
            
            // Fetch all equipment tied to this specific booking to know what to restore
            $equip_query = "SELECT equip_id, quantity FROM booking_equipment WHERE booking_id = '$booking_id'";
            $equip_res = mysqli_query($conn, $equip_query);

            if ($equip_res && mysqli_num_rows($equip_res) > 0) {
                while ($item = mysqli_fetch_assoc($equip_res)) {
                    $eid = $item['equip_id'];
                    $qty = $item['quantity'];

                    // Add the quantity back to the equipment's available stock
                    $restore_sql = "UPDATE equipment SET avail_qty = avail_qty + $qty WHERE equip_id = '$eid'";
                    mysqli_query($conn, $restore_sql);
                }

                // Now that stock is restored, remove the records from the link table
                $delete_equip_records = "DELETE FROM booking_equipment WHERE booking_id = '$booking_id'";
                mysqli_query($conn, $delete_equip_records);
            }

            // Finally, update the booking status to Cancelled
            $update = "UPDATE booking SET status = 'Cancelled' WHERE booking_id = '$booking_id'";
            if (mysqli_query($conn, $update)) {
                echo "<script>alert('Booking cancelled, stock restored, and equipment records cleared.'); window.location.href='$redirect_url';</script>";
            }
        } else {
            echo "<script>alert('Notice: This booking is already cancelled.'); window.location.href='$redirect_url';</script>";
        }
    } else {
        echo "<script>alert('Error: Unauthorized action.'); window.location.href='$redirect_url';</script>";
    }
} else {
    header("Location: ../prototypes/user-dashboard.php");
}
?>