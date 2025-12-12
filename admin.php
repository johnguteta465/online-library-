<?php
/**
 * Library Admin Dashboard (admin.php) - FIXED
 * Profile picture logic retrieves the precise path stored in the database.
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
$profilePicUrl = $default_image; // Initialize with default

// 1. Fetch the profile_pic path from the database (the source of truth)
if ($userId && isset($conn)) {
    // Attempt to use the session variable for speed (updated by profile.php)
    $db_path = $_SESSION['profile_pic'] ?? null; 

    // If session path is missing, query the DB
    if (!$db_path) {
        $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $db_path = $user_data['profile_pic'] ?? null;
    }
    
    // 2. Use DB path if the file actually exists on disk
    if ($db_path && file_exists($db_path) && !is_dir($db_path)) {
        $profilePicUrl = $db_path;
    }
}
// ---------------------------------------------
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* * =========================================
 * 🎨 VISUAL STANDARD CSS
 * =========================================
 */

/* ===== Variables (Color Palette) ===== */
:root {
    --primary: #4e54c8;         /* Deep Blue */
    --secondary: #8f94fb;       /* Soft Purple/Blue */
    --accent: #ff6b6b;          /* Soft Red (Logout/Alerts) */
    --bg-color: #f4f7fc;        /* Light Blue-Grey Background */
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --glass: rgba(255, 255, 255, 0.95); 
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
}

/* ===== General Reset & Body ===== */
body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-color);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

/* =========================================
   HEADER STYLE (Glass + Pill)
   ========================================= */
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

/* Brand / Logo Area */
header .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    transition: opacity 0.3s;
}

header .brand img {
    height: 45px;
    width: auto;
}

header .brand span {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary);
    letter-spacing: -0.5px;
}

/* User Profile "Pill" Container */
header .user-actions {
    position: relative;
}

.profile-pill {
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
    padding: 6px 6px 6px 20px; 
    border: 1px solid #e1e4e8;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
}

.profile-pill:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transform: translateY(-1px);
}

.user-info {
    text-align: right;
    line-height: 1.3;
}

.user-name {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
}

.user-role {
    display: block;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1px 4px;
    border-radius: 4px;
    font-weight: 700;
    background: <?= ($userRole === 'super_admin') ? '#ffeb3b' : 'transparent'; ?>;
    color: <?= ($userRole === 'super_admin') ? '#2d3436' : 'var(--text-light)'; ?>;
}

/* Avatar Circle (Image/Text Placeholder) */
.avatar-circle {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 16px;
    box-shadow: 0 2px 5px rgba(78, 84, 200, 0.2);
    /* NEW: Ensures image fits inside circle */
    overflow: hidden; 
}

/* NEW: Style for the profile image inside the avatar circle */
.avatar-circle img {
    width: 100%; 
    height: 100%; 
    object-fit: cover;
}


/* Dropdown Menu */
.dropdown-menu {
    display: none;
    position: absolute;
    top: 65px;
    right: 0;
    width: 220px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.04);
    overflow: hidden;
    animation: slideDown 0.2s ease forwards;
}

.dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: var(--text-dark);
    text-decoration: none;
    font-size: 14px;
    border-bottom: 1px solid #f8f9fa;
    transition: background 0.2s;
}

.dropdown-menu a:hover {
    background: #f9fafb;
    color: var(--primary);
}

.dropdown-menu a:last-child {
    border-bottom: none;
    color: var(--accent);
}

