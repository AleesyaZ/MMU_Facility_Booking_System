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

// --- ACTION: DELETE EQUIPMENT ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    $sql = "DELETE FROM equipment WHERE equip_id = '$id'";
    if(mysqli_query($conn, $sql)) {
        header("Location: admin-equipment.php?msg=deleted");
    } else {
        header("Location: admin-equipment.php?msg=error_fk");
    }
    exit();
}

// --- ACTION: QUICK RESTOCK ---
if (isset($_GET['action']) && $_GET['action'] == 'restock' && isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    mysqli_query($conn, "UPDATE equipment SET avail_qty = total_qty, status = 'Available' WHERE equip_id = '$id'");
    header("Location: admin-equipment.php?msg=restocked");
    exit();
}

// --- ACTION: ADD / EDIT EQUIPMENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_equip']) || isset($_POST['edit_equip']))) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $campus = mysqli_real_escape_string($conn, $_POST['campus']); 
    $category = mysqli_real_escape_string($conn, $_POST['category']); 
    $total = (int)$_POST['total_qty'];
    $avail = (int)$_POST['avail_qty'];
    
    $status = ($avail <= 0) ? 'Out of Stock' : 'Available';

    if (isset($_POST['edit_equip'])) {
        $eid = mysqli_real_escape_string($conn, $_POST['equip_id']);
        $sql = "UPDATE equipment SET name='$name', campus='$campus', category='$category', total_qty='$total', avail_qty='$avail', status='$status' WHERE equip_id='$eid'";
        $m = "updated";
    } else {
        $sql = "INSERT INTO equipment (name, campus, category, total_qty, avail_qty, status) VALUES ('$name', '$campus', '$category', '$total', '$avail', '$status')";
        $m = "added";
    }

    mysqli_query($conn, $sql);
    header("Location: admin-equipment.php?msg=$m");
    exit();
}

