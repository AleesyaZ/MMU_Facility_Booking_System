<?php
session_start();
include('../PHP/db_config.php');

// 1. LOGIN & ROLE LOGIC
$is_logged_in = isset($_SESSION['user_id']);
$booking_page = "login.html"; 
$has_quota = true; 
$is_suspended = false; 
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

    // Handle AJAX to mark all as read
    if (isset($_GET['mark_read'])) {
        mysqli_query($conn, "UPDATE notification SET is_read = 1 WHERE user_id = '$user_id'");
        exit();
    }

    // UPDATED: Using your existing 'status' column from the database
    $user_result = mysqli_query($conn, "SELECT name, booking_quota, status FROM user WHERE user_id = '$user_id' LIMIT 1");
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";
    $max_quota = $user_data['booking_quota'] ?? 0;
    
    // Check if the user is suspended using the 'status' column
    if (isset($user_data['status']) && $user_data['status'] === 'Suspended') {
        $is_suspended = true;
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
        return 'student-dashboard.php'; // fallback default
    }
}

// 2. FETCH DYNAMIC CATEGORIES & FACULTIES
$cat_list_result = mysqli_query($conn, "SELECT DISTINCT category FROM facility ORDER BY category ASC");
$fac_list_result = mysqli_query($conn, "SELECT DISTINCT faculty FROM facility WHERE faculty IS NOT NULL ORDER BY faculty ASC");

