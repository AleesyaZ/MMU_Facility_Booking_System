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

// --- ACTION: DELETE ANNOUNCEMENT ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "DELETE FROM annoucement WHERE annoucement_id = '$id'");
    header("Location: admin-announcements.php?msg=deleted");
    exit();
}

// --- ACTION: UNPUBLISH (SET TO ARCHIVED) ---
if (isset($_GET['action']) && $_GET['action'] == 'unpublish' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE annoucement SET status = 'Archived' WHERE annoucement_id = '$id'");
    header("Location: admin-announcements.php?msg=archived");
    exit();
}

// --- ACTION: REPUBLISH (SET TO LIVE) ---
if (isset($_GET['action']) && $_GET['action'] == 'republish' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE annoucement SET status = 'Live' WHERE annoucement_id = '$id'");
    header("Location: admin-announcements.php?msg=republished");
    exit();
}

// --- ACTION: POST/UPDATE ANNOUNCEMENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['publish_announcement'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $category = mysqli_real_escape_string($conn, $_POST['category']); // New Category Logic
    $id = mysqli_real_escape_string($conn, $_POST['announcement_id']);
    
    if (!empty($id)) {
        // UPDATE EXISTING (Including Category)
        $sql = "UPDATE annoucement SET title = '$title', content = '$content', category = '$category' WHERE annoucement_id = '$id'";
        $msg = "updated";
    } else {
        // INSERT NEW (Including Category)
        $sql = "INSERT INTO annoucement (admin_id, title, content, publish_date, status, category) 
                VALUES ('$admin_id', '$title', '$content', NOW(), 'Live', '$category')";
        $msg = "posted";
    }
            
    if (mysqli_query($conn, $sql)) {
        header("Location: admin-announcements.php?msg=$msg");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

// --- FETCH ANNOUNCEMENTS ---
$query = "SELECT a.*, u.name as publisher_name 
          FROM annoucement a 
          JOIN user u ON a.admin_id = u.user_id 
          ORDER BY a.publish_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Announcements - MMU Campus Space</title>
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
                <a href="admin-announcements.php" class="admin-nav-item active">
                    <span class="material-symbols-outlined">campaign</span> Announcements
                </a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Broadcast Announcements</h2>
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
                <div class="admin-toolbar">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="announcementSearch" onkeyup="searchTable()" placeholder="Search announcements...">
                    </div>
                    
                    <button class="btn btn-primary" id="toggleFormBtn" style="padding: 8px 16px;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">campaign</span> Post New Announcement
                    </button>
                </div>

                <div class="admin-form-panel" id="announcementForm">
                    <h3 id="formTitle" style="font-size: 16px; font-weight: 600; color: var(--primary); margin-bottom: 16px;">Draft New Announcement</h3>
                    <form action="admin-announcements.php" method="POST">
                        <input type="hidden" name="announcement_id" id="form_announcement_id">
                        <div class="form-group">
                            <label>Title / Headline</label>
                            <input type="text" name="title" id="form_title_input" class="form-control" placeholder="e.g. FCI Lab Maintenance" required>
                        </div>
                        <!-- New Category Dropdown -->
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" id="form_category_input" class="form-control" required>
                                <option value="Update">Facility Update</option>
                                <option value="Reminder">Reminder</option>
                                <option value="Event">Event</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Message Content</label>
                            <textarea name="content" id="form_content_input" class="form-control" placeholder="Write your announcement details here..." style="min-height: 80px;" required></textarea>
                        </div>
                        <div style="display: flex; gap: 12px; justify-content: flex-end;">
                            <button type="button" class="btn btn-outline" id="cancelFormBtn" style="padding: 8px 16px;">Cancel</button>
                            <button type="submit" name="publish_announcement" id="formSubmitBtn" class="btn btn-primary" style="padding: 8px 16px;">Publish Now</button>
                        </div>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table" id="announcementsTable">
                        <thead>
                            <tr>
                                <th>Headline & Details</th>
                                <th>Category</th> <!-- New Column Header -->
                                <th>Published By</th>
                                <th>Date Posted</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr <?php echo ($row['status'] == 'Archived') ? 'style="background-color: #fafafa;"' : ''; ?>>
                                    <td>
                                        <strong style="color: <?php echo ($row['status'] == 'Archived') ? 'var(--text-muted)' : 'var(--text-main)'; ?>; font-size: 15px;"><?php echo htmlspecialchars($row['title']); ?></strong><br>
                                        <span style="font-size: 13px; color: var(--text-muted); display: inline-block; max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 4px;">
                                            <?php echo htmlspecialchars($row['content']); ?>
                                        </span>
                                    </td>
                                    <!-- Dynamic Category Cell -->
                                    <td>
                                        <span style="font-size: 13px; font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['category'] ?? 'Update'); ?></span>
                                    </td>
                                    <td><span style="font-size: 13px; font-weight: 500; color: <?php echo ($row['status'] == 'Archived') ? 'var(--text-muted)' : 'inherit'; ?>;"><?php echo htmlspecialchars($row['publisher_name']); ?></span></td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($row['publish_date'])); ?><br>
                                        <span class="table-meta-text"><?php echo date('g:i A', strtotime($row['publish_date'])); ?></span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'Live'): ?>
                                            <span class="badge badge-approved">Live</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #f1f3f5; color: #6b7280;">Archived</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <?php if($row['status'] == 'Live'): ?>
                                                <button class="btn-icon edit" title="Edit Post" onclick='prepareEdit(<?php echo json_encode($row); ?>)'><span class="material-symbols-outlined" style="font-size: 18px;">edit</span></button>
                                                <button class="btn-icon delete" title="Unpublish" onclick="window.location.href='admin-announcements.php?action=unpublish&id=<?php echo $row['annoucement_id']; ?>'"><span class="material-symbols-outlined" style="font-size: 18px;">visibility_off</span></button>
                                            <?php else: ?>
                                                <button class="btn-icon edit" title="Republish" onclick="window.location.href='admin-announcements.php?action=republish&id=<?php echo $row['annoucement_id']; ?>'"><span class="material-symbols-outlined" style="font-size: 18px;">visibility</span></button>
                                            <?php endif; ?>
                                            
                                            <button class="btn-icon delete" title="Delete Permanently" onclick="if(confirm('Delete this announcement?')) window.location.href='admin-announcements.php?action=delete&id=<?php echo $row['annoucement_id']; ?>'">
                                                <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 32px; color: var(--text-muted);">No announcements posted yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggleFormBtn');
        const cancelBtn = document.getElementById('cancelFormBtn');
        const formPanel = document.getElementById('announcementForm');

        toggleBtn.addEventListener('click', () => {
            resetForm();
            formPanel.classList.add('show');
            toggleBtn.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            formPanel.classList.remove('show');
            toggleBtn.style.display = 'inline-flex';
        });

        function prepareEdit(data) {
            formPanel.classList.add('show');
            toggleBtn.style.display = 'none';
            document.getElementById('formTitle').innerText = "Edit Announcement";
            document.getElementById('formSubmitBtn').innerText = "Update Changes";
            document.getElementById('form_announcement_id').value = data.annoucement_id;
            document.getElementById('form_title_input').value = data.title;
            document.getElementById('form_content_input').value = data.content;
            document.getElementById('form_category_input').value = data.category || 'Update';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function resetForm() {
            document.getElementById('formTitle').innerText = "Draft New Announcement";
            document.getElementById('formSubmitBtn').innerText = "Publish Now";
            document.getElementById('form_announcement_id').value = "";
            document.getElementById('form_title_input').value = "";
            document.getElementById('form_content_input').value = "";
            document.getElementById('form_category_input').value = "Update";
        }

        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', function(e) { 
            e.stopPropagation(); 
            menu.classList.toggle('show'); 
        });
        window.addEventListener('click', function() { 
            if (menu.classList.contains('show')) { menu.classList.remove('show'); } 
        });

        function searchTable() {
            let input = document.getElementById("announcementSearch").value.toLowerCase();
            let rows = document.getElementById("announcementsTable").getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = rows[i].innerText.toLowerCase().includes(input) ? "" : "none";
            }
        }

        function checkAlerts() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('msg') === 'posted') alert("Announcement published successfully!");
            if (params.get('msg') === 'updated') alert("Announcement updated successfully!");
            if (params.get('msg') === 'deleted') alert("Announcement removed.");
            if (params.get('msg') === 'archived') alert("Announcement has been unpublished (Archived).");
            if (params.get('msg') === 'republished') alert("Announcement is now Live again.");
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>