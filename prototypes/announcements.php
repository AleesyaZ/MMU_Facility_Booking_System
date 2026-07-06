<?php
session_start();
include('../PHP/db_config.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// Timezone to match Malaysia time
date_default_timezone_set("Asia/Kuala_Lumpur");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH NOTIFICATIONS
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

// FETCH USER DETAILS FOR NAV PROFILE
$user_query = "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = $user_data['name'] ?? "User";
$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// GET CURRENT FILTER CATEGORY FROM URL
$current_category = isset($_GET['category']) ? trim($_GET['category']) : 'All';

// DYNAMIC SIDEBAR COUNTS FROM DATABASE 
$count_query = "SELECT category, COUNT(*) as count FROM annoucement WHERE status = 'Live' GROUP BY category";
$count_result = mysqli_query($conn, $count_query);

$counts = [
    'All' => 0,
    'Facility Updates' => 0,
    'Reminders' => 0,
    'Events' => 0
];

$total_live = 0;
while ($row = mysqli_fetch_assoc($count_result)) {
    $db_cat = $row['category'];
    $cnt = (int)$row['count'];
    
    // Explicitly align database rows to your HTML template category names
    if ($db_cat === 'Update') {
        $counts['Facility Updates'] = $cnt;
    } elseif ($db_cat === 'Reminder') {
        $counts['Reminders'] = $cnt;
    } elseif ($db_cat === 'Event') {
        $counts['Events'] = $cnt;
    } elseif (array_key_exists($db_cat, $counts)) {
        $counts[$db_cat] = $cnt;
    }
    $total_live += $cnt;
}
$counts['All'] = $total_live;

// BUILD FILTERED ANNOUNCEMENT FEED QUERY
if ($current_category !== 'All') {
    // Map URL queries back to exact matching DB keywords
    $db_filter_value = '';
    if ($current_category === 'Facility Updates') {
        $db_filter_value = 'Update';
    } elseif ($current_category === 'Reminders') {
        $db_filter_value = 'Reminder';
    } elseif ($current_category === 'Events') {
        $db_filter_value = 'Event';
    } else {
        $db_filter_value = $current_category;
    }
    
    $ann_query = "SELECT * FROM annoucement WHERE status = 'Live' AND category = '" . mysqli_real_escape_string($conn, $db_filter_value) . "' ORDER BY publish_date DESC";
} else {
    $ann_query = "SELECT * FROM annoucement WHERE status = 'Live' ORDER BY publish_date DESC";
}
$ann_result = mysqli_query($conn, $ann_query);

// Helper function to match the dashboard's relative timing strings
function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);           
    $hours   = round($seconds / 3600);         
    $days    = round($seconds / 86400);        
    if ($seconds <= 60) return "Just Now";
    else if ($minutes <= 60) return "$minutes minutes ago";
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

