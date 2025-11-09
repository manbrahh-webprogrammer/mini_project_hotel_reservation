<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyHotel - Home</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .hero {
            text-align: center;
            background: linear-gradient(135deg, #60a5fa, #818cf8);
            color: white;
            padding: 100px 20px;
            border-bottom-left-radius: 80px;
            border-bottom-right-radius: 80px;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
        }

        .hero a {
            background-color: white;
            color: #1e3a8a;
            padding: 12px 28px;
            border-radius: 25px;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .hero a:hover {
            background-color: #1e3a8a;
            color: white;
        }

        .rooms {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 30px;
            padding: 60px 20px;
        }

        .room-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .room-card img {
            width: 100%;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            height: 200px;
            object-fit: cover;
        }

        .room-card h3 {
            margin: 15px 0 5px;
            color: #1e3a8a;
        }

        .room-card p {
            color: #64748b;
            margin-bottom: 15px;
        }

        .why {
            text-align: center;
            background-color: #ffffff;
            padding: 60px 20px;
            border-top: 2px solid #e5e7eb;
        }

        .why h2 {
            color: #1e3a8a;
            margin-bottom: 20px;
        }

        .why ul {
            list-style: none;
            padding: 0;
            color: #475569;
            font-size: 18px;
        }

        .why ul li {
            margin: 10px 0;
        }

        footer {
            background-color: #1e293b;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <?php include 'nav_template.php'; ?>

    <!-- Hero Section -->
    <section class="hero" style="
  position: relative;
  background: url('hotel_bg.jpg') center/cover no-repeat;
  height: 50vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  color: white;
">
        <!-- Overlay -->
        <div style="
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.45);
  "></div>

        <!-- Hero Content -->
        <div style="position: relative; z-index: 2; max-width: 800px;">
            <h1 style="
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 1rem;
      text-shadow: 0 3px 6px rgba(0,0,0,0.4);
    ">
                Welcome to MyHotel
            </h1>
            <p style="
      font-size: 1.2rem;
      margin-bottom: 2rem;
      line-height: 1.6;
      color: #f1f1f1;
    ">
                Experience comfort, convenience, and class — all in one stay.
            </p>
            <a href="reserve.php" style="
      background: #ff4d4d;
      padding: 12px 28px;
      border-radius: 25px;
      color: white;
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      transition: background 0.3s ease;
    " onmouseover="this.style.background='#ff3333'" onmouseout="this.style.background='#ff4d4d'">
                Book Now
            </a>
        </div>
    </section>


    <section class="rooms">
        <div class="room-card">
            <img src="room1.jpg" alt="Single Room">
            <h3>Single Room</h3>
            <p>RM150 / night</p>
        </div>

        <div class="room-card">
            <img src="room2.jpg" alt="Double Room">
            <h3>Double Room</h3>
            <p>RM220 / night</p>
        </div>

        <div class="room-card">
            <img src="room3.jpg" alt="Suite Room">
            <h3>Suite Room</h3>
            <p>RM350 / night</p>
        </div>
    </section>

    <section class="why">
        <h2>Why Choose MyHotel?</h2>
        <ul>
            <li>🛏️ <strong>Easy Booking:</strong> Reserve your room in just a few clicks.</li>
            <li>💳 <strong>Secure QR Payment:</strong> Instant confirmation & safe transactions.</li>
            <li>👨‍💼 <strong>Friendly Staff:</strong> We’re here 24/7 to make your stay great.</li>
        </ul>
    </section>

    <footer>
        <p>© <?= date("Y") ?> MyHotel. All rights reserved.</p>
    </footer>
</body>

</html>