<?php
/**
 * Library System Report (report.php)
 * Displays system statistics and summaries.
 */
session_start();
include "db.php"; // Assumes db.php contains $conn

// --- SECURITY CHECK ---
$allowedRoles = ['admin', 'super_admin'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

// Load user data
$userId = $_SESSION['user_id'] ?? 0;
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);

// --- FIXED PHP LOGIC FOR PROFILE PICTURE ---
$base_path = 'images/profile_pics/';
$default_image = $base_path . 'default_admin.png';
$profilePicUrl = $default_image; 

if ($userId && isset($conn)) {
    $db_path = $_SESSION['profile_pic'] ?? null; 
    if (!$db_path) {
        $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $db_path = $user_data['profile_pic'] ?? null;
    }
    if ($db_path && file_exists($db_path) && !is_dir($db_path)) {
        $profilePicUrl = $db_path;
    }
}

// --- REPORT DATA LOGIC ---
$total_books = 0;
$total_users = 0;
$total_admins = 0;

if (isset($conn)) {
    // 1. Count Books
    $q1 = $conn->query("SELECT COUNT(*) as count FROM books");
    if($q1) $total_books = $q1->fetch_assoc()['count'];

    // 2. Count Users (assuming 'admin' and 'super_admin' are roles)
    $q2 = $conn->query("SELECT COUNT(*) as count FROM users WHERE role NOT IN ('admin', 'super_admin')");
    if($q2) $total_users = $q2->fetch_assoc()['count'];

    // 3. Count Admins
    $q3 = $conn->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
    if($q3) $total_admins = $q3->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Report - Library Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* INHERITED STYLES FROM ADMIN.PHP */
:root {
    --primary: #4e54c8;
    --secondary: #8f94fb;
    --accent: #ff6b6b;
    --bg-color: #f4f7fc;
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --glass: rgba(255, 255, 255, 0.95); 
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
    --success: #00b894; /* New color for stats */
}

body { font-family: 'Poppins', sans-serif; background: var(--bg-color); margin: 0; padding: 0; color: var(--text-dark); }

/* HEADER */
header {
    background: var(--glass);
    backdrop-filter: blur(12px); 
    -webkit-backdrop-filter: blur(12px);
    height: 80px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 5%;
    box-shadow: 0 1px 0 rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 1000;
}
header .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
header .brand img { height: 45px; width: auto; }
header .brand span { font-size: 20px; font-weight: 700; color: var(--primary); }

/* PROFILE PILL */
header .user-actions { position: relative; }
.profile-pill { display: flex; align-items: center; gap: 12px; background: white; padding: 6px 6px 6px 20px; border: 1px solid #e1e4e8; border-radius: 50px; cursor: pointer; transition: all 0.2s ease; user-select: none; }
.profile-pill:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.05); transform: translateY(-1px); }
.user-info { text-align: right; line-height: 1.3; }
.user-name { display: block; font-size: 14px; font-weight: 600; }
.user-role { display: block; font-size: 11px; text-transform: uppercase; font-weight: 700; padding: 1px 4px; border-radius: 4px; background: <?= ($userRole === 'super_admin') ? '#ffeb3b' : 'transparent'; ?>; color: <?= ($userRole === 'super_admin') ? '#2d3436' : 'var(--text-light)'; ?>; }

.avatar-circle { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px; box-shadow: 0 2px 5px rgba(78, 84, 200, 0.2); overflow: hidden; }
.avatar-circle img { width: 100%; height: 100%; object-fit: cover; }

