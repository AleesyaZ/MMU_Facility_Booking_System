<?php
session_start();
include('../PHP/db_config.php');

// SECURITY CHECK: Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];
$initials = substr($admin_name, 0, 1);

// CAPTURE FILTER/SEARCH CRITERIA
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_date = isset($_GET['date']) ? mysqli_real_escape_string($conn, $_GET['date']) : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : 'All Statuses';

// CONSTRUCT DYNAMIC SQL MATCHES
$where_clauses = [];

if (!empty($search_query)) {
    $where_clauses[] = "(b.booking_id LIKE '%$search_query%' OR u.name LIKE '%$search_query%')";
}
if (!empty($filter_date)) {
    $where_clauses[] = "b.booking_date = '$filter_date'";
}
if ($filter_status !== 'All Statuses') {
    $where_clauses[] = "b.status = '$filter_status'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// FETCH LIVE BOOKING ENTRIES
$bookings_query = "
    SELECT 
        b.*, 
        u.name AS user_name, 
        u.role AS user_role, 
        f.facility_name, 
        f.location,
        GROUP_CONCAT(CONCAT(be.quantity, 'x ', e.name) SEPARATOR '<br>') AS allocated_equipment
    FROM booking b
    JOIN user u ON b.user_id = u.user_id
    JOIN facility f ON b.facility_id = f.facility_id
    LEFT JOIN booking_equipment be ON b.booking_id = be.booking_id
    LEFT JOIN equipment e ON be.equip_id = e.equip_id
    $where_sql
    GROUP BY b.booking_id
    ORDER BY b.booking_date DESC, b.start_time DESC
";
$bookings_result = mysqli_query($conn, $bookings_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Bookings - MMU MMU Facility Booking System</title>
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
                <a href="admin-bookings.php" class="admin-nav-item active">
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

        <main class="admin-main">
            
            <header class="admin-topbar">
                <div>
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">All Bookings</h2>
                </div>
                
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo strtoupper($initials); ?></div>
                    <span class="profile-name" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>
                    
                    <div class="profile-menu" id="profileMenu">
                        <a href="admin-profile.php"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                        <div style="border-top: 1px solid rgba(194, 198, 211, 0.3); margin: 4px 0;"></div>
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                
                <form method="GET" action="admin-bookings.php" class="admin-toolbar" style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 24px;">
                    <div class="search-wrapper" style="display: flex; align-items: center; gap: 8px; flex-grow: 1; max-width: 400px; position: relative;">
                        <span class="material-symbols-outlined" style="position: absolute; left: 12px; color: var(--text-muted);">search</span>
                        <input type="text" name="search" placeholder="Search Booking ID or User Name..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 100%; padding: 8px 12px 8px 38px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                    
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <input type="date" name="date" class="form-control" style="width: 140px; padding: 8px 12px; color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 6px;" value="<?php echo htmlspecialchars($filter_date); ?>" onchange="this.form.submit();">
                        
                        <select name="status" class="form-control" style="width: 140px; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px;" onchange="this.form.submit();">
                            <option value="All Statuses" <?php if($filter_status === 'All Statuses') echo 'selected'; ?>>All Statuses</option>
                            <option value="Pending" <?php if($filter_status === 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Approved" <?php if($filter_status === 'Approved') echo 'selected'; ?>>Approved</option>
                            <option value="Cancelled" <?php if($filter_status === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                            <option value="Rejected" <?php if($filter_status === 'Rejected') echo 'selected'; ?>>Rejected</option>
                        </select>

                        <?php if(!empty($search_query) || !empty($filter_date) || $filter_status !== 'All Statuses'): ?>
                            <a href="admin-bookings.php" class="btn btn-outline" style="padding: 8px 12px; color: var(--secondary); border: 1px solid var(--secondary); border-radius: 6px; text-decoration: none; font-size: 13px;">Clear</a>
                        <?php endif; ?>

                        <button type="button" class="btn btn-outline" style="padding: 8px 16px; border: 1px solid var(--border-color); border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background: #fff;" onclick="window.print();">
                            <span class="material-symbols-outlined" style="font-size: 18px;">download</span> Export CSV
                        </button>
                    </div>
                </form>

                <div class="admin-table-container">
                    <table class="admin-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="border-bottom: 2px solid var(--border-color);">
                                <th style="padding: 12px 8px;">Booking ID</th>
                                <th style="padding: 12px 8px;">User Info</th>
                                <th style="padding: 12px 8px;">Facility</th>
                                <th style="padding: 12px 8px;">Date & Time</th>
                                <th style="padding: 12px 8px;">Equipment</th>
                                <th style="padding: 12px 8px;">Status</th>
                                <th style="text-align: right; padding: 12px 8px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($bookings_result)): 
                                    $row_style = ($row['is_priority'] == 1) ? 'style="background-color: rgba(255, 243, 205, 0.3); border-bottom: 1px solid rgba(194,198,211,0.5);"' : 'style="border-bottom: 1px solid rgba(194,198,211,0.5);"';
                                    
                                    $badge_style = 'style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 13px;"';
                                    $badge_class = 'badge-pending';
                                    $icon = 'schedule';
                                    
                                    if ($row['status'] === 'Pending') {
                                        if ($row['is_priority'] == 1) {
                                            $badge_class = 'badge';
                                            $badge_style = 'style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 13px; background: #fff3cd; color: #856404;"';
                                        } else {
                                            $badge_class = 'badge-pending';
                                        }
                                    } elseif ($row['status'] === 'Approved') { 
                                        $badge_class = 'badge-approved'; 
                                        $icon = 'check_circle'; 
                                    } elseif ($row['status'] === 'Cancelled') { 
                                        $badge_class = 'badge-cancelled'; 
                                        $icon = 'cancel'; 
                                    } elseif ($row['status'] === 'Rejected') { 
                                        $badge_class = 'badge-cancelled'; 
                                        $badge_style = 'style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 13px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;"'; 
                                        $icon = 'cancel'; 
                                    }
                                ?>
                                    <tr <?php echo $row_style; ?>>
                                        <td style="padding: 12px 8px;"><strong style="color: var(--text-main);">#BK-<?php echo $row['booking_id']; ?></strong></td>
                                        <td style="padding: 12px 8px;">
                                            <strong style="<?php echo ($row['user_role'] === 'Lecturer') ? 'color: var(--primary);' : 'color: var(--text-main);'; ?> display: block;">
                                                <?php echo htmlspecialchars($row['user_name']); ?>
                                            </strong>
                                            <span class="table-meta-text" style="display: block; font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['user_role']); ?><?php if($row['is_priority'] == 1) echo ' (Priority)'; ?></span>
                                        </td>
                                        <td style="padding: 12px 8px;">
                                            <span style="font-weight: 500;"><?php echo htmlspecialchars($row['facility_name']); ?></span><br>
                                            <span class="table-meta-text" style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['location']); ?></span>
                                        </td>
                                        <td style="white-space: nowrap; padding: 12px 8px;">
                                            <span><?php echo date("M d, Y", strtotime($row['booking_date'])); ?></span><br>
                                            <span class="table-meta-text" style="font-size: 12px; color: var(--text-muted);">
                                                <?php echo date("g:i A", strtotime($row['start_time'])); ?> - <?php echo date("g:i A", strtotime($row['end_time'])); ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 8px;">
                                            <span style="font-size: 13px; color: var(--text-muted);">
                                                <?php echo $row['allocated_equipment'] ? $row['allocated_equipment'] : 'None'; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px 8px;">
                                            <span class="<?php echo $badge_class; ?>" <?php echo $badge_style; ?>>
                                                <span class="material-symbols-outlined" style="font-size: 16px;"><?php echo $icon; ?></span> 
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap; padding: 12px 8px;">
                                            <button class="btn-icon edit view-details-trigger" 
                                                    title="View Details"
                                                    style="background: none; border: none; cursor: pointer; padding: 4px;"
                                                    data-id="<?php echo $row['booking_id']; ?>"
                                                    data-user="<?php echo htmlspecialchars($row['user_name'] . ' (' . $row['user_role'] . ')'); ?>"
                                                    data-facility="<?php echo htmlspecialchars($row['facility_name'] . ' (' . $row['location'] . ')'); ?>"
                                                    data-schedule="<?php echo date('M d, Y', strtotime($row['booking_date'])); ?> • <?php echo date('g:i A', strtotime($row['start_time'])); ?> - <?php echo date('g:i A', strtotime($row['end_time'])); ?>"
                                                    data-equipment="<?php echo htmlspecialchars(strip_tags(str_replace('<br>', ', ', $row['allocated_equipment'] ?? 'None'))); ?>"
                                                    data-purpose="<?php echo htmlspecialchars($row['purpose'] ?? 'No purpose stated.'); ?>"
                                                    data-proof="<?php echo htmlspecialchars($row['proof_file'] ?? ''); ?>">
                                                <span class="material-symbols-outlined" style="font-size: 18px;">visibility</span>
                                            </button>

                                            <?php if ($row['status'] === 'Pending'): ?>
                                                <button type="button" class="btn-icon edit approve-trigger" data-id="<?php echo $row['booking_id']; ?>" style="color: var(--success-text); border: 1px solid rgba(22,101,52,0.2); background: none; cursor: pointer; padding: 4px; border-radius: 4px; margin-left:4px;" title="Approve">
                                                    <span class="material-symbols-outlined" style="font-size: 18px;">check</span>
                                                </button>
                                                <button type="button" class="btn-icon delete reject-trigger" data-id="<?php echo $row['booking_id']; ?>" style="background: none; border: none; cursor: pointer; padding: 4px; margin-left:4px;" title="Reject">
                                                    <span class="material-symbols-outlined" style="font-size: 18px;">close</span>
                                                </button>
                                            <?php elseif ($row['status'] === 'Approved'): ?>
                                                <button type="button" class="btn-icon delete cancel-trigger" data-id="<?php echo $row['booking_id']; ?>" style="background: none; border: none; cursor: pointer; padding: 4px; margin-left:4px;" title="Cancel Booking">
                                                    <span class="material-symbols-outlined" style="font-size: 18px;">cancel</span>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">No booking requests found matching criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </main>
    </div>

    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box modal-warning">
            <div class="modal-header" style="display: flex; align-items: center; gap: 8px;">
                <div class="modal-icon">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <h3 class="modal-title" id="confirmTitle">Confirm Action</h3>
            </div>
            <p class="modal-text" id="confirmText">Are you sure you want to proceed with this action?</p>
            <div class="modal-actions" style="display: flex; justify-content: flex-end; gap: 8px;">
                <button class="btn btn-outline close-modal" style="padding: 8px 16px; border: 1px solid var(--border-color); border-radius: 4px; background: transparent; cursor: pointer;">Cancel</button>
                <a href="#" class="btn btn-primary" id="confirmActionBtn" style="padding: 8px 16px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; color: #fff; font-weight: 500;">Yes, Proceed</a>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="detailsModal">
        <div class="modal-box modal-info" style="max-width: 530px; width: 90%; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header" style="flex-shrink: 0; display: flex; align-items: center; gap: 8px;">
                <div class="modal-icon">
                    <span class="material-symbols-outlined">visibility</span>
                </div>
                <h3 class="modal-title">Booking Details <span id="modal-target-id" style="font-size: 14px; font-weight: 500; color: var(--text-muted);"></span></h3>
            </div>
            
            <div class="details-modal-body" style="overflow-y: auto; flex-grow: 1; padding-right: 4px; margin-bottom: 16px;">
                <div class="details-grid" style="display: grid; grid-template-columns: 100px 1fr; gap: 12px 16px; align-items: start;">
                    <div class="details-label" style="font-weight: 600; color: var(--text-muted);">User:</div>
                    <div class="details-value" id="modal-view-user" style="word-break: break-word;"></div>
                    
                    <div class="details-label" style="font-weight: 600; color: var(--text-muted);">Facility:</div>
                    <div class="details-value" id="modal-view-facility" style="word-break: break-word;"></div>
                    
                    <div class="details-label" style="font-weight: 600; color: var(--text-muted);">Schedule:</div>
                    <div class="details-value" id="modal-view-schedule" style="word-break: break-word;"></div>
                    
                    <div class="details-label" style="font-weight: 600; color: var(--text-muted);">Equipment:</div>
                    <div class="details-value" id="modal-view-equipment" style="word-break: break-word;"></div>
                    
                    <div class="details-label" style="font-weight: 600; color: var(--text-muted);">Proof:</div>
                    <div class="details-value" id="modal-view-proof" style="word-break: break-word;"></div>

                    <div class="details-label" style="grid-column: span 2; margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(194, 198, 211, 0.4); font-weight: 600;">
                        Purpose of Booking:
                    </div>
                    <div class="details-value" style="grid-column: span 2; max-width: 100%;">
                        <div id="modal-view-purpose" style="font-style: italic; line-height: 1.6; color: var(--text-muted); white-space: normal; word-break: break-word; overflow-wrap: break-word; max-height: 105px; overflow-y: auto; padding-right: 6px;"></div>
                    </div>
                </div>
            </div>

            <div class="modal-actions" style="flex-shrink: 0; padding-top: 8px; border-top: 1px solid rgba(194, 198, 211, 0.2);">
                <button class="btn btn-primary close-modal" style="padding: 10px 24px; width: 100%; justify-content: center; border: none; border-radius: 4px; cursor: pointer; background: var(--primary); color: #fff; font-weight: 600;">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Profile Dropdown Toggle
        const profileTrigger = document.getElementById('profileTrigger');
        const profileMenu = document.getElementById('profileMenu');

        profileTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            profileMenu.classList.toggle('show');
        });

        window.addEventListener('click', function(e) {
            if (!profileTrigger.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });

        // Modals Assignment Constants
        const confirmModal = document.getElementById('confirmModal');
        const detailsModal = document.getElementById('detailsModal');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmText = document.getElementById('confirmText');
        const confirmBtn = document.getElementById('confirmActionBtn');
        const closeBtns = document.querySelectorAll('.close-modal');

        closeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                confirmModal.classList.remove('show');
                detailsModal.classList.remove('show');
            });
        });

        // View Details Modal Trigger Data Payload
        document.querySelectorAll('.view-details-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal-target-id').textContent = '#BK-' + this.dataset.id;
                document.getElementById('modal-view-user').textContent = this.dataset.user;
                document.getElementById('modal-view-facility').textContent = this.dataset.facility;
                document.getElementById('modal-view-schedule').textContent = this.dataset.schedule;
                document.getElementById('modal-view-equipment').textContent = this.dataset.equipment;
                document.getElementById('modal-view-purpose').textContent = '"' + this.dataset.purpose + '"';
                
                const proofContainer = document.getElementById('modal-view-proof');
                if (this.dataset.proof && this.dataset.proof !== "") {
                    proofContainer.innerHTML = `<a href="../public/uploads/proofs/${this.dataset.proof}" target="_blank" style="color: var(--primary); display: inline-flex; align-items: center; gap: 4px; font-weight:500;">
                        <span class="material-symbols-outlined" style="font-size: 16px;">description</span> View ${this.dataset.proof}
                    </a>`;
                } else {
                    proofContainer.innerHTML = `<span style="color: var(--text-muted); font-size: 13px;">No file proof attached</span>`;
                }
                
                detailsModal.classList.add('show');
            });
        });

        // Action Confirmations Redirection Triggers targeting manage-booking-action.php
        document.querySelectorAll('.approve-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                confirmTitle.textContent = 'Approve Booking Request';
                confirmText.textContent = 'Are you sure you want to approve this booking application? Any parallel student bookings conflict will be handled automatically.';
                confirmBtn.style.backgroundColor = 'var(--success-text)';
                confirmBtn.style.borderColor = 'var(--success-text)';
                confirmBtn.textContent = 'Approve Booking';
                confirmBtn.setAttribute('href', '../PHP/admin_booking_action.php?action=approve&booking_id=' + this.dataset.id);
                confirmModal.classList.add('show');
            });
        });

        document.querySelectorAll('.reject-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                confirmTitle.textContent = 'Reject Booking Request';
                confirmText.textContent = 'Are you sure you want to decline this facility usage application?';
                confirmBtn.style.backgroundColor = 'var(--secondary)';
                confirmBtn.style.borderColor = 'var(--secondary)';
                confirmBtn.textContent = 'Reject Application';
                confirmBtn.setAttribute('href', '../PHP/admin_booking_action.php?action=reject&booking_id=' + this.dataset.id);
                confirmModal.classList.add('show');
            });
        });

        document.querySelectorAll('.cancel-trigger').forEach(btn => {
            btn.addEventListener('click', function() {
                confirmTitle.textContent = 'Cancel Confirmed Booking';
                confirmText.textContent = 'Are you sure you want to cancel this previously approved booking slot?';
                confirmBtn.style.backgroundColor = 'var(--secondary)';
                confirmBtn.style.borderColor = 'var(--secondary)';
                confirmBtn.textContent = 'Yes, Cancel It';
                confirmBtn.setAttribute('href', '../PHP/admin_booking_action.php?action=cancel&booking_id=' + this.dataset.id);
                confirmModal.classList.add('show');
            });
        });
    </script>
</body>
</html>