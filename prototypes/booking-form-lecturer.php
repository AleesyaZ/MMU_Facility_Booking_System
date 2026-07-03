<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK: Only allow logged-in Lecturers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- FETCH NOTIFICATIONS ---
$unread_query = "SELECT COUNT(*) as total FROM notification WHERE user_id = '$user_id' AND is_read = 0";
$unread_res = mysqli_query($conn, $unread_query);
$unread_count = mysqli_fetch_assoc($unread_res)['total'];

$notif_list_query = "SELECT * FROM notification WHERE user_id = '$user_id' ORDER BY date_sent DESC LIMIT 5";
$notif_list_res = mysqli_query($conn, $notif_list_query);

// Handle AJAX to mark all as read
if (isset($_GET['mark_read'])) {
    mysqli_query($conn, "UPDATE notification SET is_read = 1 WHERE user_id = '$user_id'");
    exit();
}

// Helper for notification timestamps
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);           
    $hours   = round($seconds / 3600);         
    $days    = round($seconds / 86400);        
    if ($seconds <= 60) return "Just Now";
    else if ($minutes <= 60) return "$minutes mins ago";
    else if ($hours <= 24) return "$hours hours ago";
    else if ($days <= 7) return "$days days ago";
    else return date("d M Y", $time_ago);
}

// 2. GET FACILITY DETAILS from URL (?id=X)
if (!isset($_GET['id'])) {
    header("Location: facilities.php");
    exit();
}

$facility_id = mysqli_real_escape_string($conn, $_GET['id']);
$fac_query = "SELECT * FROM facility WHERE facility_id = '$facility_id' LIMIT 1";
$fac_result = mysqli_query($conn, $fac_query);
$facility = mysqli_fetch_assoc($fac_result);

if (!$facility) {
    die("Facility not found in database.");
}

// 3. GET USER DETAILS (For Navbar and Quota Notice)
$user_query = "SELECT name, booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1";
$user_data = mysqli_fetch_assoc(mysqli_query($conn, $user_query));
$full_name = $user_data['name'] ?? "Lecturer";
$max_quota = $user_data['booking_quota'] ?? 2;

// Get initials for the avatar
$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// --- UPDATED: 4. FETCH DYNAMIC EQUIPMENTS BASED ON CAMPUS ---
$facility_location = $facility['location'];
$target_campus = "";

// Detect campus from facility location string
if (stripos($facility_location, 'Cyberjaya') !== false) {
    $target_campus = 'Cyberjaya';
} elseif (stripos($facility_location, 'Melaka') !== false) {
    $target_campus = 'Melaka';
}

$safe_cat = mysqli_real_escape_string($conn, $facility['category']);
$safe_campus = mysqli_real_escape_string($conn, $target_campus);

// Filter equipment by status, category, AND the detected campus
$equip_query = "SELECT * FROM equipment 
                WHERE status = 'Available' 
                AND (category = '$safe_cat' OR category = 'General') 
                AND campus = '$safe_campus'
                ORDER BY name ASC";
$equip_result = mysqli_query($conn, $equip_query);

// --- 5. FETCH EXISTING BOOKINGS (For Lecturers to see what to override) ---
$occupied_query = "SELECT booking_date, start_time, end_time, is_priority FROM booking 
                   WHERE facility_id = '$facility_id' 
                   AND status IN ('Approved', 'Pending') 
                   AND booking_date >= CURDATE()
                   ORDER BY booking_date ASC, start_time ASC";
