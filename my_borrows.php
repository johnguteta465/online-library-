<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all borrowed books for this user
$stmt = $conn->prepare("
    SELECT b.id, bk.title, bk.author, b.borrow_date, b.return_date, b.status
    FROM borrows b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ?
    ORDER BY b.borrow_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$borrowed = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Borrowed Books</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f1f2f6;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 800px;
    margin: 50px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
h2 { color: #0984e3; text-align: center; margin-bottom: 20px; }
table {
    width: 100%;
    border-collapse: collapse;
}
table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ccc;
}
table th { background: #0984e3; color: white; }
.status-borrowed { color: #d63031; font-weight: bold; }
.status-returned { color: #2d9a2d; font-weight: bold; }
.back-link {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    color: #0984e3;
    font-weight: bold;
}
.back-link:hover { text-decoration: underline; }
</style>
</head>
<body>
<div class="container">
<h2>My Borrowed Books</h2>

<?php if($borrowed->num_rows > 0): ?>
<table>
<tr>
<th>Title</th>
<th>Author</th>
<th>Borrow Date</th>
<th>Return Date</th>
<th>Status</th>
</tr>
<?php while($row = $borrowed->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= htmlspecialchars($row['author']) ?></td>
<td><?= htmlspecialchars($row['borrow_date']) ?></td>
<td><?= $row['return_date'] ? htmlspecialchars($row['return_date']) : '-' ?></td>
<td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p style="text-align:center;">You have not borrowed any books yet.</p>
<?php endif; ?>

<a class="back-link" href="dashboard.php">← Back to Dashboard</a>
</div>
</body>
</html>
<?php $stmt->close(); ?>
