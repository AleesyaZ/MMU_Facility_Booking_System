<?php
session_start();
include('../PHP/db_config.php');

// 1. LOGIN & ROLE LOGIC
$is_logged_in = isset($_SESSION['user_id']);
$booking_page = "login.html"; 
$has_quota = true; 
$is_suspended = false; 
$days_left = 0; // New variable for countdown
$used_quota = 0;
$max_quota = 0;
$unread_count = 0;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'Lecturer') {
        $booking_page = "booking-form-lecturer.php";
    } else {
        $booking_page = "booking-form.php";
    }

    // --- FETCH NOTIFICATIONS ---
    $unread_query = "SELECT COUNT(*) as total FROM notification WHERE user_id = '$user_id' AND is_read = 0";
    $unread_res = mysqli_query($conn, $unread_query);
    $unread_count = mysqli_fetch_assoc($unread_res)['total'];

    $notif_list_query = "SELECT * FROM notification WHERE user_id = '$user_id' ORDER BY date_sent DESC LIMIT 5";
    $notif_list_res = mysqli_query($conn, $notif_list_query);

    if (isset($_GET['mark_read'])) {
        mysqli_query($conn, "UPDATE notification SET is_read = 1 WHERE user_id = '$user_id'");
        exit();
    }

    // UPDATED QUERY: Fetching suspension_start to calculate remaining days
    $user_result = mysqli_query($conn, "SELECT name, booking_quota, status, suspension_start FROM user WHERE user_id = '$user_id' LIMIT 1");
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";
    $max_quota = $user_data['booking_quota'] ?? 0;
    
    // Check if the user is suspended and calculate days
    if (isset($user_data['status']) && $user_data['status'] === 'Suspended') {
        $is_suspended = true;
        
        if (!empty($user_data['suspension_start'])) {
            $start = new DateTime($user_data['suspension_start']);
            $now = new DateTime();
            $diff = $start->diff($now)->days;
            $days_left = 30 - $diff;
            if ($days_left < 0) $days_left = 0;
        }
    }
    
    $name_parts = explode(' ', trim($full_name));
    $initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

    $quota_query = "SELECT COUNT(*) as total FROM booking 
                    WHERE user_id = '$user_id' 
                    AND status NOT IN ('Cancelled', 'Rejected') 
                    AND is_priority = 0 
                    AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
    $quota_res = mysqli_query($conn, $quota_query);
    $usage_data = mysqli_fetch_assoc($quota_res);
    $used_quota = $usage_data['total'];

    if ($role === 'Student' && $used_quota >= $max_quota) {
        $has_quota = false;
    } else {
        $has_quota = true; 
    }
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

// Determine where a notification should link to
function get_notification_link($title) {
    $title_lower = strtolower($title);
    if (strpos($title_lower, 'cancel') !== false) return 'my-bookings.php';
    elseif (strpos($title_lower, 'report') !== false) return 'my-reports.php';
    elseif (strpos($title_lower, 'booking') !== false) return 'my-bookings.php';
    else return 'student-dashboard.php';
}

$cat_list_result = mysqli_query($conn, "SELECT DISTINCT category FROM facility ORDER BY category ASC");
$fac_list_result = mysqli_query($conn, "SELECT DISTINCT faculty FROM facility WHERE faculty IS NOT NULL ORDER BY faculty ASC");

$where_clauses = [];
if (isset($_GET['campus']) && $_GET['campus'] !== 'all') {
    $campus = mysqli_real_escape_string($conn, $_GET['campus']);
    $where_clauses[] = "location LIKE '%$campus%'";
}
if (isset($_GET['faculty']) && $_GET['faculty'] !== 'all') {
    $faculty_val = mysqli_real_escape_string($conn, $_GET['faculty']);
    $where_clauses[] = "faculty = '$faculty_val'";
}
if (isset($_GET['category']) && $_GET['category'] !== 'all') {
    $cat = mysqli_real_escape_string($conn, $_GET['category']);
    $where_clauses[] = "category = '$cat'";
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "facility_name LIKE '%$search%'";
}

