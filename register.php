
<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($name && $email && $password) {
        $check = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "<script>alert('Email already registered! Please login.'); window.location='login.php';</script>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $role;
                header("Location: dashboard.php?msg=registered");
                exit;
            } else {
                echo "<script>alert('Database error. Please try again.');</script>";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        echo "<script>alert('Please fill in all fields.');</script>";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Account</title>
  <link rel="stylesheet" href="style.css">
  <!-- Add Font Awesome for social media icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* HEADER - Fixed positioning to prevent overlap */
    header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background-color: #0984e3;
      color: white;
      padding: 10px 30px;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    header .left {
      display: flex;
      align-items: center;
    }

    header img {
      width: 200px;
      height: 100px;
      object-fit: cover;
      margin-right: 15px;
    }

    h1 {
      font-size: 18px;
      text-align: center;
      color:blue;
    }

    nav a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: bold;
    }

    nav a:hover {
      text-decoration: underline;
    }

    /* MAIN CONTENT - Added margin-top to prevent header overlap */
    main {
      flex: 1;
      padding: 40px 0;
      margin-top: 120px; /* Height of header + some extra space */
      min-height: calc(100vh - 120px); /* Ensure footer stays at bottom */
    }

    form {
      width: 350px;
      margin: 0 auto;
      background: white;
      padding: 25px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
      text-align: center;
    }

    h2 {
      color: #333;
      margin-top: 0;
    }

    input {
      width: 90%;
      padding: 10px;
      margin: 8px 0;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    button {
      background: #0984e3;
      color: white;
      border: none;
      padding: 10px 15px;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
    }

    button:hover {
      background: #065fa7;
    }

    a {
      color: #0984e3;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    /* FOOTER STYLES */
    footer {
      background-color: #003366;
      color: white;
      padding: 40px 0 15px;
      width: 100%;
      margin-top: auto;
    }


.footer-content {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .footer-section {
      flex: 1;
      min-width: 250px;
      margin-bottom: 25px;
      padding: 0 15px;
    }

    .footer-section h3 {
      font-size: 18px;
      margin-bottom: 15px;
      border-bottom: 2px solid #0984e3;
      padding-bottom: 5px;
      display: inline-block;
    }

    .footer-section p, .footer-section ul {
      font-size: 14px;
      line-height: 1.6;
    }

    .footer-section ul {
      list-style: none;
      padding: 0;
    }

    .footer-section ul li {
      margin-bottom: 8px;
    }

    .footer-section ul li a {
      color: #ddd;
      text-decoration: none;
      transition: color 0.3s;
    }

    .footer-section ul li a:hover {
      color: #fff;
      text-decoration: underline;
    }

    .footer-section .button {
      display: inline-block;
      background: #0984e3;
      color: white;
      padding: 8px 15px;
      border-radius: 4px;
      text-decoration: none;
      margin-top: 10px;
      font-weight: bold;
    }

    .footer-section .button:hover {
      background: #065fa7;
      text-decoration: none;
    }

    .contact-info li {
      display: flex;
      align-items: flex-start;
      margin-bottom: 10px;
    }

    /* Social Media Styles */
    .social-media {
      margin-top: 15px;
    }

    .social-media h4 {
      margin-bottom: 10px;
      font-size: 16px;
    }

    .social-icons {
      display: flex;
      gap: 15px;
    }

    .social-icons a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      background: #0984e3;
      color: white;
      border-radius: 50%;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 18px;
    }

    .social-icons a:hover {
      background: white;
      color: #0984e3;
      transform: translateY(-3px);
    }

    .footer-bottom {
      text-align: center;
      padding-top: 20px;
      border-top: 1px solid rgba(255,255,255,0.1);
      margin-top: 20px;
      font-size: 14px;
    }

    .footer-bottom span {
      font-size: 16px;
      font-weight: bold;
    }

    @media (max-width: 768px) {
      .footer-section {
        flex: 100%;
      }
      
      header {
        flex-direction: column;
        text-align: center;
        padding: 15px 20px;
        height: auto;
        position: fixed;
      }
      
      nav {
        margin-top: 15px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
      }
      
      nav a {
        margin: 0 5px;
      }
      
      .social-icons {
        justify-content: center;
      }
      
      /* Adjust main content margin for mobile */
      main {
        margin-top: 160px; /* Increased for mobile header height */
        min-height: calc(100vh - 160px);
      }
      
      header img {
        width: 150px;
        height: 80px;
      }
      
      header h1 {
        font-size: 16px;
      }
    }

    @media (max-width: 480px) {
      form {
        width: 90%;
        margin: 0 auto;
      }
      
      main {
        margin-top: 180px; /* Further adjustment for very small screens */
        min-height: calc(100vh - 180px);
      }
    }
  </style>
</head>
<body>

<header>
  <div class="left">
    <img src="ambo.png" alt="University Logo">
   
  </div>
  <nav>
    <a href="index.php">Home</a>
    
    <a href="logout.php">Logout</a>
  </nav>
</header>

<main>
   <h1>Ambo University Online Library System</h1>
  <center><h2>Create New Account</h2></center>
  <form action="register.php" method="post">
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="hidden" name="role" value="user">


<button type="submit">Sign Up</button>

    <p>Already have an account? <a href="login.php">Login</a></p>
  </form>
</main>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>ABOUT US</h3>
      <p>Ambo University Library (AUL) supports the academic community by providing reliable and up-to-date information for research, innovation, and quality education. It houses over a million resources, including about 30,000 books, journals, theses, and dissertations across various disciplines. Alongside its vast print collection, AUL is expanding digital resources to meet the growing demand for e-learning and research materials.</p>
      <a href="#" class="button">Start Learning Now</a>
    </div>
    
    <div class="footer-section">
      <h3>IMPORTANT LINKS</h3>
      <ul>
        <li><a href="#">Future students</a></li>
        <li><a href="#">International students</a></li>
        <li><a href="#">Researchers</a></li>
      </ul>
    </div>
    
    <div class="footer-section">
      <h3>QUICK LINKS</h3>
      <ul>
        <li><a href="#">Accessibility</a></li>
        <li><a href="#">Contact us</a></li>
        <li><a href="#">Jobs</a></li>
      </ul>
    </div>
    
    <div class="footer-section">
      <h3>CONTACT US</h3>
      <ul class="contact-info">
        <li>Ambo University, Oromiya, Ethiopia</li>
        <li>Phone: (+25) 1112 36 81 60</li>
        <li>E-mail: info@ambou.edu.et</li>
      </ul>
      
      <!-- Social Media Section -->
      <div class="social-media">
        <h4>Follow Us</h4>
        <div class="social-icons">
          <a href="https://twitter.com/ambouniversity" target="_blank" title="Twitter">
            <i class="fab fa-twitter"></i>
          </a>
          <a href="https://facebook.com/ambouniversity" target="_blank" title="Facebook">
            <i class="fab fa-facebook-f"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <div class="footer-bottom">
    <span>Ambo University Library Management System</span><br>
    © 2025 Ambo University. All Rights Reserved.
  </div>
</footer>

</body>
</html>