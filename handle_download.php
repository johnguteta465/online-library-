<?php
/**
 * Secure File Download Handler (handle_download.php)
 * Checks user authorization before serving a file.
 * FIX APPLIED: Changed column name in SQL to b.pdf.
 */
session_start();
include "db.php"; 

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); 
    die("Access Denied. Please log in.");
}

$current_id = $_SESSION['user_id'];
$file_path_param = $_GET['file'] ?? ''; // This is the content of the 'pdf' column passed via URL

if (empty($file_path_param)) {
    http_response_code(400); 
    die("File parameter missing.");
}

// --- 1. VERIFY AUTHORIZATION ---
// We retrieve the secure path from the database using the link provided in the URL.
$sql = "SELECT b.title, b.pdf 
        FROM books b
        JOIN borrows br ON b.id = br.book_id
        WHERE br.user_id = ? 
          AND br.return_date IS NULL 
          AND b.pdf = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    error_log("Download SQL Prepare Error: " . $conn->error);
    http_response_code(500);
    die("Server error during authorization.");
}

$stmt->bind_param("is", $current_id, $file_path_param);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();
$stmt->close();

if (!$book) {
    http_response_code(403); 
    die("You are not authorized to download this file or the borrow period has ended.");
}

// --- 2. SERVE THE FILE ---
$full_path = $book['pdf']; // Use the secure, full path retrieved from the database

if (!file_exists($full_path)) {
    http_response_code(404);
    die("File not found on the server: " . htmlspecialchars($full_path));
}

// Determine file type (MIME type)
$mime = mime_content_type($full_path);
if ($mime === false) {
    $mime = 'application/octet-stream'; 
}

// Set headers for file download
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($full_path) . '"');
header('Content-Length: ' . filesize($full_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Output file contents
readfile($full_path);
exit;
?>