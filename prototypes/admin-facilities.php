<?php
session_start();
include('../PHP/db_config.php');

// Set Timezone
date_default_timezone_set("Asia/Kuala_Lumpur");

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];
$initials = substr($admin_name, 0, 1);

// --- AUTO-CLEANUP EXPIRED ENTRIES ---
mysqli_query($conn, "DELETE FROM timetable WHERE expiry_date < CURDATE()");

// --- ACTION: SAVE SCHEDULE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_timetable'])) {
    $fid = mysqli_real_escape_string($conn, $_POST['facility_id']);
    $weeks = (int)$_POST['recurrence_weeks'];
    $slots = json_decode($_POST['selected_slots'], true); 
    $current_week_monday = $_POST['current_week_monday'];

    // Fetch facility name for notifications
    $f_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT facility_name FROM facility WHERE facility_id = '$fid'"));
    $f_name = $f_info['facility_name'];

    if (!empty($slots)) {
        // Calculate the Final Expiry Date for the timetable table
        // Subtract 1 week so the expiry lands on the LAST valid week's Monday, not the week after it
        $expiry_date = date('Y-m-d', strtotime("$current_week_monday +" . ($weeks - 1) . " weeks"));

        foreach ($slots as $slot) {
            $day = mysqli_real_escape_string($conn, $slot['day']);
            $start = mysqli_real_escape_string($conn, $slot['start']);
            $end = mysqli_real_escape_string($conn, $slot['end']);

            // 1. Insert the recurring schedule into the timetable
            $safe_start_date = mysqli_real_escape_string($conn, $current_week_monday);
            $sql = "INSERT INTO timetable (facility_id, day_of_week, start_time, end_time, expiry_date, start_date) 
                    VALUES ('$fid', '$day', '$start', '$end', '$expiry_date', '$safe_start_date')";
            mysqli_query($conn, $sql);

            // 2. LOGIC: Cancel existing bookings that conflict with this new fixed class
            // We loop through the number of weeks requested to find specific dates to cancel
            for ($i = 0; $i < $weeks; $i++) {
                // Get the specific date for this weekday in this specific week
                $target_date = date('Y-m-d', strtotime("$current_week_monday +$i weeks $day"));
                
                // Find all bookings (Standard & Priority) clashing with this slot
                $find_sql = "SELECT booking_id, user_id FROM booking 
                             WHERE facility_id = '$fid' 
                             AND booking_date = '$target_date'
                             AND status IN ('Approved', 'Pending')
                             AND (
                                ('$start' >= start_time AND '$start' < end_time) OR 
                                ('$end' > start_time AND '$end' <= end_time) OR 
                                (start_time >= '$start' AND start_time < '$end')
                             )";
                
                $conflicts = mysqli_query($conn, $find_sql);
                while ($bk = mysqli_fetch_assoc($conflicts)) {
                    $bid = $bk['booking_id'];
                    $uid = $bk['user_id'];

                    // A. Restore Equipment Stock
                    $eq_res = mysqli_query($conn, "SELECT equip_id, quantity FROM booking_equipment WHERE booking_id = '$bid'");
                    while ($eq = mysqli_fetch_assoc($eq_res)) {
                        $eid = $eq['equip_id']; $qty = $eq['quantity'];
                        mysqli_query($conn, "UPDATE equipment SET avail_qty = avail_qty + $qty WHERE equip_id = '$eid'");
                    }
                    mysqli_query($conn, "DELETE FROM booking_equipment WHERE booking_id = '$bid'");

                    // B. Notify the User
                    $notif_title = "Booking Cancelled: Schedule Update";
                    $notif_msg = "Your booking for $f_name on $target_date has been cancelled because the slot is now reserved for a recurring Academic Class.";
                    mysqli_query($conn, "INSERT INTO notification (user_id, title, message, is_read, date_sent) 
                                         VALUES ('$uid', '$notif_title', '$notif_msg', 0, NOW())");

                    // C. Set Booking to Cancelled
                    mysqli_query($conn, "UPDATE booking SET status = 'Cancelled' WHERE booking_id = '$bid'");
                }
            }
        }
        header("Location: admin-facilities.php?msg=schedule_saved");
    } else {
        header("Location: admin-facilities.php?msg=no_slots");
    }
    exit();
}

