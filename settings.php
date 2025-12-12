<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_role'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];
$message = "";

// Handle Password Update
if (isset($_POST['update_pass'])) {
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    if ($new_pass === $confirm_pass) {
        // In a real app, hash this password!
        // $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT); 
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_pass, $user_id);
        
        if($stmt->execute()) {
            $message = "<div class='alert success'>Password changed successfully!</div>";
        } else {
            $message = "<div class='alert error'>Error updating password.</div>";
        }
    } else {
        $message = "<div class='alert error'>Passwords do not match.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    /* Same Visual Standard */
    :root { --primary: #4e54c8; --bg-color: #f4f7fc; --white: #ffffff; --text-dark: #2d3436; }
    body { font-family: 'Poppins', sans-serif; background: var(--bg-color); margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    
    .card { background: var(--white); padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
    h2 { color: var(--primary); text-align: center; margin-top: 0; }
    
    .form-group { margin-bottom: 15px; }
    label { display: block; margin-bottom: 5px; font-size: 14px; font-weight: 500; }
    input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; }
    input:focus { border-color: var(--primary); outline: none; }

    .btn { background: var(--primary); color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; width: 100%; font-size: 16px; margin-top: 10px; }
    .back-link { display: block; margin-top: 20px; text-align: center; text-decoration: none; color: #666; font-size: 14px; }
    
    .alert { padding: 10px; margin-bottom: 15px; border-radius: 8px; font-size: 14px; text-align: center; }
    .success { background: #d4edda; color: #155724; }
    .error { background: #f8d7da; color: #721c24; }
</style>
</head>
<body>

<div class="card">
    <h2>Account Settings</h2>
    <?= $message; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_pass" required placeholder="Enter new password">
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_pass" required placeholder="Repeat password">
        </div>
        <button type="submit" name="update_pass" class="btn">Update Password</button>
    </form>

    <a href="index.php" class="back-link">← Back to Dashboard</a>
</div>

</body>
</html>