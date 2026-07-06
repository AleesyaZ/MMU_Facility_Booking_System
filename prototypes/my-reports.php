<?php
session_start();
include('../PHP/db_config.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// Ensure time() matches Malaysia time
date_default_timezone_set("Asia/Kuala_Lumpur");

// SECURITY CHECK
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

// NAVBAR DATA
$user_result = mysqli_query($conn, "SELECT name, role FROM user WHERE user_id = '$user_id' LIMIT 1");
$user_data = mysqli_fetch_assoc($user_result);
$full_name = $user_data['name'] ?? "User";

// Safe initials logic
$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// FETCH USER'S REPORTS
$query = "SELECT r.*, f.facility_name 
          FROM issue_report r 
          JOIN facility f ON r.facility_id = f.facility_id 
          WHERE r.user_id = '$user_id' 
          ORDER BY r.report_date DESC, r.report_id DESC";
$result = mysqli_query($conn, $query);

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Reports - MMU Facility Booking System</title>
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
                <a href="report-issue.php" class="active">Report Issue</a>
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

    <div class="page-header" style="padding-bottom: 40px;">
        <h1>My Submitted Reports</h1>
        <p>Track the status of the facility issues or misuse reports you have submitted.</p>
    </div>

    <main class="feed-container" style="margin-top: 32px; max-width: 800px; margin-left: auto; margin-right: auto; padding: 0 20px;">
        
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($report = mysqli_fetch_assoc($result)): 
                $status = $report['status'];
                $badge_class = ''; 

                if ($status == 'Under Review') {
                    $badge_class = 'badge-pending'; 
                } elseif ($status == 'In Progress') {
                    $badge_class = 'badge-info';    
                } elseif ($status == 'Resolved') {
                    $badge_class = 'badge-approved'; 
                } else {
                    $badge_class = 'badge'; 
                }
                
                $type_icon = "report";
                if($report['issue_type'] == 'Damage') $type_icon = "build";
                if($report['issue_type'] == 'Cleanliness' || $report['issue_type'] == 'Messy') $type_icon = "mop";
                if($report['issue_type'] == 'IT') $type_icon = "router";
            ?>
                <article class="feed-card">
                    <div class="feed-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                        <div class="feed-user-info" style="display: flex; gap: 12px;">
                            <div class="avatar" style="width: 40px; height: 40px; font-size: 14px;"><?php echo strtoupper($initials); ?></div>
                            <div class="feed-meta">
                                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-main);"><?php echo htmlspecialchars($full_name); ?></h4>
                                <p style="font-size: 12px; color: var(--text-muted);"><?php echo time_ago($report['report_date']); ?> • Report #RP-<?php echo str_pad($report['report_id'], 4, "0", STR_PAD_LEFT); ?></p>
                            </div>
                        </div>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                    </div>

                    <div class="feed-body" style="margin-bottom: 20px;">
                        <p class="feed-text" style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
                            <?php echo htmlspecialchars($report['description']); ?>
                        </p>
                        <div class="feed-tags" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <span class="feed-tag" style="background: #f0f2f5; padding: 4px 12px; border-radius: 50px; font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;">location_on</span> <?php echo htmlspecialchars($report['facility_name']); ?>
                            </span>
                            <span class="feed-tag" style="background: #f0f2f5; padding: 4px 12px; border-radius: 50px; font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                                <span class="material-symbols-outlined" style="font-size: 16px;"><?php echo $type_icon; ?></span> <?php echo htmlspecialchars($report['issue_type']); ?>
                            </span>
                        </div>
                    </div>

                    <?php if(!empty($report['issue_image'])): ?>
                    <div class="feed-media" style="padding: 0 20px 20px; display: flex; justify-content: center; background: transparent;">
                        <img src="../public/uploads/issues/<?php echo $report['issue_image']; ?>" 
                             alt="Issue Photo" 
                             style="width: 100% !important; height: auto !important; max-height: none !important; object-fit: contain !important; border-radius: 12px; border: 1px solid rgba(194, 198, 211, 0.2);">
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($report['admin_reply'])): ?>
                    <div class="feed-footer" style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #eee; display: flex; gap: 16px;">
                        <span class="material-symbols-outlined" style="color: var(--primary); background: rgba(0,61,124,0.1); width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; flex-shrink: 0;">support_agent</span>
                        <div class="feed-reply">
                            <h5 style="font-size: 13px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">Admin Response • <?php echo date("d M Y", strtotime($report['reply_date'])); ?></h5>
                            <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;"><?php echo htmlspecialchars($report['admin_reply']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; color: var(--text-muted);">
                <span class="material-symbols-outlined" style="font-size: 64px; opacity: 0.2; margin-bottom: 16px;">assignment_late</span>
                <p style="font-weight: 500;">No reports submitted yet.</p>
            </div>
        <?php endif; ?>

    </main>

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        if (trigger && menu) {
            trigger.addEventListener('click', (e) => { 
                e.stopPropagation(); 
                notifMenu.classList.remove('show');
                menu.classList.toggle('show'); 
            });
        }

        if (notifTrigger && notifMenu) {
            notifTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.remove('show');
                notifMenu.classList.toggle('show');

                if (notifMenu.classList.contains('show')) {
                    fetch('my-reports.php?mark_read=1');
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.style.display = 'none';
                }
            });
        }

        window.addEventListener('click', () => { 
            if (menu.classList.contains('show')) { menu.classList.remove('show'); } 
            if (notifMenu && notifMenu.classList.contains('show')) { notifMenu.classList.remove('show'); }
        });
    </script>
</body>
</html>