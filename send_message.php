<?php
/**
 * Send Message Page (send_message.php)
 * Allows Admins/Super Admins to send specific messages to users, filtered to those 
 * who have an entry in the 'borrows' table.
 */
session_start();
include "db.php"; 

// --- SECURITY CHECK (Allow Admin and Super Admin) ---
$allowedRoles = ['admin', 'super_admin'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

$sender_id = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);
$message_display = "";
$message_type = "";

// =========================================
// ✅ Fetch Borrowing Users Only
// =========================================
// Fetch users who have borrowed a book (distinct to avoid duplicates)
$users_result = $conn->query("
    SELECT DISTINCT u.id, u.name 
    FROM users u
    JOIN borrows b ON u.id = b.user_id
    ORDER BY u.name ASC
");

// =========================================
// ✅ Handle Send Message (SECURE)
// =========================================
if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $recipient_id = intval($_POST['recipient_id']); // Renamed from user_id
    $content = trim($_POST['content']); // Renamed from message
    $subject = "Admin Notification regarding Borrows"; // Default subject

    if ($recipient_id && $content) {
        // Use the established column names: sender_id, recipient_id, subject, content
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
             $message_display = "Database prepare failed: (" . $conn->errno . ") " . $conn->error;
             $message_type = 'error';
        } else {
            $stmt->bind_param("iiss", $sender_id, $recipient_id, $subject, $content);
        
            if ($stmt->execute()) {
                $message_display = "Message successfully sent to User ID **$recipient_id**!";
                $message_type = 'success';
                // Clear post data to prevent resend on refresh
                unset($_POST); 
            } else {
                $message_display = "Error sending message: " . $stmt->error;
                $message_type = 'error';
            }
            $stmt->close();
        }
    } else {
        $message_display = "Please select a user and enter a message.";
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin: Send Message to Borrowers</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* * =========================================
 * 🎨 Harmonized CSS from admin.php
 * =========================================
 */
:root {
    --primary: #4e54c8;      
    --secondary: #8f94fb;    
    --accent: #ff6b6b;       
    --bg-color: #f4f7fc;     
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
}

body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg-color);
    margin: 0;
    padding: 0;
    color: var(--text-dark);
}

header { 
    background: var(--primary); 
    color: white; 
    padding: 15px 40px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    box-shadow: 0 3px 10px rgba(0,0,0,0.15);
}
header h1{margin:0; font-size: 20px;}
header div { font-size: 14px; font-weight: 500; opacity: 0.9; }

.container {
    max-width: 600px;
    margin: 40px auto;
    background: var(--white);
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-soft);
}

h2 {
    color: var(--primary);
    border-bottom: 1px solid #e0e0e0;
    padding-bottom: 10px;
    margin-bottom: 20px;
    font-weight: 700;
}

/* Form Styling */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

label {
    font-weight: 500;
    color: var(--text-dark);
    margin-top: 5px;
}

select, textarea {
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    width: 100%;
    box-sizing: border-box;
    font-family: inherit;
    resize: vertical;
}

textarea {
    min-height: 120px;
}

button[type="submit"] {
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    color: white;
    padding: 14px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: 0.3s;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
button[type="submit"]:hover {
    opacity: 0.9;
}

/* Messages */
.message {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
}
.message.success {
    background: #e6ffed;
    border: 1px solid #3ac47d;
    color: #2d6a4f;
}
.message.error {
    background: #ffe6e6;
    border: 1px solid var(--accent);
    color: #a04444;
}

/* Back Link */
.back-link {
    display: block;
    width: fit-content;
    margin-top: 20px;
    text-decoration: none;
    color: var(--primary);
    font-weight: 600;
    padding: 10px 0;
    transition: 0.3s;
}
.back-link:hover {
    color: var(--secondary);
}
</style>
</head>
<body>

<header>
    <h1>Send Message to Borrowers </h1>
    <div>Sender: <?= $userName ?>(<?= strtoupper($userRole) ?>)</div>
</header>

<div class="container">
    <h2>Notify Borrowers</h2>

    <?php if ($message_display): ?>
        <div class="message <?= $message_type ?>">
            <?= $message_display ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="recipient_id">Select User (Only Borrowers Shown):</label>
        <select name="recipient_id" id="recipient_id" required>
            <option value="">-- Choose Borrower --</option>
            <?php if ($users_result->num_rows > 0): ?>
                <?php while($u = $users_result->fetch_assoc()): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (ID: <?= $u['id'] ?>)</option>
                <?php endwhile; ?>
            <?php else: ?>
                 <option value="" disabled>No users currently borrowing books.</option>
            <?php endif; ?>
        </select>

        <label for="content">Message Content:</label>
        <textarea name="content" id="content" rows="6" placeholder="Write your message here (e.g., Reminder: Your book is due tomorrow)." required><?= $_POST['content'] ?? '' ?></textarea>

        <button type="submit">Send Message</button>
    </form>

    <a href="admin.php" class="back-link">⬅ Back to Admin Dashboard</a>
</div>
</body>
</html>