<?php
session_start();
include('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_report'])) {
    $report_id = mysqli_real_escape_string($conn, $_POST['report_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $reply = mysqli_real_escape_string($conn, $_POST['admin_reply']);
    $date = date("Y-m-d");

    $sql = "UPDATE issue_report SET 
            status = '$status', 
            admin_reply = '$reply', 
            reply_date = '$date' 
            WHERE report_id = '$report_id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Report status updated successfully.'); window.location.href='../prototypes/admin-reports.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>