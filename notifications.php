<?php
/**
 * Notifications Page (notifications.php)
 * Displays messages sent by admins (recipients are the current user).
 * Fixed JOIN query to use the 'name' column from the users table.
 */
session_start();
include "db.php";

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; // This is the recipient_id

// --- Database Connection Check ---
if (!isset($conn) || $conn->connect_error) {
    die("Database Connection Failed: " . ($conn->connect_error ?? "Connection object not available. Check db.php."));
}

// --- 1. MARK ALL AS READ ---
$update_sql = "UPDATE messages SET is_read = TRUE WHERE recipient_id = ? AND is_read = FALSE";
$stmt = $conn->prepare($update_sql);

if ($stmt === false) {
    die("SQL Prepare Error (Update): " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();


// --- 2. FETCH MESSAGES ---
$messages = [];
// Select messages where the current user is the recipient
// FIX APPLIED: Changed u.user_name to u.name
$fetch_sql = "SELECT m.*, u.name AS sender_name 
              FROM messages m
              JOIN users u ON m.sender_id = u.id
              WHERE m.recipient_id = ? 
              ORDER BY m.sent_at DESC";
              
$stmt = $conn->prepare($fetch_sql);

if ($stmt === false) {
    // If this error is hit, the problem is likely in the table names or JOIN condition
    die("SQL Prepare Error (Select): " . $conn->error); 
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Notifications</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
    /* Same root variables as dashboard for consistency */
    :root {
        --primary: #4e54c8;
        --secondary: #8f94fb;
        --bg-color: #f0f3f8;
        --text-dark: #2c3e50;
        --text-light: #7f8c8d;
        --white: #ffffff;
        --shadow-deep: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    body { font-family: 'Poppins', sans-serif; background: var(--bg-color); color: var(--text-dark); margin: 0; padding: 0; }
    
    .header-simple {
        background: var(--primary); color: white; padding: 20px; text-align: center; position: relative;
    }
    .back-btn {
        position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: white; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 5px;
    }
    
    .container {
        max-width: 800px; margin: 40px auto; padding: 0 20px;
    }
    
    .msg-card {
        background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-left: 5px solid var(--secondary); animation: fadeIn 0.5s ease;
    }
    .msg-meta {
        display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 12px; color: var(--text-light); text-transform: uppercase; letter-spacing: 1px;
    }
    .msg-header {
        font-weight: 600; font-size: 18px; margin-bottom: 10px; color: var(--primary);
    }
    .msg-body { font-size: 15px; line-height: 1.6; color: var(--text-dark); }
    .no-msg { text-align: center; color: var(--text-light); margin-top: 50px; }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>

<div class="header-simple">
    <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    <h2>My Messages & Alerts</h2>
</div>

<div class="container">
    <?php if (empty($messages)): ?>
        <div class="no-msg">
            <i class="fa-regular fa-envelope-open" style="font-size: 40px; margin-bottom: 15px;"></i>
            <p>You have no new or old messages.</p>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $msg): ?>
            <div class="msg-card">
                <div class="msg-meta">
                    <span><i class="fa-solid fa-user-shield"></i> From: <?= htmlspecialchars($msg['sender_name'] ?? 'System Admin') ?></span> 
                    <span><?= date('M d, Y h:i A', strtotime($msg['sent_at'])) ?></span>
                </div>
                <div class="msg-header">
                    <?= htmlspecialchars($msg['subject']) ?>
                </div>
                <div class="msg-body">
                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>