$base_query = "SELECT * FROM facility";
if (count($where_clauses) > 0) {
    $base_query .= " WHERE " . implode(' AND ', $where_clauses);
}
$facility_result = mysqli_query($conn, $base_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Browse Facilities - MMU Facility Booking System</title>
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
                <?php if ($is_logged_in): ?>
                    <a href="student-dashboard.php">Dashboard</a>
                    <a href="facilities.php" class="active">Browse Facilities</a>
                    <a href="my-bookings.php">My Bookings</a>
                    <a href="report-issue.php">Report Issue</a>
                <?php else: ?>
                    <a href="index.php">Home</a>
                    <a href="facilities.php" class="active">Browse Facilities</a>
                    <a href="guidelines.php">Guidelines</a>
                <?php endif; ?>
            </nav>
            
            <?php if ($is_logged_in): ?>
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
                                            <div style="padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.05); cursor: pointer; transition: background 0.15s; <?php echo ($n['is_read'] == 0) ? 'background: #f0f7ff;' : ''; ?>">
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
                    <span style="font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; text-transform: uppercase;">
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
            <?php else: ?>
                <div class="nav-auth" style="flex-shrink: 0;">
                    <a href="login.html" class="btn btn-outline">Sign In</a>
                    <a href="activate.html" class="btn btn-primary" style="margin-left: 8px;">Activate Account</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-header">
        <h1>Browse Campus Facilities</h1>
        <p>Find and book available lecture halls, discussion rooms, labs, and sports courts across MMU campuses.</p>
    </div>

    <main class="container">
        <form class="filter-bar" action="facilities.php" method="GET" style="flex-wrap: nowrap; gap: 12px;">
            <div class="filter-group" style="min-width: 160px;"><label>Campus Location</label>
                <select name="campus"><option value="all">All Campuses</option><option value="Cyberjaya" <?php if(isset($_GET['campus']) && $_GET['campus'] == 'Cyberjaya') echo 'selected'; ?>>Cyberjaya</option><option value="Melaka" <?php if(isset($_GET['campus']) && $_GET['campus'] == 'Melaka') echo 'selected'; ?>>Melaka</option></select>
            </div>
            <div class="filter-group" style="min-width: 160px;"><label>Faculty Type</label>
                <select name="faculty"><option value="all">All Faculties</option><?php mysqli_data_seek($fac_list_result, 0); while ($fac_row = mysqli_fetch_assoc($fac_list_result)): ?><option value="<?php echo $fac_row['faculty']; ?>" <?php echo (isset($_GET['faculty']) && $_GET['faculty'] == $fac_row['faculty']) ? 'selected' : ''; ?>><?php echo $fac_row['faculty']; ?></option><?php endwhile; ?></select>
            </div>
            <div class="filter-group" style="min-width: 160px;"><label>Facility Type</label>
                <select name="category"><option value="all">All Categories</option><?php mysqli_data_seek($cat_list_result, 0); while ($cat_row = mysqli_fetch_assoc($cat_list_result)): ?><option value="<?php echo $cat_row['category']; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat_row['category']) ? 'selected' : ''; ?>><?php echo $cat_row['category']; ?></option><?php endwhile; ?></select>
            </div>
            <div class="filter-group" style="min-width: 160px;"><label>Search by Name</label><input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"></div>
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; height: 42px; flex-shrink: 0;"><span class="material-symbols-outlined" style="font-size: 18px;">search</span> Filter</button>
        </form>

        <div class="facility-grid">
            <?php if (mysqli_num_rows($facility_result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($facility_result)): 
                    $img_url = "../public/img/facilities/" . (!empty($row['image_path']) ? $row['image_path'] : "default.jpg");
                    $is_available = (strtolower($row['status']) == 'available');
                ?>
                    <div class="facility-card">
                        <img src="<?php echo $img_url; ?>" class="facility-img" onerror="this.src='../public/img/mmulogo.jpg'">
                        <div class="facility-info">
                            <h3><?php echo htmlspecialchars($row['facility_name']); ?></h3>
                            <div class="facility-meta">
                                <span class="meta-item"><span class="material-symbols-outlined">location_on</span> <?php echo htmlspecialchars($row['location']); ?></span>
                                <span class="meta-item"><span class="material-symbols-outlined">group</span> Max <?php echo $row['capacity']; ?></span>
                            </div>
                            <p style="font-size: 12px; color: var(--primary); font-weight: 600; margin-bottom: 8px;">Faculty: <?php echo htmlspecialchars($row['faculty']); ?></p>
                            <p class="facility-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                            
                            <?php if ($is_available): ?>
                                <!-- MODIFIED BUTTON: Displaying remaining days if suspended -->
                                <?php if ($is_suspended): ?>
                                    <button class="btn btn-disabled" style="background-color: #ffebee; color: #bb0013; cursor: not-allowed; width: 100%; justify-content: center;" title="Your account has been suspended" disabled>
                                        Suspended (<?php echo $days_left; ?> Days Left)
                                    </button>
                                <?php elseif ($has_quota): ?>
                                    <a href="<?php echo $booking_page; ?>?id=<?php echo $row['facility_id']; ?>" class="btn btn-primary" style="justify-content: center; width: 100%;">Book Now</a>
                                <?php else: ?>
                                    <button class="btn btn-disabled" style="background-color: #ffebee; color: #bb0013; cursor: not-allowed; width: 100%; justify-content: center;" disabled>Quota Full (<?php echo $used_quota; ?>/<?php echo $max_quota; ?>)</button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-disabled" style="width: 100%; justify-content: center;" disabled><?php echo $row['status']; ?> (Unavailable)</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style='grid-column: 1/-1; text-align: center; padding: 50px;'><p>No facilities match your filters.</p></div>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');
        trigger.addEventListener('click', (e) => { e.stopPropagation(); notifMenu.classList.remove('show'); menu.classList.toggle('show'); });
        notifTrigger.addEventListener('click', function(e) { e.stopPropagation(); menu.classList.remove('show'); notifMenu.classList.toggle('show'); if (notifMenu.classList.contains('show')) { fetch('facilities.php?mark_read=1'); const badge = document.getElementById('notifBadge'); if (badge) badge.style.display = 'none'; } });
        window.addEventListener('click', () => { menu.classList.remove('show'); notifMenu.classList.remove('show'); });
    </script>
</body>
</html>