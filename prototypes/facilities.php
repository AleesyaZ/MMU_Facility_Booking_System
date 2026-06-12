<?php
session_start();
include('../PHP/db_config.php');

// 1. LOGIN & ROLE LOGIC
$is_logged_in = isset($_SESSION['user_id']);
$booking_page = "login.html"; 
$has_quota = true; 
$used_quota = 0;
$max_quota = 0;

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    if ($role === 'Lecturer') {
        $booking_page = "booking-form-lecturer.php";
    } else {
        $booking_page = "booking-form.php";
    }

    $user_result = mysqli_query($conn, "SELECT name, booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1");
    $user_data = mysqli_fetch_assoc($user_result);
    $full_name = $user_data['name'] ?? "User";
    $max_quota = $user_data['booking_quota'] ?? 0;
    
    $name_parts = explode(' ', trim($full_name));
    $initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

    $quota_query = "SELECT COUNT(*) as total FROM booking 
                    WHERE user_id = '$user_id' 
                    AND status NOT IN ('Cancelled', 'Rejected') 
                    AND is_priority = 0 
                    AND YEARWEEK(booking_date, 1) = YEARWEEK(CURDATE(), 1)";
    $quota_res = mysqli_query($conn, $quota_query);
    $usage_data = mysqli_fetch_assoc($quota_res);
    $used_quota = $usage_data['total'];

    if ($role === 'Student' && $used_quota >= $max_quota) {
        $has_quota = false;
    } else {
        $has_quota = true; 
    }
}

// 2. FETCH DYNAMIC CATEGORIES & FACULTIES
$cat_list_result = mysqli_query($conn, "SELECT DISTINCT category FROM facility ORDER BY category ASC");
$fac_list_result = mysqli_query($conn, "SELECT DISTINCT faculty FROM facility WHERE faculty IS NOT NULL ORDER BY faculty ASC");

// 3. FILTERING LOGIC
$where_clauses = [];
if (isset($_GET['campus']) && $_GET['campus'] !== 'all') {
    $campus = mysqli_real_escape_string($conn, $_GET['campus']);
    $where_clauses[] = "location LIKE '%$campus%'";
}
if (isset($_GET['faculty']) && $_GET['faculty'] !== 'all') {
    $faculty_val = mysqli_real_escape_string($conn, $_GET['faculty']);
    $where_clauses[] = "faculty = '$faculty_val'";
}
if (isset($_GET['category']) && $_GET['category'] !== 'all') {
    $cat = mysqli_real_escape_string($conn, $_GET['category']);
    $where_clauses[] = "category = '$cat'";
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_clauses[] = "facility_name LIKE '%$search%'";
}

