<?php
session_start();
require_once 'db_connect.php';
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT id, full_name, email, room, guests, check_in, check_out, special_request, receipt, status, created_at FROM reservations WHERE user_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$bookings = mysqli_fetch_all($res, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>My Bookings - MyHotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'nav_template.php'; ?>
    <div class="container">
        <h2>Your Booking History</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Room</th>
                    <th>Guests</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Receipt</th>
                    <th>Request</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bookings)): ?>
                    <tr>
                        <td colspan="8">No bookings yet.</td>
                    </tr>
                <?php else:
                    foreach ($bookings as $b): ?>
                        <tr>
                            <td><?= htmlspecialchars($b['id']) ?></td>
                            <td><?= htmlspecialchars($b['room']) ?></td>
                            <td><?= htmlspecialchars($b['guests']) ?></td>
                            <td><?= htmlspecialchars($b['check_in']) ?></td>
                            <td><?= htmlspecialchars($b['check_out']) ?></td>
                            <td>
                                <?php
                                $status = $b['status'];
                                $color = match ($status) {
                                    'Pending' => '#f59e0b',     // amber
                                    'Approved' => '#3b82f6',    // blue
                                    'Completed' => '#10b981',   // green
                                    'Rejected' => '#ef4444',    // red
                                    default => '#6b7280'        // gray
                                };

                                ?>
                                <span style="color:<?= $color ?>; font-weight:bold;">
                                    <?= htmlspecialchars($status) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (!empty($b['receipt'])): ?>
                                    <a href="<?= htmlspecialchars($b['receipt']) ?>" target="_blank">View</a>
                                <?php else:
                                    echo 'No file';
                                endif; ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($b['special_request'])) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>