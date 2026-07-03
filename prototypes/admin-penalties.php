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
$initials = substr($admin_name, 0, 1); // Fixed the undefined variable error

// --- ACTION LOGIC: WAIVE STRIKE ---
if (isset($_GET['action']) && $_GET['action'] == 'waive' && isset($_GET['uid'])) {
    $uid = mysqli_real_escape_string($conn, $_GET['uid']);
    
    // Reduce strike count by 1 (but not below 0)
    $update_strike = "UPDATE penalty SET strike_count = GREATEST(strike_count - 1, 0) WHERE user_id = '$uid' AND status = 'Active'";
    mysqli_query($conn, $update_strike);
    
    // If strikes fall below 3, reactivate user
    $check = mysqli_query($conn, "SELECT strike_count FROM penalty WHERE user_id = '$uid' AND status = 'Active'");
    $data = mysqli_fetch_assoc($check);
    if ($data['strike_count'] < 3) {
        mysqli_query($conn, "UPDATE user SET status = 'Active' WHERE user_id = '$uid'");
    }
    
    header("Location: admin-penalties.php?msg=waived");
    exit();
}

// --- ACTION LOGIC: LIFT SUSPENSION ---
if (isset($_GET['action']) && $_GET['action'] == 'lift' && isset($_GET['uid'])) {
    $uid = mysqli_real_escape_string($conn, $_GET['uid']);
    
    // Reset strikes to 0 and set user back to Active
    mysqli_query($conn, "UPDATE penalty SET strike_count = 0 WHERE user_id = '$uid'");
    mysqli_query($conn, "UPDATE user SET status = 'Active' WHERE user_id = '$uid'");
    
    header("Location: admin-penalties.php?msg=lifted");
    exit();
}

// --- POST LOGIC: ISSUE MANUAL STRIKE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issue_strike'])) {
    $uid = mysqli_real_escape_string($conn, $_POST['user_id']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $reason_text = mysqli_real_escape_string($conn, $_POST['reason']);
    $full_reason = "[$category] $reason_text";

    // Check current strikes
    $strike_query = mysqli_query($conn, "SELECT strike_count FROM penalty WHERE user_id = '$uid' ORDER BY penalty_id DESC LIMIT 1");
    $current_strikes = (mysqli_num_rows($strike_query) > 0) ? mysqli_fetch_assoc($strike_query)['strike_count'] : 0;
    $new_strike_count = $current_strikes + 1;

    // Insert or Update Penalty
    if (mysqli_num_rows($strike_query) > 0) {
        mysqli_query($conn, "UPDATE penalty SET strike_count = '$new_strike_count', reason = '$full_reason' WHERE user_id = '$uid'");
    } else {
        mysqli_query($conn, "INSERT INTO penalty (user_id, booking_id, reason, strike_count, status) VALUES ('$uid', 0, '$full_reason', '$new_strike_count', 'Active')");
    }

    // Check for Automatic Suspension
    if ($new_strike_count >= 3) {
        mysqli_query($conn, "UPDATE user SET status = 'Suspended' WHERE user_id = '$uid'");
    }

    header("Location: admin-penalties.php?msg=issued");
    exit();
}

// --- FETCH DATA ---
$stats_suspended = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM user WHERE status = 'Suspended'"))['total'];
$stats_at_risk = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM penalty WHERE strike_count = 2"))['total'];

$penalties_query = "
    SELECT p.*, u.name, u.email, u.role, u.status as user_status 
    FROM penalty p 
    JOIN user u ON p.user_id = u.user_id 
    ORDER BY p.strike_count DESC";
$penalties_res = mysqli_query($conn, $penalties_query);

