<?php
session_start();
include('../PHP/db_config.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// GET FACILITY DETAILS
if (!isset($_GET['id'])) {
    header("Location: facilities.php");
    exit();
}

$facility_id = mysqli_real_escape_string($conn, $_GET['id']);
$fac_query = "SELECT * FROM facility WHERE facility_id = '$facility_id' LIMIT 1";
$fac_result = mysqli_query($conn, $fac_query);
$facility = mysqli_fetch_assoc($fac_result);

if (!$facility) { die("Facility not found."); }

// GET USER DETAILS
$user_id = $_SESSION['user_id'];
$user_query = "SELECT name, booking_quota FROM user WHERE user_id = '$user_id'";
$user_data = mysqli_fetch_assoc(mysqli_query($conn, $user_query));
$full_name = $user_data['name'];
$max_quota = $user_data['booking_quota'];

$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// WEEK CALCULATION FOR TIMETABLE 
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

$sunday = clone $monday;
$sunday->modify("+6 days");

$start_date = $monday->format('Y-m-d');
$end_date = $sunday->format('Y-m-d');

// FETCH GRID DATA 
// Fixed Classes
$tt_query = "SELECT day_of_week, start_time, end_time FROM timetable 
             WHERE facility_id = '$facility_id' 
             AND expiry_date >= '$start_date' 
             AND (start_date IS NULL OR start_date <= '$end_date')";
$tt_res = mysqli_query($conn, $tt_query);

// User Bookings (Normal + Priority)
$bk_query = "SELECT booking_date, start_time, end_time, is_priority, DAYNAME(booking_date) as day_name 
             FROM booking 
             WHERE facility_id = '$facility_id' 
             AND status IN ('Approved', 'Pending') 
             AND booking_date BETWEEN '$start_date' AND '$end_date'";
$bk_res = mysqli_query($conn, $bk_query);

$schedule_map = [];

// Logic Fill all hour slots covered by the time range
while ($row = mysqli_fetch_assoc($tt_res)) {
    $start_h = (int)substr($row['start_time'], 0, 2);
    $end_h = (int)substr($row['end_time'], 0, 2);
    $end_m = (int)substr($row['end_time'], 3, 2);
    
    // If minutes > 0 (e.g., 9:14), include the following hour box (10 PM)
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

// NOTIFICATIONS
$unread_query = "SELECT COUNT(*) as total FROM notification WHERE user_id = '$user_id' AND is_read = 0";
$unread_res = mysqli_query($conn, $unread_query);
$unread_count = mysqli_fetch_assoc($unread_res)['total'];

$notif_list_query = "SELECT * FROM notification WHERE user_id = '$user_id' ORDER BY date_sent DESC LIMIT 5";
$notif_list_res = mysqli_query($conn, $notif_list_query);

if (isset($_GET['mark_read'])) {
    mysqli_query($conn, "UPDATE notification SET is_read = 1 WHERE user_id = '$user_id'");
    exit();
}

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

// Determine where a notification should link to, based on its title
function get_notification_link($title) {
    $title_lower = strtolower($title);

    if (strpos($title_lower, 'cancel') !== false) {
        return 'my-bookings.php';
    } elseif (strpos($title_lower, 'Update on Report') !== false || strpos($title_lower, 'Update on Report') !== false || strpos($title_lower, 'report') !== false) {
        return 'my-reports.php';
    } elseif (strpos($title_lower, 'Booking Confirmed') !== false || strpos($title_lower, 'Confirmed') !== false || strpos($title_lower, 'booking') !== false) {
        return 'my-bookings.php';
    } elseif (strpos($title_lower, 'Booking Overridden By Admin') !== false || strpos($title_lower, 'Overridden') !== false || strpos($title_lower, 'booking') !== false) {
        return 'my-bookings.php';
    } elseif (strpos($title_lower, 'Admin Cancellation') !== false || strpos($title_lower, 'Admin') !== false || strpos($title_lower, 'Cancellation') !== false) {
        return 'my-bookings.php';
    } else {
        return 'user-dashboard.php'; // fallback default
    }
}

// EQUIPMENT 
$facility_location = $facility['location'];
$target_campus = (stripos($facility_location, 'Cyberjaya') !== false) ? 'Cyberjaya' : 'Melaka';
$safe_cat = mysqli_real_escape_string($conn, $facility['category']);
$safe_campus = mysqli_real_escape_string($conn, $target_campus);

$equip_query = "SELECT * FROM equipment 
                WHERE status = 'Available' 
                AND (category = '$safe_cat' OR category = 'General') 
                AND campus = '$safe_campus'
                ORDER BY name ASC";
$equip_result = mysqli_query($conn, $equip_query);

$occupied_result = mysqli_query($conn, "SELECT booking_date, start_time, end_time FROM booking 
                   WHERE facility_id = '$facility_id' 
                   AND status IN ('Approved', 'Pending') 
                   AND booking_date >= CURDATE()
                   ORDER BY booking_date ASC, start_time ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Book <?php echo htmlspecialchars($facility['facility_name']); ?> - MMU Facility Booking System</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
    <style>
        .tt-slot.priority { background-color: #ffeb3b !important; color: #856404; font-weight: 600; border: 1px solid #fbc02d; }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container nav-container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="user-dashboard.php" class="nav-logo" style="display: flex; align-items: center; flex-shrink: 0;">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo">
                <div class="logo-divider"></div>
                <span class="system-name">Facility Booking</span>
            </a>
            
            <nav class="nav-links" style="display: flex; align-items: center; gap: 20px;">
                <a href="user-dashboard.php">Dashboard</a>
                <a href="facilities.php" class="active">Browse Facilities</a>
                <a href="my-bookings.php">My Bookings</a>
                <a href="report-issue.php">Report Issue</a>
            </nav>
            
            <div class="nav-profile" id="profileTrigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; position: relative; max-width: 300px; flex-shrink: 0;">
                <div id="notifTrigger" style="position: relative; display: flex; align-items: center; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="color: var(--text-muted); flex-shrink: 0;">notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <span id="notifBadge" style="position: absolute; top: -4px; right: -4px; background: var(--secondary); color: white; font-size: 9px; font-weight: 700; min-width: 15px; height: 15px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                            <?php echo $unread_count; ?>
                        </span>
                    <?php endif; ?>

                    <div class="profile-menu" id="notifMenu" style="width: 320px; right: -60px; padding: 0; top: 36px;">
                        <div style="padding: 16px; border-bottom: 1px solid var(--border-color);"><span style="font-weight: 700; font-size: 14px;">Recent Notifications</span></div>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php if (mysqli_num_rows($notif_list_res) > 0): ?>
                                <?php while ($n = mysqli_fetch_assoc($notif_list_res)): ?>
                                    <a href="<?php echo get_notification_link($n['title']); ?>" style="text-decoration: none; color: inherit; display: block;">
                                         <div style="padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.05); cursor: pointer; transition: background 0.15s; <?php echo ($n['is_read'] == 0) ? 'background: #f0f7ff;' : ''; ?>" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='<?php echo ($n['is_read'] == 0) ? '#f0f7ff' : 'transparent'; ?>'">
                                             <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 2px;"><?php echo htmlspecialchars($n['title']); ?></p>
                                             <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;"><?php echo htmlspecialchars($n['message']); ?></p>
                                             <span style="font-size: 10px; color: var(--text-muted); margin-top: 4px; display: block;"><?php echo time_ago($n['date_sent']); ?></span>
                                        </div>
                                     </a>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div style="padding: 30px; text-align: center; color: var(--text-muted); font-size: 13px;">No notifications.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="avatar" style="flex-shrink: 0;"><?php echo strtoupper($initials); ?></div>
                <span style="font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; text-transform: uppercase;" title="<?php echo htmlspecialchars($full_name); ?>"><?php echo htmlspecialchars($full_name); ?></span>
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

            <form action="../PHP/booking_process.php" method="POST">
                <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">
                <div class="form-group">
                    <label>Select Date</label>
                    <input type="date" name="booking_date" class="form-control" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
                    <div style="margin-top: 12px; padding: 12px; background: #f0f2f5; border-radius: 8px; border-left: 4px solid var(--secondary);">
                        <span style="font-size: 12px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 5px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event_busy</span> OCCUPIED SLOTS:
                        </span>
                        <ul style="font-size: 11px; color: var(--text-muted); list-style: none; padding-left: 0;">
                            <?php if(mysqli_num_rows($occupied_result) > 0): while($occ = mysqli_fetch_assoc($occupied_result)): ?>
                                    <li>• <?php echo date("d M", strtotime($occ['booking_date'])); ?>: <?php echo date("g:i A", strtotime($occ['start_time'])); ?> - <?php echo date("g:i A", strtotime($occ['end_time'])); ?></li>
                            <?php endwhile; else: ?>
                                <li>No existing bookings. All slots available!</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" required min="08:00" max="21:00">
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" required min="09:00" max="22:00">
                    </div>
                </div>

                <div class="form-group"><label>Purpose of Booking</label><textarea name="purpose" class="form-control" placeholder="e.g., Group Assignment Meeting, Club Activity..." required></textarea></div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 32px 0;">
                <div class="dashboard-header" style="margin-bottom: 16px;"><h2 style="font-size: 20px;">Add-On Equipment (Available in <?php echo $target_campus; ?>)</h2></div>

                <div class="equipment-list">
                    <?php if(mysqli_num_rows($equip_result) > 0): while($equip = mysqli_fetch_assoc($equip_result)): ?>
                        <div class="equipment-option">
                            <div class="equipment-left">
                                <input type="checkbox" name="equipment[]" value="<?php echo $equip['equip_id']; ?>" id="eq<?php echo $equip['equip_id']; ?>">
                                <label for="eq<?php echo $equip['equip_id']; ?>"><?php echo htmlspecialchars($equip['name']); ?></label>
                            </div>
                            <input type="number" name="qty_<?php echo $equip['equip_id']; ?>" class="equipment-qty" min="1" max="<?php echo $equip['avail_qty']; ?>" value="1">
                        </div>
                    <?php endwhile; else: ?>
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
                <img src="../public/img/facilities/<?php echo !empty($facility['image_path']) ? $facility['image_path'] : 'default.jpg'; ?>" alt="Facility" class="summary-img" onerror="this.src='../public/img/mmulogo.jpg'">
                <div class="summary-details">
                    <h3><?php echo htmlspecialchars($facility['facility_name']); ?></h3>
                    <div class="summary-item"><span class="material-symbols-outlined">location_on</span><span><?php echo htmlspecialchars($facility['location']); ?></span></div>
                    <div class="summary-item"><span class="material-symbols-outlined">group</span><span>Maximum Capacity: <?php echo $facility['capacity']; ?> people</span></div>
                </div>
                
                <div class="quota-alert">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                    <div><strong>Quota Notice</strong><br>This reservation will consume 1 of your <?php echo $max_quota; ?> facility quotas.</div>
                </div>

                <!-- ADDED: ID "timetableContainer" -->
                <div class="timetable-container" id="timetableContainer">
                    <div class="timetable-header">
                        <h4><span class="material-symbols-outlined" style="font-size: 18px; color: var(--primary);">calendar_month</span> Weekly Schedule</h4>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <button type="button" class="btn-icon" onclick="changeWeek(-1)" style="width: 24px; height: 24px;"><span class="material-symbols-outlined" style="font-size: 16px;">chevron_left</span></button>
                            <span style="font-size: 11px; font-weight: 700; color: var(--primary);"><?php echo $monday->format('d M') . ' - ' . $sunday->format('d M'); ?></span>
                            <button type="button" class="btn-icon" onclick="changeWeek(1)" style="width: 24px; height: 24px;"><span class="material-symbols-outlined" style="font-size: 16px;">chevron_right</span></button>
                        </div>
                    </div>

                    <div style="overflow-x: auto; width: 460px; max-width: 100%;">
                        <div class="timetable-grid" style="width: 760px; grid-template-columns: 60px repeat(7, 100px);">
                            <div class="tt-cell tt-head">Time</div><div class="tt-cell tt-head">Mon</div><div class="tt-cell tt-head">Tue</div><div class="tt-cell tt-head">Wed</div><div class="tt-cell tt-head">Thu</div><div class="tt-cell tt-head">Fri</div><div class="tt-cell tt-head">Sat</div><div class="tt-cell tt-head">Sun</div>

                            <?php 
                            $hours = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22];
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            foreach($hours as $h): 
                                $label = ($h >= 12) ? ($h==12 ? "12 PM" : ($h-12)." PM") : $h." AM";
                            ?>
                            <div class="tt-cell tt-time"><?php echo $label; ?></div>
                            <?php foreach($days as $d): 
                                $class = "free";
                                if(isset($schedule_map[$d][$h])) {
                                    if($schedule_map[$d][$h] == 'fixed') $class = "blocked";
                                    elseif($schedule_map[$d][$h] == 'priority') $class = "priority";
                                    else $class = "booked";
                                }
                            ?>
                            <div class="tt-cell tt-slot <?php echo $class; ?>"></div>
                            <?php endforeach; endforeach; ?>
                        </div>
                    </div>

                    <div class="tt-legend">
                        <div class="legend-item"><div class="legend-box"></div> Free</div>
                        <div class="legend-item"><div class="legend-box" style="background: #a4a2a2;"></div> Taken</div>
                        <div class="legend-item"><div class="legend-box" style="background: #ffeb3b;"></div> Priority</div>
                        <div class="legend-item"><div class="legend-box" style="background: #fee2e2; border-color: #991b1b;"></div> Fixed Class</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Fetches the target page in the background and replaces the timetable element
        function changeWeek(offset) {
            const urlParams = new URLSearchParams(window.location.search);
            let currentWeek = parseInt(urlParams.get('week') || 0);
            urlParams.set('week', currentWeek + offset);
            
            const newUrl = window.location.pathname + '?' + urlParams.toString();
            
            // Updates URL bar without triggering a hard refresh or reset
            window.history.pushState({ path: newUrl }, '', newUrl);
            
            // Request the same page in the background
            fetch(newUrl)
                .then(response => response.text())
                .then(html => {
                    // Parse text response 
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Replace the inner content of our designated container
                    const newTimetable = doc.getElementById('timetableContainer');
                    const oldTimetable = document.getElementById('timetableContainer');
                    if (newTimetable && oldTimetable) {
                        oldTimetable.innerHTML = newTimetable.innerHTML;
                    }
                })
                .catch(err => console.error('Error asynchronously pagination loading schedule:', err));
        }

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
                fetch('booking-form.php?mark_read=1');
                const badge = document.getElementById('notifBadge');
                if (badge) badge.style.display = 'none';
            }
        });

        window.addEventListener('click', function() { 
            menu.classList.remove('show');
            notifMenu.classList.remove('show');
        });
    </script>
</body>
</html>