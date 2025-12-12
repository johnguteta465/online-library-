<?php
/**
 * Secure Login Page Template (login.php)
 * Authenticates users and redirects them based on their role.
 */
session_start();
// NOTE: Assuming db.php is available and contains $conn object
include "db.php";

// Redirect already logged-in users to the appropriate page
if (isset($_SESSION['user_role'])) {
    $redirect_page = (in_array($_SESSION['user_role'], ['admin', 'super_admin'])) ? 'admin.php' : 'dashboard.php';
    header("Location: " . $redirect_page);
    exit;
}

$error = "";

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Check if user exists securely
        // Note: The 'role' column is critical for redirection.
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_role'] = $row['role'];
                $_SESSION['user_name'] = $row['name'];

                // Redirect based on role: Admins/Super Admins go to admin.php
                if (in_array($row['role'], ['admin', 'super_admin'])) {
                    header("Location: admin.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $error = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Library System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
/* STANDARD COLOR PALETTE (From admin.php) */
:root { 
    --primary: #4e54c8; 
    --secondary: #8f94fb;
    --bg-color: #f4f7fc; 
    --white: #ffffff; 
    --text-dark: #2d3436; 
    --accent: #ff6b6b; 
}

body { 
    font-family: 'Poppins', sans-serif; 
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    margin: 0; 
    padding: 0; 
    display: flex; 
    flex-direction: column;
    justify-content: center; 
    align-items: center; 
    min-height: 100vh; 
}

/* Header/Branding Area */
.branding-header {
    background: rgba(255, 255, 255, 0.9);
    padding: 15px 30px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    position: absolute;
    top: 0;
}

.branding-header img {
    height: 40px; 
    width: auto;
    margin-right: 15px;
}

.branding-header h1 {
    font-size: 16px; 
    margin: 0;
    color: var(--primary);
    font-weight: 600;
    line-height: 1.4;
    text-align: center;
}

.login-card {
    background: var(--white);
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 380px;
    text-align: center;
    margin-top: 100px; 
}

h2 { 
    color: var(--text-dark); 
    margin-bottom: 30px; 
    font-weight: 700;
}

.form-group { 
    margin-bottom: 20px; 
    text-align: left;
}

label { 
    display: block; 
    margin-bottom: 5px; 
    font-size: 14px; 
    font-weight: 500; 
    color: var(--text-dark);
}

input { 
    width: 100%; 
    padding: 12px; 
    border: 1px solid #ddd; 
    border-radius: 8px; 
    box-sizing: border-box; 
    font-family: inherit; 
    font-size: 14px;
    transition: border-color 0.3s;
}

input:focus { 
    border-color: var(--primary); 
    outline: none; 
}

.btn-primary {
    background: var(--primary);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
    transition: background 0.3s;
    font-weight: 600;
    margin-top: 10px;
}

.btn-primary:hover { 
    background: #3a3fb0; 
}

.alert-error {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 8px;
    font-size: 14px;
    color: #721c24;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
}
</style>
</head>
<body>

<div class="branding-header">
    <img src="ambo.png" alt="University Logo" onerror="this.src='https://placehold.co/40x40/4e54c8/ffffff?text=AU'">
    <h1>AMBO UNIVERSITY HACHALU HUNDESSA CAMPUS<br>ONLINE LIBRARY MANAGEMENT SYSTEM</h1>
</div>

<div class="login-card">
    <h2>User Login</h2>
    
    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        <button type="submit" class="btn-primary">Log In</button>
    </form>
</div>

</body>
</html>