<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. NAVBAR DATA
$user_query = mysqli_query($conn, "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1");
$user_data = mysqli_fetch_assoc($user_query);
$full_name = $user_data['name'] ?? "User";

$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// 3. TAB LOGIC
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
    <title>My Bookings - MMU Campus Space</title>
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
                    <a href="my-bookings.php" class="active">My Bookings</a>
                    <a href="report-issue.php">Report Issue</a>
            </nav>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer; display: flex; align-items: center; gap: 8px; position: relative; max-width: 300px; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="color: var(--text-muted); flex-shrink: 0;">notifications</span>
                    <div class="avatar" style="flex-shrink: 0;"><?php echo strtoupper($initials); ?></div>
                    
                    <span style="font-weight: 500; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px;" title="<?php echo htmlspecialchars($full_name); ?>">
                        <?php echo htmlspecialchars($full_name); ?>
                    </span>
                    
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted); flex-shrink: 0;">expand_more</span>

                    <div class="profile-dropdown" id="profileMenu">
                        <a href="#" class="dropdown-item">
                            <span class="material-symbols-outlined" style="font-size: 20px;">account_circle</span>
                            My Profile
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../PHP/logout.php" class="dropdown-item logout-item">
                            <span class="material-symbols-outlined" style="font-size: 20px;">logout</span>
                            Logout
                        </a>
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
        trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });
    </script>
</body>
</html>