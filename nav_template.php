<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar">
    <div class="nav-left">
        <a href="index.php" class="logo">🏨 MyHotel</a>

        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <a href="admin.php">Admin Dashboard</a>
        <?php elseif (!empty($_SESSION['user_id'])): ?>
            <a href="reserve.php">Reserve</a>
            <a href="history.php">Booking History</a>
        <?php endif; ?>
    </div>


    <div class="nav-right">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <span>👋 Welcome, <?= htmlspecialchars($_SESSION['loggedInUser']) ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="register.php">Register</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>

<style>
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f43f5e;
        padding: 15px 40px;
        font-family: 'Segoe UI', sans-serif;
    }

    .navbar a {
        color: white;
        text-decoration: none;
        margin: 0 12px;
        font-weight: 500;
    }

    .navbar a:hover {
        text-decoration: underline;
    }

    .logo {
        font-weight: bold;
        font-size: 18px;
    }

    .logout-btn {
        background: #111;
        padding: 6px 12px;
        border-radius: 6px;
    }
</style>