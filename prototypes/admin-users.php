<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];
$initials = substr($admin_name, 0, 1);

// --- ACTION: DELETE USER ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    if ($id != $admin_id) {
        mysqli_query($conn, "DELETE FROM user WHERE user_id = '$id'");
        header("Location: admin-users.php?msg=deleted");
    } else {
        header("Location: admin-users.php?msg=error_self");
    }
    exit();
}

// --- ACTION: ADD NEW USER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $quota = mysqli_real_escape_string($conn, $_POST['booking_quota']);
    
    $check_email = mysqli_query($conn, "SELECT email FROM user WHERE email = '$email'");
    if (mysqli_num_rows($check_email) > 0) {
        header("Location: admin-users.php?msg=email_exists");
    } else {
        $sql = "INSERT INTO user (name, email, role, contact_no, booking_quota, is_activated) 
                VALUES ('$name', '$email', '$role', '$contact', '$quota', 0)";
        mysqli_query($conn, $sql);
        header("Location: admin-users.php?msg=added");
    }
    exit();
}

// --- ACTION: EDIT USER (UPDATED TO INCLUDE ACTIVATION) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $uid = mysqli_real_escape_string($conn, $_POST['user_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $quota = mysqli_real_escape_string($conn, $_POST['booking_quota']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $activated = isset($_POST['is_activated']) ? 1 : 0; // Capture activation checkbox

    $sql = "UPDATE user SET 
            name='$name', 
            role='$role', 
            booking_quota='$quota', 
            contact_no='$contact', 
            is_activated='$activated' 
            WHERE user_id='$uid'";
            
    mysqli_query($conn, $sql);
    header("Location: admin-users.php?msg=updated");
    exit();
}

// --- ACTION: ISSUE PENALTY (STRIKE) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_penalty'])) {
    $uid = mysqli_real_escape_string($conn, $_POST['user_id']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $full_reason = "[$category] $reason";

    $check_penalty = mysqli_query($conn, "SELECT strike_count FROM penalty WHERE user_id = '$uid' LIMIT 1");
    
    if (mysqli_num_rows($check_penalty) > 0) {
        $current_data = mysqli_fetch_assoc($check_penalty);
        $new_count = $current_data['strike_count'] + 1;
        $sql = "UPDATE penalty SET strike_count = '$new_count', reason = '$full_reason', status = 'Active' WHERE user_id = '$uid'";
    } else {
        $new_count = 1;
        $sql = "INSERT INTO penalty (user_id, booking_id, reason, strike_count, status) VALUES ('$uid', NULL, '$full_reason', 1, 'Active')";
    }
    
    if(mysqli_query($conn, $sql)) {
        if ($new_count >= 3) {
            mysqli_query($conn, "UPDATE user SET status = 'Suspended' WHERE user_id = '$uid'");
        }
        header("Location: admin-users.php?msg=penalty_issued");
    }
    exit();
}

// --- FETCH USERS ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$role_filter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : 'All Roles';

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM booking b WHERE b.user_id = u.user_id AND b.status IN ('Pending', 'Approved') AND (b.is_priority = 0 OR b.is_priority IS NULL)) as active_bookings,
          COALESCE((SELECT strike_count FROM penalty p WHERE p.user_id = u.user_id LIMIT 1), 0) as strike_count 
          FROM user u 
          WHERE u.role != 'Admin'";

