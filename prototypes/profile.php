<?php
session_start();
include('../PHP/db_config.php');

// 1. Check Login Status
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

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

// 2. Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_no = mysqli_real_escape_string($conn, trim($_POST['contact_number']));
    $password = trim($_POST['password']);

    if (!empty($password)) {
        // Securely hash the updated password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $update_query = "UPDATE user SET contact_no = '$contact_no', password = '$hashed_password' WHERE user_id = '$user_id'";
    } else {
        $update_query = "UPDATE user SET contact_no = '$contact_no' WHERE user_id = '$user_id'";
    }

    if (mysqli_query($conn, $update_query)) {
        $success_msg = "Profile updated successfully!";
    } else {
        $error_msg = "Error updating profile: " . mysqli_error($conn);
    }
}

// 3. Fetch User Details & Account Standings (Sync with Reference Code)
$user_query = "SELECT name, email, contact_no, booking_quota, role FROM user WHERE user_id = '$user_id' LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = $user_data['name'] ?? "User";
$email = $user_data['email'] ?? "";
$contact_no = $user_data['contact_no'] ?? "";
$max_quota = $user_data['booking_quota'] ?? 5;
$user_role = $user_data['role'] ?? "Student";

// Generate Initials
$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// Quota Logic (Sync with Dashboard rule)
$quota_query = "SELECT COUNT(*) as total FROM booking 
                WHERE user_id = '$user_id' 
                AND status NOT IN ('Cancelled', 'Rejected') 
                AND is_priority = 0 
                AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
$quota_result = mysqli_query($conn, $quota_query);
$quota_data = mysqli_fetch_assoc($quota_result);
$used_quota = $quota_data['total'];

// Penalty Logic (Sync with Dashboard rule)
$penalty_query = "SELECT SUM(strike_count) as total_strikes FROM penalty WHERE user_id = '$user_id' AND LOWER(status) = 'active'";
$penalty_result = mysqli_query($conn, $penalty_query);
$penalty_row = mysqli_fetch_assoc($penalty_result);
$strikes = $penalty_row['total_strikes'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Profile - MMU Campus Space</title>
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
                <a href="facilities.php">Browse Facilities</a>
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

    <div class="page-header" style="padding-bottom: 40px; background-color: var(--background);">
        <div class="container">
            <h1 style="color: var(--text-main); margin-bottom: 0;">My Profile</h1>
        </div>
    </div>

    <main class="container booking-layout" style="padding-top: 32px;">
        
        <div class="card" style="margin-bottom: 0;">
            <div class="dashboard-header" style="margin-bottom: 24px;">
                <h2 style="font-size: 20px;">Personal Information</h2>
            </div>

            <?php if (!empty($success_msg)): ?>
                <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 500;">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <form action="profile.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($full_name); ?>" disabled style="background-color: #f1f3f5; color: #6b7280; cursor: not-allowed;">
                    <span class="form-help" style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 4px;">Name cannot be changed. Contact Admin if incorrect.</span>
                </div>

                <div class="form-group">
                    <label>MMU Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled style="background-color: #f1f3f5; color: #6b7280; cursor: not-allowed;">
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($contact_no); ?>" required>
                </div>

                <div class="form-group">
                    <label>Change Password (Optional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter new password to change">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">Save Changes</button>
            </form>
        </div>

        <div>
            <div class="card">
                <h3 class="card-title" style="color: var(--text-main); font-size: 16px; margin-bottom: 16px;">
                    <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 4px;">analytics</span>Account Standing
                </h3>
                
                <div class="stat-box primary" style="margin-bottom: 12px;">
                    <span class="stat-label">Booking Quota Used</span>
                    <span class="stat-value"><?php echo $used_quota; ?> / <?php echo $max_quota; ?></span>
                </div>
                
                <div class="stat-box warning">
                    <span class="stat-label">Penalty Strikes</span>
                    <span class="stat-value"><?php echo $strikes; ?> / 3</span>
                </div>

                <?php if($user_role === 'Lecturer'): ?>
                    <p style="font-size: 11px; color: var(--secondary); margin-top: 12px; font-weight: 600; line-height: 1.4;">Note: Priority academic bookings are excluded from this quota count.</p>
                <?php else: ?>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 12px; line-height: 1.4;">Quotas resets on a weekly basis. 3 penalty strikes result in a 30-day suspension.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        if (trigger) {
            trigger.addEventListener('click', function(e) { 
                e.stopPropagation(); 
                notifMenu.classList.remove('show');
                menu.classList.toggle('show'); 
            });
        }

        if (notifTrigger) {
            notifTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.remove('show');
                notifMenu.classList.toggle('show');

                if (notifMenu.classList.contains('show')) {
                    fetch('profile.php?mark_read=1');
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.style.display = 'none';
                }
            });
        }

        window.addEventListener('click', function() { 
            if (menu.classList.contains('show')) { menu.classList.remove('show'); } 
            if (notifMenu.classList.contains('show')) { notifMenu.classList.remove('show'); }
        });
    </script>
</body>
</html>