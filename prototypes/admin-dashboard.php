<?php
session_start();
include('../PHP/db_config.php');

// 1. SECURITY CHECK: Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];
$initials = substr($admin_name, 0, 1);

// 2. FETCH METRICS
// Count Pending Priority Overrides
$priority_count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE is_priority = 1 AND status = 'Pending'");
$priority_count = mysqli_fetch_assoc($priority_count_res)['total'];

// Count Open Issue Reports (Using the corrected table name 'issue_report')
$issue_count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM issue_report WHERE status = 'Open'");
$issue_count = mysqli_fetch_assoc($issue_count_res)['total'];

// Count Suspended Users (Users with 3 or more strikes)
$suspended_res = mysqli_query($conn, "SELECT COUNT(DISTINCT user_id) as total FROM penalty WHERE strike_count >= 3 AND status = 'Active'");
$suspended_count = mysqli_fetch_assoc($suspended_res)['total'];

// Count Today's Usage (Approved bookings for today)
$usage_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE booking_date = CURDATE() AND status = 'Approved'");
$usage_count = mysqli_fetch_assoc($usage_res)['total'];


// 3. FETCH PENDING OVERRIDES TABLE
$overrides_query = "SELECT b.*, u.name as lecturer_name, u.email, f.facility_name, f.location 
                    FROM booking b 
                    JOIN user u ON b.user_id = u.user_id 
                    JOIN facility f ON b.facility_id = f.facility_id 
                    WHERE b.is_priority = 1 AND b.status = 'Pending' 
                    ORDER BY b.booking_date ASC";
$overrides_result = mysqli_query($conn, $overrides_query);

// 4. FETCH RECENT ISSUE REPORTS SIDEBAR
$reports_query = "SELECT r.*, f.facility_name, u.name as reporter_name 
                  FROM issue_report r 
                  JOIN facility f ON r.facility_id = f.facility_id 
                  JOIN user u ON r.user_id = u.user_id 
                  ORDER BY r.report_date DESC LIMIT 5";
