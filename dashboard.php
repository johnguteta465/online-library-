<?php
/**
 * User Dashboard (dashboard.php)
 * Landing page for regular 'user' roles.
 * FEATURE ADDED: Link to download borrowed books.
 */
session_start();
include "db.php"; // Assuming db.php is available

// ==========================================
// 🔧 CONFIGURATION 
// ==========================================
$TABLE_NAME = 'users'; 
$COL_ID     = 'id';    
$COL_PIC    = 'profile_pic';
$default_pic_path = "assets/avatars/default.png";
$cache_buster = time();
// ==========================================

// --- 1. SECURITY CHECK & ROLE REDIRECTION ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_id = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'user';

// Redirect admins/super_admins who might access this page directly
if (in_array($userRole, ['admin', 'super_admin'])) {
    header("Location: admin.php");
    exit;
}

// --- 2. FETCH PROFILE DATA ---
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'Library User');
$userInitial = strtoupper(substr($userName, 0, 1)); 
$profilePicPath = $default_pic_path;

// Fetch the profile picture path from the database
if (isset($conn)) {
    $sql = "SELECT $COL_PIC FROM $TABLE_NAME WHERE $COL_ID = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $current_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        
        // Check if a path is stored and if the file actually exists on the server
        if ($user_data && $user_data[$COL_PIC] && file_exists($user_data[$COL_PIC])) {
            $profilePicPath = $user_data[$COL_PIC];
        }
        $stmt->close();
    }
}

// --- 3. CHECK FOR UNREAD NOTIFICATIONS ---
$unread_count = 0;
if (isset($conn)) {
    // IMPORTANT: Using recipient_id and is_read = FALSE based on your table schema
    $notif_sql = "SELECT COUNT(*) as count FROM messages WHERE recipient_id = ? AND is_read = FALSE";
    $n_stmt = $conn->prepare($notif_sql);
    
    if ($n_stmt) {
        $n_stmt->bind_param("i", $current_id);
        $n_stmt->execute();
        $res = $n_stmt->get_result();
        $row = $res->fetch_assoc();
        $unread_count = $row['count'];
        $n_stmt->close();
    } else {
        // Fallback or error logging if preparation fails (good practice)
        // echo "Notification SQL Prepare Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>📚 User Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* =========================================
 * 🎨 MODERNIZED VISUAL STANDARD CSS
 * ========================================= */
:root {
    --primary: #4e54c8;      /* Deep Blue */
    --secondary: #8f94fb;    /* Light Purple/Blue */
    --accent: #ff6b6b;       /* Soft Red (Alert/Logout) */
    --success: #2ecc71;      /* Green (Borrow) */
    --info: #3498db;         /* Blue (Return) */
    --warning: #f39c12;      /* Orange (Profile/Notification) */
    --download: #9b59b6;     /* Purple (New Download button) */
    --bg-color: #f0f3f8;
    --text-dark: #2c3e50;
    --text-light: #7f8c8d;
    --white: #ffffff;
    --shadow-deep: 0 15px 35px rgba(0, 0, 0, 0.1);
    --shadow-button: 0 4px 10px rgba(0, 0, 0, 0.1);
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-color);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

/* --- Header Style --- */
header {
    background: var(--primary);
    color: white;
    padding: 15px 40px; 
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 3px 12px rgba(0,0,0,0.2);
    position: relative;
}

header .portal-title { 
    font-size: 18px; 
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
header .portal-title img {
    height: 30px; /* Sizing the logo */
    border-radius: 4px;
}

/* =========================================
 * 👤 PROFILE DROPDOWN STYLES
 * ========================================= */
.profile-dropdown {
    position: relative;
    cursor: pointer;
    z-index: 1000;
}

.profile-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--white);
    padding: 5px 15px 5px 5px;
    border-radius: 50px;
    color: var(--text-dark);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    transition: background 0.3s;
}

.profile-trigger:hover { background: #f0f0f0; }

.profile-info {
    font-size: 15px;
    font-weight: 600;
    line-height: 1.2;
    text-align: right;
}

.profile-role {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 5px;
    border-radius: 4px;
    background: var(--info); /* Use a less jarring color for regular users */
    color: var(--white);
    display: inline-block;
    margin-top: 2px;
}

.profile-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--secondary);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: 600;
    overflow: hidden;
    flex-shrink: 0;
    border: 2px solid var(--white); 
}
.profile-avatar img { 
    width: 100%; 
    height: 100%; 
    object-fit: cover; 
} 

.dropdown-menu {
    position: absolute;
    top: 60px;
    right: 0;
    background: var(--white);
    border-radius: 12px;
    box-shadow: var(--shadow-deep);
    width: 200px;
    overflow: hidden;
    padding: 10px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: opacity 0.3s, transform 0.3s, visibility 0.3s;
}

.dropdown-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none;
    color: var(--text-dark);
    font-weight: 500;
    transition: background 0.2s;
}

.dropdown-item:hover { background: var(--bg-color); }

.dropdown-item i { font-size: 18px; width: 20px; }

