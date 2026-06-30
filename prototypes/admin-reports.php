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

// --- ACTION LOGIC (QUICK ACTIONS VIA GET) ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $rid = mysqli_real_escape_string($conn, $_GET['id']);
    
    // ACTION 3: ISSUE PENALTY 
    if ($_GET['action'] == 'penalty') {
        $report_query = mysqli_query($conn, "SELECT facility_id, report_date FROM issue_report WHERE report_id = '$rid'");
        $report_data = mysqli_fetch_assoc($report_query);
        
        if ($report_data) {
            $fid = $report_data['facility_id'];
            $rdate = $report_data['report_date'];

            $prev_user_query = "SELECT user_id, booking_id FROM booking 
                                WHERE facility_id = '$fid' AND status = 'Approved' AND booking_date <= '$rdate' 
                                ORDER BY booking_date DESC, start_time DESC LIMIT 1";
            $prev_user_res = mysqli_query($conn, $prev_user_query);

            if (mysqli_num_rows($prev_user_res) > 0) {
                $prev_row = mysqli_fetch_assoc($prev_user_res);
                $prev_uid = $prev_row['user_id'];
                $booking_id = $prev_row['booking_id'];
                
                $strike_check = mysqli_query($conn, "SELECT MAX(strike_count) as last_strike FROM penalty WHERE user_id = '$prev_uid'");
                $strike_data = mysqli_fetch_assoc($strike_check);
                $new_strike_count = ($strike_data['last_strike'] ?? 0) + 1;

                $reason = "Damaged/Misused facility (Ref: Issue Report #RP-$rid)";
                
                $existing_penalty = mysqli_query($conn, "SELECT COUNT(*) as count FROM penalty WHERE reason LIKE '%#RP-$rid%'");
                if (mysqli_fetch_assoc($existing_penalty)['count'] == 0) {
                    mysqli_query($conn, "INSERT INTO penalty (user_id, booking_id, reason, strike_count, status) 
                                         VALUES ('$prev_uid', '$booking_id', '$reason', '$new_strike_count', 'Active')");
                    header("Location: admin-reports.php?penalty_status=approved");
                } else {
                    header("Location: admin-reports.php?penalty_status=already_given");
                }
                exit();
            } else {
                header("Location: admin-reports.php?penalty_status=no_user");
                exit();
            }
        }
    }

    if ($_GET['action'] == 'set_status') {
        $new_status = mysqli_real_escape_string($conn, $_GET['status']);
        mysqli_query($conn, "UPDATE issue_report SET status = '$new_status' WHERE report_id = '$rid'");
        header("Location: admin-reports.php");
        exit();
    }
}