// 3. FILTERING LOGIC
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
            <div class="filter-group" style="min-width: 160px;">
                <label>Campus Location</label>
                <select name="campus">
                    <option value="all">All Campuses</option>
                    <option value="Cyberjaya" <?php if(isset($_GET['campus']) && $_GET['campus'] == 'Cyberjaya') echo 'selected'; ?>>Cyberjaya</option>
                    <option value="Melaka" <?php if(isset($_GET['campus']) && $_GET['campus'] == 'Melaka') echo 'selected'; ?>>Melaka</option>
                </select>
            </div>

            <div class="filter-group" style="min-width: 160px;">
                <label>Faculty Type</label>
                <select name="faculty">
                    <option value="all">All Faculties</option>
                    <?php while ($fac_row = mysqli_fetch_assoc($fac_list_result)): 
                        $fac_name = $fac_row['faculty'];
                        $selected = (isset($_GET['faculty']) && $_GET['faculty'] == $fac_name) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $fac_name; ?>" <?php echo $selected; ?>><?php echo $fac_name; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="filter-group" style="min-width: 160px;">
                <label>Facility Type</label>
                <select name="category">
                    <option value="all">All Categories</option>
                    <?php while ($cat_row = mysqli_fetch_assoc($cat_list_result)): 
                        $cat_name = $cat_row['category'];
                        $selected = (isset($_GET['category']) && $_GET['category'] == $cat_name) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $cat_name; ?>" <?php echo $selected; ?>><?php echo $cat_name; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group" style="min-width: 160px;">
                <label>Search by Name</label>
                <input type="text" name="search" placeholder="e.g. Lab 3" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; height: 42px; flex-shrink: 0;">
                <span class="material-symbols-outlined" style="font-size: 18px;">search</span> Filter
            </button>
        </form>

        <div class="facility-grid">
            <?php 
            if (mysqli_num_rows($facility_result) > 0) {
                while ($row = mysqli_fetch_assoc($facility_result)) { 
                    $image_name = !empty($row['image_path']) ? $row['image_path'] : "default.jpg";
                    $img_url = "../public/img/facilities/" . $image_name;
                    $status = $row['status'];
                    $is_available = (strtolower($status) == 'available');
            ?>
                    <div class="facility-card">
                        <img src="<?php echo $img_url; ?>" alt="Facility" class="facility-img" onerror="this.src='../public/img/mmulogo.jpg'">
                        <div class="facility-info">
                            <h3><?php echo htmlspecialchars($row['facility_name']); ?></h3>
                            <div class="facility-meta">
                                <span class="meta-item"><span class="material-symbols-outlined">location_on</span> <?php echo htmlspecialchars($row['location']); ?></span>
                                <span class="meta-item"><span class="material-symbols-outlined">group</span> Max <?php echo $row['capacity']; ?></span>
                            </div>
                            <p style="font-size: 12px; color: var(--primary); font-weight: 600; margin-bottom: 8px;">Faculty: <?php echo htmlspecialchars($row['faculty']); ?></p>
                            <p class="facility-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                            
                            <?php if ($is_available): ?>
                                <!-- Blocks booking if user is suspended -->
                                <?php if ($is_suspended): ?>
                                    <button class="btn btn-disabled" style="background-color: #ffebee; color: #bb0013; cursor: not-allowed; width: 100%; justify-content: center;" title="Your account has been suspended" disabled>
                                        Account Suspended
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo $booking_page; ?>?id=<?php echo $row['facility_id']; ?>" class="btn btn-primary" style="justify-content: center; width: 100%;">Book Now</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-disabled" style="width: 100%; justify-content: center;" disabled><?php echo $status; ?> (Unavailable)</button>
                            <?php endif; ?>
                        </div>
                    </div>
            <?php 
                } 
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px;'><p>No facilities match your filters.</p></div>";
            }
            ?>
        </div>
    </main>
    
    <?php if (!$is_logged_in): ?>
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-info">
                <div class="footer-logo">
                    <img src="../public/img/mmulogo.jpg" alt="MMU Logo">
                </div>
                <div style="font-size: 13px; line-height: 1.6; color: var(--text-muted); margin-top: 16px;">
                    <strong>Cyberjaya Campus</strong><br>
                    Persiaran Multimedia, 63100 Cyberjaya, Selangor<br><br>
                    <strong>Melaka Campus</strong><br>
                    Jalan Ayer Keroh Lama, 75450 Bukit Beruang, Melaka
                </div>
            </div>
            <div class="footer-links">
                <h4>Support & Resources</h4>
                <div class="footer-links-list">
                    <a href="guidelines.php#general">Booking Guidelines</a>
                    <a href="guidelines.php#quota">Quota & Penalty Rules</a>
                    <a href="guidelines.php#priority">Staff Priority Booking</a>
                    <a href="guidelines.php#hours">Operating Hours</a>
                    <a href="guidelines.php#faq">FAQ / Helpdesk</a>
                </div>
            </div>

            <div class="footer-social">
                <h4>Connect with Us</h4>
                <div class="social-icons">
                    <a href="https://www.facebook.com/mmumalaysia/" target="_blank" class="social-icon">
                        <img src="../public/img/facebook.png" alt="Facebook">
                    </a>
                    <a href="https://www.instagram.com/mmumalaysia/" target="_blank" class="social-icon">
                         <img src="../public/img/instagram.png" alt="Instagram">
                    </a>
                    <a href="https://www.tiktok.com/@mmumalaysia" target="_blank" class="social-icon">
                        <img src="../public/img/tiktok.jpg" alt="TikTok">
                    </a>
                    <a href="#" target="_blank" class="social-icon">
                        <img src="../public/img/youtube.png" alt="YouTube">
                    </a>
                </div>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; 2024 MULTIMEDIA UNIVERSITY. ALL RIGHTS RESERVED.</p>
            <div class="tm-logo">
                <span style="font-size: 10px; color: #666; margin-right: 8px;">UNIVERSITY TELEKOM SDN BHD 199701021324 (436821-T)</span>
                <span style="color: #003d7c; font-weight: 800;">TM Group</span>
            </div>
        </div>
    </footer>
    <?php endif; ?>

    <?php if ($is_logged_in): ?>
    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        trigger.addEventListener('click', (e) => { e.stopPropagation(); notifMenu.classList.remove('show'); menu.classList.toggle('show'); });

        notifTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.remove('show');
            notifMenu.classList.toggle('show');

            if (notifMenu.classList.contains('show')) {
                fetch('facilities.php?mark_read=1');
                const badge = document.getElementById('notifBadge');
                if (badge) badge.style.display = 'none';
            }
        });

        window.addEventListener('click', () => {
            menu.classList.remove('show');
            notifMenu.classList.remove('show');
        });
    </script>
    <?php endif; ?>

</body>
</html>