// Helper for dynamic dots
function renderStrikes($count) {
    $html = '<div class="strike-tracker" title="'.$count.' out of 3 strikes">';
    for ($i = 1; $i <= 3; $i++) {
        $filled = ($i <= $count) ? 'filled' : '';
        $html .= '<div class="strike-dot '.$filled.'"></div>';
    }
    $html .= '</div>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Penalty System - MMU Campus Space</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
</head>
<body onload="checkGlobalAlerts()">

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
                <a href="admin-penalties.php" class="admin-nav-item active">
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
                <div>
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Penalty Management</h2>
                </div>
                
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
                <!-- Metrics -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; margin-bottom: 32px;">
                    <div class="card" style="padding: 20px;">
                        <span class="stat-label" style="color: var(--secondary);">Active Suspensions</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value"><?php echo $stats_suspended; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted);">Users currently locked out</span>
                        </div>
                    </div>
                    <div class="card" style="padding: 20px;">
                        <span class="stat-label" style="color: #d97706;">Users At Risk (2 Strikes)</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value"><?php echo $stats_at_risk; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted);">One strike away</span>
                        </div>
                    </div>
                    <div class="card" style="padding: 20px;">
                        <span class="stat-label" style="color: var(--success-text);">Appeal Pending</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value">0</span>
                            <span style="font-size: 13px; color: var(--text-muted);">Awaiting Admin review</span>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="admin-toolbar">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="penaltySearch" onkeyup="searchTable()" placeholder="Search Student Name or ID...">
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button type="button" class="btn btn-primary" style="background-color: var(--secondary);" onclick="openPenaltyModal()">
                            <span class="material-symbols-outlined" style="font-size: 18px;">gavel</span> Issue Manual Strike
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="admin-table-container">
                    <table class="admin-table" id="penaltyTable">
                        <thead>
                            <tr>
                                <th>User Info</th>
                                <th>Latest Offense & Reason</th>
                                <th>Strike Count</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($penalties_res)): ?>
                            <tr style="<?php echo ($row['user_status'] == 'Suspended') ? 'background-color: rgba(187, 0, 19, 0.02);' : ''; ?>">
                                <td>
                                    <strong style="color: <?php echo ($row['user_status'] == 'Suspended') ? 'var(--secondary)' : 'var(--text-main)'; ?>;">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </strong><br>
                                    <span class="table-meta-text"><?php echo htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td>
                                    <div class="penalty-reason"><?php echo htmlspecialchars($row['reason']); ?></div>
                                </td>
                                <td><?php echo renderStrikes($row['strike_count']); ?></td>
                                <td>
                                    <?php if($row['user_status'] == 'Suspended'): ?>
                                        <span class="badge badge-danger">Suspended</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #fef3c7; color: #92400e;">Warning</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if($row['user_status'] == 'Suspended'): ?>
                                        <button class="btn-icon edit" onclick="if(confirm('Lift suspension for this user?')) window.location.href='admin-penalties.php?action=lift&uid=<?php echo $row['user_id']; ?>'" title="Lift Suspension"><span class="material-symbols-outlined">lock_open</span></button>
                                    <?php else: ?>
                                        <div style="display: inline-flex; gap: 8px;">
                                            <button class="btn-icon penalty" onclick="if(confirm('Waive one strike for this user?')) window.location.href='admin-penalties.php?action=waive&uid=<?php echo $row['user_id']; ?>'" title="Waive Strike"><span class="material-symbols-outlined">healing</span></button>
                                            <button type="button" class="btn-icon delete" onclick="openPenaltyModal('<?php echo $row['user_id']; ?>')" title="Issue Another Strike"><span class="material-symbols-outlined">add_circle</span></button>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL FOR MANUAL STRIKE -->
    <div class="modal-overlay" id="manualStrikeModal">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-icon" style="background:#fee2e2; color:var(--secondary);"><span class="material-symbols-outlined">gavel</span></div>
                <h3 class="modal-title">Issue Manual Strike</h3>
            </div>
            <form action="admin-penalties.php" method="POST">
                <div class="form-group">
                    <label>Target User</label>
                    <select name="user_id" id="modal_user_select" class="form-control" required>
                        <option value="">Select User...</option>
                        <?php 
                        $users = mysqli_query($conn, "SELECT user_id, name FROM user WHERE role != 'Admin'");
                        while($u = mysqli_fetch_assoc($users)) echo "<option value='".$u['user_id']."'>".htmlspecialchars($u['name'])."</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Offense Category</label>
                    <select name="category" class="form-control" required>
                        <option>No-Show</option>
                        <option>Vandalism / Damage</option>
                        <option>Priority Abuse</option>
                        <option>Late Equipment Return</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reasoning</label>
                    <textarea name="reason" class="form-control" placeholder="Provide details for this strike..." required></textarea>
                </div>
                <div class="modal-actions" style="margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="issue_strike" class="btn btn-primary" style="background: var(--secondary);">Confirm Strike</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Profile Dropdown Logic
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

        function openPenaltyModal(userId = null) {
            const modal = document.getElementById('manualStrikeModal');
            const userSelect = document.getElementById('modal_user_select');
            
            if(userId) {
                userSelect.value = userId;
            } else {
                userSelect.value = "";
            }
            
            modal.classList.add('show');
        }

        function closeModal() {
            document.getElementById('manualStrikeModal').classList.remove('show');
        }

        function checkGlobalAlerts() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'issued') {
                alert("Penalty strike has been issued successfully.");
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.get('msg') === 'waived') {
                alert("One strike has been waived.");
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.get('msg') === 'lifted') {
                alert("User suspension has been lifted.");
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function searchTable() {
            let input = document.getElementById("penaltySearch").value.toLowerCase();
            let rows = document.getElementById("penaltyTable").getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = rows[i].innerText.toLowerCase().includes(input) ? "" : "none";
            }
        }
    </script>
</body>
</html>