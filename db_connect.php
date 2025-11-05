<?php
$servername = "localhost";  // Change if needed
$username = "root";         // Default MySQL username
$password = "";             // Default is empty for local servers
$database = "library_db";     // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
