<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Check DB connection
if ($conn->connect_errno) die("DB connection failed: " . $conn->connect_error);

// Handle return form submission
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['borrow_id'])) {
    $borrow_id = intval($_POST['borrow_id']);

    // Get borrowed book info
    $stmt = $conn->prepare("
        SELECT b.book_id, bk.title 
        FROM borrows b
        JOIN books bk ON b.book_id = bk.id
        WHERE b.id=? AND b.user_id=? AND b.status='borrowed'
    ");
    if (!$stmt) die("Prepare failed (fetch borrow): " . $conn->error);
    $stmt->bind_param("ii", $borrow_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $borrow = $result->fetch_assoc();
        $book_id = $borrow['book_id'];
        $book_title = $borrow['title'];

        // Update borrows table
        $stmtUpdate = $conn->prepare("UPDATE borrows SET status='returned', return_date=NOW() WHERE id=?");
        if (!$stmtUpdate) die("Prepare failed (update borrows): " . $conn->error);
        $stmtUpdate->bind_param("i", $borrow_id);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Update book quantity
        $stmtQty = $conn->prepare("UPDATE books SET quantity = quantity + 1 WHERE id=?");
        if (!$stmtQty) die("Prepare failed (update books): " . $conn->error);
        $stmtQty->bind_param("i", $book_id);
        $stmtQty->execute();
        $stmtQty->close();

        // Add notification
        $admin_id = 1; // Super admin ID
        $notif_msg = "You returned a book: " . $book_title;
        $notif = $conn->prepare("INSERT INTO notifications (user_id, admin_id, message) VALUES (?, ?, ?)");
        if (!$notif) die("Prepare failed (insert notification): " . $conn->error);
        $notif->bind_param("iis", $user_id, $admin_id, $notif_msg);
        $notif->execute();
        $notif->close();

        $msg = "Book returned successfully ";

    } else {
        $msg = "Invalid selection or book already returned ";
    }

    $stmt->close();
}

// Fetch borrowed books for this user
$stmtBooks = $conn->prepare("
    SELECT b.id, bk.title, bk.author
    FROM borrows b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id=? AND b.status='borrowed'
");
if (!$stmtBooks) die("Prepare failed (fetch borrowed books): " . $conn->error);
$stmtBooks->bind_param("i", $user_id);
$stmtBooks->execute();
$borrowed = $stmtBooks->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return Book</title>
<style>
/* GENERAL STYLES */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #74ebd5, #ACB6E5);
    margin: 0;
    padding: 0;
}
.container {
    max-width: 550px;
    margin: 60px auto;
    background: #ffffffcc;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
    transition: 0.3s ease-in-out;
}
.container:hover {
    transform: translateY(-5px);
}

/* HEADINGS */
h2 {
    color: #2d3436;
    font-size: 28px;
    margin-bottom: 25px;
}

/* FORM */
form select, form button {
    width: 100%;
    padding: 12px 15px;
    margin: 12px 0;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 16px;
    transition: 0.3s;
}
form select:focus {
    border-color: #0984e3;
    outline: none;
    box-shadow: 0 0 8px rgba(9,132,227,0.3);
}
form button {
    background: #0984e3;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}
form button:hover {
    background: #74b9ff;
    transform: scale(1.03);
}

/* MESSAGES */
.message {
    margin: 20px 0;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    opacity: 0.95;
    animation: fadein 0.5s ease-in-out;
}
.message.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* BACK LINK */
a.back-link {
    display: inline-block;
    margin-top: 25px;
    text-decoration: none;
    color: #0984e3;
    font-weight: 500;
    transition: 0.3s;
}
a.back-link:hover {
    text-decoration: underline;
    color: #74b9ff;
}

/* ANIMATION */
@keyframes fadein {
    from {opacity: 0; transform: translateY(-10px);}
    to {opacity: 1; transform: translateY(0);}
}
</style>
</head>
<body>
<div class="container">
<h2>Return Borrowed Book</h2>

<?php if($msg): ?>
<div class="message <?= strpos($msg,'successfully')!==false?'success':'error' ?>">
<?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<form method="POST">
<select name="borrow_id" required>
<option value="">Select a borrowed book</option>
<?php while($b = $borrowed->fetch_assoc()): ?>
<option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?> - <?= htmlspecialchars($b['author']) ?></option>
<?php endwhile; ?>
</select>
<button type="submit">Return Book</button>
</form>

<a class="back-link" href="dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
<?php $stmtBooks->close(); ?>
