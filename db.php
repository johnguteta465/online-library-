<?php
$SERVER = "localhost";
$user = "root";
$pass = "";
$dbname = "library_db";

$conn = new mysqli($SERVER, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>