$reports_result = mysqli_query($conn, $reports_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Admin Dashboard - MMU Campus Space</title>
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
                <a href="admin-dashboard.php" class="admin-nav-item active">
                    <span class="material-symbols-outlined">dashboard</span> Dashboard Overview
                </a>
                <a href="admin-bookings.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">calendar_month</span> Manage Bookings
                </a>
                <a href="facilities.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">meeting_room</span> Manage Facilities
                </a>
                <a href="#" class="admin-nav-item">
                    <span class="material-symbols-outlined">cable</span> Manage Equipment
                </a>
                <a href="#" class="admin-nav-item">
                    <span class="material-symbols-outlined">group</span> Manage Users & Quotas
                </a>
                <a href="admin-penalties.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">gavel</span> Penalty System
                </a>
                <a href="admin-reports.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">report</span> Issue Reports
                </a>
                <a href="#" class="admin-nav-item">
                    <span class="material-symbols-outlined">campaign</span> Announcements
                </a>
            </nav>
        </aside>

        <!-- Main Area -->
        <main class="admin-main">
            
            <!-- Topbar -->
            <header class="admin-topbar">
                <div>
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Dashboard Overview</h2>
                </div>
                
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <span class="material-symbols-outlined" style="color: var(--text-muted);">notifications</span>
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo strtoupper($initials); ?></div>
                    <span class="profile-name" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>
                    
                    <div class="profile-menu" id="profileMenu">
                        <a href="#"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                        <a href="#"><span class="material-symbols-outlined">settings</span> System Settings</a>
                        <div style="border-top: 1px solid rgba(194, 198, 211, 0.3); margin: 4px 0;"></div>
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                
                <!-- Metrics Grid (DYNAMIC) -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px; margin-bottom: 32px;">
                    
                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label" style="color: var(--secondary);">Action Required</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value"><?php echo $priority_count; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Priority Overrides</span>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label" style="color: #856404;">Pending Review</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value"><?php echo $issue_count; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Open Issue Reports</span>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label">Active Penalties</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value"><?php echo $suspended_count; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Suspended Users</span>
                        </div>
                    </div>

                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label">Today's Usage</span>
                        <div style="display: flex; align-items: flex-end; gap: 12px; margin-top: 8px;">
                            <span class="stat-value"><?php echo $usage_count; ?></span>
                            <span style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Active Bookings</span>
                        </div>
                    </div>
                </div>

                <!-- Split Content: Tables -->
                <div class="dashboard-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 24px;">
                    
                    <!-- Left: Pending Overrides Table (DYNAMIC) -->
                    <div style="grid-column: span 3;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h3 style="font-size: 18px; font-weight: 600; color: var(--text-main);">Pending Priority Overrides</h3>
                        </div>
                        
                        <div class="admin-table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Lecturer</th>
                                        <th>Facility</th>
                                        <th>Date & Time</th>
                                        <th>Proof</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(mysqli_num_rows($overrides_result) > 0): ?>
                                        <?php while($row = mysqli_fetch_assoc($overrides_result)): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--primary);"><?php echo htmlspecialchars($row['lecturer_name']); ?></strong><br>
                                                <span style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></span>
                                            </td>
                                            <td style="font-weight: 500;"><?php echo htmlspecialchars($row['facility_name']); ?></td>
                                            <td style="color: var(--text-muted); white-space: nowrap;">
                                                <?php echo date("d M", strtotime($row['booking_date'])); ?> • <?php echo date("g:i A", strtotime($row['start_time'])); ?>
                                            </td>
                                            <td>
                                                <?php if($row['proof_file']): ?>
                                                <a href="../public/uploads/proofs/<?php echo $row['proof_file']; ?>" target="_blank" style="color: var(--primary); font-size: 13px; display: inline-flex; align-items: center; gap: 4px; background: rgba(0,61,124,0.05); padding: 4px 8px; border-radius: 6px; font-weight: 500; white-space: nowrap;">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">description</span> View File
                                                </a>
                                                <?php else: ?>
                                                    <span style="font-size: 11px; color: gray;">No proof</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; gap: 8px; justify-content: center;">
                                                    <button class="btn btn-primary" style="padding: 6px 12px; font-size: 12px; border-radius: 6px;">Approve</button>
                                                    <button class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; border-radius: 6px; color: var(--secondary); border-color: rgba(187,0,19,0.3); background: rgba(187,0,19,0.02);">Reject</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" style="text-align:center; padding: 20px; color: gray;">No pending overrides found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right: Latest Issue Reports (DYNAMIC) -->
                    <div style="grid-column: span 1;">
                        <div style="margin-bottom: 16px;">
                            <h3 style="font-size: 18px; font-weight: 600; color: var(--text-main);">Recent Issue Reports</h3>
                        </div>

                        <div class="card" style="padding: 0; overflow: hidden; height: auto;">
                            <div class="list-group" style="padding-bottom: 0; gap: 0;">
                                <?php if(mysqli_num_rows($reports_result) > 0): ?>
                                    <?php while($report = mysqli_fetch_assoc($reports_result)): ?>
                                    <div class="report-summary-item" style="padding: 16px; border-bottom: 1px solid rgba(194, 198, 211, 0.2);">
                                        <div class="report-item-top" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <h4 style="font-size: 11px; font-weight: 700; color: var(--text-main); text-transform: uppercase;"><?php echo htmlspecialchars($report['issue_type']); ?></h4>
                                            <span class="badge" style="background: #fef3c7; color: #92400e; padding: 2px 8px; font-size: 10px; border-radius: 50px;"><?php echo $report['status']; ?></span>
                                        </div>
                                        <div class="report-item-body">
                                            <p style="font-size: 13px; font-weight: 600; color: var(--text-main); margin-bottom: 2px;"><?php echo htmlspecialchars($report['facility_name']); ?></p>
                                            <p style="font-size: 12px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">By: <?php echo htmlspecialchars($report['reporter_name']); ?></p>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div style="padding: 20px; text-align: center; color: gray; font-size: 13px;">No issues reported yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            </div>
        </main>
    </div>
    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', function(e) { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', function() { if (menu.classList.contains('show')) { menu.classList.remove('show'); } });
    </script>
</body>
</html>