// Helper styling configuration matching initial UI context variations
function get_avatar_style($category) {
    switch ($category) {
        case 'Update':
        case 'Facility Updates': 
            return ['bg' => 'var(--primary)', 'text' => 'FS', 'author' => 'Facilities Staff'];
        case 'Event':
        case 'Events': 
            return ['bg' => '#059669', 'text' => 'SRC', 'author' => 'Student Representative Council'];
        case 'Reminder':
        case 'Reminders':
        default: 
            return ['bg' => 'var(--secondary)', 'text' => 'AD', 'author' => 'System Admin'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Campus Announcements - MMU Facility Booking System</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
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
                <a href="facilities.php">Browse Facilities</a>
                <a href="my-bookings.php">My Bookings</a>
                <a href="report-issue.php">Report Issue</a>
            </nav>
            
            <div class="nav-profile" id="profileTrigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; position: relative; max-width: 300px; flex-shrink: 0;">

                <!-- NOTIFICATION BELL -->
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
        </div>
    </header>

    <!-- Page Header -->
    <div class="page-header" style="padding-bottom: 40px; background-color: var(--background);">
        <h1 style="color: var(--text-main);">Campus Announcements</h1>
        <p style="color: var(--text-muted);">Stay updated with the latest facility news, maintenance schedules, and campus events.</p>
    </div>

    <!-- Main Layout -->
    <div class="announcements-layout">
        
        <!-- Sidebar Filters -->
        <aside class="announcements-sidebar">
            <h3 class="filter-title">Filter by Category</h3>
            <div class="filter-nav">
                <button class="filter-btn <?php echo ($current_category === 'All') ? 'active' : ''; ?>" onclick="window.location.href='announcements.php?category=All'">
                    All Announcements <span class="filter-count"><?php echo $counts['All']; ?></span>
                </button>
                <button class="filter-btn <?php echo ($current_category === 'Facility Updates') ? 'active' : ''; ?>" onclick="window.location.href='announcements.php?category=Facility Updates'">
                    <span style="display: flex; align-items: center; gap: 8px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #0369a1;">meeting_room</span> Facility Updates</span>
                    <span class="filter-count"><?php echo $counts['Facility Updates']; ?></span>
                </button>
                <button class="filter-btn <?php echo ($current_category === 'Reminders') ? 'active' : ''; ?>" onclick="window.location.href='announcements.php?category=Reminders'">
                    <span style="display: flex; align-items: center; gap: 8px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #92400e;">warning</span> Reminders</span>
                    <span class="filter-count"><?php echo $counts['Reminders']; ?></span>
                </button>
                <button class="filter-btn <?php echo ($current_category === 'Events') ? 'active' : ''; ?>" onclick="window.location.href='announcements.php?category=Events'">
                    <span style="display: flex; align-items: center; gap: 8px;"><span class="material-symbols-outlined" style="font-size: 18px; color: #9d174d;">celebration</span> Events</span>
                    <span class="filter-count"><?php echo $counts['Events']; ?></span>
                </button>
            </div>
        </aside>

        <!-- Feed Content -->
        <main class="announcements-feed">
            
            <?php 
            if (mysqli_num_rows($ann_result) > 0) {
                while ($ann = mysqli_fetch_assoc($ann_result)) {
                    $ui_config = get_avatar_style($ann['category']);
            ?>
                    <article class="feed-card">
                        <div class="feed-header">
                            <div class="feed-user-info">
                                <div class="feed-avatar" style="background-color: <?php echo $ui_config['bg']; ?>;"><?php echo $ui_config['text']; ?></div>
                                <div class="feed-meta">
                                    <h4><?php echo $ui_config['author']; ?></h4>
                                    <p>Posted <?php echo time_ago($ann['publish_date']); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="feed-body">
                            <h2 class="announcement-title" style="overflow-wrap: break-word; word-break: break-word;"><?php echo htmlspecialchars($ann['title']); ?></h2>
                            
                            <!-- Keep the tags and PHP on a single, continuous line -->
                            <p class="feed-text" style="white-space: pre-wrap; overflow-wrap: break-word; word-break: break-word; text-align: left;"><?php echo htmlspecialchars($ann['content']); ?></p>
                        </div>
                    </article>
            <?php 
                }
            } else {
                echo "<div class='feed-card' style='text-align: center; padding: 40px; color: var(--text-muted);'><p>No live announcements found in this category.</p></div>";
            }
            ?>

        </main>
    </div>

    <!-- Script for Dropdown -->
    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        if (trigger && menu) {
            trigger.addEventListener('click', function(e) { 
                e.stopPropagation(); 
                if (notifMenu) notifMenu.classList.remove('show');
                menu.classList.toggle('show'); 
            });
        }

        if (notifTrigger && notifMenu) {
            notifTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.remove('show');
                notifMenu.classList.toggle('show');

                if (notifMenu.classList.contains('show')) {
                    fetch('announcements.php?mark_read=1');
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.style.display = 'none';
                }
            });
        }

        window.addEventListener('click', function() { 
            if (menu && menu.classList.contains('show')) menu.classList.remove('show');
            if (notifMenu && notifMenu.classList.contains('show')) notifMenu.classList.remove('show');
        });
    </script>
</body>
</html>