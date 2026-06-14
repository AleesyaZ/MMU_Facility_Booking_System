<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// 2. GET FACILITY DETAILS
if (!isset($_GET['id'])) {
    header("Location: facilities.php");
    exit();
}

$facility_id = mysqli_real_escape_string($conn, $_GET['id']);
$fac_query = "SELECT * FROM facility WHERE facility_id = '$facility_id' LIMIT 1";
$fac_result = mysqli_query($conn, $fac_query);
$facility = mysqli_fetch_assoc($fac_result);

if (!$facility) { die("Facility not found."); }

// 3. GET USER DETAILS
$user_id = $_SESSION['user_id'];
$user_query = "SELECT name, booking_quota FROM user WHERE user_id = '$user_id'";
$user_data = mysqli_fetch_assoc(mysqli_query($conn, $user_query));
$full_name = $user_data['name'];
$max_quota = $user_data['booking_quota'];

$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// 4. FETCH DYNAMIC EQUIPMENTS
$safe_cat = mysqli_real_escape_string($conn, $facility['category']);
$equip_query = "SELECT * FROM equipment 
                WHERE status = 'Available' 
                AND (category = '$safe_cat' OR category = 'General') 
                ORDER BY name ASC";
$equip_result = mysqli_query($conn, $equip_query);

// --- NEW: 5. FETCH EXISTING BOOKINGS TO SHOW OCCUPIED SLOTS ---
$occupied_query = "SELECT booking_date, start_time, end_time FROM booking 
                   WHERE facility_id = '$facility_id' 
                   AND status IN ('Approved', 'Pending') 
                   AND booking_date >= CURDATE()
                   ORDER BY booking_date ASC, start_time ASC";
$occupied_result = mysqli_query($conn, $occupied_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Book <?php echo $facility['facility_name']; ?> - MMU Campus Space</title>
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
                <a href="facilities.php" class="active">Browse Facilities</a>
                <a href="my-bookings.php">My Bookings</a>
                <a href="report-issue.php">Report Issue</a>
            </nav>
            
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
        </div>
    </header>

    <main class="container booking-layout">
        
        <div class="card" style="margin-bottom: 0;">
            <div class="dashboard-header" style="margin-bottom: 24px;">
                <h2>Reservation Details</h2>
                <p>Please fill in the date, time, and required equipment for your session.</p>
            </div>

            <form action="../PHP/booking_process.php" method="POST">
                <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">

                <div class="form-group">
                    <label>Select Date</label>
                    <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    
                    <div style="margin-top: 12px; padding: 12px; background: #f0f2f5; border-radius: 8px; border-left: 4px solid var(--secondary);">
                        <span style="font-size: 12px; font-weight: 700; color: var(--text-main); display: block; margin-bottom: 5px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event_busy</span> OCCUPIED SLOTS:
                        </span>
                        <ul style="font-size: 11px; color: var(--text-muted); list-style: none; padding-left: 0;">
                            <?php if(mysqli_num_rows($occupied_result) > 0): ?>
                                <?php while($occ = mysqli_fetch_assoc($occupied_result)): ?>
                                    <li>• <?php echo date("d M", strtotime($occ['booking_date'])); ?>: <?php echo date("g:i A", strtotime($occ['start_time'])); ?> - <?php echo date("g:i A", strtotime($occ['end_time'])); ?></li>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <li>No existing bookings. All slots available!</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purpose of Booking</label>
                    <textarea name="purpose" class="form-control" placeholder="e.g., Group Assignment Meeting, Club Activity..." required></textarea>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 32px 0;">

                <div class="dashboard-header" style="margin-bottom: 16px;">
                    <h2 style="font-size: 20px;">Add-On Equipment</h2>
                </div>

                <div class="equipment-list">
                    <?php if(mysqli_num_rows($equip_result) > 0): ?>
                        <?php while($equip = mysqli_fetch_assoc($equip_result)): ?>
                        <div class="equipment-option">
                            <div class="equipment-left">
                                <input type="checkbox" name="equipment[]" value="<?php echo $equip['equip_id']; ?>" id="eq<?php echo $equip['equip_id']; ?>">
                                <label for="eq<?php echo $equip['equip_id']; ?>"><?php echo $equip['name']; ?></label>
                            </div>
                            <input type="number" name="qty_<?php echo $equip['equip_id']; ?>" class="equipment-qty" min="1" max="<?php echo $equip['avail_qty']; ?>" value="1">
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 14px; color: var(--text-muted);">No equipment available for request.</p>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 40px;">
                    <a href="facilities.php" class="btn btn-outline" style="flex: 1; justify-content: center;">Cancel</a>
                    <button type="submit" name="confirm_booking" class="btn btn-primary" style="flex: 2; justify-content: center;">Confirm Booking</button>
                </div>
            </form>
        </div>

        <div>
            <div class="card" style="position: sticky; top: 100px;">
                <h3 class="card-title">Booking Summary</h3>
                
                <img src="../public/img/facilities/<?php echo !empty($facility['image_path']) ? $facility['image_path'] : 'default.jpg'; ?>" 
                     alt="Facility" class="summary-img" onerror="this.src='../public/img/mmulogo.jpg'">
                
                <div class="summary-details">
                    <h3><?php echo htmlspecialchars($facility['facility_name']); ?></h3>
                    <div class="summary-item">
                        <span class="material-symbols-outlined">location_on</span>
                        <span><?php echo htmlspecialchars($facility['location']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="material-symbols-outlined">group</span>
                        <span>Maximum Capacity: <?php echo $facility['capacity']; ?> people</span>
                    </div>
                </div>

                <div class="quota-alert">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                    <div>
                        <strong>Quota Notice</strong><br>
                        This reservation will consume 1 of your <?php echo $max_quota; ?> facility quotas.
                    </div>
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