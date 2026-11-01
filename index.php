<?php
session_start();

// If the user is already logged in, send them directly to their workspace
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['username'] === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: store.php");
    }
    exit();
}

// Default action: Redirect anonymous visitors immediately to the Login screen
header("Location: login.php");
exit();
?>