<?php
/**
 * User Profile/Settings Page (edit_profile.php)
 * FINAL VERSION with Header, Form Logic, and Profile Picture check from DB.
 */
session_start();
include "db.php"; 

// ==========================================
// 🔧 DATABASE CONFIGURATION 
// ==========================================
$TABLE_NAME = 'users';      
$COL_ID     = 'id';         
$COL_NAME   = 'name';       
$COL_EMAIL  = 'email';      
$COL_PASS   = 'password';   
$COL_PIC    = 'profile_pic'; // Column to store the path/filename
// ==========================================

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_id = $_SESSION['user_id'];
$message = ''; 
// This is the path to the placeholder image if no picture is uploaded.
$default_pic = "assets/avatars/default.png"; 


// --- 1. FETCH USER DATA & PROFILE PIC PATH ---
if (!isset($conn)) { die("Error: Database connection (\$conn) is missing."); }

// Fetch Name, Email, and Profile Pic Path
$sql = "SELECT $COL_NAME, $COL_EMAIL, $COL_PIC FROM $TABLE_NAME WHERE $COL_ID = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing query: " . $conn->error . "<br>Check the column names at the top of the file!");
}
$stmt->bind_param("i", $current_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize variables for display
if (!$user) {
    $user = [$COL_NAME => '', $COL_EMAIL => '', $COL_PIC => ''];
    $userName = 'User';
} else {
    $userName = htmlspecialchars($user[$COL_NAME]);
}

$userInitial = strtoupper(substr($userName, 0, 1)); 
$profileLink = "edit_profile.php";

// Set the path for the header display: check if DB path exists on disk, otherwise use default.
$profilePicPath = ($user[$COL_PIC] && file_exists($user[$COL_PIC])) ? $user[$COL_PIC] : $default_pic;


// --- 2. UPDATE DATA ON POST REQUEST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postName = trim($_POST['form_name'] ?? '');
    $postEmail = trim($_POST['form_email'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confPass = $_POST['confirm_password'] ?? '';
    $errors = [];

    if (empty($postName)) $errors[] = "Name cannot be empty.";
    if (empty($postEmail)) $errors[] = "Email cannot be empty.";

    $doPassUpdate = false;
    if (!empty($newPass)) {
        if ($newPass !== $confPass) {
            $errors[] = "Passwords do not match.";
        } else {
            $doPassUpdate = true;
        }
    }

    if (empty($errors)) {
        if ($doPassUpdate) {
            // HASH PASSWORD HERE
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $sql = "UPDATE $TABLE_NAME SET $COL_NAME = ?, $COL_EMAIL = ?, $COL_PASS = ? WHERE $COL_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $postName, $postEmail, $hashed, $current_id);
        } else {
            $sql = "UPDATE $TABLE_NAME SET $COL_NAME = ?, $COL_EMAIL = ? WHERE $COL_ID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $postName, $postEmail, $current_id);
        }

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Saved successfully!</div>';
            // Update session and display variables after successful update
            $_SESSION['user_name'] = $postName; 
            $user[$COL_NAME] = $postName;
            $user[$COL_EMAIL] = $postEmail;
            $userName = $postName;
            $userInitial = strtoupper(substr($userName, 0, 1));
        } else {
            $message = '<div class="alert alert-error">Database error: ' . $conn->error . '</div>';
        }
    } else {
        $message = '<div class="alert alert-error">' . implode('<br>', $errors) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* CSS Styles */
        :root {
            --primary: #4e54c8; --secondary: #8f94fb; --accent: #ff6b6b;
            --warning: #f39c12; --bg-color: #f0f3f8; --text-dark: #2c3e50;
            --white: #ffffff; --shadow-deep: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-color); margin: 0; padding: 0; color: var(--text-dark); }
        header { 
            background: var(--primary); color: white; padding: 15px 40px; display: flex; 
            justify-content: space-between; align-items: center; box-shadow: 0 3px 12px rgba(0,0,0,0.2); 
        }
        .profile-dropdown { position: relative; z-index: 1000; }
        .profile-trigger {
            display: flex; align-items: center; gap: 10px; background: var(--white);
            padding: 5px; border-radius: 50px; color: var(--text-dark);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15); transition: all 0.2s; cursor: pointer;
        }
        .profile-trigger:hover { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
        .profile-info { display: flex; align-items: center; padding: 0 10px; user-select: none; font-weight: 600; }
        .profile-name { font-size: 15px; line-height: 1.2; }
        .profile-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--secondary); color: var(--white);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 600; overflow: hidden; flex-shrink: 0;
            border: 2px solid var(--white); 
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; } /* CRITICAL STYLE */
        .dropdown-menu {
            display: none; position: absolute; top: 60px; right: 0; background: var(--white);
            border-radius: 12px; box-shadow: var(--shadow-deep); width: 220px; 
            overflow: hidden; padding: 10px 0;
        }
        .dropdown-menu.active { display: block; }
        .dropdown-item {
            display: flex; align-items: center; gap: 15px; padding: 12px 20px;
            text-decoration: none; color: var(--text-dark); font-weight: 500; transition: background 0.2s;
        }
        .dropdown-item:hover { background: var(--bg-color); }
        .dropdown-item i { font-size: 18px; width: 20px; color: var(--secondary); }
        .dropdown-item.logout { color: var(--accent); border-top: 1px solid var(--bg-color); margin-top: 5px; padding-top: 15px; }
        .dropdown-item.logout i { color: var(--accent); }
        .container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        label { font-weight: 600; display: block; margin-bottom: 5px; }
        .btn { width: 100%; padding: 12px; background: var(--warning); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; transition: background 0.3s; }
        .btn:hover { background: #d68910; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 5px; font-weight: 500;}
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .profile-pic-link {
            display: inline-block; text-align: center; width: 100%; padding: 12px;
            margin-bottom: 20px; background: #3498db; color: white; border-radius: 5px;
            text-decoration: none; font-weight: 600; transition: background 0.3s;
        }
        .profile-pic-link:hover { background: #2a7fb3; }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" style="color: white; text-decoration: none; font-size: 18px; font-weight: 600;">&laquo; Back to Dashboard</a>

        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-trigger">
                <div class="profile-info">
                    <span class="profile-name"><?= $userName ?></span>
                </div>
                
                <div class="profile-avatar">
                    <?php 
                    // Check if a valid, non-default image path is set
                    if ($profilePicPath && $profilePicPath !== $default_pic) {
                        echo '<img src="' . htmlspecialchars($profilePicPath) . '" alt="Profile Image">';
                    } else {
                        // Fallback to initials if no custom image is found
                        echo $userInitial;
                    }
                    ?>
                </div>
            </div>

            <div class="dropdown-menu" id="dropdownMenu">
                <a href="<?= $profileLink ?>" class="dropdown-item">
                    <i class="fa-solid fa-user"></i> My Profile
                </a>
                <a href="update_profile_pic.php" class="dropdown-item">
                    <i class="fa-solid fa-image"></i> Update Photo
                </a>
                <a href="logout.php" class="dropdown-item logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <h2 style="text-align: center; margin-top: 10px; color: var(--primary);">Edit Profile</h2>
        
        <a href="update_profile_pic.php" class="profile-pic-link">
            <i class="fa-solid fa-camera"></i> Update Profile Picture
        </a>
        
        <?= $message ?>

        <form method="POST">
            <label>Name</label>
            <input type="text" name="form_name" value="<?= htmlspecialchars($user[$COL_NAME]) ?>" required>

            <label>Email</label>
            <input type="email" name="form_email" value="<?= htmlspecialchars($user[$COL_EMAIL]) ?>" required>

            <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 14px; color: #666;">Change Password (Optional)</p>

            <input type="password" name="new_password" placeholder="New Password">
            <input type="password" name="confirm_password" placeholder="Confirm Password">

            <button type="submit" class="btn">Update Profile</button>
        </form>
    </div>

<script>
    // --- JAVASCRIPT FOR TOGGLING THE DROPDOWN ---
    const profileDropdown = document.getElementById('profileDropdown');
    const dropdownMenu = document.getElementById('dropdownMenu');

    profileDropdown.addEventListener('click', function(event) {
        event.stopPropagation(); 
        dropdownMenu.classList.toggle('active');
    });

    document.addEventListener('click', function() {
        if (dropdownMenu.classList.contains('active')) {
            dropdownMenu.classList.remove('active');
        }
    });

    dropdownMenu.addEventListener('click', function(event) {
        event.stopPropagation();
    });
</script>
</body>
</html>