$base_query = "SELECT * FROM facility";
if (count($where_clauses) > 0) {
    $base_query .= " WHERE " . implode(' AND ', $where_clauses);
}
$facility_result = mysqli_query($conn, $base_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Browse Facilities - MMU Campus Space</title>
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
                <?php if ($is_logged_in): ?>
                    <a href="student-dashboard.php">Dashboard</a>
                    <a href="facilities.php" class="active">Browse Facilities</a>
                    <a href="my-bookings.php">My Bookings</a>
                    <a href="report-issue.php">Report Issue</a>
                <?php else: ?>
                    <a href="index.php">Home</a>
                    <a href="facilities.php" class="active">Browse Facilities</a>
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
                        <a href="#" class="dropdown-item">My Profile</a>
                        <div class="dropdown-divider"></div>
                        <a href="../PHP/logout.php" class="dropdown-item logout-item">Logout</a>
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

    <div class="page-header">
        <h1>Browse Campus Facilities</h1>
        <p>Find and book available lecture halls, discussion rooms, labs, and sports courts across MMU campuses.</p>
    </div>

    <main class="container">
        <!-- UI FIX: Reduced min-width on groups so all items stay on one line -->
        <form class="filter-bar" action="facilities.php" method="GET" style="flex-wrap: nowrap; gap: 12px;">
            <div class="filter-group" style="min-width: 160px;">
                <label>Campus Location</label>
                <select name="campus">
                    <option value="all">All Campuses</option>
                    <option value="Cyberjaya" <?php if(isset($_GET['campus']) && $_GET['campus'] == 'Cyberjaya') echo 'selected'; ?>>Cyberjaya</option>
                    <option value="Melaka" <?php if(isset($_GET['campus']) && $_GET['campus'] == 'Melaka') echo 'selected'; ?>>Melaka</option>
                </select>
            </div>

            <div class="filter-group" style="min-width: 160px;">
                <label>Faculty Type</label>
                <select name="faculty">
                    <option value="all">All Faculties</option>
                    <?php while ($fac_row = mysqli_fetch_assoc($fac_list_result)): 
                        $fac_name = $fac_row['faculty'];
                        $selected = (isset($_GET['faculty']) && $_GET['faculty'] == $fac_name) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $fac_name; ?>" <?php echo $selected; ?>><?php echo $fac_name; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="filter-group" style="min-width: 160px;">
                <label>Facility Type</label>
                <select name="category">
                    <option value="all">All Categories</option>
                    <?php while ($cat_row = mysqli_fetch_assoc($cat_list_result)): 
                        $cat_name = $cat_row['category'];
                        $selected = (isset($_GET['category']) && $_GET['category'] == $cat_name) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $cat_name; ?>" <?php echo $selected; ?>><?php echo $cat_name; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="filter-group" style="min-width: 160px;">
                <label>Search by Name</label>
                <input type="text" name="search" placeholder="e.g. Lab 3" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>

            <!-- Button is now on the same line -->
            <button type="submit" class="btn btn-primary" style="padding: 10px 20px; height: 42px; flex-shrink: 0;">
                <span class="material-symbols-outlined" style="font-size: 18px;">search</span> Filter
            </button>
        </form>

        <div class="facility-grid">
            <?php 
            if (mysqli_num_rows($facility_result) > 0) {
                while ($row = mysqli_fetch_assoc($facility_result)) { 
                    $image_name = !empty($row['image_path']) ? $row['image_path'] : "default.jpg";
                    $img_url = "../public/img/facilities/" . $image_name;
                    $status = $row['status'];
                    $is_available = (strtolower($status) == 'available');
            ?>
                    <div class="facility-card">
                        <img src="<?php echo $img_url; ?>" alt="Facility" class="facility-img" onerror="this.src='../public/img/mmulogo.jpg'">
                        <div class="facility-info">
                            <h3><?php echo htmlspecialchars($row['facility_name']); ?></h3>
                            <div class="facility-meta">
                                <span class="meta-item"><span class="material-symbols-outlined">location_on</span> <?php echo htmlspecialchars($row['location']); ?></span>
                                <span class="meta-item"><span class="material-symbols-outlined">group</span> Max <?php echo $row['capacity']; ?></span>
                            </div>
                            <p style="font-size: 12px; color: var(--primary); font-weight: 600; margin-bottom: 8px;">Faculty: <?php echo htmlspecialchars($row['faculty']); ?></p>
                            <p class="facility-desc"><?php echo htmlspecialchars($row['description']); ?></p>
                            
                            <?php if ($is_available): ?>
                                <?php if ($has_quota): ?>
                                    <a href="<?php echo $booking_page; ?>?id=<?php echo $row['facility_id']; ?>" class="btn btn-primary" style="justify-content: center; width: 100%;">Book Now</a>
                                <?php else: ?>
                                    <button class="btn btn-disabled" style="background-color: #ffebee; color: #bb0013; cursor: not-allowed;" title="You have reached your weekly booking limit" disabled>
                                        Quota Full (<?php echo $used_quota; ?>/<?php echo $max_quota; ?>)
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled><?php echo $status; ?> (Unavailable)</button>
                            <?php endif; ?>
                        </div>
                    </div>
            <?php 
                } 
            } else {
                echo "<div style='grid-column: 1/-1; text-align: center; padding: 50px;'><p>No facilities match your filters.</p></div>";
            }
            ?>
        </div>
    </main>

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