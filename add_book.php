<?php
/**
 * Add Book Page (add_book.php)
 * Handles the form submission and file uploads for adding a new book to the database.
 */
session_start();
include "db.php"; // Assuming db.php is available

// --- SECURITY CHECK ---
$allowedRoles = ['admin', 'super_admin'];

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles)) {
    header("Location: login.php");
    exit;
}

// Load user data
$userName = htmlspecialchars($_SESSION['user_name']);
$userRole = htmlspecialchars($_SESSION['user_role']);
$message = '';
$message_type = ''; // success or error

// --- FILE UPLOAD CONFIGURATION ---
$maxFileSize = 5 * 1024 * 1024; // 5MB limit

if (isset($_POST['submit'])) {
    // 1. Data Sanitization and Validation
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $category = trim($_POST['category']);
    $year = intval($_POST['year']);
    $isbn = trim($_POST['isbn']);

    if (empty($title) || empty($author) || empty($isbn)) {
        $message = "Please fill in all required fields (Title, Author, ISBN).";
        $message_type = 'error';
    } else {

        // 2. Directories Setup
        $coverDir = 'uploads/covers/';
        $pdfDir = 'uploads/pdfs/';
        if (!is_dir($coverDir)) mkdir($coverDir, 0777, true);
        if (!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);

        // 3. Handle Cover Upload
        $coverPath = null;
        if (isset($_FILES['cover']) && $_FILES['cover']['error'] == 0) {
            if ($_FILES['cover']['size'] > $maxFileSize) {
                $message = "Cover image size exceeds the 5MB limit.";
                $message_type = 'error';
            } else {
                $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $message = "Invalid cover file type. Must be JPG, PNG, or GIF.";
                    $message_type = 'error';
                } else {
                    $coverPath = $coverDir . uniqid('cover_') . '.' . $ext;
                    if (!move_uploaded_file($_FILES['cover']['tmp_name'], $coverPath)) {
                        $message = "Failed to upload cover image.";
                        $message_type = 'error';
                        $coverPath = null; // Reset path if upload fails
                    }
                }
            }
        }

        // 4. Handle PDF Upload
        $pdfPath = null;
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] == 0) {
            if ($_FILES['pdf']['size'] > $maxFileSize) {
                $message = "PDF file size exceeds the 5MB limit.";
                $message_type = 'error';
            } else {
                $ext = strtolower(pathinfo($_FILES['pdf']['name'], PATHINFO_EXTENSION));
                if ($ext !== 'pdf') {
                    $message = "Invalid PDF file type. Must be PDF.";
                    $message_type = 'error';
                } else {
                    $pdfPath = $pdfDir . uniqid('pdf_') . '.' . $ext;
                    if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfPath)) {
                        $message = "Failed to upload PDF file.";
                        $message_type = 'error';
                        $pdfPath = null; // Reset path if upload fails
                    }
                }
            }
        }
        
        // Only proceed with DB insert if no major file errors occurred
        if ($message_type !== 'error') {
            // 5. Insert into Database
            $created_at = date("Y-m-d H:i:s");

            $stmt = $conn->prepare("INSERT INTO books (title, author, publisher, category, year, isbn, cover, pdf, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                $message = "Database prepare failed: (" . $conn->errno . ") " . $conn->error;
                $message_type = 'error';
            } else {
                $stmt->bind_param("ssssissss", $title, $author, $publisher, $category, $year, $isbn, $coverPath, $pdfPath, $created_at);

                if ($stmt->execute()) {
                    $message = "Book '**" . htmlspecialchars($title) . "**' added successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error inserting book: " . $stmt->error;
                    $message_type = 'error';
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Book - Admin</title>
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

/* Header Style */
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

/* Dashboard Card */
.dashboard {
    max-width: 700px;
    margin: 40px auto;
    background: var(--white);
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}
.dashboard h2 {
    color: var(--primary);
    margin-top: 0;
    font-weight: 700;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

/* Form Styling */
form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.full-width {
    grid-column: 1 / -1;
}

input[type="text"], 
input[type="number"], 
select {
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    width: 100%;
    box-sizing: border-box;
    font-family: inherit;
}

/* File Inputs are tricky to style, but we wrap them in a styled div/label */
.file-upload-group {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    background: #fdfdfd;
}
.file-upload-group label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-dark);
    margin-bottom: 5px;
}
input[type="file"] {
    border: none;
    padding: 0;
}

input[type="submit"] {
    grid-column: 1 / -1;
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
input[type="submit"]:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
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
    background: #e6ffed; /* Light Green */
    border: 1px solid #3ac47d;
    color: #2d6a4f;
}
.message.error {
    background: #ffe6e6; /* Light Red */
    border: 1px solid var(--accent);
    color: #a04444;
}

/* Back Link */
.back-link {
    display: block;
    width: fit-content;
    margin: 20px auto;
    text-decoration: none;
    color: var(--primary);
    font-weight: 500;
    padding: 10px 20px;
    border-radius: 50px;
    transition: 0.3s;
}
.back-link:hover {
    background: #e7e9ff;
}

@media (max-width: 600px) {
    .dashboard { padding: 20px; margin: 20px auto; }
    form { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header>
    <h1>Add New Book</h1>
    <div><?= $userName ?> (<?= strtoupper($userRole) ?>)</div>
</header>

<div class="dashboard">
    <h2>Book Information Entry </h2>
    
    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>
    
    <form action="add_book.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Book Title *" required value="<?= $_POST['title'] ?? '' ?>">
        <input type="text" name="author" placeholder="Author *" required value="<?= $_POST['author'] ?? '' ?>">
        
        <input type="text" name="publisher" placeholder="Publisher" value="<?= $_POST['publisher'] ?? '' ?>">
        <input type="text" name="category" placeholder="Category (e.g., Science, Fiction)" value="<?= $_POST['category'] ?? '' ?>">
        
        <input type="number" name="year" placeholder="Publication Year (e.g., 2023)" required value="<?= $_POST['year'] ?? '' ?>">
        <input type="text" name="isbn" placeholder="ISBN *" required value="<?= $_POST['isbn'] ?? '' ?>">
        
        <div class="file-upload-group">
            <label>Cover Image (JPG/PNG/GIF, max 5MB):</label>
            <input type="file" name="cover" accept="image/*">
        </div>
        
        <div class="file-upload-group">
            <label>Book PDF (Optional, max 5MB):</label>
            <input type="file" name="pdf" accept="application/pdf">
        </div>
        
        <input type="submit" name="submit" value="Add Book to Library">
    </form>
</div>

<a href="admin.php" class="back-link">⬅ Back to Admin Dashboard</a>

</body>
</html>