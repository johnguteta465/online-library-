<?php
/**
 * Download Books Page (download_books.php)
 * Lists all books currently borrowed by the user and provides download links.
 * FIX APPLIED: Changed b.file_path to b.pdf to match the existing column name in the books table.
 */
session_start();
include "db.php"; // Database connection

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_id = $_SESSION['user_id'];
$borrowed_books = [];
$error_message = "";

// Database Connection Check
if (!isset($conn) || $conn->connect_error) {
    die("Database Connection Failed: " . ($conn->connect_error ?? "Connection object not available. Check db.php."));
}

// --- 1. FETCH CURRENTLY BORROWED BOOKS WITH FILE PATHS ---
// Using b.pdf as the file path column
$sql = "SELECT b.id, b.title, b.pdf, br.borrow_date
        FROM borrows br
        JOIN books b ON br.book_id = b.id
        WHERE br.user_id = ? AND br.return_date IS NULL AND b.pdf IS NOT NULL
        ORDER BY br.borrow_date DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // If this fails again, there is a fundamental issue with table names or the connection.
    $error_message = "SQL Prepare Error: " . $conn->error;
} else {
    $stmt->bind_param("i", $current_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // We will now check and use the 'pdf' column value
        if (!empty($row['pdf'])) {
            $borrowed_books[] = $row;
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Downloads</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
/* Basic styles copied from dashboard for consistency */
:root {
    --primary: #4e54c8;
    --secondary: #8f94fb;
    --download: #9b59b6;
    --bg-color: #f0f3f8;
    --text-dark: #2c3e50;
    --text-light: #7f8c8d;
    --white: #ffffff;
    --shadow-deep: 0 15px 35px rgba(0, 0, 0, 0.1);
}

body { font-family: 'Poppins', sans-serif; background: var(--bg-color); color: var(--text-dark); margin: 0; padding: 0; }

.header-simple {
    background: var(--download);
    color: white; padding: 20px; text-align: center; position: relative;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}
.header-simple h2 { margin: 0; font-size: 24px; }

.back-btn {
    position: absolute; left: 20px; top: 50%; transform: translateY(-50%); 
    color: white; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 5px;
    transition: opacity 0.2s;
}
.back-btn:hover { opacity: 0.8; }

.container {
    max-width: 900px; margin: 40px auto; padding: 0 20px;
}

.book-list {
    background: var(--white);
    border-radius: 12px;
    box-shadow: var(--shadow-deep);
    padding: 20px;
}

.book-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 10px;
    border-bottom: 1px solid #eee;
}
.book-item:last-child { border-bottom: none; }

.book-info {
    text-align: left;
    flex-grow: 1;
}

.book-title {
    font-weight: 600;
    font-size: 18px;
    color: var(--text-dark);
}

.book-date {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 2px;
}

.download-link {
    background: var(--primary);
    color: var(--white);
    padding: 8px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.3s;
}

.download-link:hover {
    background: var(--secondary);
}

.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-light);
    background: var(--white);
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}
.empty-state i { font-size: 40px; margin-bottom: 15px; color: var(--text-light); }

.error-box {
    padding: 15px;
    margin-bottom: 20px;
    background-color: #fdd;
    border: 1px solid #f00;
    color: #c00;
    border-radius: 8px;
    font-weight: 500;
}
</style>
</head>
<body>

<div class="header-simple">
    <a href="dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    <h2>Download Library</h2>
</div>

<div class="container">

    <?php if ($error_message): ?>
        <div class="error-box"><i class="fa-solid fa-triangle-exclamation"></i> <?= $error_message ?></div>
    <?php endif; ?>

    <h3>Currently Borrowed E-Books (Available for Download)</h3>

    <div class="book-list">
        <?php if (!empty($borrowed_books)): ?>
            <?php foreach ($borrowed_books as $book): ?>
                <div class="book-item">
                    <div class="book-info">
                        <div class="book-title"><?= htmlspecialchars($book['title']) ?></div>
                        <div class="book-date">Borrowed on: <?= date('M d, Y', strtotime($book['borrow_date'])) ?></div>
                    </div>
                    <a href="handle_download.php?file=<?= urlencode($book['pdf']) ?>" class="download-link">
                        <i class="fa-solid fa-file-arrow-down"></i> Download
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-book-open"></i>
                <p>You currently do not have any digital books borrowed that are available for download.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>