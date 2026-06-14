<?php
session_start();
include('db_config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $facility_name = mysqli_real_escape_string($conn, $_POST['facility_name']);
    $issue_type = mysqli_real_escape_string($conn, $_POST['issue_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $report_date = date("Y-m-d");

    // 1. Find the Facility ID based on the name typed
    $fac_query = mysqli_query($conn, "SELECT facility_id FROM facility WHERE facility_name = '$facility_name' LIMIT 1");
    if (mysqli_num_rows($fac_query) > 0) {
        $fac_data = mysqli_fetch_assoc($fac_query);
        $facility_id = $fac_data['facility_id'];
    } else {
        echo "<script>alert('Error: Facility name not recognized. Please use the suggestions provided.'); window.history.back();</script>";
        exit();
    }

    // 2. Handle Photo Upload
    $photo_name = NULL;
    if (isset($_FILES['proofUpload']) && $_FILES['proofUpload']['error'] == 0) {
        $target_dir = "../public/uploads/issues/";
        
        // Create folder if it doesn't exist
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $ext = pathinfo($_FILES["proofUpload"]["name"], PATHINFO_EXTENSION);
        // unique name: issue_171789012_10000000.jpg
        $photo_name = "issue_" . time() . "_" . $user_id . "." . $ext;
        
        move_uploaded_file($_FILES["proofUpload"]["tmp_name"], $target_dir . $photo_name);
    }

    // 3. INSERT INTO DATABASE
    // We added 'issue_image' to the list of columns below
    $sql = "INSERT INTO issue_report (user_id, facility_id, issue_type, description, issue_image, report_date, status) 
            VALUES ('$user_id', '$facility_id', '$issue_type', '$description', '$photo_name', '$report_date', 'Under Review')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Issue reported successfully. The facilities team has been notified.'); window.location.href='../prototypes/student-dashboard.php';</script>";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>