// --- REST OF THE CODE REMAINS EXACTLY THE SAME ---
if (isset($_GET['action'])) {

    // --- NEW: AJAX endpoint to fetch a facility's real schedule for the Preview modal ---
    if ($_GET['action'] == 'get_schedule') {
        header('Content-Type: application/json');
        $fid = mysqli_real_escape_string($conn, $_GET['fid']);
        $week_offset = isset($_GET['week']) ? (int)$_GET['week'] : 0;

        $today = new DateTime();
        $day_of_week = $today->format('N');
        $monday = clone $today;
        $monday->modify("-" . ($day_of_week - 1) . " days");
        if ($week_offset != 0) {
            $monday->modify("$week_offset weeks");
        }
        $friday = clone $monday;
        $friday->modify("+4 days");

        $start_date = $monday->format('Y-m-d');
        $end_date = $friday->format('Y-m-d');

        // 1. Fixed Classes (only show weeks on/after the schedule's actual start date)
        $tt_query = "SELECT day_of_week, start_time, end_time FROM timetable 
                     WHERE facility_id = '$fid' 
                     AND expiry_date >= '$start_date' 
                     AND (start_date IS NULL OR start_date <= '$end_date')";
        $tt_res = mysqli_query($conn, $tt_query);
        // 2. Bookings (Normal + Priority)
        $bk_query = "SELECT booking_date, start_time, end_time, is_priority, DAYNAME(booking_date) as day_name 
                     FROM booking 
                     WHERE facility_id = '$fid' 
                     AND status IN ('Approved', 'Pending') 
                     AND booking_date BETWEEN '$start_date' AND '$end_date'";
        $bk_res = mysqli_query($conn, $bk_query);

        $schedule_map = [];

        while ($row = mysqli_fetch_assoc($tt_res)) {
            $start_h = (int)substr($row['start_time'], 0, 2);
            $end_h = (int)substr($row['end_time'], 0, 2);
            $end_m = (int)substr($row['end_time'], 3, 2);
            $limit = ($end_m > 0) ? $end_h : $end_h - 1;
            for ($i = $start_h; $i <= $limit; $i++) {
                $schedule_map[$row['day_of_week']][$i] = 'fixed';
            }
        }

        while ($row = mysqli_fetch_assoc($bk_res)) {
            $start_h = (int)substr($row['start_time'], 0, 2);
            $end_h = (int)substr($row['end_time'], 0, 2);
            $end_m = (int)substr($row['end_time'], 3, 2);
            $type = ($row['is_priority'] == 1) ? 'priority' : 'booked';
            $limit = ($end_m > 0) ? $end_h : $end_h - 1;
            for ($i = $start_h; $i <= $limit; $i++) {
                $schedule_map[$row['day_name']][$i] = $type;
            }
        }

        echo json_encode([
            'schedule' => $schedule_map,
            'range' => $monday->format('d M') . ' - ' . $friday->format('d M')
        ]);
        exit();
    }

    $id = mysqli_real_escape_string($conn, $_GET['id']);
    if ($_GET['action'] == 'delete') {
        if(mysqli_query($conn, "DELETE FROM facility WHERE facility_id = '$id'")) header("Location: admin-facilities.php?msg=deleted");
        else header("Location: admin-facilities.php?msg=error_fk");
    }
    if ($_GET['action'] == 'set_status') {
        $st = mysqli_real_escape_string($conn, $_GET['status']);
        mysqli_query($conn, "UPDATE facility SET status = '$st' WHERE facility_id = '$id'");
        header("Location: admin-facilities.php?msg=status_updated");
    }
    exit();
}

