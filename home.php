<?php
session_start();
include('db.php');

// Fetch available drivers
$sql = "SELECT * FROM drivers WHERE is_available = 1"; 
$result = $con->query($sql);

// Set page title
$pageTitle = "Naksa - Home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0072ff;
            --secondary-color: #28a745;
            --dark-bg: #333;
            --light-bg: #f8f8f8;
            --text-color: #333;
            --text-light: #777;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
        }
        nav {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: center;
            gap: 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('assets/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            text-align: center;
            padding: 100px 20px;
            margin-bottom: 40px;
        }
        .btn {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 12px 25px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            border: 2px solid var(--secondary-color);
        }
        .driver-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto 40px;
        }
        .driver {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .driver-img-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            border: 3px solid var(--primary-color);
        }
        .driver img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
       footer {
            background-color: var(--dark-bg);
            color: white;
            text-align: center;
            padding: 15px 10px;
            font-size: 14px;
            margin-top: auto;
        }
    </style>
</head>
<body>

<nav>
    <a href="admin/admin_login.php" class="admin-link"><i class="fas fa-user-shield"></i> Admin</a>
    <a href="home.php"><i class="fas fa-home"></i> Home</a>
    <a href="book_ride.php"><i class="fas fa-car"></i> Book Now</a>
    <a href="booking_report.php"><i class="fas fa-list"></i> My Bookings</a>
    <a href="register_driver.php"><i class="fas fa-id-card"></i> Drivers</a>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php else: ?>
        <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
    <?php endif; ?>
</nav>

<section class="hero">
    <h1>Your Reliable Ride-Hailing Service</h1>
    <p>Book your ride in seconds and enjoy comfortable transportation at affordable prices</p>
    <a href="book_ride.php" class="btn">Book a Ride Now</a>
</section>

<h2 style="text-align:center; margin-bottom:20px;">Available Drivers</h2>

<div class="driver-container">
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="driver">
                <div class="driver-img-container">
                    <?php if (!empty($row['driver_photo']) && file_exists("uploads/drivers/" . $row['driver_photo'])): ?>
                        <img src="uploads/drivers/<?php echo htmlspecialchars($row['driver_photo']); ?>" alt="Driver Photo" />
                    <?php else: ?>
                        <img src="assets/default-driver.png" alt="No Photo Available" />
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($row['phone']); ?></p>
                <p><strong>Vehicle:</strong> <?php echo htmlspecialchars($row['vehicle_name'] . ' ' . $row['vehicle_model']); ?></p>
                
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center;">No drivers available at the moment.</p>
    <?php endif; ?>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Naksa Ride-Hailing Service. All rights reserved.
</footer>

<?php $con->close(); ?>
</body>
</html>