/* Specific styling for Logout */
.dropdown-item.logout {
    color: var(--accent);
    border-top: 1px solid var(--bg-color);
    margin-top: 5px;
    padding-top: 15px;
}
.dropdown-item.logout i { color: var(--accent); }

/* --- Container and Button Styles --- */
.container {
    max-width: 950px; /* Increased max width to fit 5 buttons better */
    background: var(--white);
    margin: 50px auto;
    padding: 40px;
    border-radius: 20px;
    box-shadow: var(--shadow-deep);
    text-align: center;
}
.btn-container { 
    /* Now uses 5 columns if enough space is available */
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); 
    gap: 25px;
    margin-top: 30px; 
}
a.btn {
    text-decoration: none;
    background: var(--white);
    color: var(--text-dark);
    border: none;
    padding: 25px 15px;
    border-radius: 16px;
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    box-shadow: var(--shadow-button);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    line-height: 1.4;
    position: relative; /* Needed for the badge */
}
a.btn i {
    font-size: 28px; /* Larger icon size */
    margin-bottom: 8px;
    display: block;
}

a.btn:hover { 
    background: var(--white); 
    color: var(--primary);
    transform: translateY(-8px);
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
}

/* Color coordination for icons */
a[href="borrow.php"] i { color: var(--success); }
a[href="return.php"] i { color: var(--info); }
a[href="my_borrows.php"] i { color: var(--secondary); }
a[href="notifications.php"] i { color: var(--warning); } 
/* NEW COLOR FOR DOWNLOAD BUTTON */
a[href="download_books.php"] i { color: var(--download); }

/* --- Notification Badge Style (NEW) --- */
.notif-badge {
    position: absolute; 
    top: 10px; 
    right: 15px; 
    background: var(--accent); 
    color: white; 
    border-radius: 50%; 
    width: 24px; 
    height: 24px; 
    font-size: 12px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-weight: bold; 
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>
</head>
<body>

<header>
    <span class="portal-title">
        <img src="ambo.png" alt="University Logo">
        Library User Portal
    </span>

    <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-trigger">
            <div class="profile-info">
                <span><?= $userName ?></span>
                <span class="profile-role"><?= strtoupper($userRole) ?></span>
            </div>
            
            <div class="profile-avatar">
                <?php 
                // Display the image with a cache buster, or the initial if no image
                if ($profilePicPath && $profilePicPath !== $default_pic_path) {
                    echo '<img src="' . htmlspecialchars($profilePicPath) . '?t=' . $cache_buster . '" alt="Profile Image">';
                } else {
                    echo $userInitial;
                }
                ?>
            </div>
        </div>

        <div class="dropdown-menu" id="dropdownMenu">
            <a href="edit_profile.php" class="dropdown-item">
                <i class="fa-solid fa-user-edit"></i> Edit Profile
            </a>
            <a href="my_borrows.php" class="dropdown-item">
                <i class="fa-solid fa-book-reader"></i> My Borrowed
            </a>
            
            <a href="notifications.php" class="dropdown-item">
                <i class="fa-solid fa-bell"></i> Notifications 
                <?php if($unread_count > 0): ?>
                    <span class="notif-badge" style="position: static; margin-left: auto; width: 20px; height: 20px; font-size: 10px;"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            
            <a href="download_books.php" class="dropdown-item">
                <i class="fa-solid fa-download"></i> Download Books
            </a>
            
            <a href="logout.php" class="dropdown-item logout">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
            
        </div>
    </div>
</header>

<div class="container">
    <h2>Welcome Back, <?= $userName ?></h2>
    <p style="color: var(--text-light);">Access your library services and account settings below.</p>
    <hr>
    <div class="btn-container">
        <a href="borrow.php" class="btn">
            <i class="fa-solid fa-cart-plus"></i> Borrow Book
        </a>
        <a href="return.php" class="btn">
            <i class="fa-solid fa-undo"></i> Return Book
        </a>
        <a href="my_borrows.php" class="btn">
            <i class="fa-solid fa-list-ul"></i> My Borrowed Books
        </a>
        
        <a href="download_books.php" class="btn">
            <i class="fa-solid fa-download"></i> Download Books
        </a>
        
        <a href="notifications.php" class="btn">
            <?php if($unread_count > 0): ?>
                <span class="notif-badge"><?= $unread_count ?></span>
            <?php endif; ?>
            <i class="fa-solid fa-bell"></i> Notifications
        </a>
        
    </div>
</div>

<script>
    // --- JAVASCRIPT FOR TOGGLING THE DROPDOWN ---
    const profileDropdown = document.getElementById('profileDropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');

    // Toggle the 'active' class on click
    profileDropdown.addEventListener('click', function(event) {
        event.stopPropagation(); 
        dropdownMenu.classList.toggle('active');
    });

    // Close the dropdown if the user clicks anywhere else on the page
    document.addEventListener('click', function() {
        if (dropdownMenu.classList.contains('active')) {
            dropdownMenu.classList.remove('active');
        }
    });

    // Prevent closing the menu when clicking INSIDE the menu itself
    dropdownMenu.addEventListener('click', function(event) {
        event.stopPropagation();
    });
</script>

</body>
</html>