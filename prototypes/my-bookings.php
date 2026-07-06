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

$user_id = $_SESSION['user_id'];

// NAVBAR DATA
$user_query = mysqli_query($conn, "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1");
$user_data = mysqli_fetch_assoc($user_query);
$full_name = $user_data['name'] ?? "User";

$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

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
        return 'user-dashboard.php'; // fallback default
    }
}

// TAB LOGIC
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

// Define query based on tab
if ($current_tab == 'history') {
    // Past bookings that were Approved or Rejected
    $status_filter = "AND (b.status = 'Approved' OR b.status = 'Rejected') AND b.booking_date < CURDATE()";
} elseif ($current_tab == 'cancelled') {
    // Any booking with Cancelled status
    $status_filter = "AND b.status = 'Cancelled'";
} else {
    // Upcoming: Approved or Pending bookings for today onwards
    $status_filter = "AND b.status IN ('Approved', 'Pending') AND b.booking_date >= CURDATE()";
}

$query = "SELECT b.*, f.facility_name, f.image_path, f.location 
          FROM booking b 
          JOIN facility f ON b.facility_id = f.facility_id 
          WHERE b.user_id = '$user_id' $status_filter 
          ORDER BY b.booking_date ASC, b.start_time ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Bookings - MMU Facility Booking System</title>
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
                <a href="my-bookings.php" class="active">My Bookings</a>
                <a href="report-issue.php">Report Issue</a>
            </nav>
            
            <?php if (isset($_SESSION['user_id'])): ?>
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
            <?php else: ?>
                <div class="nav-auth" style="flex-shrink: 0;">
                    <a href="login.html" class="btn btn-outline">Sign In</a>
                    <a href="activate.html" class="btn btn-primary" style="margin-left: 8px;">Activate Account</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="page-header" style="padding-bottom: 40px;">
        <h1>My Bookings</h1>
        <p>Manage your upcoming reservations and view your past booking history.</p>
    </div>

    <main class="container" style="max-width: 1000px; margin-top: 32px;">
        
        <div class="nav-tabs">
            <a href="?tab=upcoming" class="tab-btn <?php echo ($current_tab == 'upcoming') ? 'active' : ''; ?>">Upcoming</a>
            <a href="?tab=history" class="tab-btn <?php echo ($current_tab == 'history') ? 'active' : ''; ?>">History</a>
            <a href="?tab=cancelled" class="tab-btn <?php echo ($current_tab == 'cancelled') ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <div class="booking-list">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $img = !empty($row['image_path']) ? $row['image_path'] : "default.jpg";
                    $status = $row['status'];
                    $badge_class = "badge-pending";
                    if($status == "Approved") $badge_class = "badge-approved";
                    if($status == "Rejected" || $status == "Cancelled") $badge_class = "badge-cancelled";
                ?>
                    <div class="booking-card" <?php echo ($status == 'Cancelled') ? 'style="opacity: 0.7;"' : ''; ?>>
                        <div class="booking-info">
                            <div class="booking-img-wrapper" style="width: 120px; height: 80px; border-radius: 8px; overflow: hidden;">
                                <img src="../public/img/facilities/<?php echo $img; ?>" alt="Facility" style="width:100%; height:100%; object-fit:cover;" onerror="this.src='../public/img/mmulogo.jpg'">
                            </div>
                            <div class="booking-details" style="margin-left: 20px;">
                                <h3><?php echo htmlspecialchars($row['facility_name']); ?></h3>
                                <div class="booking-meta" style="display: flex; flex-direction: column; gap: 4px; font-size: 13px; color: var(--text-muted); margin-top: 8px;">
                                    <span><strong>Booking ID:</strong> #BK-<?php echo str_pad($row['booking_id'], 5, "0", STR_PAD_LEFT); ?></span>
                                    <span><strong>Date & Time:</strong> <?php echo date("d M Y", strtotime($row['booking_date'])); ?> • <?php echo date("g:i A", strtotime($row['start_time'])); ?> - <?php echo date("g:i A", strtotime($row['end_time'])); ?></span>
                                    <span><strong>Purpose:</strong> <?php echo htmlspecialchars($row['purpose']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="booking-actions" style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo $status; ?>
                            </span>
                            
                            <?php if($current_tab == 'upcoming'): ?>
                                <a href="../PHP/cancel_booking.php?id=<?php echo $row['booking_id']; ?>" 
                                   class="btn btn-outline" 
                                   style="padding: 6px 16px; font-size: 13px;"
                                   onclick="return confirm('Are you sure you want to cancel this booking?')">
                                     Cancel Booking
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: var(--text-muted);">
                    <span class="material-symbols-outlined" style="font-size: 48px; margin-bottom: 10px;">event_busy</span>
                    <p>No bookings found in this category.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

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
                fetch('my-bookings.php?mark_read=1');
                const badge = document.getElementById('notifBadge');
                if (badge) badge.style.display = 'none';
            }
        });

        window.addEventListener('click', () => {
            menu.classList.remove('show');
            notifMenu.classList.remove('show');
        });
    </script>
</body>
</html>