<?php
session_start();
include('../PHP/db_config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Updated to fetch 'role' as well
$user_query = "SELECT name, booking_quota, role FROM user WHERE user_id = '$user_id' LIMIT 1";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

$full_name = $user_data['name'] ?? "User";
$max_quota = $user_data['booking_quota'] ?? 5;
$user_role = $user_data['role'];

$name_parts = explode(' ', trim($full_name));
$first_name = $name_parts[0]; 
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// Quota Query: Corrected to exclude Priority bookings (is_priority = 0) for Lecturers
$quota_query = "SELECT COUNT(*) as total FROM booking 
                WHERE user_id = '$user_id' 
                AND status NOT IN ('Cancelled', 'Rejected') 
                AND is_priority = 0 
                AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
$quota_result = mysqli_query($conn, $quota_query);
$quota_data = mysqli_fetch_assoc($quota_result);
$used_quota = $quota_data['total'];

$penalty_query = "SELECT SUM(strike_count) as total_strikes FROM penalty WHERE user_id = '$user_id' AND LOWER(status) = 'active'";
$penalty_result = mysqli_query($conn, $penalty_query);
$penalty_row = mysqli_fetch_assoc($penalty_result);
$strikes = $penalty_row['total_strikes'] ?? 0;

$bookings_query = "SELECT b.*, f.facility_name, f.category 
                   FROM booking b 
                   JOIN facility f ON b.facility_id = f.facility_id 
                   WHERE b.user_id = '$user_id' 
                   AND b.status != 'Cancelled' 
                   ORDER BY b.booking_date ASC, b.start_time ASC 
                   LIMIT 3"; 
$bookings_result = mysqli_query($conn, $bookings_query);

$ann_query = "SELECT * FROM annoucement ORDER BY publish_date DESC LIMIT 3";
$ann_result = mysqli_query($conn, $ann_query);

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard - MMU Facility Booking</title>
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
                <a href="student-dashboard.php" class="active">Dashboard</a>
                <a href="facilities.php">Browse Facilities</a>
                <a href="my-bookings.html">My Bookings</a>
                <a href="#">Report Issue</a>
            </nav>
            
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
        </div>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-header">
            <h2>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h2>
            <p>Here is your campus facility overview for today.</p>
        </div>

        <div class="dashboard-grid">
            
            <div class="card col-span-2">
                <h3 class="card-title">
                    <span class="material-symbols-outlined">analytics</span> Account Standing
                </h3>
                <div class="stat-grid">
                    <div class="stat-box primary">
                        <span class="stat-label">Booking Quota Used</span>
                        <span class="stat-value"><?php echo $used_quota; ?> / <?php echo $max_quota; ?></span>
                    </div>
                    <div class="stat-box warning">
                        <span class="stat-label">Penalty Strikes</span>
                        <span class="stat-value"><?php echo $strikes; ?> / 3</span>
                    </div>
                </div>
                <!-- Conditional Text for Lecturers -->
                <?php if($user_role === 'Lecturer'): ?>
                    <p style="font-size: 11px; color: var(--secondary); margin-top: 12px; font-weight: 600;">Note: Priority academic bookings are excluded from this quota count.</p>
                <?php else: ?>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 12px;">Quotas resets on a weekly basis. 3 penalty strikes result in a 30-day suspension.</p>
                <?php endif; ?>
            </div>

            <div class="card col-span-1" style="align-self: start; height: auto;">
                <h3 class="card-title">
                    <span class="material-symbols-outlined">bolt</span> Quick Actions
                </h3>
                <div style="display: flex; flex-direction: column; gap: 12px; height: 100%; justify-content: center;">
                    <a href="facilities.php" class="btn btn-primary" style="justify-content: center;">
                        <span class="material-symbols-outlined">add_circle</span> New Booking
                    </a>
                    <a href="report-issue.php" class="btn btn-outline" style="justify-content: center;">
                        <span class="material-symbols-outlined">warning</span> Report an Issue
                    </a>
                </div>
            </div>

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
                            
                            // IMAGE LOGIC: Match a default image URL based on the category
                            $img_url = "https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=300&q=80"; // Default (Room)
                            $cat = strtolower($row['category']);
                            
                            if (strpos($cat, 'hall') !== false) {
                                $img_url = "https://images.unsplash.com/photo-1577415124269-fc1140a69e91?auto=format&fit=crop&w=300&q=80";
                            } elseif (strpos($cat, 'court') !== false || strpos($cat, 'sport') !== false) {
                                $img_url = "https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=300&q=80"; // Replace with sports image later
                            } elseif (strpos($cat, 'lab') !== false) {
                                $img_url = "https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=300&q=80";
                            }

                            $formatted_date = date("d M Y", strtotime($row['booking_date']));
                            $formatted_time = date("g:i A", strtotime($row['start_time'])) . " - " . date("g:i A", strtotime($row['end_time']));
                            $status = $row['status'];
                            $badge_class = ($status == "Approved") ? "badge-approved" : "badge-pending";
                    ?>
                            <div class="list-item" style="align-items: center;">
                                
                                <!-- Left: Image -->
                                <div class="booking-img-wrapper">
                                    <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($row['category']); ?>">
                                </div>
                                
                                <!-- Middle: Text Details -->
                                <div class="item-details booking-details" style="flex: 1;">
                                    <h4><?php echo htmlspecialchars($row['facility_name']); ?></h4>
                                    <p style="margin-bottom: 0;"><?php echo $formatted_date; ?> • <?php echo $formatted_time; ?></p>
                                </div>
                                
                                <!-- Right: Badge and Button -->
                                <div style="display: flex; align-items: center; gap: 8px; justify-content: flex-end;">
                                    
                                    <!-- Status Badge -->
                                    <span class="badge <?php echo $badge_class; ?>" style="height: 28px; padding: 0 12px; display: inline-flex; align-items: center; box-sizing: border-box;">
                                        <span class="material-symbols-outlined" style="font-size: 14px; margin-right: 4px;">
                                            <?php echo ($status == 'Approved' ? 'check_circle' : 'schedule'); ?>
                                        </span> 
                                        <?php echo $status; ?>
                                    </span>
                                    
                                    <!-- Priority Badge for Lecturers -->
                                    <?php if(isset($row['is_priority']) && $row['is_priority']): ?>
                                        <span class="badge" style="background: #fff3cd; color: #856404; height: 28px; padding: 0 12px; display: inline-flex; align-items: center; box-sizing: border-box;">
                                            <span class="material-symbols-outlined" style="font-size: 14px; margin-right: 4px;">star</span> Priority
                                        </span>
                                    <?php endif; ?>
                                    
                                    <!-- Cancel Button -->
                                    <button class="btn btn-outline" style="height: 28px; padding: 0 14px; border-radius: 50px; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box;">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                    <?php 
                        } 
                    } else {
                        echo "<div style='text-align:center; padding: 20px; color: var(--text-muted);'><p>No upcoming bookings found.</p></div>";
                    }
                    ?>
                </div>
            </div>

            <div class="card col-span-1" style="align-self: start; height: auto;">
                <h3 class="card-title">
                    <span class="material-symbols-outlined">campaign</span> Campus Announcements
                </h3>
                <div class="list-group">
                    <?php 
                    if (mysqli_num_rows($ann_result) > 0) {
                        while ($ann = mysqli_fetch_assoc($ann_result)) { 
                    ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 12px;">
                                <h4 style="font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: 4px;"><?php echo htmlspecialchars($ann['title']); ?></h4>
                                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4;"><?php echo htmlspecialchars($ann['content']); ?></p>
                                <span style="font-size: 10px; color: var(--primary); font-weight: 600; margin-top: 4px; display: block;">Posted <?php echo time_ago($ann['publish_date']); ?></span>
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

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', function(e) { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', function() { if (menu.classList.contains('show')) { menu.classList.remove('show'); } });
    </script>
</body>
</html>