<?php
session_start();
require_once 'db_connect.php';
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// Admin actions — approve / reject / checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['res_id'])) {
    $action = $_POST['action'];
    $resId = intval($_POST['res_id']);

    // Get room type for this booking
    $stmtRoom = mysqli_prepare($conn, "SELECT room FROM reservations WHERE id = ?");
    mysqli_stmt_bind_param($stmtRoom, "i", $resId);
    mysqli_stmt_execute($stmtRoom);
    mysqli_stmt_bind_result($stmtRoom, $roomType);
    mysqli_stmt_fetch($stmtRoom);
    mysqli_stmt_close($stmtRoom);

    if (empty($roomType)) {
        $_SESSION['admin_msg'] = "Booking not found.";
        header("Location: admin.php");
        exit;
    }

    if ($action === 'approve') {
        // Approve booking
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'Approved' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $resId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Update related payment record to Paid
        $stmtPay = mysqli_prepare($conn, "UPDATE payment SET status = 'Paid' WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmtPay, "i", $resId);
        mysqli_stmt_execute($stmtPay);
        mysqli_stmt_close($stmtPay);

        // Mark room as Unavailable
        $stmt2 = mysqli_prepare($conn, "UPDATE rooms SET status = 'Unavailable' WHERE room_type = ?");
        mysqli_stmt_bind_param($stmt2, "s", $roomType);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $_SESSION['admin_msg'] = "Booking approved — payment marked Paid and room marked Unavailable.";
    } elseif ($action === 'reject') {
        // Update payment as Failed if rejected
        $stmtPay = mysqli_prepare($conn, "UPDATE payment SET status = 'Failed' WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmtPay, "i", $resId);
        mysqli_stmt_execute($stmtPay);
        mysqli_stmt_close($stmtPay);

        // Reject booking — no change to room
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'Rejected' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $resId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $_SESSION['admin_msg'] = "Booking rejected.";
    } elseif ($action === 'checkout') {
        // Mark payment Completed when guest checks out
        $stmtPay = mysqli_prepare($conn, "UPDATE payment SET status = 'Completed' WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmtPay, "i", $resId);
        mysqli_stmt_execute($stmtPay);
        mysqli_stmt_close($stmtPay);

        // Checkout booking
        $stmt = mysqli_prepare($conn, "UPDATE reservations SET status = 'Completed' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $resId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Make room available again
        $stmt2 = mysqli_prepare($conn, "UPDATE rooms SET status = 'Available' WHERE room_type = ?");
        mysqli_stmt_bind_param($stmt2, "s", $roomType);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $_SESSION['admin_msg'] = "Guest checked out — room marked Available.";
    }

    header("Location: admin.php");
    exit;
}


// load reservations
$res = mysqli_query($conn, "SELECT r.*, u.email AS user_email FROM reservations r JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
$reservations = mysqli_fetch_all($res, MYSQLI_ASSOC);
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Admin - MyHotel</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'nav_template.php'; ?>
    <div class="container">
        <h2>Admin Dashboard</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Guest</th>
                    <th>Email</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Receipt</th>
                    <th>Special</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="10">No reservations.</td>
                    </tr>
                <?php else:
                    foreach ($reservations as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['id']) ?></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['room']) ?></td>
                            <td><?= htmlspecialchars($r['check_in']) ?></td>
                            <td><?= htmlspecialchars($r['check_out']) ?></td>
                            <td><?= $r['receipt'] ? '<a href="' . htmlspecialchars($r['receipt']) . '" target="_blank">View</a>' : 'No file' ?>
                            </td>
                            <td><?= nl2br(htmlspecialchars($r['special_request'])) ?></td>
                            <td>
                                <?php
                                $status = $r['status'];
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
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="res_id" value="<?= intval($r['id']) ?>">
                                        <button name="action" value="approve" class="btn-primary">Approve</button>
                                        <button name="action" value="reject" class="btn-danger"
                                            onclick="return confirm('Reject booking?')">Reject</button>
                                    </form>
                                <?php elseif ($r['status'] === 'Approved'): ?>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="res_id" value="<?= intval($r['id']) ?>">
                                        <button name="action" value="checkout" class="btn-primary">Checkout</button>
                                    </form>
                                <?php else: ?>
                                    <?= htmlspecialchars($r['status']) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>