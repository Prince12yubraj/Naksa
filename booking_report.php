<?php
session_start();
include('db.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cancel booking POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = (int)$_POST['booking_id'];

    $stmtCheck = $con->prepare("SELECT status, created_at FROM bookings WHERE id = ? AND user_id = ?");
    $stmtCheck->bind_param("ii", $bookingId, $user_id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows > 0) {
        $booking = $resultCheck->fetch_assoc();

        $created_at = strtotime($booking['created_at']);
        $now = time();
        $time_diff = $now - $created_at;

        if ($booking['status'] === 'cancelled' || $booking['status'] === 'completed') {
            $_SESSION['message'] = "Booking #$bookingId cannot be cancelled. Already {$booking['status']}.";
        } elseif ($time_diff > 120) { // 2 minutes
            $_SESSION['message'] = "Booking #$bookingId can only be cancelled within 2 minutes of booking.";
        } else {
            $stmtCancel = $con->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ?");
            $stmtCancel->bind_param("ii", $bookingId, $user_id);
            $stmtCancel->execute();

            if ($stmtCancel->affected_rows > 0) {
                $_SESSION['message'] = "Booking #$bookingId has been cancelled.";
            } else {
                $_SESSION['message'] = "Unable to cancel booking #$bookingId.";
            }
            $stmtCancel->close();
        }
    } else {
        $_SESSION['message'] = "Booking not found.";
    }

    $stmtCheck->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch bookings
$stmt = $con->prepare("SELECT b.*, d.name as driver_name, d.phone as driver_phone 
                       FROM bookings b 
                       JOIN drivers d ON b.driver_id = d.id 
                       WHERE b.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Booking Report - Naksa</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
   body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f4f4f4;
    padding: 20px;
    color: #333;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #0072ff;
}

.print-btn {
    background: #3498db;
    color: white;
    border: none;
    padding: 10px 20px;
    font-weight: bold;
    border-radius: 5px;
    cursor: pointer;
    margin: 0 auto 20px;
    display: block;
    transition: background 0.3s ease;
}

.print-btn:hover {
    background: #2980b9;
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

th, td {
    padding: 15px;
    border: 1px solid #ddd;
    text-align: center;
}

th {
    background: #0072ff;
    color: white;
}

tr:nth-child(even) {
    background: #f9f9f9;
}

.status-pending {
    color: orange;
    font-weight: bold;
}

.status-accepted {
    color: green;
    font-weight: bold;
}

.status-rejected {
    color: red;
    font-weight: bold;
}

.status-cancelled {
    color: #e67e22;
    font-weight: bold;
}

.cancel-btn {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease;
}

.cancel-btn:hover {
    background: #c0392b;
}

.message-box {
    max-width: 800px;
    margin: 0 auto 20px;
    padding: 10px 15px;
    background-color: #d4edda;
    color: #155724;
    border-radius: 5px;
    font-weight: bold;
    text-align: center;
    border: 1px solid #c3e6cb;
}

nav {
    background: #0072ff;
    padding: 12px 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    gap: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-weight: 600;
    font-size: 16px;
}

nav a {
    color: white;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background-color 0.3s ease;
}

nav a i {
    font-size: 18px;
}

nav a:hover,
nav a:focus {
    background-color: rgba(255, 255, 255, 0.2);
    outline: none;
}

.admin-link {
    background-color: #e74c3c;
    font-weight: 700;
}

.admin-link:hover {
    background-color: #c0392b;
}

@media (max-width: 768px) {
    table, thead, tbody, th, td, tr {
        display: block;
    }

    thead {
        display: none;
    }

    td {
        padding: 10px;
        text-align: right;
        position: relative;
    }

    td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        text-align: left;
        font-weight: bold;
    }
}

@media (max-width: 600px) {
    nav {
        justify-content: center;
        gap: 10px;
        font-size: 14px;
    }
    nav a i {
        font-size: 16px;
    }
}

@media print {
    body {
        background: white;
        color: black;
    }

    .print-btn,
    .cancel-btn,
    .message-box,
    form {
        display: none !important;
    }

    td::before {
        content: none !important;
    }
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

<h2>My Booking Report</h2>


<button onclick="window.print()" class="print-btn">🖨️ Print Report</button>

<?php if (isset($_SESSION['message'])): ?>
    <div class="message-box">
        <?php 
            echo htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']);
        ?>
    </div>
<?php endif; ?>

<?php if ($result->num_rows > 0): ?>
<table>
    <thead>
        <tr>
            <th>Driver</th>
            <th>Phone</th>
            <th>Pickup</th>
            <th>Dropoff</th>
            <th>Price (₹)</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td data-label="Driver"><?php echo htmlspecialchars($row['driver_name']); ?></td>
            <td data-label="Phone"><?php echo htmlspecialchars($row['driver_phone']); ?></td>
            <td data-label="Pickup"><?php echo htmlspecialchars($row['pickup_location']); ?></td>
            <td data-label="Dropoff"><?php echo htmlspecialchars($row['dropoff_location']); ?></td>
            <td data-label="Price">₹<?php echo number_format($row['price'], 2); ?></td>
            <td data-label="Status">
                <?php
                    $status = strtolower($row['status']);
                    if ($status == 'pending') echo "<span class='status-pending'>Pending</span>";
                    elseif ($status == 'accepted') echo "<span class='status-accepted'>Accepted</span>";
                    elseif ($status == 'rejected') echo "<span class='status-rejected'>Rejected</span>";
                    elseif ($status == 'cancelled') echo "<span class='status-cancelled'>Cancelled</span>";
                    else echo htmlspecialchars($row['status']);
                ?>
            </td>
            <td data-label="Action">
                <?php
                    $booking_time = strtotime($row['created_at']);
                    $now = time();
                    $within_two_minutes = ($now - $booking_time <= 120);
                ?>
                <?php if (!in_array($status, ['cancelled', 'completed', 'rejected']) && $within_two_minutes): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="cancel_booking" class="cancel-btn">Cancel</button>
                    </form>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align:center;">You have no bookings yet.</p>
<?php endif; ?>

<?php
$stmt->close();
$con->close();
?>

</body>
</html>
