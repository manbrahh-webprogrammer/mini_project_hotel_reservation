<?php
session_start();
require_once 'db_connect.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email.";
    if (strlen($password) < 6)
        $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)
        $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        // check existing
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email is already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (email, password, role) VALUES (?, ?, 'user')");
            mysqli_stmt_bind_param($stmt2, "ss", $email, $hash);
            if (mysqli_stmt_execute($stmt2)) {
                header("Location: login.php?registered=1");
                exit;

            } else {
                $errors[] = "Database error. Try again.";
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!-- Minimal HTML preserved; you can copy your existing register page layout & just ensure form posts here -->
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Register - MyHotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'nav_template.php'; // optional; see note below ?>
    <div class="container">
        <h2>Register</h2>
        <?php if (!empty($errors)): ?>
            <div class="output" style="color:red;">
                <?php foreach ($errors as $e)
                    echo "<div>{$e}</div>"; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="register.php">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
            <button class="btn-primary" type="submit">Register</button>
        </form>
    </div>
</body>

</html>