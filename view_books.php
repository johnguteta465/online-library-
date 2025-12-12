<?php
session_start();
include "db.php";

// Fetch all books
$result = $conn->query("SELECT * FROM books");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Books</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    padding: 0;
    margin: 0;
}

header {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #0984e3;
    color: white;
    padding: 20px;
    text-align: center;
}

header h1 {
    font-size: 22px;
    line-height: 1.4;
}

h2 {
    text-align: center;
    font-size: 28px;
    color: #2d3436;
    margin-top: 20px;
}

table {
    width: 90%;
    margin: 20px auto;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

th, td {
    padding: 12px;
    border: 1px solid #ccc;
    text-align: left;
    font-size: 16px;
}

th {
    background: #0984e3;
    color: white;
}

tr:hover {
    background: #f1f1f1;
}

a {
    display: block;
    width: 140px;
    margin: 20px auto;
    text-align: center;
    padding: 10px 15px;
    background: #0984e3;
    color: white;
    border-radius: 6px;
    text-decoration: none;
}

a:hover {
    background: #74b9ff;
}
</style>

</head>
<body>

<header>
    <h1>AMBO UNIVERSITY HACHALU HUNDESSA CAMPUS<br>ONLINE LIBRARY MANAGEMENT SYSTEM</h1>
</header>

<h2>Books</h2>

<table>
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Author</th>
    <th>ISBN</th>
    <th>Quantity</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['title']) ?></td>
    <td><?= htmlspecialchars($row['author']) ?></td>
    <td><?= htmlspecialchars($row['isbn']) ?></td>
    <td><?= $row['quantity'] ?></td>
</tr>
<?php endwhile; ?>

</table>

<a href="admin.php">⬅ Back to Dashboard</a>

</body>
</html>
