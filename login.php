<?php

session_start();
require_once 'db_connect.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid email.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $id, $hash, $role);
        if (mysqli_stmt_num_rows($stmt) === 1) {
            mysqli_stmt_fetch($stmt);
            if (password_verify($password, $hash)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['loggedInUser'] = $email;
                $_SESSION['role'] = $role;
                if ($role === 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: history.php");
                }
                exit;
            } else {
                $err = "Invalid credentials.";
            }
        } else {
            $err = "No account found with that email.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login - MyHotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'nav_template.php'; ?>
    <div class="container">
        <h2>Login</h2>
        <?php if ($err): ?>
            <div class="output" style="color:red;"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <form method="post" action="login.php">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button class="btn-primary" type="submit">Login</button>
        </form>
    </div>
</body>

</html>