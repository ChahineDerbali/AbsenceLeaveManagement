<?php
$servername = "localhost"; // Change if your database is on a different host
$username = "root"; // Default username for XAMPP
$password = ""; // Default password for XAMPP (empty string)
$dbname = "gestion_absences_conges"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

