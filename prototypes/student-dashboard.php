<?php
session_start();
// Database connection - path adjusted to your PHP folder
include('../PHP/db_config.php');

// 1. SECURITY CHECK: If not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. FETCH USER DETAILS (Full Name)
$user_query = "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);
$full_name = $user_data['name'] ?? "User";

// Get first name and initials for the UI
$name_parts = explode(' ', trim($full_name));
$first_name = $name_parts[0]; 
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// 3. FETCH BOOKING QUOTA (Count active bookings)
$quota_query = "SELECT COUNT(*) as total FROM booking WHERE user_id = '$user_id' AND status != 'Cancelled' AND status != 'Rejected'";
$quota_result = mysqli_query($conn, $quota_query);
$quota_data = mysqli_fetch_assoc($quota_result);
$used_quota = $quota_data['total'];

// 4. FETCH PENALTY STRIKES (Sum active strikes)
$penalty_query = "SELECT SUM(strike_count) as total_strikes FROM penalty WHERE user_id = '$user_id' AND status = 'Active'";
$penalty_result = mysqli_query($conn, $penalty_query);
$penalty_row = mysqli_fetch_assoc($penalty_result);
$strikes = $penalty_row['total_strikes'] ?? 0;

// 5. FETCH UPCOMING BOOKINGS
// We join 'booking' and 'facility' tables to get the name of the place
$bookings_query = "SELECT b.*, f.facility_name, f.category 
                   FROM booking b 
                   JOIN facility f ON b.facility_id = f.facility_id 
                   WHERE b.user_id = '$user_id' 
                   AND b.status != 'Cancelled' 
                   ORDER BY b.booking_date ASC, b.start_time ASC 
                   LIMIT 3"; // We only show the top 3 on the dashboard

$bookings_result = mysqli_query($conn, $bookings_query);

// 6. FETCH CAMPUS ANNOUNCEMENTS
$ann_query = "SELECT * FROM annoucement ORDER BY publish_date DESC LIMIT 3";
$ann_result = mysqli_query($conn, $ann_query);