if (!empty($search)) {
    $query .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.user_id LIKE '%$search%')";
}
if ($role_filter != 'All Roles') {
    $query .= " AND u.role = '$role_filter'";
}
$query .= " ORDER BY u.name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Users - MMU Facility Booking System</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body onload="checkAlerts()">

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="../public/img/mmulogo.jpg" alt="MMU Logo" style="height: 32px; object-fit: contain;">
                <div class="logo-divider" style="height: 24px;"></div>
                <span style="font-size: 16px; font-weight: 600; color: var(--text-muted); white-space: nowrap;">Admin Panel</span>
            </div>
            
            <nav class="admin-nav">
                <a href="admin-dashboard.php" class="admin-nav-item ">
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
                <a href="admin-users.php" class="admin-nav-item active">
                    <span class="material-symbols-outlined">group</span> Manage Users & Quotas
                </a>
                <a href="admin-penalties.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">gavel</span> Penalty System
                </a>
                <a href="admin-reports.php" class="admin-nav-item ">
                    <span class="material-symbols-outlined">report</span> Issue Reports
                </a>
                <a href="admin-announcements.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">campaign</span> Announcements
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div><h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">User Management</h2></div>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo strtoupper($initials); ?></div>
                    <span class="profile-name" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>

                    <div class="profile-menu" id="profileMenu">
                        <a href="admin-profile.php"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <div class="admin-toolbar">
                    <form action="admin-users.php" method="GET" style="display: flex; flex: 1; gap: 12px;">
                        <div class="search-wrapper">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Name or Student ID...">
                        </div>
                        <select name="role" class="form-control" style="width: 150px; padding: 8px 12px;" onchange="this.form.submit()">
                            <option <?php if($role_filter == 'All Roles') echo 'selected'; ?>>All Roles</option>
                            <option <?php if($role_filter == 'Student') echo 'selected'; ?>>Student</option>
                            <option <?php if($role_filter == 'Lecturer') echo 'selected'; ?>>Lecturer</option>
                        </select>
                    </form>
                    <button class="btn btn-primary" onclick="openAddModal()" style="padding: 8px 16px;"><span class="material-symbols-outlined" style="font-size: 18px;">person_add</span> Add New User</button>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User Info</th>
                                <th>Role</th>
                                <th style="text-align: center;">Quota Left</th>
                                <th style="text-align: center;">Strikes</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): 
                                    $quota_left = $row['booking_quota'] - $row['active_bookings'];
                                    if($quota_left < 0) $quota_left = 0;

                                    $status_label = "Active"; $status_class = "badge-approved"; $status_icon = "check_circle";
                                    if ($row['strike_count'] >= 3) {
                                        $status_label = "Suspended"; $status_class = "badge-danger"; $status_icon = "block";
                                    } elseif ($row['is_activated'] == 0) {
                                        $status_label = "Pending"; $status_class = "badge-warning"; $status_icon = "pending";
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                        <span style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: <?php echo ($row['role'] == 'Lecturer') ? '#e0e7ff' : 'var(--surface-container-low)'; ?>; color: <?php echo ($row['role'] == 'Lecturer') ? '#3730a3' : 'var(--primary)'; ?>;">
                                            <?php echo $row['role']; ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;"><span class="stat-pill <?php echo ($quota_left > 0) ? 'good' : 'bad'; ?>"><?php echo $quota_left; ?></span></td>
                                    <td style="text-align: center;"><span class="stat-pill <?php echo ($row['strike_count'] > 0) ? 'bad' : 'good'; ?>"><?php echo $row['strike_count']; ?></span></td>
                                    <td><span class="badge <?php echo $status_class; ?>"><span class="material-symbols-outlined" style="font-size: 14px;"><?php echo $status_icon; ?></span> <?php echo $status_label; ?></span></td>
                                    <td style="text-align: right;">
                                        <button class="btn-icon edit" title="Edit User" onclick='openEditModal(<?php echo json_encode($row); ?>)'><span class="material-symbols-outlined" style="font-size: 18px;">edit</span></button>
                                        
                                        <?php if ($row['strike_count'] >= 3): ?>
                                            <button class="btn-icon penalty" title="Waive Penalty" onclick="window.location.href='admin-penalties.php?search=<?php echo $row['user_id']; ?>'"><span class="material-symbols-outlined" style="font-size: 18px; color: #d97706;">healing</span></button>
                                        <?php else: ?>
                                            <button class="btn-icon penalty" title="Issue Strike" onclick='openPenaltyModal(<?php echo json_encode($row); ?>)'><span class="material-symbols-outlined" style="font-size: 18px;">gavel</span></button>
                                        <?php endif; ?>

                                        <button class="btn-icon delete" title="Delete User" onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='admin-users.php?action=delete&id=<?php echo $row['user_id']; ?>'">
                                            <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center; padding: 40px; color: var(--text-muted);">No users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL -->
    <div class="modal-overlay" id="addModal">
        <div class="modal-box">
            <h3 style="margin-bottom: 20px;">Add New User</h3>
            <form action="admin-users.php" method="POST">
                <div class="form-group"><label>Full Name</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" required></div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;"><label>Role</label><select name="role" class="form-control"><option value="Student">Student</option><option value="Lecturer">Lecturer</option></select></div>
                    <div style="flex: 1;"><label>Booking Limit</label><input type="number" name="booking_quota" value="2" class="form-control" required></div>
                </div>
                <div class="form-group"><label>Contact Number</label><input type="text" name="contact_no" class="form-control" required></div>
                <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <h3 style="margin-bottom: 20px;">Edit User Profile</h3>
            <form action="admin-users.php" method="POST">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;"><label>Role</label><select name="role" id="edit_role" class="form-control"><option value="Student">Student</option><option value="Lecturer">Lecturer</option></select></div>
                    <div style="flex: 1;"><label>Booking Limit</label><input type="number" name="booking_quota" id="edit_quota" class="form-control" required></div>
                </div>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="text" name="contact_no" id="edit_contact" class="form-control" required>
                </div>
                <!-- ACCOUNT ACTIVATION FEATURE -->
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: var(--background); padding: 12px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <input type="checkbox" name="is_activated" id="edit_activated" style="width: 18px; height: 18px; accent-color: var(--primary);">
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-main);">Activate Account (User can log in)</span>
                    </label>
                </div>
                <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ISSUE PENALTY MODAL -->
    <div class="modal-overlay" id="penaltyModal">
        <div class="modal-box">
            <h3 style="margin-bottom: 20px; color: var(--secondary);">Issue Penalty Strike</h3>
            <p style="font-size: 14px; margin-bottom: 16px;">Issuing strike to: <strong id="penalty_user_name"></strong></p>
            <form action="admin-users.php" method="POST">
                <input type="hidden" name="user_id" id="penalty_user_id">
                <div class="form-group">
                    <label>Offense Category</label>
                    <select name="category" class="form-control">
                        <option>No-Show</option>
                        <option>Facility Damage</option>
                        <option>Priority Abuse</option>
                        <option>Late Equipment Return</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reasoning</label>
                    <textarea name="reason" class="form-control" placeholder="Provide detailed reason..." required></textarea>
                </div>
                <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('penaltyModal')">Cancel</button>
                    <button type="submit" name="submit_penalty" class="btn btn-primary" style="background: var(--secondary);">Confirm Strike</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('addModal').classList.add('show'); }
        
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_name').value = user.name;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_quota').value = user.booking_quota;
            document.getElementById('edit_contact').value = user.contact_no;
            // Set activation checkbox state
            document.getElementById('edit_activated').checked = (user.is_activated == 1);
            document.getElementById('editModal').classList.add('show');
        }

        function openPenaltyModal(user) {
            document.getElementById('penalty_user_id').value = user.user_id;
            document.getElementById('penalty_user_name').innerText = user.name;
            document.getElementById('penaltyModal').classList.add('show');
        }

        function closeModal(id) { document.getElementById(id).classList.remove('show'); }

        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', function(e) { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', function() { if (menu.classList.contains('show')) { menu.classList.remove('show'); } });

        function checkAlerts() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('msg') === 'added') alert("User account created successfully!");
            if (params.get('msg') === 'updated') alert("User profile updated.");
            if (params.get('msg') === 'deleted') alert("User deleted.");
            if (params.get('msg') === 'penalty_issued') alert("Penalty strike issued successfully!");
            if (params.get('msg') === 'email_exists') alert("Error: This email is already registered.");
            if (params.get('msg') === 'error_self') alert("You cannot delete your own admin account!");
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>