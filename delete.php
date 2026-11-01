<?php
session_start();

// Affirm authorization context flags are verified before proceeding
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/db.php';
$id = $_GET['id'];

try {
    // Delete database entry match via target primary tracking ID string
    $stmt = $conn->prepare("DELETE FROM gadgets WHERE id = :id");
    $stmt->execute([':id' => $id]);
} catch(PDOException $e) {
    // Execution exception caught silently to avoid workflow rendering layout issues
}

// Reroute back to active interface view 
header("Location: dashboard.php");
exit();
?>