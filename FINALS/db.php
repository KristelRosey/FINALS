<?php
// db.php - Database Connection

$host = 'localhost'; // Database host
$user = 'root'; // Database username
$pass = ''; // Database password
$dbname = 'findhire'; // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
