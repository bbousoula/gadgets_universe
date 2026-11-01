<?php
session_start();
require_once 'config/db.php';

$message = "";

/**
 * Generate a unique username from first + last name.
 *   John Doe      → johndoe
 *   John Doe (2nd)→ johndoe1
 *   John Doe (3rd)→ johndoe2
 * Falls back to random suffix if somehow 9999 collisions occur.
 */
function generate_username(PDO $conn, string $first, string $last): string {
    $base = strtolower(preg_replace('/[^a-z0-9]/i', '', $first . $last));
    if ($base === '') $base = 'user';

    $reserved = ['admin', 'root', 'administrator', 'system'];
    $candidate = $base;
    $suffix = 1;

    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = :u LIMIT 1");
    while (true) {
        if (!in_array($candidate, $reserved, true)) {
            $stmt->execute([':u' => $candidate]);
            if (!$stmt->fetchColumn()) return $candidate;
        }
        $candidate = $base . $suffix++;
        if ($suffix > 9999) {
            return $base . bin2hex(random_bytes(3));
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $address    = trim($_POST['address']    ?? '');
    $dob        = trim($_POST['dob']        ?? '');
    $password   = $_POST['password']         ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    // Validate date of birth
    $dob_valid = false;
    if ($dob !== '') {
        $dob_dt = DateTime::createFromFormat('Y-m-d', $dob);
        if ($dob_dt && $dob_dt->format('Y-m-d') === $dob && $dob_dt <= new DateTime()) {
            $dob_valid = true;
        }
    }

    if (empty($first_name) || empty($last_name) || empty($email) ||
        empty($address) || empty($dob) || empty($password) || empty($confirm)) {
        $message = "<p style='color: red;'>All fields are mandatory.</p>";
    } elseif (!$dob_valid) {
        $message = "<p style='color: red;'>Please enter a valid date of birth.</p>";
    } elseif ($password !== $confirm) {
        $message = "<p style='color: red;'>Passwords do not match. Please try again.</p>";
    } elseif (strlen($password) < 6) {
        $message = "<p style='color: red;'>Password must be at least 6 characters.</p>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Generate a unique username from the person's real name
            $username = generate_username($conn, $first_name, $last_name);

            $stmt = $conn->prepare(
                "INSERT INTO users (first_name, last_name, username, email, address, dob, password)
                 VALUES (:first_name, :last_name, :username, :email, :address, :dob, :password)"
            );
            $stmt->execute([
                ':first_name' => $first_name,
                ':last_name'  => $last_name,
                ':username'   => $username,
                ':email'      => $email,
                ':address'    => $address,
                ':dob'        => $dob,
                ':password'   => $hashed_password,
            ]);

            $_SESSION['user_id']    = $conn->lastInsertId();
            $_SESSION['username']   = $username;
            $_SESSION['first_name'] = $first_name;

            header("Location: store.php");
            exit();
        } catch(PDOException $e) {
            $message = "<p style='color: red;'>Error: Email address already in use.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Gadgets Universe</title>
    <link rel="stylesheet" href="css/style.css">

    <style>
    /* Force the background */
    body {
        background-color: #dae0ea;
    }
    
    /* Make wrappers transparent */
    .crud-container, 
    .admin-container, 
    .orders-container, 
    .auth-container {
        background: transparent !important;
    }

    /* Fix text colors for light background */
    h2, h3, p {
        color: #2c3e50 !important;
        text-shadow: none !important;
    }
</style>

    <style>
        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
            box-sizing: border-box;
            font-family: inherit;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Client Registration</h2>
        <?php echo $message; ?>
        <form action="register.php" method="POST" onsubmit="return validatePasswords()">
            <div class="form-group">
                <label>First Name:</label>
                <input type="text" name="first_name" required
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Last Name:</label>
                <input type="text" name="last_name" required
                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email Address:</label>
                <input type="email" name="email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <p style="font-size:0.78rem; color:#7f8c8d; margin-top:4px;">
                    You'll use this email to log in.
                </p>
            </div>
            <div class="form-group">
                <label>Delivery Address:</label>
                <textarea name="address" rows="3" required
                          placeholder="Street, City, Postal Code"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Date of Birth:</label>
                <input type="date" name="dob" required
                       max="<?php echo date('Y-m-d'); ?>"
                       value="<?php echo htmlspecialchars($_POST['dob'] ?? ''); ?>">
                <p style="font-size:0.78rem; color:#7f8c8d; margin-top:4px;">
                    Click to open a calendar picker.
                </p>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" id="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                <p id="password-match-hint" style="font-size:0.78rem; margin-top:4px; display:none;"></p>
            </div>
            <button type="submit" class="btn">Register & Start Shopping</button>
        </form>
        <p style="margin-top:15px; text-align:center;">Already registered? <a href='login.php'>Login here</a></p>
    </div>

    <script>
        const pw   = document.getElementById('password');
        const cpw  = document.getElementById('confirm_password');
        const hint = document.getElementById('password-match-hint');

        function updateHint() {
            if (!cpw.value) { hint.style.display = 'none'; return; }
            hint.style.display = 'block';
            if (pw.value === cpw.value) {
                hint.textContent = '✓ Passwords match';
                hint.style.color = '#27ae60';
            } else {
                hint.textContent = '✗ Passwords do not match';
                hint.style.color = '#e74c3c';
            }
        }
        pw.addEventListener('input', updateHint);
        cpw.addEventListener('input', updateHint);

        function validatePasswords() {
            if (pw.value !== cpw.value) {
                alert('Passwords do not match. Please re-check both fields.');
                cpw.focus();
                return false;
            }
            return true;
        }
    </script>
</body>
</html>