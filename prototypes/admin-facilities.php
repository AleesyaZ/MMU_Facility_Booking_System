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

// --- ACTION: DELETE FACILITY ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $sql = "DELETE FROM facility WHERE facility_id = '$id'";
    if(mysqli_query($conn, $sql)) {
        header("Location: admin-facilities.php?msg=deleted");
    } else {
        header("Location: admin-facilities.php?msg=error_fk");
    }
    exit();
}

// --- ACTION: TOGGLE STATUS (MAINTENANCE/AVAILABLE) ---
if (isset($_GET['action']) && $_GET['action'] == 'set_status' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $new_status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE facility SET status = '$new_status' WHERE facility_id = '$id'");
    header("Location: admin-facilities.php?msg=status_updated");
    exit();
}

// --- ACTION: ADD / EDIT FACILITY (WITH IMAGE UPLOAD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_facility']) || isset($_POST['edit_facility']))) {
    $name = mysqli_real_escape_string($conn, $_POST['facility_name']);
    $loc = mysqli_real_escape_string($conn, $_POST['location']);
    $cat = mysqli_real_escape_string($conn, $_POST['category']);
    $cap = mysqli_real_escape_string($conn, $_POST['capacity']);
    $faculty = mysqli_real_escape_string($conn, $_POST['faculty']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // --- IMAGE UPLOAD LOGIC ---
    $image_sqltxt = "";
    if (isset($_FILES['facility_image']) && $_FILES['facility_image']['error'] == 0) {
        $target_dir = "../public/img/facilities/";
        // Create directory if not exists
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["facility_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = "fac_" . time() . "_" . rand(100, 999) . "." . $file_ext;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["facility_image"]["tmp_name"], $target_file)) {
            $image_sqltxt = $new_filename;
        }
    }

    if (isset($_POST['edit_facility'])) {
        $fid = mysqli_real_escape_string($conn, $_POST['facility_id']);
        
        // Basic Update
        $sql = "UPDATE facility SET facility_name='$name', location='$loc', category='$cat', 
                capacity='$cap', faculty='$faculty', description='$desc', status='$status'";
        
        // If a new image was uploaded, update the path
        if (!empty($image_sqltxt)) {
            $sql .= ", image_path='$image_sqltxt'";
        }
        
        $sql .= " WHERE facility_id='$fid'";
        $m = "updated";
    } else {
        $sql = "INSERT INTO facility (facility_name, location, category, capacity, faculty, description, status, image_path) 
                VALUES ('$name', '$loc', '$cat', '$cap', '$faculty', '$desc', '$status', '$image_sqltxt')";
        $m = "added";
    }

    mysqli_query($conn, $sql);
    header("Location: admin-facilities.php?msg=$m");
    exit();
}

// --- FETCH DATA WITH FILTERS ---
$campus_f = isset($_GET['campus']) ? mysqli_real_escape_string($conn, $_GET['campus']) : 'All Campuses';
$faculty_f = isset($_GET['faculty']) ? mysqli_real_escape_string($conn, $_GET['faculty']) : 'All Faculties';
$category_f = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'All Categories';

