<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
<title>Home</title>

<style>
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        background: #eef2f3;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .container {
        background: #ffffff;
        width: 400px;
        padding: 30px;
        text-align: center;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    h1 {
        font-size: 22px;
        margin-bottom: 20px;
        color: #2d3436;
    }

    p {
        font-size: 16px;
        margin-bottom: 20px;
    }

    a {
        display: inline-block;
        padding: 12px 18px;
        margin: 8px 0;
        border-radius: 6px;
        font-size: 16px;
        font-weight: bold;
        color: #fff;
        background: #0984e3;
        text-decoration: none;
        transition: 0.2s ease-in-out;
    }

    a:hover {
        background: #0769b3;
    }

    .logout {
        background: #d63031;
    }

    .logout:hover {
        background: #b02020;
    }

    .link-secondary {
        background: #00b894;
    }

    .link-secondary:hover {
        background: #009973;
    }

</style>

</head>
<body>

<div class="container">
    <h1>Welcome to Ambo University Online Library System</h1>

    <?php if(isset($_SESSION['user_name'])): ?>
        <p>Hello, <b><?= $_SESSION['user_name'] ?></b> 👋</p>

        <a href="dashboard.php" class="link-secondary">Go to Dashboard</a><br>
        <a href="logout.php" class="logout">Logout</a>

    <?php else: ?>
        <a href="login.php">Login</a><br>
        <a href="register.php">Register</a>
    <?php endif; ?>
</div>

</body>
</html>
