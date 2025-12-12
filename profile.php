<?php
/**
 * Admin/Super Admin Profile Management (profile.php) - FINAL CORRECTED VERSION
 * Ensures file saving consistency, deletes the previous picture, and includes a cache-buster for immediate display update.
 */
session_start();
include "db.php"; // Assumes db.php contains $conn

// --- Security Check for both Admin roles ---
$allowedRoles = ['admin', 'super_admin'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

$message = "";
$user_id = $_SESSION['user_id'];
$target_dir = "images/profile_pics/"; // Standard Target Directory
$standard_ext = 'jpg'; // Use a standard extension for consistent file naming

// --- Helper Function: Fetch Current Profile Pic from DB ---
function fetch_current_pic($conn, $user_id) {
    $stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['profile_pic'];
    }
    return null;
}

// Fetch the current path BEFORE processing the new upload
$current_db_path = fetch_current_pic($conn, $user_id);

// Handle File Upload
if (isset($_POST['upload_btn']) && isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
    
    // Ensure directory exists
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    $fileName = basename($_FILES["profile_image"]["name"]);
    $imageFileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Use the standard extension for the final file name
    $target_file = $target_dir . $user_id . '.' . $standard_ext; 
    
    $uploadOk = 1;

    // Validation checks
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if($check === false) {
        $message = "<div class='alert error'>File is not an image.</div>";
        $uploadOk = 0;
    }

    if ($_FILES["profile_image"]["size"] > 5000000) {
        $message = "<div class='alert error'>Sorry, your file is too large (max 5MB).</div>";
        $uploadOk = 0;
    }

    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        $message = "<div class='alert error'>Sorry, only JPG, JPEG, and PNG files are allowed.</div>";
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        
        // Delete the old file using the path stored in the database
        // This prevents old files with different extensions from remaining.
        if ($current_db_path && file_exists($current_db_path) && $current_db_path !== $target_file) {
            @unlink($current_db_path); // Use @ to suppress file not found errors
        }

        // Attempt to upload
        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            // Update Database with the new, standardized file path
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->bind_param("si", $target_file, $user_id);
            if($stmt->execute()) {
                $message = "<div class='alert success'>Profile picture updated!</div>";
                
                // Update the variable for immediate display and future deletion
                $current_db_path = $target_file; 
                // Update session variable for faster admin.php lookup
                $_SESSION['profile_pic'] = $target_file; 
            } else {
                $message = "<div class='alert error'>Database error: " . $conn->error . "</div>";
            }
        } else {
            $message = "<div class='alert error'>Sorry, there was an error uploading your file. Check folder permissions.</div>";
        }
    }
}

// Set display picture path for HTML. Use the updated path if successful.
$default_pic = $target_dir . "default_admin.png"; 
$displayPic = ($current_db_path && file_exists($current_db_path)) ? $current_db_path : $default_pic;

// NEW FIX: Generate a cache-buster timestamp for the image source
$cache_buster = time(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    /* Reuse your Dashboard Variables */
    :root { 
        --primary: #4e54c8; 
        --accent: #ff6b6b;
        --bg-color: #f4f7fc; 
        --white: #ffffff; 
        --text-dark: #2d3436; 
        --success: #2ecc71;
        --error: #e74c3c;
    }
    
    body { font-family: 'Poppins', sans-serif; background: var(--bg-color); margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    
    .card {
        background: var(--white);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    h2 { color: var(--primary); margin-top: 0; }
    
    .current-img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--primary); /* Use primary color border */
        margin-bottom: 20px;
        box-shadow: 0 0 0 4px var(--white);
    }

    /* File Input Styling */
    input[type="file"] { display: none; }
    
    .custom-file-upload {
        display: inline-block;
        padding: 10px 20px;
        /* Match Button style */
        background: #f0f0f5; 
        border: 1px solid #ddd;
        border-radius: 50px;
        cursor: pointer;
        font-size: 14px;
        margin-bottom: 15px;
        transition: 0.3s;
        color: var(--text-dark);
        font-weight: 500;
    }
    .custom-file-upload:hover { background: #e0e0e5; }

    .btn {
        background: var(--primary);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
        transition: 0.3s;
    }
    .btn:hover { background: #3a3fb0; }

    /* --- Back link styling --- */
    .back-link { 
        display: block; 
        margin-top: 25px; 
        text-decoration: none; 
        color: var(--primary); 
        font-size: 14px; 
        font-weight: 500;
        transition: color 0.2s;
    }
    .back-link:hover { color: var(--accent); }
    
    /* Alert Styling */
    .alert { padding: 12px; margin-bottom: 20px; border-radius: 8px; font-size: 14px; text-align: left; }
    .success { background: #d4edda; color: var(--success); border: 1px solid #c3e6cb;}
    .error { background: #f8d7da; color: var(--error); border: 1px solid #f5c6cb;}
</style>
</head>
<body>

<div class="card">
    <h2> Update Profile Picture</h2>
    <?= $message; ?>

    <img src="<?= $displayPic ?>?t=<?= $cache_buster ?>" class="current-img" alt="Current Profile Picture">

    <form action="" method="POST" enctype="multipart/form-data">
        <label for="profile_input" class="custom-file-upload">
            <input type="file" name="profile_image" id="profile_input" accept="image/jpeg, image/png" required>
            📷 Choose JPG or PNG
        </label>
        <br>
        <button type="submit" name="upload_btn" class="btn">Upload and Save</button>
    </form>

    <a href="admin.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>