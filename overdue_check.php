<?php
session_start();
include 'db.php';  // your fixed db.php

// Only allow admin to run this
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    die("Access denied.");
}

// Get today's date
$today = date("Y-m-d");

// Query to find overdue books
$sql = "SELECT b.borrow_id, u.full_name, u.email, bo.title, b.return_date
        FROM borrows b
        JOIN users u ON b.user_id = u.user_id
        JOIN books bo ON b.book_id = bo.book_id
        WHERE b.returned = 0 AND b.return_date < ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

echo "<h2>Overdue Books</h2>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user = htmlspecialchars($row['full_name']);
        $book = htmlspecialchars($row['title']);
        $due = $row['return_date'];

        echo "<div style='padding:10px; margin:10px 0; border:1px solid red; color:red; border-radius:6px;'>
                User: $user <br>
                Book: $book <br>
                Due Date: $due <br>
                <strong>Message:</strong> Your time has expired! Please return the book.
              </div>";

        // Optional: Send email (if you configure mail server)
        /*
        $to = $row['email'];
        $subject = "Library Overdue Notice";
        $message = "Dear $user,\n\nYour time for '$book' has expired. Please return it immediately.\n\nLibrary Management System";
        $headers = "From: library@example.com";
        mail($to, $subject, $message, $headers);
        */
    }
} else {
    echo "<div style='padding:10px; color:green;'>No overdue books today.</div>";
}

$stmt->close();
$conn->close();
?>
