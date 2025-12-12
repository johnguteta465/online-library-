<?php
/**
 * send_chat_message.php - Processes a reply sent from the message.php chat interface.
 */
session_start();
include "db.php"; 

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$sender_id = $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
// --- FIX: Define a default recipient admin ID ---
// You MUST ensure the user with ID 1 exists and is an admin/super_admin.
$recipient_admin_id = 1; 
$subject = "User Dialogue Reply from " . htmlspecialchars($_SESSION['user_name']);

if (!empty($content)) {
    // Insert the user's message into the messages table
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, subject, content) VALUES (?, ?, ?, ?)");
    
    if (!$stmt) {
        // Handle prepare error (e.g., if columns are still wrong)
        error_log("DB Prepare Error: " . $conn->error);
        $message = urlencode("Error: Could not prepare database statement. Check columns.");
        header("Location: message.php?msg=" . $message . "&type=error");
        exit;
    }
    
    $stmt->bind_param("iiss", $sender_id, $recipient_admin_id, $subject, $content);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to the chat page to see the new message
header("Location: message.php");
exit;
?>