// --- UPDATED POST LOGIC: UPDATE STATUS & SEND NOTIFICATION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_report'])) {
    $rid = mysqli_real_escape_string($conn, $_POST['report_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $reply = mysqli_real_escape_string($conn, $_POST['admin_reply']);
    $today = date('Y-m-d');
    
    // 1. Update the report table
    $update_sql = "UPDATE issue_report SET 
                   status = '$new_status', 
                   admin_reply = '$reply', 
                   reply_date = '$today' 
                   WHERE report_id = '$rid'";
                   
    if(mysqli_query($conn, $update_sql)) {
        // 2. Fetch reporter user_id to send notification
        $user_res = mysqli_query($conn, "SELECT user_id FROM issue_report WHERE report_id = '$rid'");
        $user_data = mysqli_fetch_assoc($user_res);
        $reporter_id = $user_data['user_id'];

        // 3. Insert notification for the reporter
        $notif_title = "Update on Report #RP-" . $rid;
        $notif_message = "Your report status is now: " . $new_status . ". Message: " . $reply;
        
        $notif_sql = "INSERT INTO notification (user_id, title, message, is_read, date_sent) 
                      VALUES ('$reporter_id', '$notif_title', '$notif_message', 0, NOW())";
        mysqli_query($conn, $notif_sql);

        header("Location: admin-reports.php?update_status=success");
        exit();
    }
}

// Counter Metrics
$open_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issue_report WHERE status = 'Under Review'"))['total'];
$in_progress_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issue_report WHERE status = 'In Progress'"))['total'];
$resolved_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM issue_report WHERE status = 'Resolved'"))['total'];

// Fetch Table Data
$reports_result = mysqli_query($conn, "
    SELECT r.*, f.facility_name, u.name as reporter_name,
    (SELECT COUNT(*) FROM penalty p WHERE p.reason LIKE CONCAT('%#RP-', r.report_id, '%')) as penalty_exists
    FROM issue_report r 
    JOIN facility f ON r.facility_id = f.facility_id 
    JOIN user u ON r.user_id = u.user_id 
    ORDER BY r.report_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Issue Reports - MMU Campus Space</title>
    <link rel="stylesheet" href="../public/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300,0..1&display=swap" rel="stylesheet"/>
    <style>
        .metric-card-content { display: flex; align-items: baseline; gap: 8px; margin-top: 8px; }
        .metric-sub-label { font-size: 13px; color: #6b7280; font-weight: 400; }
        .filter-controls-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 16px; }
        .search-input-wrapper { position: relative; flex: 1; max-width: 320px; }
        .search-input-wrapper input { width: 100%; padding: 10px 16px 10px 38px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; background-color: #fff; }
        .search-input-wrapper .search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 20px; }
        .filter-selects { display: flex; gap: 12px; }
        .filter-selects select { padding: 10px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #fff; font-size: 14px; color: #374151; min-width: 140px; }
    </style>
</head>
<body onload="checkGlobalAlerts()">

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
                <a href="admin-reports.php" class="admin-nav-item active">
                    <span class="material-symbols-outlined">report</span> Issue Reports
                </a>
                <a href="#" class="admin-nav-item">
                    <span class="material-symbols-outlined">campaign</span> Announcements
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Issue Reports</h2>
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
                <div class="dashboard-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; margin-bottom: 32px;">
                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label" style="color: #b91c1c; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Open Reports</span>
                        <div class="metric-card-content">
                            <span class="stat-value" style="font-size: 28px; font-weight: 700; color: #111827;"><?php echo (int)$open_count; ?></span>
                            <span class="metric-sub-label">Require immediate review</span>
                        </div>
                    </div>
                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label" style="color: #2563eb; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">In Progress</span>
                        <div class="metric-card-content">
                            <span class="stat-value" style="font-size: 28px; font-weight: 700; color: #111827;"><?php echo (int)$in_progress_count; ?></span>
                            <span class="metric-sub-label">Assigned to maintenance</span>
                        </div>
                    </div>
                    <div class="card" style="margin-bottom: 0; padding: 20px;">
                        <span class="stat-label" style="color: #16a34a; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Resolved (7 Days)</span>
                        <div class="metric-card-content">
                            <span class="stat-value" style="font-size: 28px; font-weight: 700; color: #111827;"><?php echo (int)$resolved_count; ?></span>
                            <span class="metric-sub-label">Successfully closed</span>
                        </div>
                    </div>
                </div>

                <div class="filter-controls-row">
                    <div class="search-input-wrapper">
                        <span class="material-symbols-outlined search-icon">search</span>
                        <input type="text" id="reportSearch" placeholder="Search Report ID or Facility..." onkeyup="filterReportsTable()">
                    </div>
                    <div class="filter-selects">
                        <select id="statusFilter" onchange="filterReportsTable()">
                            <option value="All">All Statuses</option>
                            <option value="Open">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                        <select id="categoryFilter" onchange="filterReportsTable()">
                            <option value="All">All Categories</option>
                            <option value="Equipment Damage">Equipment Damage</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Misuse by Previous User">Misuse by Previous User</option>
                        </select>
                    </div>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table" id="reportsTable">
                        <thead>
                            <tr>
                                <th>Report Info</th>
                                <th>Facility & Category</th>
                                <th>Description & Evidence</th>
                                <th style="text-align: center;">Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($reports_result)): ?>
                            <tr class="report-row-item" data-status="<?php echo htmlspecialchars($row['status'] == 'Under Review' ? 'Open' : $row['status']); ?>" data-category="<?php echo htmlspecialchars($row['issue_type']); ?>">
                                <td>
                                    <strong style="color: #111827; font-size: 14px;">#RP-<?php echo htmlspecialchars($row['report_id']); ?></strong><br>
                                    <span class="table-meta-text" style="color: #6b7280; font-size: 12px;">By: <?php echo htmlspecialchars($row['reporter_name']); ?></span><br>
                                    <span class="table-meta-text" style="color: #9ca3af; font-size: 11px;"><?php echo date('M d • g:i A', strtotime($row['report_date'])); ?></span>
                                </td>
                                <td>
                                    <strong style="color: #111827; font-size: 14px;"><?php echo htmlspecialchars($row['facility_name']); ?></strong><br>
                                    <span class="table-meta-text" style="color: #dc2626; font-size: 12px; font-weight: 500;"><?php echo htmlspecialchars($row['issue_type']); ?></span>
                                </td>
                                <td style="max-width: 320px;">
                                    <div style="display: flex; gap: 12px; align-items: flex-start;">
                                        <?php if(!empty($row['issue_image'])): ?>
                                            <img src="../public/uploads/issues/<?php echo htmlspecialchars($row['issue_image']); ?>" class="report-thumb-fixed" alt="Evidence" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e7eb;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f3f4f6; color: #9ca3af; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid #e5e7eb;"><span class="material-symbols-outlined" style="font-size: 20px;">image_not_supported</span></div>
                                        <?php endif; ?>
                                        <div style="font-size: 13px; color: #4b5563; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($row['description']); ?></div>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <?php 
                                        if ($row['status'] == 'Under Review') {
                                            echo '<span class="badge" style="background: #fef3c7; color: #92400e; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 12px;">Open</span>';
                                        } elseif ($row['status'] == 'In Progress') {
                                            echo '<span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 12px;">In Progress</span>';
                                        } elseif ($row['status'] == 'Resolved') {
                                            echo '<span class="badge" style="background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 12px;">Resolved</span>';
                                        }
                                    ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                        <?php if($row['status'] == 'Under Review'): ?>
                                            <button type="button" class="btn-icon edit" onclick='openModal(<?php echo json_encode($row); ?>)' title="Review and Reply"><span class="material-symbols-outlined">chat</span></button>
                                            <a href="admin-reports.php?action=set_status&status=In%20Progress&id=<?php echo urlencode($row['report_id']); ?>" class="btn-icon edit" style="color: #0369a1; border-color: rgba(3,105,161,0.2);" title="Set to In Progress"><span class="material-symbols-outlined">engineering</span></a>
                                            <button type="button" class="btn-icon delete" onclick="handlePenalty(<?php echo (int)$row['report_id']; ?>, <?php echo (int)$row['penalty_exists']; ?>)" title="Issue Penalty Strike"><span class="material-symbols-outlined">gavel</span></button>
                                        
                                        <?php elseif($row['status'] == 'In Progress'): ?>
                                            <button type="button" class="btn-icon edit" onclick='openModal(<?php echo json_encode($row); ?>)' title="Review and Reply"><span class="material-symbols-outlined">chat</span></button>
                                            <a href="admin-reports.php?action=set_status&status=Resolved&id=<?php echo urlencode($row['report_id']); ?>" class="btn-icon edit" style="color: var(--success-text); border-color: rgba(22,101,52,0.2);" title="Mark as Resolved"><span class="material-symbols-outlined">check_circle</span></a>
                                        
                                        <?php else: ?>
                                            <button type="button" class="btn-icon edit" onclick='openModal(<?php echo json_encode($row); ?>)' title="View Details"><span class="material-symbols-outlined">visibility</span></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="reportDetailsModal">
        <div class="modal-box modal-box-large">
            <form action="admin-reports.php" method="POST">
                <input type="hidden" name="report_id" id="modal_report_id">
                <div style="background-color: var(--surface); padding: 16px 24px; border-bottom: 1px solid rgba(194, 198, 211, 0.4); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 0;">Report Details <span style="color: var(--text-muted); font-weight: 500;" id="modal_id_label"></span></h3>
                        <span class="badge" id="modal_status_badge" style="padding: 2px 8px; font-size: 10px;"></span>
                    </div>
                    <button type="button" class="btn-icon close-modal" style="color: var(--text-muted); background: white;"><span class="material-symbols-outlined">close</span></button>
                </div>
                <div style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 24px;">
                        <div style="display: flex; flex-direction: column; gap: 24px;">
                            <div>
                                <h4 class="modal-section-title with-line">Reporter Info</h4>
                                <p style="font-size: 14px; font-weight: 600;" id="modal_reporter"></p>
                                <p style="font-size: 12px; color: var(--text-muted);" id="modal_date"></p>
                            </div>
                            <div>
                                <h4 class="modal-section-title with-line">Facility & Category</h4>
                                <p style="font-size: 14px; font-weight: 600;" id="modal_facility"></p>
                                <p style="font-size: 13px; color: var(--secondary); font-weight: 500;" id="modal_type"></p>
                            </div>
                        </div>
                        <div>
                            <h4 class="modal-section-title">Evidence Photo</h4>
                            <div class="modal-img-wrapper" id="openLightboxBtn">
                                <img src="" alt="Evidence" id="reportImageSource">
                                <div style="position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.7); color: white; font-size: 11px; font-weight: 500; padding: 6px 10px; border-radius: 6px; pointer-events: none;">Click to enlarge</div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 24px;">
                        <h4 class="modal-section-title">Detailed Description</h4>
                        <div class="scrollable-desc" id="modal_desc" style="padding: 16px; font-size: 13px; max-height: 80px; overflow-y: auto; background: #f9fafb; border-radius: 6px;"></div>
                    </div>
                    <div style="background-color: #f8f9ff; border: 1px solid rgba(194, 198, 211, 0.4); border-radius: 8px; padding: 20px;">
                        <h4 class="modal-section-title" style="margin-bottom: 16px;">Admin Action & Reply</h4>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">Update Work Status</label>
                        <select name="status" class="form-control" style="margin-bottom: 12px; width: 100%;" id="modal_status_select" required>
                            <option value="Under Review">Open</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                        </select>
                        <label style="display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px;">Message to Reporter</label>
                        <textarea name="admin_reply" class="form-control" id="modal_reply" placeholder="Write a response..." style="width: 100%; min-height: 80px; border: 1px solid #c2c6d3; border-radius: 6px; padding: 10px;" required></textarea>
                        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 12px;">
                            <button type="submit" name="update_report" class="btn btn-primary">Update Status</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- LIGHTBOX -->
    <div class="lightbox-overlay" id="imageLightbox">
        <img src="" alt="Enlarged View" id="lightboxImage">
    </div>

    <script>
        function checkGlobalAlerts() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Check for status update success
            if (urlParams.get('update_status') === 'success') {
                alert("The reply has been sent to the reporter");
                window.history.replaceState({}, document.title, window.location.pathname);
            }

            // Check for penalty status
            if (urlParams.get('penalty_status') === 'approved') {
                alert("Penalty Strike Approved");
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.get('penalty_status') === 'already_given') {
                alert("You have already given the Penalty Strike to this user");
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (urlParams.get('penalty_status') === 'no_user') {
                alert("There was no previous user to strike");
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function filterReportsTable() {
            const searchQuery = document.getElementById('reportSearch').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const rows = document.querySelectorAll('.report-row-item');

            rows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowCategory = row.getAttribute('data-category');
                const matchesSearch = textContent.includes(searchQuery);
                const matchesStatus = (statusFilter === 'All' || rowStatus === statusFilter);
                const matchesCategory = (categoryFilter === 'All' || rowCategory === categoryFilter);
                row.style.display = (matchesSearch && matchesStatus && matchesCategory) ? '' : 'none';
            });
        }

        function handlePenalty(reportId, exists) {
            if (exists > 0) {
                alert("You have already given the Penalty Strike to this user");
            } else {
                if (confirm("Are you sure you want to issue a penalty?")) {
                    window.location.href = "admin-reports.php?action=penalty&id=" + reportId;
                }
            }
        }

        function openModal(data) {
            document.getElementById('modal_report_id').value = data.report_id;
            document.getElementById('modal_id_label').innerText = '#RP-' + data.report_id;
            document.getElementById('modal_reporter').innerText = data.reporter_name;
            document.getElementById('modal_date').innerText = data.report_date;
            document.getElementById('modal_facility').innerText = data.facility_name;
            document.getElementById('modal_type').innerText = data.issue_type;
            document.getElementById('modal_desc').innerText = data.description;
            document.getElementById('modal_reply').value = data.admin_reply || '';
            document.getElementById('modal_status_select').value = data.status;
            
            const img = document.getElementById('reportImageSource');
            const lightboxBtn = document.getElementById('openLightboxBtn');
            if (data.issue_image) {
                img.src = "../public/uploads/issues/" + data.issue_image;
                lightboxBtn.style.display = 'block';
            } else {
                img.src = "";
                lightboxBtn.style.display = 'none';
            }

            const badge = document.getElementById('modal_status_badge');
            if (data.status == 'Under Review') {
                badge.innerText = 'Open'; badge.style.background = '#fef3c7'; badge.style.color = '#92400e';
            } else if (data.status == 'In Progress') {
                badge.innerText = 'In Progress'; badge.style.background = '#e0f2fe'; badge.style.color = '#0369a1';
            } else {
                badge.innerText = 'Resolved'; badge.style.background = '#dcfce7'; badge.style.color = '#166534';
            }
            
            document.getElementById('reportDetailsModal').classList.add('show');
        }

        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('reportDetailsModal').classList.remove('show');
            });
        });

        document.getElementById('openLightboxBtn').addEventListener('click', () => {
            const lightbox = document.getElementById('imageLightbox');
            document.getElementById('lightboxImage').src = document.getElementById('reportImageSource').src;
            lightbox.style.display = 'flex';
        });

        document.getElementById('imageLightbox').addEventListener('click', () => {
            document.getElementById('imageLightbox').style.display = 'none';
        });
    </script>
</body>
</html>