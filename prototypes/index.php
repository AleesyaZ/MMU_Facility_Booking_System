<?php
session_start();
include('../PHP/db_config.php');

// 1. Check login status
$is_logged_in = isset($_SESSION['user_id']);
$unread_count = 0;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";

    $name_parts = explode(' ', trim($full_name));
    $initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>MMU Facility Booking - Fairly Accessible to All</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body>

    <header class="navbar">
        <div class="container nav-container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a href="index.php" class="nav-logo" style="display: flex; align-items: center; flex-shrink: 0;">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo">
                <div class="logo-divider"></div>
                <span class="system-name">Facility Booking</span>
            </a>
            
            <nav class="nav-links" style="display: flex; align-items: center; gap: 20px;">
                <?php if ($is_logged_in): ?>
                    <a href="student-dashboard.php">Dashboard</a>
                    <a href="facilities.php">Browse Facilities</a>
                    <a href="my-bookings.php">My Bookings</a>
                    <a href="report-issue.php">Report Issue</a>
                <?php else: ?>
                    <a href="index.php" class="active">Home</a>
                    <a href="facilities.php">Browse Facilities</a>
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
            <?php else: ?>
                <div class="nav-auth" style="flex-shrink: 0;">
                    <a href="login.html" class="btn btn-outline">Sign In</a>
                    <a href="activate.html" class="btn btn-primary" style="margin-left: 8px;">Activate Account</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero-fullscreen" style="background-image: url('../public/img/melakalibrary.jpg');">
        <div class="hero-overlay"></div>
        <div class="hero-center-content">
            <h1>Campus Facilities, <br><span>Fairly Accessible to All.</span></h1>
            <p>Say goodbye to confusing physical QR codes. Book lecture halls, labs, and sports courts instantly with real-time scheduling.</p>
            <div class="hero-button-wrapper">
                <a href="facilities.php" class="btn btn-primary" style="padding: 14px 32px;">Book a Facility</a>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-number">2</div>
                <div class="stat-label">Campuses</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">5+</div>
                <div class="stat-label">Facility Types</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">40+</div>
                <div class="stat-label">Bookable Spaces</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">0</div>
                <div class="stat-label">Physical QR Codes</div>
            </div>
        </div>
    </section>

    <section class="features" style="padding-top: 140px;">
        <div class="container">
            <div class="section-header">
                <h2 style="font-size: 42px; font-weight: 800; color: #003d7c; margin-bottom: 24px;">Awesome Core Features</h2>
                <p>We redesigned the booking experience from the ground up to solve campus scheduling conflicts and remove staff dependency.</p>
            </div>

            <div class="feature-showcase">
                <div class="creative-card">
                    <div class="feature-img-wrapper">
                        <img src="../public/img/qrcode.jpg" alt="QR Codes illustration">
                    </div>
                    <h3>No More Physical QR Codes</h3>
                    <p>Stop walking to facilities just to scan a sticker. Search and book any campus resource completely online from anywhere.</p>
                </div>

                <div class="creative-card center-card">
                    <div class="feature-img-wrapper">
                        <img src="../public/img/fairquota.jpg" alt="Quota illustration">
                    </div>
                    <h3>Fair Quota Management</h3>
                    <p>Our automated quota system guarantees that no single group can monopolize campus resources, ensuring equal opportunity.</p>
                </div>

                <div class="creative-card">
                    <div class="feature-img-wrapper">
                        <img src="../public/img/addequipments.jpg" alt="Equipment illustration">
                    </div>
                    <h3>Add-On Equipment</h3>
                    <p>Need a projector or sports gear? Add required equipment directly to your booking request before checkout.</p>
                </div>
            </div>
        </div>
    </section>

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
                        
                    <a href="https://www.youtube.com/mmumalaysiatv" target="_blank" class="social-icon">
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

    <?php if ($is_logged_in): ?>
    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        if (trigger && menu) {
            trigger.addEventListener('click', (e) => { 
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
                    fetch('index.php?mark_read=1');
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.style.display = 'none';
                }
            });
        }

        window.addEventListener('click', () => { 
            if (menu && menu.classList.contains('show')) menu.classList.remove('show'); 
            if (notifMenu && notifMenu.classList.contains('show')) notifMenu.classList.remove('show');
        });
    </script>
    <?php endif; ?>

</body>
</html>