$occupied_result = mysqli_query($conn, $occupied_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Lecturer Booking - MMU Campus Space</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body>

    <header class="navbar">
        <div class="container nav-container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="student-dashboard.php" class="nav-logo" style="display: flex; align-items: center; flex-shrink: 0;">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo">
                <div class="logo-divider"></div>
                <span class="system-name">Facility Booking</span>
            </a>
            
            <nav class="nav-links" style="display: flex; align-items: center; gap: 20px;">
                <a href="student-dashboard.php">Dashboard</a>
                <a href="facilities.php" class="active">Browse Facilities</a>
                <a href="my-bookings.php">My Bookings</a>
                <a href="report-issue.php">Report Issue</a>
            </nav>
            
            <div class="nav-profile" id="profileTrigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; position: relative; max-width: 300px; flex-shrink: 0;">

                <!-- NOTIFICATION BELL (functional) -->
                <div id="notifTrigger" style="position: relative; display: flex; align-items: center; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="color: var(--text-muted); flex-shrink: 0;">notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span id="notifBadge" style="position: absolute; top: -4px; right: -4px; background: var(--secondary); color: white; font-size: 9px; font-weight: 700; min-width: 15px; height: 15px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>

                    <div class="profile-menu" id="notifMenu" style="width: 320px; right: -60px; padding: 0; top: 36px;">
                        <div style="padding: 16px; border-bottom: 1px solid var(--border-color);">
                            <span style="font-weight: 700; font-size: 14px;">Recent Notifications</span>
                        </div>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php if (mysqli_num_rows($notif_list_res) > 0): ?>
                                <?php while ($n = mysqli_fetch_assoc($notif_list_res)): ?>
                                    <div style="padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.05); <?php echo ($n['is_read'] == 0) ? 'background: #f0f7ff;' : ''; ?>">
                                        <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 2px;"><?php echo htmlspecialchars($n['title']); ?></p>
                                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;"><?php echo htmlspecialchars($n['message']); ?></p>
                                        <span style="font-size: 10px; color: var(--text-muted); margin-top: 4px; display: block;"><?php echo time_ago($n['date_sent']); ?></span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">No notifications.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="avatar" style="flex-shrink: 0;"><?php echo strtoupper($initials); ?></div>
                
                <span style="font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; text-transform: uppercase;" title="<?php echo htmlspecialchars($full_name); ?>">
                    <?php echo htmlspecialchars($full_name); ?>
                </span>
                
                <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted); flex-shrink: 0;">expand_more</span>

                <div class="profile-menu" id="profileMenu">
                    <a href="profile.php"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                    <a href="my-reports.php"><span class="material-symbols-outlined">report</span> My Reports</a>
                    <a href="guidelines.php"><span class="material-symbols-outlined">help</span> Guidelines</a>
                    <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="container booking-layout">
        
        <div class="card" style="margin-bottom: 0;">
            <div class="dashboard-header" style="margin-bottom: 24px;">
                <h2>Reservation Details</h2>
                <p>Please fill in the date, time, and required equipment for your session.</p>
            </div>

            <form action="../PHP/lecturer_booking_process.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">

                <div class="form-group">
                    <label>Select Date</label>
                    <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    
                    <div style="margin-top: 12px; padding: 12px; background: #fdf2f2; border-radius: 8px; border-left: 4px solid var(--secondary);">
                        <span style="font-size: 12px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 5px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event_busy</span> CURRENT SCHEDULE (Occupied):
                        </span>
                        <ul style="font-size: 11px; color: var(--text-muted); list-style: none; padding-left: 0;">
                        <?php if(mysqli_num_rows($occupied_result) > 0): ?>
                            <?php while($occ = mysqli_fetch_assoc($occupied_result)): ?>
                                <li style="margin-bottom: 5px; padding: 4px; border-radius: 4px; <?php echo $occ['is_priority'] ? 'background: #ffebee;' : ''; ?>">
                                    • <?php echo date("d M", strtotime($occ['booking_date'])); ?>: 
                                    <?php echo date("g:i A", strtotime($occ['start_time'])); ?> - <?php echo date("g:i A", strtotime($occ['end_time'])); ?>
                                    
                                    <?php if($occ['is_priority']): ?>
                                        <strong style="color:var(--secondary);">[LOCKED: Priority]</strong>
                                    <?php else: ?>
                                        <span style="color: #666;">(Standard - Overridable)</span>
                                    <?php endif; ?>
                                </li>
                            <?php endwhile; ?>
                            <li style="margin-top: 8px; color: #856404; font-style: italic;">
                                Note: You can only book over "Standard" slots using Priority Override. "Locked" slots cannot be overridden.
                            </li>
                        <?php else: ?>
                            <li>No existing bookings. All slots available!</li>
                        <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="priority-box">
                    <input type="checkbox" id="priority" name="priority">
                    <div class="priority-text">
                        <label for="priority" style="font-size: 15px; font-weight: 600; color: #856404; margin-bottom: 4px;">Request Priority Override</label>
                        <p>Check this for academic purposes (Replacement classes/exams). Standard quota will be bypassed upon Admin approval.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purpose of Booking & Subject Code</label>
                    <textarea name="purpose" class="form-control" placeholder="Please state Subject Code and reason (e.g. CPT4214 Lab Session)..." required></textarea>
                    
                    <label class="upload-zone" id="dropZone" for="proofUpload">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span class="material-symbols-outlined upload-icon">cloud_upload</span>
                            <span class="upload-text">Click to upload proof</span>
                        </div>
                        <span class="upload-hint">Upload Faculty Memo or Timetable (PDF/JPG, Max 5MB)</span>
                        <input type="file" id="proofUpload" name="proofUpload" accept=".pdf,.jpg,.jpeg,.png">
                        
                        <div id="fileNameDisplay" style="margin-top: 16px; font-size: 13px; font-weight: 600; color: var(--primary); display: none;"></div>
                    </label>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 32px 0;">

                <div class="dashboard-header" style="margin-bottom: 16px;">
                    <h2 style="font-size: 20px;">Add-On Equipment (Available in <?php echo $target_campus; ?>)</h2>
                </div>

                <div class="equipment-list">
                    <?php if(mysqli_num_rows($equip_result) > 0): ?>
                        <?php while($equip = mysqli_fetch_assoc($equip_result)): ?>
                            <div class="equipment-option">
                                <div class="equipment-left">
                                    <input type="checkbox" name="equipment[]" value="<?php echo $equip['equip_id']; ?>" id="eq<?php echo $equip['equip_id']; ?>">
                                    <label for="eq<?php echo $equip['equip_id']; ?>"><?php echo htmlspecialchars($equip['name']); ?></label>
                                </div>
                                <input type="number" name="qty_<?php echo $equip['equip_id']; ?>" class="equipment-qty" min="1" max="<?php echo $equip['avail_qty']; ?>" value="1">
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 14px; color: var(--text-muted);">No equipment available for this campus/category.</p>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 40px;">
                    <a href="facilities.php" class="btn btn-outline" style="flex: 1; justify-content: center;">Cancel</a>
                    <button type="submit" name="confirm_booking" class="btn btn-primary" style="flex: 2; justify-content: center;">Confirm Booking</button>
                </div>
            </form>
        </div>

        <div>
            <div class="card" style="position: sticky; top: 100px;">
                <h3 class="card-title">Booking Summary</h3>
                
                <img src="../public/img/facilities/<?php echo !empty($facility['image_path']) ? $facility['image_path'] : 'default.jpg'; ?>" 
                     alt="Facility" class="summary-img" onerror="this.src='../public/img/mmulogo.jpg'">
                
                <div class="summary-details">
                    <h3><?php echo htmlspecialchars($facility['facility_name']); ?></h3>
                    <div class="summary-item">
                        <span class="material-symbols-outlined">location_on</span>
                        <span><?php echo htmlspecialchars($facility['location']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="material-symbols-outlined">group</span>
                        <span>Maximum Capacity: <?php echo $facility['capacity']; ?> people</span>
                    </div>
                </div>

                <div class="quota-alert">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                    <div>
                        <strong>Staff Quota</strong><br>
                        Standard bookings consume 1 of your <?php echo $max_quota; ?> weekly quotas. Priority requests require Admin approval.
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        trigger.addEventListener('click', function(e) { 
            e.stopPropagation(); 
            notifMenu.classList.remove('show');
            menu.classList.toggle('show'); 
        });

        notifTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.remove('show');
            notifMenu.classList.toggle('show');

            if (notifMenu.classList.contains('show')) {
                fetch('booking-form-lecturer.php?mark_read=1');
                const badge = document.getElementById('notifBadge');
                if (badge) badge.style.display = 'none';
            }
        });

        window.addEventListener('click', function() { 
            menu.classList.remove('show');
            notifMenu.classList.remove('show');
        });

        const fileInput = document.getElementById('proofUpload');
        const fileNameDisplay = document.getElementById('fileNameDisplay');
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.innerText = "Selected: " + this.files[0].name;
                fileNameDisplay.style.display = "block";
            } else {
                fileNameDisplay.style.display = "none";
            }
        });
    </script>

</body>
</html>