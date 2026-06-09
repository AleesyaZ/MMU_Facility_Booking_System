<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK: Only allow logged-in Lecturers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Lecturer') {
    header("Location: login.html");
    exit();
}

// 2. GET FACILITY DETAILS from URL (?id=X)
if (!isset($_GET['id'])) {
    header("Location: facilities.php");
    exit();
}

$facility_id = mysqli_real_escape_string($conn, $_GET['id']);
$fac_query = "SELECT * FROM facility WHERE facility_id = '$facility_id' LIMIT 1";
$fac_result = mysqli_query($conn, $fac_query);
$facility = mysqli_fetch_assoc($fac_result);

if (!$facility) {
    die("Facility not found in database.");
}

// 3. GET USER DETAILS (For Navbar and Quota Notice)
$user_id = $_SESSION['user_id'];
$user_query = "SELECT name, booking_quota FROM user WHERE user_id = '$user_id' LIMIT 1";
$user_data = mysqli_fetch_assoc(mysqli_query($conn, $user_query));
$full_name = $user_data['name'] ?? "Lecturer";
$max_quota = $user_data['booking_quota'] ?? 2;

// Get initials for the avatar
$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// 4. FETCH DYNAMIC EQUIPMENTS
$safe_cat = mysqli_real_escape_string($conn, $facility['category']);

$equip_query = "SELECT * FROM equipment 
                WHERE status = 'Available' 
                AND (category = '$safe_cat' OR category = 'General') 
                ORDER BY name ASC";

$equip_result = mysqli_query($conn, $equip_query);

if (!$equip_result) {
    echo "Query Error: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Lecturer Booking - MMU Campus Space</title>
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
                <a href="#">My Bookings</a>
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

    <main class="container booking-layout">
        
        <div class="card" style="margin-bottom: 0;">
            <div class="dashboard-header" style="margin-bottom: 24px;">
                <h2>Reservation Details</h2>
                <p>Please fill in the date, time, and required equipment for your session.</p>
            </div>

            <form action="../PHP/lecturer_booking_process.php" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="facility_id" value="<?php echo $facility_id; ?>">

                <div class="form-group">
                    <label>Select Date</label>
                    <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
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

                <div class="priority-box">
                    <input type="checkbox" id="priority" name="priority">
                    <div class="priority-text">
                        <label for="priority" style="font-size: 15px; font-weight: 600; color: #856404; margin-bottom: 4px;">Request Priority Override</label>
                        <p>Check this for academic purposes (Replacement classes/exams). Standard quota will be bypassed upon Admin approval.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label>Purpose of Booking & Subject Code</label>
                    <textarea name="purpose" class="form-control" placeholder="Please state Subject Code and reason (e.g. CPT4214 Lab Session)..." required></textarea>
                    
                    <label class="upload-zone" id="dropZone" for="proofUpload">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span class="material-symbols-outlined upload-icon">cloud_upload</span>
                            <span class="upload-text">Click to upload proof</span>
                        </div>
                        <span class="upload-hint">Upload Faculty Memo or Timetable (PDF/JPG, Max 5MB)</span>
                        <input type="file" id="proofUpload" name="proofUpload" accept=".pdf,.jpg,.jpeg,.png">
                        
                        <div id="fileNameDisplay" style="margin-top: 16px; font-size: 13px; font-weight: 600; color: var(--primary); display: none;"></div>
                    </label>
                </div>

                <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 32px 0;">

                <div class="dashboard-header" style="margin-bottom: 16px;">
                    <h2 style="font-size: 20px;">Add-On Equipment <span style="font-size: 14px; font-weight: normal; color: var(--text-muted);">(Optional)</span></h2>
                </div>

                <div class="equipment-list">
                    <?php if(mysqli_num_rows($equip_result) > 0): ?>
                        <?php while($equip = mysqli_fetch_assoc($equip_result)): ?>
                            <div class="equipment-option">
                                <div class="equipment-left">
                                    <input type="checkbox" name="equipment[]" value="<?php echo $equip['equip_id']; ?>" id="eq<?php echo $equip['equip_id']; ?>">
                                    <label for="eq<?php echo $equip['equip_id']; ?>"><?php echo htmlspecialchars($equip['name']); ?></label>
                                </div>
                                <input type="number" name="qty_<?php echo $equip['equip_id']; ?>" class="equipment-qty" min="1" max="<?php echo $equip['avail_qty']; ?>" value="1">
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 14px; color: var(--text-muted);">No equipment available in database.</p>
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
                        <strong>Staff Quota</strong><br>
                        Standard bookings consume 1 of your <?php echo $max_quota; ?> weekly quotas. Priority requests require Admin approval.
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        // 1. Profile Dropdown Logic
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            menu.classList.toggle('show');
        });

        window.addEventListener('click', function() {
            if (menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });

        // 2. File Name Display for Upload
        const fileInput = document.getElementById('proofUpload');
        const fileNameDisplay = document.getElementById('fileNameDisplay');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.innerText = "Selected: " + this.files[0].name;
                fileNameDisplay.style.display = "block";
            } else {
                fileNameDisplay.style.display = "none";
            }
        });
    </script>

</body>
</html>