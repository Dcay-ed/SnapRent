<?php
// Database connection settings
$host = "localhost";
$user = "root"; // Replace with your database username
$pass = ""; // Replace with your database password
$dbname = "snaprent"; // Replace with your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
