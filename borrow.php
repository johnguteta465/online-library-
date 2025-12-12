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

// Handle borrow form submission
if ($_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);

    // Check book quantity
    $check = $conn->prepare("SELECT title, author, quantity FROM books WHERE id=?");
    if (!$check) die("Prepare failed (check book): " . $conn->error);
    $check->bind_param("i", $book_id);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();

    if ($result && $result['quantity'] > 0) {
        $book_title = $result['title'];

        // Insert borrow record
        $stmt = $conn->prepare("INSERT INTO borrows (user_id, book_id, borrow_date, status) VALUES (?, ?, NOW(), 'borrowed')");
        if (!$stmt) die("Prepare failed (insert borrow): " . $conn->error);
        $stmt->bind_param("ii", $user_id, $book_id);

        if ($stmt->execute()) {
            // Decrease book quantity
            $update = $conn->prepare("UPDATE books SET quantity = quantity - 1 WHERE id=?");
            if (!$update) die("Prepare failed (update quantity): " . $conn->error);
            $update->bind_param("i", $book_id);
            $update->execute();
            $update->close();

            // Add notification
            $admin_id = 1; // super admin ID
            $notif_msg = "You borrowed a book: " . $book_title;
            $notif = $conn->prepare("INSERT INTO notifications (user_id, admin_id, message) VALUES (?, ?, ?)");
            if (!$notif) die("Prepare failed (insert notification): " . $conn->error);
            $notif->bind_param("iis", $user_id, $admin_id, $notif_msg);
            $notif->execute();
            $notif->close();

            $msg = "Book borrowed successfully ";
        } else {
            $msg = "Error borrowing book ";
        }
        $stmt->close();
    } else {
        $msg = "Sorry, this book is not available ";
    }
    $check->close();
}

// Fetch available books
$books = $conn->query("SELECT * FROM books WHERE quantity > 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Borrow Book</title>
<style>
body { font-family: Arial, sans-serif; background: #f1f2f6; margin:0; padding:0; }
.container { max-width: 500px; margin: 50px auto; background: white; padding:30px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); text-align:center; }
h2 { color:#0984e3; margin-bottom:20px; }
form select, form button { width:100%; padding:10px 15px; margin:10px 0; border-radius:8px; border:1px solid #ccc; font-size:16px; }
form button { background:#0984e3; color:white; border:none; cursor:pointer; transition:0.3s; }
form button:hover { background:#74b9ff; }
.message { margin:15px 0; padding:10px; border-radius:6px; font-weight:bold; }
.message.success { background:#e0ffe0; color:#2d9a2d; }
.message.error { background:#ffe0e0; color:#d63031; }
a.back-link { display:inline-block; margin-top:20px; text-decoration:none; color:#0984e3; }
a.back-link:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
<h2>Borrow a Book</h2>

<?php if($msg): ?>
<div class="message <?= strpos($msg,'successfully')!==false?'success':'error' ?>">
<?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>

<form method="POST">
<select name="book_id" required>
<option value="">Select a book</option>
<?php while($row = $books->fetch_assoc()): ?>
<option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?> by <?= htmlspecialchars($row['author']) ?> (<?= $row['quantity'] ?> available)</option>
<?php endwhile; ?>
</select>
<button type="submit">Borrow</button>
</form>

<a class="back-link" href="dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
