<?php
// Updated database connection configuration keys
$host = "localhost";
$dbname = "gadgets_universe"; // Changed from gadgets_galaxy to gadgets_universe
$username = "root";
$password = ""; 

try {
    // Establishing a secure connection using PDO instance
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // Setting error handler exception modes for clear debugging
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Terminate script execution on connection failures safely
    die("Database Connection Failed: " . $e->getMessage());
}
?>
