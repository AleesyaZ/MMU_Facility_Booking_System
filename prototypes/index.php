<?php
session_start();
include('../PHP/db_config.php');

// 1. Check login status
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1";
    $user_result = mysqli_query($conn, $user_query);
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";

    $name_parts = explode(' ', trim($full_name));
    $initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");
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
                    <a href="my-bookings.html">My Bookings</a>
                    <a href="report-issue.html">Report Issue</a>
                <?php else: ?>
                    <a href="index.php" class="active">Home</a>
                    <a href="facilities.php">Browse Facilities</a>
                    <a href="guidelines.html">Guidelines</a>
                <?php endif; ?>
            </nav>
            
            <?php if ($is_logged_in): ?>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; position: relative; max-width: 300px; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="color: var(--text-muted); flex-shrink: 0;">notifications</span>
                    <div class="avatar" style="flex-shrink: 0;"><?php echo strtoupper($initials); ?></div>
                    
                    <span style="font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;" title="<?php echo htmlspecialchars($full_name); ?>">
                        <?php echo htmlspecialchars($full_name); ?>
                    </span>
                    
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted); flex-shrink: 0;">expand_more</span>

                    <div class="profile-dropdown" id="profileMenu">
                        <a href="#" class="dropdown-item"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                        <div class="dropdown-divider"></div>
                        <a href="../PHP/logout.php" class="dropdown-item logout-item"><span class="material-symbols-outlined">logout</span> Logout</a>
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
                    <a href="guidelines.html#general">Booking Guidelines</a>
                    <a href="guidelines.html#quota">Quota & Penalty Rules</a>
                    <a href="guidelines.html#priority">Staff Priority Booking</a>
                    <a href="guidelines.html#hours">Operating Hours</a>
                    <a href="guidelines.html#faq">FAQ / Helpdesk</a>
                </div>
            </div>

            <!-- right column: social media -->
            <div class="footer-social">
                <h4>Connect with Us</h4>
                <div class="social-icons">

                    <!-- Facebook -->
                    <a href="https://www.facebook.com/mmumalaysia/" target="_blank" class="social-icon">
                        <img src="../public/img/facebook.png" alt="Facebook">
                    </a>
                    
                    <!-- Instagram -->
                    <a href="https://www.instagram.com/mmumalaysia/" target="_blank" class="social-icon">
                         <img src="../public/img/instagram.png" alt="Instagram">
                    </a>
                        
                    <!-- TikTok -->
                    <a href="https://www.tiktok.com/@mmumalaysia" target="_blank" class="social-icon">
                        <img src="../public/img/tiktok.jpg" alt="TikTok">
                    </a>
                        
                    <!-- YouTube -->
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
        trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });
    </script>
    <?php endif; ?>

</body>
</html>