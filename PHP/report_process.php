<?php
session_start();
include('db_config.php');

// 1. SET TIMEZONE so the timestamp matches Malaysia time
date_default_timezone_set("Asia/Kuala_Lumpur");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $facility_name = mysqli_real_escape_string($conn, $_POST['facility_name']);
    $issue_type = mysqli_real_escape_string($conn, $_POST['issue_type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    // 2. Find the Facility ID based on the name typed
    $fac_query = mysqli_query($conn, "SELECT facility_id FROM facility WHERE facility_name = '$facility_name' LIMIT 1");
    if (mysqli_num_rows($fac_query) > 0) {
        $fac_data = mysqli_fetch_assoc($fac_query);
        $facility_id = $fac_data['facility_id'];
    } else {
        echo "<script>alert('Error: Facility name not recognized. Please use the suggestions provided.'); window.history.back();</script>";
        exit();
    }

    // 3. Handle Photo Upload
    $photo_name = NULL;
    if (isset($_FILES['proofUpload']) && $_FILES['proofUpload']['name'] !== '') {
        $upload_error = $_FILES['proofUpload']['error'];

        if ($upload_error === UPLOAD_ERR_OK) {
            $target_dir = "../public/uploads/issues/";

            // Create folder if it doesn't exist, and verify it worked
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (!is_writable($target_dir)) {
                echo "<script>alert('Server error: upload folder is not writable. Please contact IT support.'); window.history.back();</script>";
                exit();
            }

            $ext = pathinfo($_FILES["proofUpload"]["name"], PATHINFO_EXTENSION);
            $photo_name = "issue_" . time() . "_" . $user_id . "." . $ext;

            // Only keep the filename if the file was ACTUALLY moved successfully
            if (!move_uploaded_file($_FILES["proofUpload"]["tmp_name"], $target_dir . $photo_name)) {
                $photo_name = NULL;
                echo "<script>alert('Warning: Your report was not submitted because the photo failed to upload. Please try again with a smaller image.'); window.history.back();</script>";
                exit();
            }
        } else {
            // A real upload error occurred (e.g. file too large) - stop and tell the user instead of silently continuing
            $error_messages = [
                UPLOAD_ERR_INI_SIZE   => 'The photo exceeds the maximum upload size allowed by the server.',
                UPLOAD_ERR_FORM_SIZE  => 'The photo exceeds the maximum upload size allowed by the form.',
                UPLOAD_ERR_PARTIAL    => 'The photo was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: missing temporary upload folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write the photo to disk.',
                UPLOAD_ERR_EXTENSION  => 'The upload was blocked by a server extension.',
            ];
            $msg = $error_messages[$upload_error] ?? 'An unknown error occurred while uploading the photo.';
            echo "<script>alert('$msg Please try again with a smaller image or without a photo.'); window.history.back();</script>";
            exit();
        }
    }

    // 4. INSERT INTO DATABASE
    // UPDATED: Changed '$report_date' to NOW() to capture the exact time (hours/mins/secs)
    $sql = "INSERT INTO issue_report (user_id, facility_id, issue_type, description, issue_image, report_date, status) 
            VALUES ('$user_id', '$facility_id', '$issue_type', '$description', '$photo_name', NOW(), 'Under Review')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Issue reported successfully. The facilities team has been notified.'); window.location.href='../prototypes/user-dashboard.php';</script>";
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>