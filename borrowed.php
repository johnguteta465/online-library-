<?php
session_start();
include 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch borrowed books for this user
$stmt = $conn->prepare("
    SELECT b.id as borrow_id, bk.title, bk.author, b.borrow_date, b.status
    FROM borrows b
    JOIN books bk ON b.book_id = bk.id
    WHERE b.user_id = ? AND b.status='borrowed'
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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Borrowed Books</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(to right, #fbc2eb, #a6c1ee);
    margin: 0;
    padding: 0;
}
.container {
    max-width: 700px;
    margin: 60px auto;
    background: #ffffffcc;
    padding: 40px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}
h2 {
    text-align: center;
    color: #2d3436;
    font-size: 28px;
    margin-bottom: 30px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
table th, table td {
    padding: 12px 15px;
    border-bottom: 1px solid #ccc;
    text-align: left;
}
table th {
    background-color: #0984e3;
    color: white;
    font-weight: 600;
}
table tr:nth-child(even) {
    background-color: #f2f2f2;
}
.message {
    text-align: center;
    margin-bottom: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
}
a.back-link {
    display: inline-block;
    margin-top: 15px;
    text-decoration: none;
    color: #0984e3;
    font-weight: 500;
    transition: 0.3s;
}
a.back-link:hover {
    color: #74b9ff;
    text-decoration: underline;
}
</style>
</head>
<body>
<div class="container">
    <h2>My Borrowed Books</h2>

    <?php if($borrowed->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Borrowed On</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $borrowed->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><?= htmlspecialchars($row['author']) ?></td>
                        <td><?= date('d M Y', strtotime($row['borrow_date'])) ?></td>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="message">You have not borrowed any books yet.</div>
    <?php endif; ?>

    <p><a class="back-link" href="dashboard.php">← Back to Dashboard</a></p>
</div>
</body>
</html>
<?php $stmt->close(); ?>