.dropdown-menu a:last-child:hover {
    background: #fff5f5;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* =========================================
   DASHBOARD CONTENT (Grid Layout)
   ========================================= */
.dashboard {
    max-width: 1100px;
    margin: 60px auto;
    padding: 0 20px;
}

.dashboard-header {
    text-align: center;
    margin-bottom: 50px;
}

.dashboard-header h1 {
    font-size: 36px;
    color: var(--primary);
    margin-bottom: 10px;
    font-weight: 700;
}

.dashboard-header p {
    color: var(--text-light);
    font-size: 18px;
}

/* The Grid Container for Cards */
.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

/* Card Buttons Styling */
a.card-btn {
    background: var(--white);
    padding: 40px 20px;
    border-radius: 20px;
    text-decoration: none;
    color: var(--text-dark);
    text-align: center;
    box-shadow: var(--shadow-soft);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: 1px solid rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 15px;
    position: relative;
    overflow: hidden;
}

/* Card Hover Effects */
a.card-btn:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(78, 84, 200, 0.12);
    border-color: var(--secondary);
}

a.card-btn span {
    font-weight: 600;
    font-size: 16px;
    z-index: 2;
}

/* Special styling for Super Admin card */
.super-admin-card {
    background: linear-gradient(135deg, #FFD700, #DAA520) !important; /* Gold gradient */
    color: var(--text-dark) !important;
    border: 1px solid #FFD700;
}
.super-admin-card:hover {
    box-shadow: 0 20px 40px rgba(255, 215, 0, 0.25);
}

/* Full Width Feedback Button */
a.feedback-btn {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white !important;
}

/* Logout Footer Button */
a.logout-btn {
    display: block;
    width: fit-content;
    margin: 0 auto;
    padding: 12px 40px;
    border: 2px solid #ffecec;
    background-color: white;
    color: var(--accent);
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: 0.3s;
}

a.logout-btn:hover {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
    box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
}

/* Mobile Responsiveness */
@media (max-width: 600px) {
    header { padding: 0 20px; height: 70px; }
    .user-info { display: none; }
    .dashboard-header h1 { font-size: 28px; }
    .action-grid { grid-template-columns: 1fr; gap: 15px; }
}
</style>

<script>
function toggleDropdown() {
    const dropdown = document.getElementById('userDropdown');
    const isVisible = dropdown.style.display === 'block';
    dropdown.style.display = isVisible ? 'none' : 'block';
}

window.onclick = function(event) {
    if (!event.target.closest('.user-actions')) {
        const dropdown = document.getElementById('userDropdown');
        if(dropdown){
            // Use a slight delay or CSS transition for a smoother hide effect
            dropdown.style.display = 'none';
        }
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
                // Display the image using the dynamically sourced path
                if ($profilePicUrl !== $default_image) {
                    // FIX 3: Add a cache-busting query parameter to force browser refresh on file change
                    $cache_buster = time(); 
                    echo '<img src="'.$profilePicUrl.'?t='.$cache_buster.'" alt="Profile Image">';
                } else {
                    // Fallback: Display the first letter of the user's name
                    echo strtoupper(substr($userName, 0, 1)); 
                }
                ?>
            </div>
        </div>

        <div class="dropdown-menu" id="userDropdown">
            <a href="profile.php"><span>👤</span> My Profile</a>
            <a href="settings.php"><span>⚙️</span> Settings</a>
            <a href="logout.php"><span></span> Logout</a>
        </div>
    </div>
</header>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Overview</h1>
        <p>Welcome back, <?= $userName; ?> Manage your library system below.</p>
    </div>

    <div class="action-grid">
        <a href="add_book.php" class="card-btn"><span>+Add Book</span></a>
        <a href="view_books.php" class="card-btn"><span> View Books</span></a>
        <a href="manage_users.php" class="card-btn"><span>Manage Users</span></a>
    <a href="report.php" class="card-btn"><span>📊 System Report</span></a>
        
        <?php if ($userRole === 'super_admin'): ?>
        <a href="manage_admins.php" class="card-btn super-admin-card">
            <span> Manage Administrators</span>
        </a>
        <?php endif; ?>
        
        <a href="send_message.php" class="card-btn feedback-btn">
            <span>Send Feedback / Message Users</span>
        </a>
    </div>

   
</div>

</body>
</html>