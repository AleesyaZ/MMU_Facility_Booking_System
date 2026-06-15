<?php
session_start();
include('../PHP/db_config.php');

// 1. Check login status for the Dynamic Navbar
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";

    // Initials for avatar
    $name_parts = explode(' ', trim($full_name));
    $initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Guidelines - MMU Campus Space</title>
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
                    <a href="index.php">Home</a>
                    <a href="facilities.php">Browse Facilities</a>
                    <a href="guidelines.php" class="active">Guidelines</a>
                <?php endif; ?>
            </nav>
            
            <?php if ($is_logged_in): ?>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; position: relative; max-width: 300px; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="color: var(--text-muted); flex-shrink: 0;">notifications</span>
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
                    <li>Quotas automatically reset every <strong>Sunday at 11:59 PM</strong>.</li>
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
                <p>Facilities can only be booked during official campus operating hours. The portal will automatically prevent bookings outside these times.</p>
                <ul>
                    <li><strong>Lecture Halls & Labs:</strong> Monday - Friday (8:00 AM - 6:00 PM)</li>
                    <li><strong>Discussion Rooms:</strong> Monday - Sunday (8:00 AM - 10:00 PM)</li>
                    <li><strong>Sports Courts:</strong> Monday - Sunday (8:00 AM - 11:00 PM)</li>
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
        if(trigger) {
            trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); });
            window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });
        }
    </script>

</body>
</html>