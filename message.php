<?php
/**
 * Message Dialogue Page (message.php)
 * Displays a two-way chat thread between the current user and the admin/system.
 * Accessible by all authenticated users.
 */
session_start();
include "db.php"; // Ensure db.php is available

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);

// --- FIX: Fetch ALL messages relevant to this user ---
// This retrieves messages where the user is EITHER the sender OR the recipient.
$messages_stmt = $conn->prepare("
    SELECT 
        m.sender_id, m.content, m.sent_at, 
        u.name AS sender_name, u.role AS sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ? OR m.sender_id = ?
    ORDER BY m.sent_at ASC
");
$messages_stmt->bind_param("ii", $user_id, $user_id);
$messages_stmt->execute();
$result = $messages_stmt->get_result();

// --- OPTIONAL: Mark received messages as read upon viewing ---
// This only targets messages sent TO the user by others.
$mark_read_stmt = $conn->prepare("UPDATE messages SET is_read = TRUE WHERE recipient_id = ? AND is_read = FALSE");
$mark_read_stmt->bind_param("i", $user_id);
$mark_read_stmt->execute();
$mark_read_stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat with Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
/* * =========================================
 * 🎨 Harmonized CSS for Chat Interface
 * ========================================= */
:root {
    --primary: #4e54c8;      
    --secondary: #8f94fb;    
    --accent: #ff6b6b;       
    --bg-color: #f4f7fc;     
    --text-dark: #2d3436;
    --text-light: #636e72;
    --white: #ffffff;
    --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
    --chat-admin: #0984e3; /* Blue for admin */
    --chat-self: #dfe6e9; /* Light gray for user */
    --font: 'Poppins', sans-serif;
}

body {
    font-family: var(--font);
    background: var(--bg-color);
    color: var(--text-dark);
    margin: 0;
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

.chat-container {
    max-width: 450px;
    margin: 20px auto;
    padding: 0 15px;
}

.chat-box {
    width: 100%;
    background: var(--white);
    border-radius: 10px;
    padding: 15px;
    height: 50vh; /* Use viewport height for better sizing */
    overflow-y: scroll;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.msg {
    margin: 8px 0;
    padding: 10px 15px;
    border-radius: 10px;
    max-width: 80%;
    line-height: 1.4;
    word-wrap: break-word;
}

/* Current user's messages */
.from-self {
    background: var(--chat-self);
    color: var(--text-dark);
    margin-left: auto;
    border-top-right-radius: 0;
}

/* Messages from admin/other */
.from-other {
    background: var(--chat-admin);
    color: var(--white);
    margin-right: auto;
    border-top-left-radius: 0;
}

.msg strong {
    font-weight: 600;
}

.msg small {
    display: block;
    margin-top: 5px;
    font-size: 0.75em;
    opacity: 0.9;
    text-align: right;
}

form {
    width: 100%;
    margin-top: 10px;
    display: flex;
    gap: 5px;
}

input[type=text] {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-family: inherit;
}

button {
    padding: 12px 18px;
    background: var(--chat-admin);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s;
}
button:hover {
    background: #066fc2;
}

.back-link {
    display: block;
    width: fit-content;
    margin: 20px auto 40px auto;
    text-decoration: none;
    color: var(--primary);
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 50px;
    transition: 0.3s;
}
.back-link:hover {
    background: #e7e9ff;
}

</style>
</head>
<body>

<header>
    <h1>Chat Dialogue </h1>
    <div>Logged in as: **<?= $userName ?>** (<?= strtoupper($userRole) ?>)</div>
</header>

<div class="chat-container">
    <h2 style="text-align:center;">Conversation Thread</h2>

    <div class="chat-box">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($m = $result->fetch_assoc()): ?>
                <?php 
                $is_self = ($m['sender_id'] == $user_id);
                $class = $is_self ? 'from-self' : 'from-other';
                $sender_name_display = $is_self ? 'You' : htmlspecialchars($m['sender_name']);
                ?>
                <div class="msg <?= $class ?>">
                    <strong><?= $sender_name_display ?>:</strong><br>
                    <?= nl2br(htmlspecialchars($m['content'])) ?><br>
                    <small><?= date("M j, Y, H:i", strtotime($m['sent_at'])) ?></small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-light); margin-top: 50px;">
                Start a new conversation with the Admin.
            </p>
        <?php endif; ?>
    </div>

    <form action="send_chat_message.php" method="post">
        <input type="text" name="content" placeholder="Type a reply..." required>
        <button type="submit">Send</button>
    </form>
    
    <a href="<?= $userRole === 'member' ? 'dashboard.php' : 'admin.php' ?>" class="back-link">
        ⬅ Back to Dashboard
    </a>
</div>

<?php $messages_stmt->close(); ?>
</body>
</html>