$campus_f = isset($_GET['campus']) ? mysqli_real_escape_string($conn, $_GET['campus']) : 'All Campuses';
$faculty_f = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : 'All Faculties';
$category_f = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'All Categories';

$query = "SELECT * FROM facility WHERE 1=1";
if ($campus_f != 'All Campuses') $query .= " AND location = '$campus_f'";
if ($faculty_f != 'All Faculties') $query .= " AND faculty = '$faculty_f'";
if ($category_f != 'All Categories') $query .= " AND category = '$category_f'";
$result = mysqli_query($conn, $query . " ORDER BY facility_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Facilities - MMU Facility Booking System</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
    <style>
        .tt-slot.priority { background-color: #ffeb3b !important; color: #856404; font-weight: 600; border: 1px solid #fbc02d; }
    </style>
</head>
<body onload="checkAlerts()">

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo" style="height: 32px;">
                <div class="logo-divider"></div>
                <span style="font-size: 16px; font-weight: 600; color: var(--text-muted); white-space: nowrap;">Admin Panel</span>
            </div>
            <nav class="admin-nav">
                <a href="admin-dashboard.php" class="admin-nav-item"><span class="material-symbols-outlined">dashboard</span> Dashboard Overview</a>
                <a href="admin-bookings.php" class="admin-nav-item"><span class="material-symbols-outlined">calendar_month</span> Manage Bookings</a>
                <a href="admin-facilities.php" class="admin-nav-item active"><span class="material-symbols-outlined">meeting_room</span> Manage Facilities</a>
                <a href="admin-equipment.php" class="admin-nav-item"><span class="material-symbols-outlined">cable</span> Manage Equipment</a>
                <a href="admin-users.php" class="admin-nav-item"><span class="material-symbols-outlined">group</span> Manage Users & Quotas</a>
                <a href="admin-penalties.php" class="admin-nav-item"><span class="material-symbols-outlined">gavel</span> Penalty System</a>
                <a href="admin-reports.php" class="admin-nav-item"><span class="material-symbols-outlined">report</span> Issue Reports</a>
                <a href="admin-announcements.php" class="admin-nav-item"><span class="material-symbols-outlined">campaign</span> Announcements</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div><h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Facility Management</h2></div>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo strtoupper($initials); ?></div>
                    <span class="profile-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>
                    <div class="profile-menu" id="profileMenu">
                        <a href="admin-profile.php"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                        <div style="border-top: 1px solid rgba(194, 198, 211, 0.3); margin: 4px 0;"></div>
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <div class="admin-toolbar">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="facilitySearch" onkeyup="searchTable()" placeholder="Search facility name...">
                    </div>
                    
                    <form action="admin-facilities.php" method="GET" class="d-inline-flex flex-wrap align-items-center gap-2 m-0">
                        <select name="campus" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                            <option value="All Campuses" <?php if($campus_f == 'All Campuses') echo 'selected'; ?>>All Campuses</option>
                            <option value="MMU Cyberjaya" <?php if($campus_f == 'MMU Cyberjaya') echo 'selected'; ?>>Cyberjaya</option>
                            <option value="MMU Melaka" <?php if($campus_f == 'MMU Melaka') echo 'selected'; ?>>Melaka</option>
                        </select>
                        <select name="faculty" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                            <option value="All Faculties" <?php if($faculty_f == 'All Faculties') echo 'selected'; ?>>All Faculties</option>
                            <option value="FCI" <?php if($faculty_f == 'FCI') echo 'selected'; ?>>FCI</option><option value="FOE" <?php if($faculty_f == 'FOE') echo 'selected'; ?>>FOE</option>
                            <option value="FCM" <?php if($faculty_f == 'FCM') echo 'selected'; ?>>FCM</option><option value="FOM" <?php if($faculty_f == 'FOM') echo 'selected'; ?>>FOM</option>
                            <option value="FCA" <?php if($faculty_f == 'FCA') echo 'selected'; ?>>FCA</option><option value="FAC" <?php if($faculty_f == 'FAC') echo 'selected'; ?>>FAC</option>
                            <option value="FET" <?php if($faculty_f == 'FET') echo 'selected'; ?>>FET</option><option value="FIST" <?php if($faculty_f == 'FIST') echo 'selected'; ?>>FIST</option>
                            <option value="FOB" <?php if($faculty_f == 'FOB') echo 'selected'; ?>>FOB</option><option value="FOL" <?php if($faculty_f == 'FOL') echo 'selected'; ?>>FOL</option>
                            <option value="General" <?php if($faculty_f == 'General') echo 'selected'; ?>>General</option>
                        </select>
                        <select name="category" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                            <option value="All Categories" <?php if($category_f == 'All Categories') echo 'selected'; ?>>All Categories</option>
                            <option value="Lecture Hall" <?php if($category_f == 'Lecture Hall') echo 'selected'; ?>>Lecture Hall</option>
                            <option value="Laboratory" <?php if($category_f == 'Laboratory') echo 'selected'; ?>>Laboratory</option>
                            <option value="Tutorial" <?php if($category_f == 'Tutorial') echo 'selected'; ?>>Tutorial</option>
                            <option value="Sports" <?php if($category_f == 'Sports') echo 'selected'; ?>>Sports</option>
                        </select>
                        <button type="button" class="btn btn-primary" onclick="openAddModal()" style="padding: 8px 16px;"><span class="material-symbols-outlined" style="font-size: 18px;">add</span> Add Facility</button>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table" id="facilityTable">
                        <thead>
                            <tr>
                                <th>Facility Name</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th style="text-align: center;">Capacity</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr <?php if($row['status'] == 'Maintenance') echo 'style="background-color: #fafafa;"'; ?>>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if(!empty($row['image_path'])): ?>
                                            <img src="../public/img/facilities/<?php echo $row['image_path']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f1f3f5; border-radius: 6px; display: flex; align-items: center; justify-content: center;"><span class="material-symbols-outlined">image</span></div>
                                        <?php endif; ?>
                                        <strong style="color: var(--text-main);"><?php echo htmlspecialchars($row['facility_name']); ?></strong>
                                    </div>
                                </td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['location']); ?> (<?php echo htmlspecialchars($row['faculty']); ?>)</td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td style="text-align: center; font-weight: 600;"><?php echo htmlspecialchars($row['capacity']); ?></td>
                                <td>
                                    <?php if($row['status'] == 'Available'): ?>
                                        <span class="badge badge-approved"><span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-maintenance"><span class="material-symbols-outlined" style="font-size: 14px;">handyman</span> Maintenance</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <button class="btn-icon" title="Preview Timetable" onclick='openPreviewModal(<?php echo $row['facility_id']; ?>, "<?php echo addslashes($row['facility_name']); ?>")'><span class="material-symbols-outlined" style="font-size: 18px;">visibility</span></button>
                                        <button class="btn-icon schedule" title="Edit Schedule" onclick='openTimetableModal(<?php echo $row['facility_id']; ?>, "<?php echo addslashes($row['facility_name']); ?>")'><span class="material-symbols-outlined" style="font-size: 18px;">calendar_month</span></button>
                                        <button class="btn-icon edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'><span class="material-symbols-outlined" style="font-size: 18px;">edit</span></button>
                                        <?php if($row['status'] == 'Available'): ?>
                                            <button class="btn-icon penalty" title="Mark as Maintenance" onclick="window.location.href='admin-facilities.php?action=set_status&status=Maintenance&id=<?php echo urlencode($row['facility_id']); ?>'"><span class="material-symbols-outlined" style="font-size: 18px;">build</span></button>
                                        <?php else: ?>
                                            <button class="btn-icon edit" title="Mark as Available" onclick="window.location.href='admin-facilities.php?action=set_status&status=Available&id=<?php echo urlencode($row['facility_id']); ?>'"><span class="material-symbols-outlined" style="font-size: 18px; color: var(--success-text);">check_circle</span></button>
                                        <?php endif; ?>
                                        <button class="btn-icon delete" title="Delete Facility" onclick="if(confirm('Delete this facility?')) window.location.href='admin-facilities.php?action=delete&id=<?php echo urlencode($row['facility_id']); ?>'"><span class="material-symbols-outlined" style="font-size: 18px;">delete</span></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODALS & SCRIPT REMAINS THE SAME -->
    <div class="modal-overlay" id="facilityModal">
        <!-- Add/Edit form content -->
        <div class="modal-box" style="max-width: 500px;">
            <h3 id="modalTitle" style="margin-bottom: 20px;">Add New Facility</h3>
            <form action="admin-facilities.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="facility_id" id="form_fid">
                <div class="form-group"><label>Facility Name</label><input type="text" name="facility_name" id="form_name" class="form-control" required></div>
                <div class="form-group">
                    <label>Facility Image</label>
                    <div id="image_preview_area" style="margin-bottom: 10px; display: none;">
                        <img id="current_img_thumb" src="" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; cursor: zoom-in;">
                    </div>
                    <input type="file" name="facility_image" class="form-control" accept="image/*">
                </div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;"><label>Campus</label><select name="location" id="form_loc" class="form-control"><option>MMU Cyberjaya</option><option>MMU Melaka</option></select></div>
                    <div style="flex: 1;"><label>Faculty</label><select name="faculty" id="form_faculty" class="form-control"><option>FCI</option><option>FOE</option><option>FCM</option><option>FOM</option><option>General</option></select></div>
                </div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;"><label>Category</label><select name="category" id="form_cat" class="form-control"><option>Lecture Hall</option><option>Laboratory</option><option>Tutorial</option><option>Sports</option></select></div>
                    <div style="flex: 1;"><label>Capacity</label><input type="number" name="capacity" id="form_cap" class="form-control" required></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" id="form_desc" class="form-control" style="min-height: 60px;"></textarea></div>
                <div class="form-group"><label>Status</label><select name="status" id="form_status" class="form-control"><option value="Available">Available</option><option value="Maintenance">Maintenance</option></select></div>
                <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('facilityModal')">Cancel</button>
                    <button type="submit" id="submitBtn" name="add_facility" class="btn btn-primary">Save Facility</button>
                </div>
            </form>
        </div>
    </div>

    <div class="lightbox-overlay" id="imageLightbox" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center; cursor: zoom-out;">
        <img src="" id="lightboxImage" style="max-width: 90%; max-height: 90%; object-fit: contain;">
    </div>

    <div class="modal-overlay" id="timetableModal">
        <div class="modal-box modal-box-large" style="max-width: 800px;"> 
            <div style="background-color: var(--surface); padding: 16px 24px; border-bottom: 1px solid rgba(194, 198, 211, 0.4); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <h3 style="font-size: 18px; font-weight: 700;">Edit Fixed Schedule</h3>
                    <span class="badge" id="tt_facility_label" style="background: var(--surface-container-low); color: var(--primary);"></span>
                </div>
                <button type="button" class="btn-icon" onclick="closeModal('timetableModal')"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div style="padding: 24px;">
                <div class="timetable-header" style="background-color: var(--surface-container-low); padding: 12px 20px; border-bottom: 1px solid rgba(194, 198, 211, 0.4); display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="font-size: 14px; font-weight: 600;"><span class="material-symbols-outlined" style="font-size: 18px; color: var(--primary);">calendar_month</span> Schedule Editor</h4>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <button type="button" class="btn-icon" style="background: white; border: 1px solid var(--border-color); width: 28px; height: 28px;" onclick="changeWeek(-1)"><span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span></button>
                        <span id="week_label" style="font-size: 13px; font-weight: 600; min-width: 160px; text-align: center;"></span>
                        <button type="button" class="btn-icon" style="background: white; border: 1px solid var(--border-color); width: 28px; height: 28px;" onclick="changeWeek(1)"><span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span></button>
                    </div>
                </div>
                <div class="timetable-grid admin-edit" id="adminTimetable">
                    <div class="tt-cell tt-head">Time</div><div class="tt-cell tt-head">Mon</div><div class="tt-cell tt-head">Tue</div><div class="tt-cell tt-head">Wed</div><div class="tt-cell tt-head">Thu</div><div class="tt-cell tt-head">Fri</div>
                    <?php $hours = range(8, 18); $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']; foreach($hours as $h): $time_label = ($h > 12) ? ($h-12)." PM" : $h . ($h==12 ? " PM" : " AM"); ?>
                        <div class="tt-cell tt-time"><?php echo $time_label; ?></div>
                        <?php foreach($days as $d): ?><div class="tt-cell tt-slot free" data-day="<?php echo $d; ?>" data-start="<?php echo str_pad($h, 2, "0", STR_PAD_LEFT).":00:00"; ?>" data-end="<?php echo str_pad($h+1, 2, "0", STR_PAD_LEFT).":00:00"; ?>"></div><?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('timetableModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="openRecurrenceModal()">Save Schedule</button>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW: Read-only Timetable Preview Modal -->
    <div class="modal-overlay" id="previewTimetableModal">
        <div class="modal-box modal-box-large" style="max-width: 800px;"> 
            <div style="background-color: var(--surface); padding: 16px 24px; border-bottom: 1px solid rgba(194, 198, 211, 0.4); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <h3 style="font-size: 18px; font-weight: 700;">Preview Timetable</h3>
                    <span class="badge" id="preview_facility_label" style="background: var(--surface-container-low); color: var(--primary);"></span>
                </div>
                <button type="button" class="btn-icon" onclick="closeModal('previewTimetableModal')"><span class="material-symbols-outlined">close</span></button>
            </div>
            <div style="padding: 24px;">
                <div class="timetable-header" style="background-color: var(--surface-container-low); padding: 12px 20px; border-bottom: 1px solid rgba(194, 198, 211, 0.4); display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="font-size: 14px; font-weight: 600;"><span class="material-symbols-outlined" style="font-size: 18px; color: var(--primary);">calendar_month</span> Live Schedule</h4>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <button type="button" class="btn-icon" style="background: white; border: 1px solid var(--border-color); width: 28px; height: 28px;" onclick="changePreviewWeek(-1)"><span class="material-symbols-outlined" style="font-size: 18px;">chevron_left</span></button>
                        <span id="preview_week_label" style="font-size: 13px; font-weight: 600; min-width: 160px; text-align: center;"></span>
                        <button type="button" class="btn-icon" style="background: white; border: 1px solid var(--border-color); width: 28px; height: 28px;" onclick="changePreviewWeek(1)"><span class="material-symbols-outlined" style="font-size: 18px;">chevron_right</span></button>
                    </div>
                </div>

                <div class="timetable-grid" id="previewTimetable">
                    <div class="tt-cell tt-head">Time</div><div class="tt-cell tt-head">Mon</div><div class="tt-cell tt-head">Tue</div><div class="tt-cell tt-head">Wed</div><div class="tt-cell tt-head">Thu</div><div class="tt-cell tt-head">Fri</div>
                    <?php 
                    $preview_hours = range(8, 22);
                    $preview_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    foreach($preview_hours as $h): 
                        $preview_label = ($h >= 12) ? ($h==12 ? "12 PM" : ($h-12)." PM") : $h." AM";
                    ?>
                        <div class="tt-cell tt-time"><?php echo $preview_label; ?></div>
                        <?php foreach($preview_days as $d): ?>
                            <div class="tt-cell tt-slot free" data-pday="<?php echo $d; ?>" data-phour="<?php echo $h; ?>"></div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>

                <div class="tt-legend">
                    <div class="legend-item"><div class="legend-box"></div> Free</div>
                    <div class="legend-item"><div class="legend-box" style="background: #a4a2a2;"></div> Taken</div>
                    <div class="legend-item"><div class="legend-box" style="background: #ffeb3b;"></div> Priority</div>
                    <div class="legend-item"><div class="legend-box" style="background: #fee2e2; border-color: #991b1b;"></div> Fixed Class</div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('previewTimetableModal')">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="recurrenceModal">
        <div class="modal-box modal-info" style="max-width: 400px; padding: 24px;">
            <div class="modal-header" style="margin-bottom: 24px;"><div class="modal-icon" style="background: #e0e7ff; color: var(--primary);"><span class="material-symbols-outlined">event_repeat</span></div><h3 class="modal-title" style="font-size: 18px;">Apply Schedule</h3></div>
            <form action="admin-facilities.php" method="POST">
                <input type="hidden" name="facility_id" id="recur_fid"><input type="hidden" name="selected_slots" id="recur_slots"><input type="hidden" name="current_week_monday" id="recur_monday">
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Apply pattern for how many weeks?</p>
                <div style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;"><input type="radio" name="recurrence_weeks" value="1" checked> <span>This week only</span></label>
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;"><input type="radio" name="recurrence_weeks" value="7"> <span>Short Semester (7 Weeks)</span></label>
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;"><input type="radio" name="recurrence_weeks" value="14"> <span>Full Semester (14 Weeks)</span></label>
                </div>
                <div class="modal-actions"><button type="button" class="btn btn-outline" onclick="closeModal('recurrenceModal'); document.getElementById('timetableModal').classList.add('show');">Back</button><button type="submit" name="save_timetable" class="btn btn-primary">Confirm & Save</button></div>
            </form>
        </div>
    </div>

    <script>
        let weekOffset = 0; let selectedFacilityId = null;
        function updateWeekDisplay() { const now = new Date(); const day = now.getDay() || 7; const monday = new Date(now); monday.setDate(now.getDate() - (day - 1) + (weekOffset * 7)); const friday = new Date(monday); friday.setDate(monday.getDate() + 4); const options = { month: 'short', day: 'numeric' }; const label = monday.toLocaleDateString('en-US', options) + " - " + friday.toLocaleDateString('en-US', options) + ", " + friday.getFullYear(); document.getElementById('week_label').innerText = label; document.getElementById('recur_monday').value = monday.toISOString().split('T')[0]; }
        function changeWeek(dir) { weekOffset += dir; updateWeekDisplay(); }
        function openTimetableModal(fid, fname) { selectedFacilityId = fid; weekOffset = 0; updateWeekDisplay(); document.getElementById('tt_facility_label').innerText = fname; document.querySelectorAll('.tt-slot').forEach(s => { s.classList.remove('blocked'); s.classList.add('free'); }); document.getElementById('timetableModal').classList.add('show'); }
        document.querySelectorAll('#adminTimetable .tt-slot').forEach(cell => { cell.addEventListener('click', function() { this.classList.toggle('free'); this.classList.toggle('blocked'); }); });
        function openRecurrenceModal() { const slots = []; document.querySelectorAll('#adminTimetable .tt-slot.blocked').forEach(s => { slots.push({ day: s.dataset.day, start: s.dataset.start, end: s.dataset.end }); }); if (slots.length === 0) { alert("Select a slot."); return; } document.getElementById('recur_fid').value = selectedFacilityId; document.getElementById('recur_slots').value = JSON.stringify(slots); document.getElementById('timetableModal').classList.remove('show'); document.getElementById('recurrenceModal').classList.add('show'); }
        document.getElementById('current_img_thumb').addEventListener('click', function() { document.getElementById('lightboxImage').src = this.src; document.getElementById('imageLightbox').style.display = 'flex'; });
        document.getElementById('imageLightbox').addEventListener('click', function() { this.style.display = 'none'; });
        function openAddModal() { document.getElementById('modalTitle').innerText = "Add New Facility"; document.getElementById('submitBtn').name = "add_facility"; document.getElementById('form_fid').value = ""; document.getElementById('image_preview_area').style.display = 'none'; document.getElementById('facilityModal').classList.add('show'); }
        function openEditModal(data) { document.getElementById('modalTitle').innerText = "Edit Facility"; document.getElementById('submitBtn').name = "edit_facility"; document.getElementById('form_fid').value = data.facility_id; document.getElementById('form_name').value = data.facility_name; document.getElementById('form_loc').value = data.location; document.getElementById('form_faculty').value = data.faculty; document.getElementById('form_cat').value = data.category; document.getElementById('form_cap').value = data.capacity; document.getElementById('form_desc').value = data.description; document.getElementById('form_status').value = data.status; const preview = document.getElementById('image_preview_area'); if(data.image_path) { document.getElementById('current_img_thumb').src = "../public/img/facilities/" + data.image_path; preview.style.display = 'block'; } else { preview.style.display = 'none'; } document.getElementById('facilityModal').classList.add('show'); }
        function closeModal(id) { document.getElementById(id).classList.remove('show'); }
        const trigger = document.getElementById('profileTrigger'); const menu = document.getElementById('profileMenu'); trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); }); window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });
        function searchTable() { let input = document.getElementById("facilitySearch").value.toLowerCase(); let rows = document.getElementById("facilityTable").getElementsByTagName("tr"); for (let i = 1; i < rows.length; i++) { rows[i].style.display = rows[i].innerText.toLowerCase().includes(input) ? "" : "none"; } }
        function checkAlerts() { const params = new URLSearchParams(window.location.search); if (params.get('msg') === 'schedule_saved') alert("Timetable updated!"); if (params.get('msg') === 'deleted') alert("Facility removed."); if (params.get('msg') === 'updated') alert("Facility updated!"); if (params.get('msg') === 'status_updated') alert("Status changed."); window.history.replaceState({}, document.title, window.location.pathname); }

        // --- NEW: Preview Timetable logic ---
        let previewWeekOffset = 0;
        let previewFacilityId = null;
        let previewRequestToken = 0; // Guards against out-of-order fetch responses

        function openPreviewModal(fid, fname) {
            previewFacilityId = fid;
            previewWeekOffset = 0;
            document.getElementById('preview_facility_label').innerText = fname;
            document.getElementById('previewTimetableModal').classList.add('show');
            loadPreviewSchedule();
        }

        function changePreviewWeek(dir) {
            previewWeekOffset += dir;
            loadPreviewSchedule();
        }

        function loadPreviewSchedule() {
            // Reset grid to free before repainting
            document.querySelectorAll('#previewTimetable .tt-slot').forEach(cell => {
                cell.classList.remove('free', 'booked', 'priority', 'blocked');
                cell.classList.add('free');
            });

            // Tag this specific request so we can tell if a newer one supersedes it
            const thisRequestToken = ++previewRequestToken;
            const requestedWeek = previewWeekOffset;

            fetch(`admin-facilities.php?action=get_schedule&fid=${previewFacilityId}&week=${requestedWeek}`)
                .then(res => res.json())
                .then(data => {
                    // If the user has since navigated to a different week, discard this stale response
                    if (thisRequestToken !== previewRequestToken) return;

                    document.getElementById('preview_week_label').innerText = data.range;
                    const schedule = data.schedule;

                    document.querySelectorAll('#previewTimetable .tt-slot').forEach(cell => {
                        const day = cell.dataset.pday;
                        const hour = cell.dataset.phour;
                        if (schedule[day] && schedule[day][hour]) {
                            const type = schedule[day][hour];
                            cell.classList.remove('free');
                            if (type === 'fixed') cell.classList.add('blocked');
                            else if (type === 'priority') cell.classList.add('priority');
                            else cell.classList.add('booked');
                        }
                    });
                })
                .catch(err => console.error('Error loading preview schedule:', err));
        }
    </script>
</body>
</html>