function time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);           // value 60 is seconds
    $hours   = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec
    $days    = round($seconds / 86400);        // value 86400 is 24 hours * 60 min * 60 sec

    if ($seconds <= 60) return "Just Now";
    else if ($minutes <= 60) return "$minutes minutes ago";
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
    <title>Student Dashboard - MMU Facility Booking</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body>

    <!-- Logged-in Navbar -->
    <header class="navbar">
        <div class="container nav-container">
            <a href="student-dashboard.php" class="nav-logo">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo">
                <div class="logo-divider"></div>
                <span class="system-name">Facility Booking</span>
            </a>
            
            <nav class="nav-links">
                <a href="student-dashboard.php" class="active">Dashboard</a>
                <a href="#">My Bookings</a>
                <a href="#">Report Issue</a>
            </nav>
            
            <div class="nav-profile">
                <span class="material-symbols-outlined" style="color: var(--text-muted);">notifications</span>
                <div class="avatar"><?php echo strtoupper($initials); ?></div>
                <span style="font-weight: 500; font-size: 14px;"><?php echo htmlspecialchars($full_name); ?></span>
                <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>
            </div>
        </div>
    </header>

    <!-- Main Dashboard Content -->
    <main class="dashboard-main">
        <div class="dashboard-header">
            <h2>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h2>
            <p>Here is your campus facility overview for today.</p>
        </div>

        <div class="dashboard-grid">
            
            <!-- ROW 1 -->
            <!-- Quota & Strikes Card -->
            <div class="card col-span-2">
                <h3 class="card-title">
                    <span class="material-symbols-outlined">analytics</span> Account Standing
                </h3>
                <div class="stat-grid">
                    <div class="stat-box success">
                        <span class="stat-label">Booking Quota Used</span>
                        <span class="stat-value"><?php echo $used_quota; ?> / 2</span>
                    </div>
                    <div class="stat-box warning">
                        <span class="stat-label">Penalty Strikes</span>
                        <span class="stat-value"><?php echo $strikes; ?> / 3</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 12px;">Quotas resets on a weekly basis. 3 penalty strikes result in a 30-day suspension.</p>
            </div>

            <!-- Quick Actions Card -->
            <div class="card col-span-1">
                <h3 class="card-title">
                    <span class="material-symbols-outlined">bolt</span> Quick Actions
                </h3>
                <div style="display: flex; flex-direction: column; gap: 12px; height: 100%; justify-content: center;">
                    <button class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <span class="material-symbols-outlined">add_circle</span> New Booking
                    </button>
                    <button class="btn btn-outline" style="width: 100%; justify-content: center;">
                        <span class="material-symbols-outlined">warning</span> Report an Issue
                    </button>
                </div>
            </div>

            <!-- ROW 2 -->
            <!-- Upcoming Bookings Card -->
            <div class="card col-span-2">
                <h3 class="card-title" style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined">event</span> Upcoming Bookings
                    </div>
                    <a href="my-bookings.php" style="font-size: 13px; font-weight: 500; color: var(--text-muted);">View All</a>
                </h3>
                
                <div class="list-group">
                    <?php 
                    if (mysqli_num_rows($bookings_result) > 0) {
                        while ($row = mysqli_fetch_assoc($bookings_result)) { 
                            // Logic to choose icon based on category
                            $icon = "event"; // Default
                            if (stripos($row['category'], 'room') !== false) $icon = "meeting_room";
                            if (stripos($row['category'], 'court') !== false || stripos($row['category'], 'sports') !== false) $icon = "sports_basketball";
                            if (stripos($row['category'], 'lab') !== false) $icon = "biotech";

                            // Format the Date and Time
                            $formatted_date = date("d M Y", strtotime($row['booking_date']));
                            $formatted_start = date("g:i A", strtotime($row['start_time']));
                            $formatted_end = date("g:i A", strtotime($row['end_time']));

                            // Set Badge Class based on status
                            $status = $row['status'];
                            $badge_class = "badge-pending"; // Default
                            if ($status == "Approved") $badge_class = "badge-approved";
                            if ($status == "Rejected") $badge_class = "badge-rejected";
                    ?>
                            <div class="list-item">
                                <div class="item-icon">
                                    <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                                </div>
                                <div class="item-details" style="flex: 1;">
                                    <h4><?php echo htmlspecialchars($row['facility_name']); ?></h4>
                                    <p><?php echo $formatted_date; ?> • <?php echo $formatted_start; ?> - <?php echo $formatted_end; ?></p>
                                    
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <span class="material-symbols-outlined" style="font-size: 12px;">
                                            <?php echo ($status == 'Approved' ? 'check_circle' : 'schedule'); ?>
                                        </span> 
                                        <?php echo $status; ?>
                                    </span>
                                </div>
                                <div>
                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px;" 
                                            onclick="alert('Cancel logic will be implemented in the next phase')">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                    <?php 
                        } 
                    } else {
                        // Show this if the user has no bookings
                        echo "<div style='text-align:center; padding: 20px; color: var(--text-muted);'>
                                <p>No upcoming bookings found.</p>
                                <button class='btn btn-primary' style='margin-top:10px;'>Book a Facility</button>
                            </div>";
                    }
                    ?>
                </div>
            </div>

            <!-- Announcements Card -->
            <div class="card col-span-1">
                <h3 class="card-title">
                    <span class="material-symbols-outlined">campaign</span> Campus Announcements
                </h3>
                <div class="list-group">
                    <?php 
                    if (mysqli_num_rows($ann_result) > 0) {
                        while ($ann = mysqli_fetch_assoc($ann_result)) { 
                    ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 12px;">
                                <h4 style="font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($ann['title']); ?>
                                </h4>
                                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;">
                                    <?php echo htmlspecialchars($ann['content']); ?>
                                </p>
                                <span style="font-size: 10px; color: var(--primary); font-weight: 600; margin-top: 4px; display: block;">
                                    Posted <?php echo time_ago($ann['publish_date']); ?>
                                </span>
                            </div>
                    <?php 
                        } 
                    } else {
                        echo "<p style='font-size: 12px; color: var(--text-muted); text-align: center;'>No announcements at this time.</p>";
                    }
                    ?>
                </div>
            </div>

        </div>
    </main>

</body>
</html>