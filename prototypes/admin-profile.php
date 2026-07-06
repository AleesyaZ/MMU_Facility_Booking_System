<?php
session_start();
include('../PHP/db_config.php');

// SECURITY CHECK: Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// HANDLE PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_no = mysqli_real_escape_string($conn, trim($_POST['contact_no']));
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $update_sql = "UPDATE user SET contact_no = '$contact_no'";
    $valid = true;

    // Password change logic
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error_msg = "Passwords do not match.";
            $valid = false;
        } elseif (strlen($new_password) < 6) {
            $error_msg = "Password must be at least 6 characters.";
            $valid = false;
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql .= ", password = '$hashed'";
        }
    }

    if ($valid) {
        $update_sql .= " WHERE user_id = '$admin_id'";
        if (mysqli_query($conn, $update_sql)) {
            $success_msg = "Profile updated successfully!";
            $_SESSION['name'] = $_SESSION['name']; // Keep session fresh
        } else {
            $error_msg = "Error updating database: " . mysqli_error($conn);
        }
    }
}

// FETCH CURRENT ADMIN DATA
$query = mysqli_query($conn, "SELECT name, email, contact_no FROM user WHERE user_id = '$admin_id' LIMIT 1");
$admin_data = mysqli_fetch_assoc($query);

$admin_name = $admin_data['name'];
$admin_email = $admin_data['email'];
$admin_contact = $admin_data['contact_no'];
$initials = strtoupper(substr($admin_name, 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Profile - MMU Facility Booking System</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body>

    <div class="admin-layout">
        
        <!-- Left Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo" style="height: 32px; object-fit: contain;">
                <div class="logo-divider" style="height: 24px;"></div>
                <span style="font-size: 16px; font-weight: 600; color: var(--text-muted); white-space: nowrap;">Admin Panel</span>
            </div>
            
            <nav class="admin-nav">
                <a href="admin-dashboard.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">dashboard</span> Dashboard Overview
                </a>
                <a href="admin-bookings.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">calendar_month</span> Manage Bookings
                </a>
                <a href="admin-facilities.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">meeting_room</span> Manage Facilities
                </a>
                <a href="admin-equipment.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">cable</span> Manage Equipment
                </a>
                <a href="admin-users.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">group</span> Manage Users & Quotas
                </a>
                <a href="admin-penalties.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">gavel</span> Penalty System
                </a>
                <a href="admin-reports.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">report</span> Issue Reports
                </a>
                <a href="admin-announcements.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">campaign</span> Announcements
                </a>
            </nav>
        </aside>

        <!-- Main Area -->
        <main class="admin-main">
            
            <!-- Topbar -->
            <header class="admin-topbar">
                <div>
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">My Profile</h2>
                </div>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo $initials; ?></div>
                    <span style="font-weight: 500; font-size: 14px; text-transform: uppercase;"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>
                    
                    <!-- The Dropdown Box -->
                    <div class="profile-menu" id="profileMenu">
                        <a href="admin-dashboard.php"><span class="material-symbols-outlined">undo</span> Admin Panel</a>
                        <div style="border-top: 1px solid rgba(194, 198, 211, 0.3); margin: 4px 0;"></div>
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <!-- Scrollable Content -->
            <div class="admin-content">
                
                <?php if($success_msg): ?>
                    <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined">check_circle</span> <?php echo $success_msg; ?>
                    </div>
                <?php endif; ?>

                <?php if($error_msg): ?>
                    <div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                        <span class="material-symbols-outlined">error</span> <?php echo $error_msg; ?>
                    </div>
                <?php endif; ?>

                <div class="dashboard-grid" style="grid-template-columns: minmax(0, 2fr) minmax(300px, 1fr); gap: 32px;">
                    
                    <!-- Left: Edit Form -->
                    <div class="card" style="margin-bottom: 0;">
                        <div style="margin-bottom: 24px;">
                            <h3 style="font-size: 18px; font-weight: 600; color: var(--text-main);">Personal Information</h3>
                            <p style="font-size: 13px; color: var(--text-muted);">Update your contact details and security credentials.</p>
                        </div>

                        <form action="admin-profile.php" method="POST">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_name); ?>" disabled style="background-color: #f1f3f5; color: #6b7280; cursor: not-allowed;">
                            </div>

                            <div class="form-group">
                                <label>Official Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($admin_email); ?>" disabled style="background-color: #f1f3f5; color: #6b7280; cursor: not-allowed;">
                            </div>

                            <div class="form-group">
                                <label>Contact Number (For System Alerts)</label>
                                <input type="text" name="contact_no" class="form-control" value="<?php echo htmlspecialchars($admin_contact); ?>" required>
                            </div>

                            <hr style="border: 0; border-top: 1px solid rgba(194, 198, 211, 0.3); margin: 32px 0;">

                            <div style="margin-bottom: 16px;">
                                <h3 style="font-size: 16px; font-weight: 600; color: var(--text-main);">Security Settings</h3>
                            </div>

                            <div class="form-group">
                                <label>New Password (Optional)</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Enter new password to change">
                            </div>
                            
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                            </div>

                            <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Save Changes</button>
                        </form>
                    </div>

                    <!-- Right: Permissions Card -->
                    <div>
                        <div class="card" style="position: sticky; top: 0;">
                            <h3 class="card-title" style="color: var(--text-main); font-size: 16px; margin-bottom: 16px;">
                                <span class="material-symbols-outlined">admin_panel_settings</span> System Permissions
                            </h3>
                            
                            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="color: var(--success-text); font-size: 18px;">check_circle</span>
                                    <span style="font-size: 13px; color: var(--text-main); font-weight: 500;">Facility Management</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="color: var(--success-text); font-size: 18px;">check_circle</span>
                                    <span style="font-size: 13px; color: var(--text-main); font-weight: 500;">User & Quota Management</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="color: var(--success-text); font-size: 18px;">check_circle</span>
                                    <span style="font-size: 13px; color: var(--text-main); font-weight: 500;">Booking Overrides</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="color: var(--success-text); font-size: 18px;">check_circle</span>
                                    <span style="font-size: 13px; color: var(--text-main); font-weight: 500;">Penalty Issuance</span>
                                </div>
                            </div>

                            <div class="quota-alert" style="background-color: #e0f2fe; color: #0369a1; flex-direction: column; align-items: flex-start; gap: 8px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                                    <strong>Super Admin Level</strong>
                                </div>
                                <div style="font-size: 12px; line-height: 1.4;">
                                    Your account has full write access to the MMU Booking System database. Actions taken are logged permanently.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', function(e) { 
            e.stopPropagation(); 
            menu.classList.toggle('show'); 
        });
        window.addEventListener('click', function() { 
            if (menu.classList.contains('show')) { menu.classList.remove('show'); } 
        });
    </script>
</body>
</html>