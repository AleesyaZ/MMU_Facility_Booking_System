<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. GET USER DETAILS (For Navbar)
$user_query = mysqli_query($conn, "SELECT name FROM user WHERE user_id = '$user_id' LIMIT 1");
$user_data = mysqli_fetch_assoc($user_query);
$full_name = $user_data['name'] ?? "User";

$name_parts = explode(' ', trim($full_name));
$initials = substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : "");

// 3. FETCH ALL FACILITIES (For the searchable datalist)
$fac_list_result = mysqli_query($conn, "SELECT facility_name FROM facility ORDER BY facility_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Report Issue - MMU Campus Space</title>
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
                <a href="my-bookings.php">My Bookings</a>
                <a href="report-issue.php" class="active">Report Issue</a>
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

    <div class="page-header" style="padding-bottom: 40px; background-color: var(--background);">
        <h1 style="color: var(--text-main);">Report a Facility Issue</h1>
        <p style="color: var(--text-muted);">Help us keep our campus in top condition. Report damages, cleanliness issues, or faulty equipment.</p>
    </div>

    <main class="container booking-layout" style="padding-top: 32px;">
        
        <div class="card" style="margin-bottom: 0;">
            <form action="../PHP/report_process.php" method="POST" enctype="multipart/form-data">
                
                <div class="form-group">
                    <label>Which facility has an issue?</label>
                    <input class="form-control" list="facility-list" name="facility_name" placeholder="Start typing to search (e.g., Lab)..." required>
                    
                    <datalist id="facility-list">
                        <?php while($f = mysqli_fetch_assoc($fac_list_result)): ?>
                            <option value="<?php echo htmlspecialchars($f['facility_name']); ?>"></option>
                        <?php endwhile; ?>
                    </datalist>
                    <span class="form-help">Type the building or room name to filter options.</span>
                </div>

                <div class="form-group">
                    <label>Category of Issue</label>
                    <select class="form-control" name="issue_type" required>
                        <option value="" disabled selected>Select category...</option>
                        <option value="Equipment/Furniture Damage">Equipment/Furniture Damage</option>
                        <option value="Left messy by previous user">Left messy by previous user</option>
                        <option value="Unauthorized movement of furniture">Unauthorized movement of furniture</option>
                        <option value="General Cleanliness / Spill">General Cleanliness / Spill</option>
                        <option value="IT / Network Issue">IT / Network Issue</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Detailed Description</label>
                    <textarea class="form-control" name="description" placeholder="Please describe the issue in detail..." required></textarea>
                </div>

                <div class="form-group">
                    <label>Photo Evidence (Optional)</label>
                    <label class="upload-zone" id="dropZone" for="proofUpload" style="margin-top: 8px;">
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span class="material-symbols-outlined upload-icon">add_a_photo</span>
                            <span class="upload-text">Click to upload photo</span>
                        </div>
                        <span class="upload-hint">Upload JPG or PNG (Max 5MB)</span>
                        <input type="file" id="proofUpload" name="proofUpload" accept="image/*">
                        <div id="fileNameDisplay" style="margin-top: 16px; font-size: 13px; font-weight: 600; color: var(--primary); display: none;"></div>
                    </label>
                </div>

                <div style="display: flex; gap: 16px; margin-top: 32px;">
                    <a href="student-dashboard.php" class="btn btn-outline" style="flex: 1; justify-content: center;">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="flex: 2; justify-content: center;">Submit Report</button>
                </div>
            </form>
        </div>

        <div>
            <div class="card" style="position: sticky; top: 100px;">
                <h3 class="card-title" style="color: var(--text-main); font-size: 16px; margin-bottom: 16px;">
                    <span class="material-symbols-outlined">gpp_maybe</span> What happens next?
                </h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                    Once you submit this report, it will be forwarded to the Campus Facilities Management team.
                </p>

                <div class="quota-alert" style="background-color: #fff3cd; color: #856404; flex-direction: column; align-items: flex-start; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined" style="font-size: 20px;">warning</span>
                        <strong>Emergency Maintenance</strong>
                    </div>
                    <div style="font-size: 13px; line-height: 1.4;">
                        Immediately call the 24/7 Security Hotline:<br><br>
                        <strong>Cyberjaya:</strong> 03-8312 5999<br>
                        <strong>Melaka:</strong> 06-252 3999
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Dropdown toggle
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });

        // Show selected file name
        document.getElementById('proofUpload').addEventListener('change', function() {
            const display = document.getElementById('fileNameDisplay');
            if(this.files.length > 0) {
                display.innerText = "Selected: " + this.files[0].name;
                display.style.display = "block";
            }
        });
    </script>

</body>
</html>