/* DROPDOWN */
.dropdown-menu { display: none; position: absolute; top: 65px; right: 0; width: 220px; background: white; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.04); overflow: hidden; animation: slideDown 0.2s ease forwards; }
.dropdown-menu a { display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: var(--text-dark); text-decoration: none; font-size: 14px; border-bottom: 1px solid #f8f9fa; transition: background 0.2s; }
.dropdown-menu a:hover { background: #f9fafb; color: var(--primary); }
.dropdown-menu a:last-child { border-bottom: none; color: var(--accent); }
.dropdown-menu a:last-child:hover { background: #fff5f5; }
@keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

/* === REPORT SPECIFIC STYLES === */
.dashboard { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
.page-header h1 { font-size: 28px; color: var(--primary); margin: 0; }

.print-btn {
    background: white; color: var(--primary); border: 2px solid var(--primary); padding: 10px 25px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
}
.print-btn:hover { background: var(--primary); color: white; box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3); }

/* STATS GRID */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 40px; }
.stat-card {
    background: white; padding: 25px; border-radius: 16px; box-shadow: var(--shadow-soft); display: flex; align-items: center; gap: 20px; border: 1px solid rgba(0,0,0,0.03);
}
.stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
.stat-info h3 { margin: 0; font-size: 32px; font-weight: 700; color: var(--text-dark); }
.stat-info p { margin: 5px 0 0 0; color: var(--text-light); font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }

/* DATA TABLE */
.report-section { background: white; border-radius: 20px; padding: 30px; box-shadow: var(--shadow-soft); }
.report-section h2 { margin-top: 0; font-size: 20px; color: var(--text-dark); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { text-align: left; padding: 15px; border-bottom: 1px solid #f4f7fc; }
.data-table th { color: var(--text-light); font-weight: 500; font-size: 14px; }
.data-table tr:hover { background: #f9fafb; }

@media print {
    header, .print-btn { display: none !important; }
    body { background: white; }
    .dashboard { margin: 0; width: 100%; max-width: 100%; }
    .stat-card { border: 1px solid #ddd; box-shadow: none; }
}
@media (max-width: 600px) {
    header { padding: 0 20px; height: 70px; }
    .user-info { display: none; }
    .stats-grid { grid-template-columns: 1fr; }
}
</style>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}
window.onclick = function(event) {
    if (!event.target.closest('.user-actions')) {
        const dropdown = document.getElementById('userDropdown');
        if(dropdown) dropdown.style.display = 'none';
    }
}
</script>
</head>
<body>

<header>
    <a href="admin.php" class="brand">
        <img src="ambo.png" alt="Logo" onerror="this.src='https://placehold.co/50x50/4e54c8/ffffff?text=L'"> 
        <span>Library Admin</span>
    </a>

    <div class="user-actions">
        <div class="profile-pill" onclick="toggleDropdown()">
            <div class="user-info">
                <span class="user-name"><?= $userName; ?></span>
                <span class="user-role"><?= ($userRole === 'super_admin') ? 'SUPER ADMIN' : strtoupper($userRole); ?></span>
            </div>
            
            <div class="avatar-circle">
                <?php 
                if ($profilePicUrl !== $default_image) {
                    $cache_buster = time(); 
                    echo '<img src="'.$profilePicUrl.'?t='.$cache_buster.'" alt="Profile Image">';
                } else {
                    echo strtoupper(substr($userName, 0, 1)); 
                }
                ?>
            </div>
        </div>

        <div class="dropdown-menu" id="userDropdown">
            <a href="admin.php"><span>🏠</span> Dashboard</a>
            <a href="profile.php"><span>👤</span> My Profile</a>
            <a href="settings.php"><span>⚙️</span> Settings</a>
            <a href="logout.php"><span>🚪</span> Logout</a>
        </div>
    </div>
</header>

<div class="dashboard">
    <div class="page-header">
        <h1>📊 System Report</h1>
        <button onclick="window.print()" class="print-btn">🖨️ Print Report</button>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(78, 84, 200, 0.1); color: var(--primary);">📚</div>
            <div class="stat-info">
                <h3><?= number_format($total_books); ?></h3>
                <p>Total Books</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(0, 184, 148, 0.1); color: var(--success);">👥</div>
            <div class="stat-info">
                <h3><?= number_format($total_users); ?></h3>
                <p>Active Users</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--accent);">🛡️</div>
            <div class="stat-info">
                <h3><?= number_format($total_admins); ?></h3>
                <p>Administrators</p>
            </div>
        </div>
    </div>

    <div class="report-section">
        <h2>Recent Book Additions</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($conn)) {
                    // Fetch last 5 books
                    $sql = "SELECT id, title, author, created_at FROM books ORDER BY id DESC LIMIT 5";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Check if created_at exists, else show 'N/A'
                            $date = isset($row['created_at']) ? date("M d, Y", strtotime($row['created_at'])) : 'N/A';
                            echo "<tr>
                                    <td>#{$row['id']}</td>
                                    <td><strong>" . htmlspecialchars($row['title']) . "</strong></td>
                                    <td>" . htmlspecialchars($row['author']) . "</td>
                                    <td>{$date}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; color:var(--text-light);'>No books found.</td></tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>