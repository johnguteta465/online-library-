<?php
session_start();
include "db.php";

// Only logged-in users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userName = htmlspecialchars($_SESSION['user_name']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard</title>

<style>
body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #f1f3f6;
    margin: 0;
    padding: 0;
}

/* Header */
header {
    background: #0984e3;
    color: white;
    padding: 18px 40px;
    font-size: 20px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header span {
    font-size: 16px;
    opacity: 0.9;
}

/* Dashboard Box */
.container {
    max-width: 700px;
    background: white;
    margin: 50px auto;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 4px 18px rgba(0,0,0,0.12);
    text-align: center;
}

h2 {
    color: #0984e3;
    margin-bottom: 10px;
}

.btn-container {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 20px;
}

/* Buttons */
a.btn {
    text-decoration: none;
    background: #0984e3;
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 16px;
    display: inline-block;
    width: 200px;
    text-align: center;
    transition: 0.25s;
}

a.btn:hover {
    background: #065fa7;
}

/* Logout */
.logout {
    background: #d63031 !important;
}

.logout:hover {
    background: #c0392b !important;
}
</style>

</head>
<body>

<!-- Header -->
<header>
    Welcome, <?= $userName ?>
    <span>User Portal</span>
</header>

<div class="container">
    <h2>User Dashboard</h2>
    <hr>

    <div class="btn-container">
        <a href="borrow.php" class="btn">Borrow Book</a>
        <a href="return.php" class="btn">Return Book</a>
        <a href="my_borrows.php" class="btn">My Borrowed Books</a>
        <a href="logout.php" class="btn logout">Logout</a>
    </div>
</div>

</body>
</html>
