<?php
session_start();
require_once 'config/db.php';

// Strict Admin Check
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'admin') {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    
    // Check which type of update is being requested
    if (isset($_POST['delivery_status'])) {
        $new_status = trim($_POST['delivery_status']);
        $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        
        if ($order_id > 0 && in_array($new_status, $allowed, true)) {
            $stmt = $conn->prepare("UPDATE orders SET delivery_status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $order_id]);
            echo "success";
        } else {
            http_response_code(400);
            echo "Invalid delivery status";
        }
    } 
    elseif (isset($_POST['payment_status'])) {
        $new_status = trim($_POST['payment_status']);
        $allowed = ['pending', 'paid', 'failed', 'refunded'];
        
        // Note: DB stores these in lowercase, so we enforce lowercase
        $new_status = strtolower($new_status);

        if ($order_id > 0 && in_array($new_status, $allowed, true)) {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $order_id]);
            echo "success";
        } else {
            http_response_code(400);
            echo "Invalid payment status";
        }
    } 
    else {
        http_response_code(400);
        echo "No status provided";
    }
}