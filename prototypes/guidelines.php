<?php
session_start();
include('../PHP/db_config.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    header("Location: admin-dashboard.php");
    exit();
}

// Check login status for the Dynamic Navbar
$is_logged_in = isset($_SESSION['user_id']);
$unread_count = 0;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";

    // Initials for avatar
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Guidelines - MMU Facility Booking System</title>
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
                    <a href="user-dashboard.php">Dashboard</a>
                    <a href="facilities.php">Browse Facilities</a>
                    <a href="my-bookings.php">My Bookings</a>
                    <a href="report-issue.php">Report Issue</a>
                <?php else: ?>
                    <a href="index.php">Home</a>
                    <a href="facilities.php">Browse Facilities</a>
                    <a href="guidelines.php" class="active">Guidelines</a>
                <?php endif; ?>
            </nav>
            
            <?php if ($is_logged_in): ?>
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

    <div class="guidelines-layout" style="margin-top: 80px;">
        
        <aside class="guidelines-sidebar">
            <div style="margin-bottom: 32px;">
                <h3>Table of Contents</h3>
                <nav class="guidelines-nav">
                    <a href="#general"><span class="material-symbols-outlined">menu_book</span> General Booking</a>
                    <a href="#quota"><span class="material-symbols-outlined">data_usage</span> Quota System</a>
                    <a href="#penalty"><span class="material-symbols-outlined">gavel</span> Penalty Strikes</a>
                    <a href="#priority"><span class="material-symbols-outlined">star</span> Priority Override (Staff)</a>
                    <a href="#hours"><span class="material-symbols-outlined">schedule</span> Operating Hours</a>
                    <a href="#faq"><span class="material-symbols-outlined">help</span> FAQ & Helpdesk</a>
                </nav>
            </div>

            <div style="background-color: #f8f9ff; border: 1px solid rgba(194, 198, 211, 0.3); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; gap: 12px;">
                <h4 style="font-size: 14px; font-weight: 600; color: #003d7c; display: flex; align-items: center; gap: 6px;">Still need help?</h4>
                <p style="font-size: 12px; color: #424751; line-height: 1.5;">If you can't find the answer, our IT Helpdesk is available during office hours.</p>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <a href="mailto:helpdesk@mmu.edu.my" class="btn btn-primary" style="padding: 8px; font-size: 12px; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">mail</span> Email Support
                    </a>
                    <a href="https://api.whatsapp.com/send/?phone=60383125959" class="btn btn-outline" style="padding: 8px; font-size: 12px; justify-content: center;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">message</span> Message Us
                    </a>
                </div>
            </div>
        </aside>

        <main class="guidelines-content">
            
            <section id="general" class="guide-section">
                <h2>General Booking Rules</h2>
                <p>Welcome to the MMU Campus Space booking portal. To ensure fair and equal access to all students and staff, we have transitioned away from physical QR codes to a fully automated system.</p>
                <ul>
                    <li>Bookings must be made at least <strong>2 hours in advance</strong>.</li>
                    <li>The minimum booking duration is <strong>1 hour</strong>, and the maximum is <strong>2 hours</strong> per session.</li>
                    <li>Add-on equipment (e.g., projectors, markers) must be requested during the booking process.</li>
                </ul>
            </section>

            <section id="quota" class="guide-section">
                <h2>The Quota System</h2>
                <p>To prevent facility monopolization, all standard users are subject to a weekly booking quota.</p>
                <ul>
                    <li>Each user is granted <strong>2 quotas per week</strong>.</li>
                    <li>1 Booking = 1 Quota deduction, regardless of whether the booking is for 1 or 2 hours.</li>
                    <li>Quotas automatically reset every <strong>Sunday at 12:00 AM</strong>.</li>
                </ul>
                <div class="guide-alert">
                    <strong>Tip:</strong> If you cancel your booking before the scheduled time, your quota will be automatically refunded to your account!
                </div>
            </section>

            <section id="penalty" class="guide-section">
                <h2>Penalty & Strike System</h2>
                <p>We rely on the community to keep our facilities clean and accessible. Administrative staff will issue Penalty Strikes based on user reports or manual inspections.</p>
                <p>Strikes are issued for:</p>
                <ul>
                    <li><strong>No-Shows:</strong> Failing to utilize a booked room without cancelling.</li>
                    <li><strong>Misuse:</strong> Unauthorized movement of furniture or leaving a room messy.</li>
                    <li><strong>Vandalism/Damage:</strong> Causing damage to university property.</li>
                </ul>
                <div class="guide-alert warning">
                    <strong>Warning:</strong> Accumulating <strong>3 Penalty Strikes</strong> will result in an automatic 30-day suspension from the booking portal.
                </div>
            </section>

            <section id="priority" class="guide-section">
                <h2>Priority Booking Override (Lecturers Only)</h2>
                <p>Academic needs take precedence. Lecturers have the ability to request a "Priority Override" for official academic purposes (e.g., replacement classes, exams).</p>
                <ul>
                    <li>Priority bookings <strong>do not consume</strong> weekly quotas.</li>
                    <li>They are <strong>not automatically approved</strong>. The Admin must manually review them.</li>
                    <li>Lecturers MUST upload proof (e.g., Faculty Memo or official timetable change) when submitting the request.</li>
                    <li>If approved, the system will cancel the existing student's booking and refund their quota automatically.</li>
                </ul>
            </section>

            <section id="hours" class="guide-section">
                <h2>Operating Hours</h2>
                <p>Facilities can only be booked during official campus operating hours.</p>
                <ul>
                    <li><strong>Lecture Halls, Labs & Tutorial:</strong> Monday - Sunday (8:00 AM - 10:00 PM)</li>
                    <li><strong>Sports Courts:</strong> Monday - Sunday (8:00 AM - 10:00 PM)</li>
                </ul>
            </section>

            <section id="faq" class="guide-section">
                <h2>FAQ & Helpdesk</h2>
                <p><strong>How do I report a messy room?</strong><br>
                Use the "Report Issue" button in your dashboard. Select the facility and upload a photo. The admin will trace the previous user and issue a penalty.</p>
                
                <p><strong>Who do I contact for emergencies?</strong><br>
                For immediate facility emergencies (water leaks, power outages), contact the 24/7 hotline:<br>
                Cyberjaya: 03-8312 5999 | Melaka: 06-252 3999</p>
            </section>

        </main>
    </div>

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
    <?php endif; ?>

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        const notifTrigger = document.getElementById('notifTrigger');
        const notifMenu = document.getElementById('notifMenu');

        if(trigger) {
            trigger.addEventListener('click', (e) => { 
                e.stopPropagation(); 
                if (notifMenu) notifMenu.classList.remove('show');
                menu.classList.toggle('show'); 
            });
        }

        if(notifTrigger) {
            notifTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                menu.classList.remove('show');
                notifMenu.classList.toggle('show');

                if (notifMenu.classList.contains('show')) {
                    fetch('guidelines.php?mark_read=1');
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

</body>
</html>