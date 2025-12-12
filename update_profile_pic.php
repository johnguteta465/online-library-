<?php
/**
 * User Profile Picture Update Page (update_profile_pic.php)
 * Uses the database to store the image path (filename).
 */
session_start();
include "db.php"; 

// ==========================================
// 🔧 CONFIGURATION 
// ==========================================
$TABLE_NAME = 'users';      // Your table name
$COL_ID     = 'id';         // Your ID column
$COL_PIC    = 'profile_pic'; // Column to store the path/filename
// ---
$current_id = $_SESSION['user_id'] ?? null;
$message = ''; 
// Physical path relative to THIS script (must be writable)
$target_dir = 'assets/avatars/'; 
$file_extension = 'jpg'; // We enforce this in the logic below
// ==========================================


// --- SECURITY CHECK ---
if (!$current_id) {
    header("Location: login.php");
    exit;
}

// Helper: Fetch Name for Display
// NOTE: Assuming user_name is stored in session after login
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
$userInitial = strtoupper(substr($userName, 0, 1)); 

// Helper: Fetch Current Profile Pic Path from DB
function fetch_current_pic($conn, $table, $col_id, $col_pic, $user_id) {
    $stmt = $conn->prepare("SELECT $col_pic FROM $table WHERE $col_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row[$col_pic];
    }
    return null;
}

// Define the expected path for the current picture check
$current_db_path = fetch_current_pic($conn, $TABLE_NAME, $COL_ID, $COL_PIC, $current_id);
$displayPic = $current_db_path && file_exists($current_db_path) ? $current_db_path : null;


// --- FILE UPLOAD LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    $errors = [];
    $uploadOk = 1;

    // 1. Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload failed with error code: " . $file['error'];
        $uploadOk = 0;
    }

    // 2. Check if image file is an actual image
    if ($uploadOk) {
        $check = getimagesize($file["tmp_name"]);
        if($check === false) {
            $errors[] = "File is not an image.";
            $uploadOk = 0;
        }
    }
    
    // 3. Define the final filename and path
    // We force the name to be user_{id}.jpg, overwriting any previous file
    $final_filename = 'user_' . $current_id . '.' . $file_extension;
    $target_file = $target_dir . $final_filename; 

    // 4. Check file size (e.g., limit to 5MB)
    if ($file['size'] > 5000000) {
        $errors[] = "File size must be less than 5MB.";
        $uploadOk = 0;
    }

    // 5. Allow certain file formats and enforce .jpg extension
    $fileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if($fileType != "jpg" && $fileType != "png" && $fileType != "jpeg") {
        $errors[] = "Only JPG, JPEG, and PNG files are allowed.";
        $uploadOk = 0;
    }

    if ($uploadOk) {
        // Create the upload directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Attempt to move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Update Database with the new file path
            $stmt = $conn->prepare("UPDATE $TABLE_NAME SET $COL_PIC = ? WHERE $COL_ID = ?");
            $stmt->bind_param("si", $target_file, $current_id);
            
            if($stmt->execute()) {
                $message = '<div class="alert alert-success">Profile picture updated!</div>';
                // Update display variables immediately
                $displayPic = $target_file;
            } else {
                $message = "<div class='alert alert-error'>Database error: " . $conn->error . "</div>";
            }
        } else {
            $message = '<div class="alert alert-error">Error moving file. Check permissions on `assets/avatars/`.</div>';
        }
    } else {
        $message = '<div class="alert alert-error"><ul><li>' . implode('</li><li>', $errors) . '</li></ul></div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile Picture</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        /* Shared Styles */
        :root {
            --primary: #4e54c8; --secondary: #8f94fb; --accent: #ff6b6b;
            --success: #2ecc71; --bg-color: #f0f3f8; --text-dark: #2c3e50;
            --white: #ffffff; --shadow-deep: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-color); color: var(--text-dark); margin: 0; padding: 0;}
        .container { max-width: 450px; margin: 80px auto; background: var(--white); padding: 40px; border-radius: 10px; box-shadow: var(--shadow-deep); text-align: center; }
        h2 { color: var(--primary); margin-bottom: 30px; font-weight: 700; }
        .profile-pic-container {
            width: 150px; height: 150px; border-radius: 50%;
            background: var(--secondary); color: var(--white); font-size: 40px; font-weight: 600;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 30px; 
            overflow: hidden; box-shadow: 0 0 0 5px var(--bg-color), 0 0 0 8px var(--primary); 
        }
        .profile-pic-container img { width: 100%; height: 100%; object-fit: cover; }
        .input-group { position: relative; overflow: hidden; display: inline-block; margin-bottom: 20px; }
        .input-group input[type="file"] { position: absolute; left: 0; top: 0; opacity: 0; cursor: pointer; }
        .file-label {
            background: var(--white); color: var(--text-dark); border: 1px solid #ddd; padding: 10px 20px; 
            border-radius: 25px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); font-weight: 500;
        }
        .file-label:hover { background: #eee; border-color: var(--primary); }
        .upload-btn {
            background: var(--primary); color: var(--white); border: none; padding: 15px 30px; 
            border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; transition: background 0.3s; 
            width: 100%; margin-top: 10px;
        }
        .upload-btn:hover { background: #3d42b0; }
        .back-link { display: block; margin-top: 30px; color: var(--primary); text-decoration: none; font-weight: 500; }
        .back-link:hover { text-decoration: underline; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; text-align: left; }
        .alert-success { background-color: #e6ffe6; color: var(--success); border: 1px solid var(--success); }
        .alert-error { background-color: #ffe6e6; color: var(--accent); border: 1px solid var(--accent); }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Profile Picture</h2>
        
        <?= $message ?>

        <div class="profile-pic-container">
            <?php 
            if ($displayPic) {
                // Since the path is stored in the DB, we use it directly for src
                echo '<img src="' . htmlspecialchars($displayPic) . '" alt="' . $userName . '">';
            } else {
                // Fallback to initials
                echo $userInitial;
            }
            ?>
        </div>

        <form action="update_profile_pic.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <input type="file" name="profile_pic" id="profile_pic" accept=".jpg, .jpeg, .png" required>
                <label for="profile_pic" class="file-label">
                    <i class="fa-solid fa-camera"></i> Choose JPG or PNG
                </label>
            </div>
            
            <button type="submit" class="upload-btn">Upload and Save</button>
        </form>

        <a href="edit_profile.php" class="back-link">&larr; Back to Settings</a>
    </div>
    
    <script>
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Choose JPG or PNG';
            document.querySelector('.file-label').innerHTML = '<i class="fa-solid fa-camera"></i> ' + fileName;
        });
    </script>
</body>
</html>