// --- FETCH DATA WITH FILTERS ---
$campus_f = isset($_GET['campus']) ? mysqli_real_escape_string($conn, $_GET['campus']) : 'All Campuses';
$category_f = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : 'All Categories';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM equipment WHERE 1=1";
if ($campus_f != 'All Campuses') {
    $query .= " AND campus = '$campus_f'";
}
if ($category_f != 'All Categories') {
    $query .= " AND category = '$category_f'";
}
if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR equip_id LIKE '%$search%')";
}
$query .= " ORDER BY name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manage Equipment - MMU Facility Booking System</title>
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
                <a href="admin-dashboard.php" class="admin-nav-item"><span class="material-symbols-outlined">dashboard</span> Dashboard Overview</a>
                <a href="admin-bookings.php" class="admin-nav-item"><span class="material-symbols-outlined">calendar_month</span> Manage Bookings</a>
                <a href="admin-facilities.php" class="admin-nav-item"><span class="material-symbols-outlined">meeting_room</span> Manage Facilities</a>
                <a href="admin-equipment.php" class="admin-nav-item active"><span class="material-symbols-outlined">cable</span> Manage Equipment</a>
                <a href="admin-users.php" class="admin-nav-item"><span class="material-symbols-outlined">group</span> Manage Users & Quotas</a>
                <a href="admin-penalties.php" class="admin-nav-item"><span class="material-symbols-outlined">gavel</span> Penalty System</a>
                <a href="admin-reports.php" class="admin-nav-item"><span class="material-symbols-outlined">report</span> Issue Reports</a>
                <a href="admin-announcements.php" class="admin-nav-item"><span class="material-symbols-outlined">campaign</span> Announcements</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div><h2 style="font-size: 22px; font-weight: 700; color: var(--text-main); letter-spacing: -0.5px;">Equipment Inventory</h2></div>
                <div class="nav-profile" id="profileTrigger" style="cursor: pointer;">
                    <div class="avatar" style="background-color: var(--secondary);"><?php echo strtoupper($initials); ?></div>
                    <span class="profile-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    <span class="material-symbols-outlined" style="font-size: 18px; color: var(--text-muted);">expand_more</span>

                    <div class="profile-menu" id="profileMenu">
                        <a href="admin-profile.php"><span class="material-symbols-outlined">account_circle</span> My Profile</a>
                        <div style="border-top: 1px solid rgba(194, 198, 211, 0.3); margin: 4px 0;"></div>
                        <a href="../PHP/logout.php" class="logout-link"><span class="material-symbols-outlined">logout</span> Logout</a>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <div class="admin-toolbar">
                    <form action="admin-equipment.php" method="GET" style="display: flex; flex: 1; gap: 12px; align-items: center; flex-wrap: wrap;">
                        <div class="search-wrapper">
                            <span class="material-symbols-outlined">search</span>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search equipment name...">
                        </div>
                        <select name="campus" class="form-control" style="width: 150px; padding: 8px 12px;" onchange="this.form.submit()">
                            <option <?php if($campus_f == 'All Campuses') echo 'selected'; ?>>All Campuses</option>
                            <option <?php if($campus_f == 'Cyberjaya') echo 'selected'; ?>>Cyberjaya</option>
                            <option <?php if($campus_f == 'Melaka') echo 'selected'; ?>>Melaka</option>
                        </select>
                        <select name="category" class="form-control" style="width: 150px; padding: 8px 12px;" onchange="this.form.submit()">
                            <option <?php if($category_f == 'All Categories') echo 'selected'; ?>>All Categories</option>
                            <option <?php if($category_f == 'Sports') echo 'selected'; ?>>Sports</option>
                            <option <?php if($category_f == 'Labaratory') echo 'selected'; ?>>Labaratory</option>
                            <option <?php if($category_f == 'Lecture Hall') echo 'selected'; ?>>Lecture Hall</option>
                            <option <?php if($category_f == 'Tutorial') echo 'selected'; ?>>Tutorial</option>
                            <option <?php if($category_f == 'General') echo 'selected'; ?>>General</option>
                        </select>
                    </form>
                    <button class="btn btn-primary" onclick="openAddModal()" style="padding: 8px 16px;">
                        <span class="material-symbols-outlined" style="font-size: 18px;">add</span> Add Equipment
                    </button>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table" id="equipmentTable">
                        <thead>
                            <tr>
                                <th>Item Code & Name</th>
                                <th>Assigned Campus</th>
                                <th>Category</th>
                                <th>Stock</th>
                                <th>Availability</th>
                                <th>Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): 
                                    $percentage = ($row['total_qty'] > 0) ? ($row['avail_qty'] / $row['total_qty']) * 100 : 0;
                                    
                                    // --- UPDATED BAR LOGIC ---
                                    if($row['avail_qty'] <= 0) {
                                        $bar_class = "stock-critical";
                                        $text_color = "var(--secondary)"; // Red
                                    } else if($percentage < 50) {
                                        $bar_class = "stock-warning";
                                        $text_color = "#d97706"; // Yellow/Orange
                                    } else {
                                        $bar_class = "stock-good";
                                        $text_color = "var(--success-text)"; // Green
                                    }
                                ?>
                                <tr <?php if($row['avail_qty'] <= 0) echo 'style="background-color: #fafafa;"'; ?>>
                                    <td>
                                        <strong style="color: <?php echo ($row['avail_qty'] <= 0) ? 'var(--text-muted)' : 'var(--text-main)'; ?>;"><?php echo htmlspecialchars($row['name']); ?></strong><br>
                                        <span class="table-meta-text">EQ-ITEM-<?php echo str_pad($row['equip_id'], 3, '0', STR_PAD_LEFT); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['campus'] ?? 'Cyberjaya'); ?></td>
                                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                                    <td style="text-align: center; font-weight: 600;"><?php echo $row['total_qty']; ?></td>
                                    <td>
                                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100px;">
                                            <span class="stock-text" style="color: <?php echo $text_color; ?>; font-weight: 600;">
                                                <?php echo $row['avail_qty']; ?> Available
                                            </span>
                                        </div>
                                        <!-- Hide bar if quantity is 0 as per image -->
                                        <?php if($row['avail_qty'] > 0): ?>
                                        <div class="stock-bar-container">
                                            <div class="stock-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['avail_qty'] > 0): ?>
                                            <span class="badge badge-approved"><span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span> Active</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #fee2e2; color: #991b1b;"><span class="material-symbols-outlined" style="font-size: 14px;">block</span> Out of Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <button class="btn-icon edit" title="Edit Item" onclick='openEditModal(<?php echo json_encode($row); ?>)'><span class="material-symbols-outlined">edit</span></button>
                                            <?php if($row['avail_qty'] < $row['total_qty']): ?>
                                                <button class="btn-icon edit" title="Quick Restock" style="color: var(--success-text); border-color: rgba(22,101,52,0.2);" onclick="if(confirm('Reset available stock to full?')) window.location.href='admin-equipment.php?action=restock&id=<?php echo $row['equip_id']; ?>'"><span class="material-symbols-outlined">inventory</span></button>
                                            <?php endif; ?>
                                            <button class="btn-icon delete" title="Delete Item" onclick="if(confirm('Delete this item?')) window.location.href='admin-equipment.php?action=delete&id=<?php echo $row['equip_id']; ?>'"><span class="material-symbols-outlined">delete</span></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align:center; padding: 40px; color: var(--text-muted);">No equipment found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="equipModal">
        <div class="modal-box" style="max-width: 500px;">
            <h3 id="modalTitle" style="margin-bottom: 20px;">Add New Equipment</h3>
            <form action="admin-equipment.php" method="POST">
                <input type="hidden" name="equip_id" id="form_eid">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="name" id="form_name" class="form-control" required>
                </div>
                <div class="form-row" style="display: flex; gap: 12px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label>Campus Location</label>
                        <select name="campus" id="form_campus" class="form-control">
                            <option value="Cyberjaya">Cyberjaya</option>
                            <option value="Melaka">Melaka</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Item Category</label>
                        <select name="category" id="form_cat" class="form-control">
                            <option value="Sports">Sports</option>
                            <option value="Labaratory">Labaratory</option>
                            <option value="Lecture Hall">Lecture Hall</option>
                            <option value="Tutorial">Tutorial</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                </div>
                <div class="form-row" style="display: flex; gap: 12px;">
                    <div style="flex: 1;">
                        <label>Total Stock</label>
                        <input type="number" name="total_qty" id="form_total" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Current Available</label>
                        <input type="number" name="avail_qty" id="form_avail" class="form-control" required>
                    </div>
                </div>
                <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" id="submitBtn" name="add_equip" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const trigger = document.getElementById('profileTrigger');
        const menu = document.getElementById('profileMenu');
        trigger.addEventListener('click', (e) => { e.stopPropagation(); menu.classList.toggle('show'); });
        window.addEventListener('click', () => { if (menu.classList.contains('show')) menu.classList.remove('show'); });

        function openAddModal() {
            document.getElementById('modalTitle').innerText = "Add New Equipment";
            document.getElementById('submitBtn').name = "add_equip";
            document.getElementById('form_eid').value = "";
            document.getElementById('form_name').value = "";
            document.getElementById('form_total').value = "";
            document.getElementById('form_avail').value = "";
            document.getElementById('equipModal').classList.add('show');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').innerText = "Edit Equipment";
            document.getElementById('submitBtn').name = "edit_equip";
            document.getElementById('form_eid').value = data.equip_id;
            document.getElementById('form_name').value = data.name;
            document.getElementById('form_campus').value = data.campus || 'Cyberjaya';
            document.getElementById('form_cat').value = data.category;
            document.getElementById('form_total').value = data.total_qty;
            document.getElementById('form_avail').value = data.avail_qty;
            document.getElementById('equipModal').classList.add('show');
        }

        function closeModal() { document.getElementById('equipModal').classList.remove('show'); }

        function checkAlerts() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('msg') === 'added') alert("Equipment added to inventory!");
            if (params.get('msg') === 'updated') alert("Item updated successfully!");
            if (params.get('msg') === 'deleted') alert("Item removed from inventory.");
            if (params.get('msg') === 'restocked') alert("Stock availability reset to full.");
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>