<?php
session_start();
require_once 'config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password']       ?? '';

    if (!empty($identifier) && !empty($password)) {
        // Match either username OR email in one query
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :id OR email = :id LIMIT 1");
        $stmt->bindParam(':id', $identifier);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $valid = false;
        if ($user && password_verify($password, $user['password'])) {
            $valid = true;
        }
        // Hardcoded admin fallback
        if ($identifier === 'admin' && $password === 'admin123') {
            $valid = true;
        }

        if ($valid) {
            session_regenerate_id(true);   // prevents session fixation
            $_SESSION['user_id']    = $user ? $user['id']         : 1;
            $_SESSION['username']   = $user ? $user['username']   : 'admin';
            $_SESSION['first_name'] = $user ? ($user['first_name'] ?: $user['username']) : 'Admin';

            if ($_SESSION['username'] === 'admin') {
                header("Location: dashboard.php");
            } else {
                header("Location: store.php");
            }
            exit();
        } else {
            $message = "<p style='color: #e74c3c; text-align: center; margin-bottom: 15px; font-weight: bold;'>Invalid email/username or password.</p>";
        }
    } else {
        $message = "<p style='color: #e74c3c; text-align: center; margin-bottom: 15px; font-weight: bold;'>Please enter your credentials.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gadgets Universe</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Force the new texture background */
        body {
            background-image: url('images/background-texture.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            background-attachment: fixed !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Frosted glass container to make the form stand out */
        .auth-container {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }

        /* Ensure text is dark and readable */
        h3, label {
            color: #2c3e50 !important;
        }

        .form-group input {
            background: #ffffff !important;
            border: 1px solid #ccc !important;
            color: #333 !important;
        }
        
        .form-group input:focus {
            border-color: #3498db !important;
            outline: none;
        }
    </style>
</head>
<body>
   <div class="auth-container">
        <!-- Logo -->
        <div style="text-align: center; margin-bottom: 25px;">
            <img src="images/logo_horizontal.png" alt="Gadgets Universe Logo" style="max-width: 250px; height: auto; display: block; margin: 0 auto;">
        </div>

        <h3 style="text-align: center; font-size: 1.4rem; margin-bottom: 25px; font-weight: 700;">Sign In</h3>
        
        <?php echo $message; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Email or Username:</label>
                <input type="text" name="identifier" placeholder="Enter your email" required autocomplete="username" style="width: 100%; padding: 12px; border-radius: 6px; box-sizing: border-box;">
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="font-weight: 600; display: block; margin-bottom: 5px;">Password:</label>
                <input type="password" name="password" placeholder="Enter password" required autocomplete="current-password" style="width: 100%; padding: 12px; border-radius: 6px; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn" style="width: 100%; padding: 12px; background: #3498db; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background 0.2s;">Login</button>
        </form>
        
        <p style="margin-top: 25px; text-align: center; color: #555; font-size: 0.9rem;">
            New client? <a href='register.php' style="color: #2980b9; font-weight: bold; text-decoration: none;">Create an account here</a>
        </p>
    </div>
</body>
</html>