$query = "SELECT * FROM facility WHERE 1=1";
if ($campus_f != 'All Campuses') $query .= " AND location LIKE '%$campus_f%'";
if ($faculty_f != 'All Faculties') $query .= " AND faculty = '$faculty_f'";
if ($category_f != 'All Categories') $query .= " AND category = '$category_f'";
$query .= " ORDER BY facility_name ASC";

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Facilities - MMU Campus Space</title>
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
                <a href="admin-facilities.php" class="admin-nav-item active">
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
                <div><h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Facility Management</h2></div>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo strtoupper($initials); ?></div>
                    <span class="profile-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>
                    <div class="profile-menu" id="profileMenu">
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <div class="admin-toolbar">
                    <div class="search-wrapper">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" id="facilitySearch" onkeyup="searchTable()" placeholder="Search facility name...">
                    </div>
                    
                    <form action="admin-facilities.php" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <select name="campus" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                            <option <?php if($campus_f == 'All Campuses') echo 'selected'; ?>>All Campuses</option>
                            <option <?php if($campus_f == 'Cyberjaya') echo 'selected'; ?>>Cyberjaya</option>
                            <option <?php if($campus_f == 'Melaka') echo 'selected'; ?>>Melaka</option>
                        </select>

                        <select name="faculty" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                            <option <?php if($faculty_f == 'All Faculties') echo 'selected'; ?>>All Faculties</option>
                            <option <?php if($faculty_f == 'FCI') echo 'selected'; ?>>FCI</option>
                            <option <?php if($faculty_f == 'FOE') echo 'selected'; ?>>FOE</option>
                            <option <?php if($faculty_f == 'FCM') echo 'selected'; ?>>FCM</option>
                            <option <?php if($faculty_f == 'FOM') echo 'selected'; ?>>FOM</option>
                            <option <?php if($faculty_f == 'General') echo 'selected'; ?>>General Campus</option>
                        </select>

                        <select name="category" class="form-control" style="width: 140px;" onchange="this.form.submit()">
                            <option <?php if($category_f == 'All Categories') echo 'selected'; ?>>All Categories</option>
                            <option <?php if($category_f == 'Lecture Hall') echo 'selected'; ?>>Lecture Hall</option>
                            <option <?php if($category_f == 'Discussion Room') echo 'selected'; ?>>Discussion Room</option>
                            <option <?php if($category_f == 'Computer Lab') echo 'selected'; ?>>Computer Lab</option>
                            <option <?php if($category_f == 'Sports Court') echo 'selected'; ?>>Sports Court</option>
                        </select>

                        <button type="button" class="btn btn-primary" onclick="openAddModal()" style="padding: 8px 16px;">
                            <span class="material-symbols-outlined" style="font-size: 18px;">add</span> Add Facility
                        </button>
                    </form>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table" id="facilityTable">
                        <thead>
                            <tr>
                                <th>Facility Name</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th style="text-align: center;">Capacity</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr <?php if($row['status'] == 'Maintenance') echo 'style="background-color: #fafafa;"'; ?>>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if(!empty($row['image_path'])): ?>
                                            <img src="../public/img/facilities/<?php echo $row['image_path']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; background: #f1f3f5; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #adb5bd;"><span class="material-symbols-outlined" style="font-size: 18px;">image</span></div>
                                        <?php endif; ?>
                                        <strong style="color: var(--text-main);"><?php echo htmlspecialchars($row['facility_name']); ?></strong>
                                    </div>
                                </td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($row['location']); ?> (<?php echo $row['faculty']; ?>)</td>
                                <td><?php echo htmlspecialchars($row['category']); ?></td>
                                <td style="text-align: center; font-weight: 600;"><?php echo $row['capacity']; ?></td>
                                <td>
                                    <?php if($row['status'] == 'Available'): ?>
                                        <span class="badge badge-approved"><span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-maintenance"><span class="material-symbols-outlined" style="font-size: 14px;">handyman</span> Maintenance</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <button class="btn-icon edit" title="Edit Facility" onclick='openEditModal(<?php echo json_encode($row); ?>)'><span class="material-symbols-outlined" style="font-size: 18px;">edit</span></button>
                                    
                                    <?php if($row['status'] == 'Available'): ?>
                                        <button class="btn-icon penalty" title="Mark as Maintenance" onclick="window.location.href='admin-facilities.php?action=set_status&status=Maintenance&id=<?php echo $row['facility_id']; ?>'"><span class="material-symbols-outlined" style="font-size: 18px;">build</span></button>
                                    <?php else: ?>
                                        <button class="btn-icon edit" title="Mark as Available" onclick="window.location.href='admin-facilities.php?action=set_status&status=Available&id=<?php echo $row['facility_id']; ?>'"><span class="material-symbols-outlined" style="font-size: 18px; color: var(--success-text);">check_circle</span></button>
                                    <?php endif; ?>

                                    <button class="btn-icon delete" title="Delete Facility" onclick="if(confirm('Delete this facility? This may fail if active bookings exist.')) window.location.href='admin-facilities.php?action=delete&id=<?php echo $row['facility_id']; ?>'"><span class="material-symbols-outlined" style="font-size: 18px;">delete</span></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL: ADD/EDIT FACILITY -->
    <div class="modal-overlay" id="facilityModal">
        <div class="modal-box" style="max-width: 500px;">
            <h3 id="modalTitle" style="margin-bottom: 20px;">Add New Facility</h3>
            <form action="admin-facilities.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="facility_id" id="form_fid">
                
                <div class="form-group">
                    <label>Facility Name</label>
                    <input type="text" name="facility_name" id="form_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Facility Image</label>
                    <div id="image_preview_area" style="margin-bottom: 10px; display: none;">
                        <img id="current_img_thumb" src="" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                        <p style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Current image shown above. Upload new to replace.</p>
                    </div>
                    <input type="file" name="facility_image" class="form-control" accept="image/*">
                </div>

                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label>Campus</label>
                        <select name="location" id="form_loc" class="form-control">
                            <option>MMU Cyberjaya</option>
                            <option>MMU Melaka</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Faculty</label>
                        <select name="faculty" id="form_faculty" class="form-control">
                            <option>FCI</option><option>FOE</option><option>FCM</option><option>FOM</option><option>General</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label>Category</label>
                        <select name="category" id="form_cat" class="form-control">
                            <option>Lecture Hall</option><option>Labaratory</option><option>Sports</option><option>you can add more option in coding</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Capacity</label>
                        <input type="number" name="capacity" id="form_cap" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="form_desc" class="form-control" style="min-height: 60px;"></textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="form_status" class="form-control">
                        <option value="Available">Available</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" id="submitBtn" name="add_facility" class="btn btn-primary">Save Facility</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = "Add New Facility";
            document.getElementById('submitBtn').name = "add_facility";
            document.getElementById('form_fid').value = "";
            // Reset fields
            document.getElementById('form_name').value = "";
            document.getElementById('form_cap').value = "";
            document.getElementById('form_desc').value = "";
            document.getElementById('image_preview_area').style.display = 'none';
            document.getElementById('facilityModal').classList.add('show'); 
        }

        function closeModal() { document.getElementById('facilityModal').classList.remove('show'); }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = "Edit Facility";
            document.getElementById('submitBtn').name = "edit_facility";
            document.getElementById('form_fid').value = data.facility_id;
            document.getElementById('form_name').value = data.facility_name;
            document.getElementById('form_loc').value = data.location;
            document.getElementById('form_faculty').value = data.faculty;
            document.getElementById('form_cat').value = data.category;
            document.getElementById('form_cap').value = data.capacity;
            document.getElementById('form_desc').value = data.description;
            document.getElementById('form_status').value = data.status;

            // Handle Image Preview
            const previewArea = document.getElementById('image_preview_area');
            const previewImg = document.getElementById('current_img_thumb');
            if(data.image_path && data.image_path !== "") {
                previewImg.src = "../public/img/facilities/" + data.image_path;
                previewArea.style.display = 'block';
            } else {
                previewArea.style.display = 'none';
            }

            document.getElementById('facilityModal').classList.add('show');
        }

        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });

        function searchTable() {
            let input = document.getElementById("facilitySearch").value.toLowerCase();
            let rows = document.getElementById("facilityTable").getElementsByTagName("tr");
            for (let i = 1; i < rows.length; i++) {
                rows[i].style.display = rows[i].innerText.toLowerCase().includes(input) ? "" : "none";
            }
        }

        function checkAlerts() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('msg') === 'added') alert("Facility added successfully!");
            if (params.get('msg') === 'updated') alert("Facility updated!");
            if (params.get('msg') === 'deleted') alert("Facility removed.");
            if (params.get('msg') === 'status_updated') alert("Facility status changed.");
            if (params.get('msg') === 'error_fk') alert("Cannot delete: This facility has active bookings or reports.");
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>