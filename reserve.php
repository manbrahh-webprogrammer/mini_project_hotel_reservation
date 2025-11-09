<?php
session_start();
require_once 'db_connect.php';

// Check login
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Example room availability (you can later link this to database logic)
// load rooms from DB (replaces the static $rooms array)
$rooms = [];
$res_rooms = mysqli_query($conn, "SELECT * FROM rooms ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($res_rooms)) {
    // normalize field names to match existing template keys
    $rooms[] = [
        'name' => $row['room_type'],
        'price' => $row['price'],
        'image' => $row['image'],
        'available' => ($row['status'] === 'Available')
    ];
}


$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['loggedInUser'];
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reserve'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $room = $_POST['room'] ?? '';
    $guests = intval($_POST['guests'] ?? 1);
    $check_in = $_POST['check_in'] ?? null;
    $check_out = $_POST['check_out'] ?? null;
    $special_request = trim($_POST['special_request'] ?? '');
    $receiptPath = null;

    if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter valid name and email.";
    }

    // handle upload if file exists
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);
        $file = $_FILES['receipt'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target = $uploadDir . $safeName;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $receiptPath = 'uploads/' . $safeName;
        }
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO reservations (user_id, full_name, email, room, guests, check_in, check_out, special_request, receipt, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param($stmt, "isssissss", $user_id, $full_name, $email, $room, $guests, $check_in, $check_out, $special_request, $receiptPath);
        if (mysqli_stmt_execute($stmt)) {
            $reservation_id = mysqli_insert_id($conn);

            // calculate total price (backend confirmation)
            $date1 = new DateTime($check_in);
            $date2 = new DateTime($check_out);
            $nights = $date2->diff($date1)->days;
            $totalPrice = 0;

            // get price from rooms table
            $roomQuery = mysqli_prepare($conn, "SELECT price FROM rooms WHERE room_type = ?");
            mysqli_stmt_bind_param($roomQuery, "s", $room);
            mysqli_stmt_execute($roomQuery);
            mysqli_stmt_bind_result($roomQuery, $roomPrice);
            mysqli_stmt_fetch($roomQuery);
            mysqli_stmt_close($roomQuery);

            if ($roomPrice) {
                $totalPrice = $roomPrice * $nights;
            }

            // insert payment record
            $stmtPay = mysqli_prepare($conn, "INSERT INTO payment (reservation_id, user_id, amount, receipt, status) VALUES (?, ?, ?, ?, 'Pending')");
            mysqli_stmt_bind_param($stmtPay, "iids", $reservation_id, $user_id, $totalPrice, $receiptPath);
            mysqli_stmt_execute($stmtPay);
            mysqli_stmt_close($stmtPay);

            header("Location: history.php?msg=reserved");
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reserve - MyHotel</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background-color: #f9fafb;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .rooms-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }

        .room-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 260px;
            text-align: center;
            background-color: #fff;
        }

        .room-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }

        .room-card h3 {
            color: #1e3a8a;
            margin-top: 10px;
        }

        .room-card p {
            color: #475569;
            margin-bottom: 10px;
        }

        .unavailable {
            opacity: 0.6;
        }

        .reserve-form label {
            display: block;
            margin-top: 15px;
            font-weight: 500;
        }

        .reserve-form input,
        .reserve-form select,
        .reserve-form textarea {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            margin-top: 5px;
        }

        .reserve-form button {
            margin-top: 20px;
            background-color: #f43f5e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
        }

        .reserve-form button:hover {
            background-color: #e11d48;
        }

        .qr-section {
            text-align: center;
            display: none;
            margin-top: 30px;
        }

        .qr-section img {
            width: 200px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php include 'nav_template.php'; ?>

    <div class="container">
        <h2 style="text-align:center;color:#1e3a8a;">Reserve Your Stay</h2>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $e)
                    echo "<div>$e</div>"; ?>
            </div>
        <?php endif; ?>

        <div class="rooms-grid">
            <?php foreach ($rooms as $r): ?>
                <div class="room-card <?= !$r['available'] ? 'unavailable' : '' ?>">
                    <img src="<?= htmlspecialchars($r['image']) ?>" alt="<?= htmlspecialchars($r['name']) ?>">
                    <h3><?= htmlspecialchars($r['name']) ?></h3>
                    <p>RM<?= $r['price'] ?>/night</p>
                    <p>Status: <strong style="color:<?= $r['available'] ? '#16a34a' : '#dc2626' ?>;">
                            <?= $r['available'] ? 'Available' : 'Unavailable' ?>
                        </strong></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post" action="reserve.php" class="reserve-form" enctype="multipart/form-data">
            <label>Full Name:</label>
            <input type="text" name="full_name" required>

            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user_email) ?>" required>

            <label>Room Type:</label>
            <select name="room" id="roomSelect" required>
                <option value="">-- Select Room --</option>
                <?php
                include 'config.php';
                $result = mysqli_query($conn, "SELECT * FROM rooms ORDER BY id ASC");
                while ($row = mysqli_fetch_assoc($result)) {
                    $roomType = htmlspecialchars($row['room_type']);
                    $price = htmlspecialchars($row['price']);
                    $disabled = ($row['status'] === 'Unavailable') ? 'disabled' : '';
                    $label = "{$roomType} - RM{$price}/night";
                    if ($row['status'] === 'Unavailable')
                        $label .= " (Unavailable)";
                    echo "<option value=\"{$roomType}\" {$disabled}>{$label}</option>";
                }
                ?>
            </select>


            <label>Guests:</label>
            <input type="number" name="guests" min="1" max="10" required>

            <label>Check-in Date:</label>
            <input type="date" name="check_in" required>

            <label>Check-out Date:</label>
            <input type="date" name="check_out" required>

            <label>Special Request:</label>
            <textarea name="special_request" placeholder="Any special requests..."></textarea>

            <div class="qr-section" id="qrSection">
                <h3>QR Payment</h3>
                <img src="qr_payment.png" alt="QR Payment">
                <p id="priceDisplay" style="font-size:18px; font-weight:bold; color:#1e3a8a; margin:10px 0;"></p>

                <label>Attach Payment Receipt (image or PDF):</label>
                <input type="file" name="receipt" accept="image/*,application/pdf" required>

                <button type="submit" name="confirm_reserve">Confirm Reservation</button>
            </div>

            <button type="button" id="reserveBtn">Reserve</button>
        </form>
    </div>
    <script>
        const reserveBtn = document.getElementById('reserveBtn');
        const qrSection = document.getElementById('qrSection');
        const roomSelect = document.getElementById('roomSelect');
        const checkInInput = document.querySelector('input[name="check_in"]');
        const checkOutInput = document.querySelector('input[name="check_out"]');
        const priceDisplay = document.getElementById('priceDisplay');

        // 👇 Build price mapping dynamically from the dropdown
        const roomPrices = {};
        Array.from(roomSelect.options).forEach(opt => {
            if (opt.value) {
                const match = opt.text.match(/RM(\d+)/); // extract price from label
                if (match) roomPrices[opt.value] = parseFloat(match[1]);
            }
        });

        reserveBtn.addEventListener('click', function () {
            const selectedRoom = roomSelect.value;
            const checkIn = new Date(checkInInput.value);
            const checkOut = new Date(checkOutInput.value);

            if (!selectedRoom) {
                alert('Please select a room before proceeding.');
                return;
            }
            if (!checkInInput.value || !checkOutInput.value) {
                alert('Please select both check-in and check-out dates.');
                return;
            }

            const timeDiff = checkOut - checkIn;
            const nights = timeDiff / (1000 * 3600 * 24);

            if (nights <= 0) {
                alert('Check-out date must be after check-in date.');
                return;
            }

            const roomPrice = roomPrices[selectedRoom];
            if (!roomPrice) {
                alert('Could not determine room price. Please try again.');
                return;
            }

            const totalPrice = roomPrice * nights;
            priceDisplay.textContent = `Total Price: RM${totalPrice.toFixed(2)} (${nights} night${nights > 1 ? 's' : ''})`;

            qrSection.style.display = 'block';
            reserveBtn.style.display = 'none';
        });
    </script>



</body>

</html>