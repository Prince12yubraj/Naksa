<?php
session_start();
include('db.php');

if (!isset($_SESSION['booking_id'])) {
    header("Location: book_ride.php");
    exit();
}


$booking_id = $_SESSION['booking_id'];

// Fetch booking and driver info
$sql = "SELECT b.*, d.name AS driver_name FROM bookings b 
        JOIN drivers d ON b.driver_id = d.id 
        WHERE b.id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo "Booking not found.";
    exit();
}
if ($booking['status'] === 'cancelled') {
    header("Location: booking_report.php");
    exit();
}

// Handle rejected or canceled
if (in_array($booking['status'], ['rejected', 'canceled'])) {
    unset($_SESSION['booking_id']);
    echo "<script>alert('Driver rejected or canceled your booking. Please try again.'); window.location='book_ride.php';</script>";
    exit();
}

// Redirect to accepted page
if ($booking['status'] === 'accepted') {
    header("Location: booking_accepted.php");
    exit();
}

// Calculate remaining time in seconds
$created_at = strtotime($booking['created_at']);
$time_left = 300 - (time() - $created_at); // 5 mins = 300 seconds

if ($time_left <= 0) {
    // Timeout
    $update = $con->prepare("UPDATE bookings SET status='canceled' WHERE id=?");
    $update->bind_param("i", $booking_id);
    $update->execute();
    unset($_SESSION['booking_id']);
    echo "<script>alert('Driver did not respond in time. Please try again.'); window.location='book_ride.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waiting for Driver</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: #f1f3f6;
            margin: 0;
        }
        .container {
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            color: #333;
        }
        .subtext {
            font-size: 1rem;
            color: #555;
            margin-bottom: 20px;
        }
        .timer {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
            margin: 20px 0;
        }
        .spinner {
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Waiting for Driver: <br><strong><?php echo htmlspecialchars($booking['driver_name']); ?></strong></h2>
    <p class="subtext">Please wait while the driver accepts your ride request.</p>
    <div class="timer" id="timer">05:00</div>
    <div class="spinner">⏳</div>
</div>

<script>
    let timeLeft = <?php echo $time_left; ?>; // seconds

    function formatTime(seconds) {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    }

    const timerEl = document.getElementById('timer');
    timerEl.textContent = formatTime(timeLeft);

    const countdown = setInterval(() => {
        timeLeft--;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            alert("Driver did not respond in time. Redirecting...");
            window.location = 'book_ride.php';
        } else {
            timerEl.textContent = formatTime(timeLeft);
        }
    }, 1000);

    // Poll for status update every 5 seconds
    setInterval(() => {
        fetch('check_booking_status.php?booking_id=<?php echo $booking_id; ?>')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'accepted') {
                    alert('Driver accepted your booking!');
                    window.location = 'booking_accepted.php';
                } else if (['rejected', 'canceled'].includes(data.status)) {
                    alert('Driver rejected or canceled your booking. Redirecting...');
                    window.location = 'book_ride.php';
                }
            });
    }, 5000);
</script>
</body>
</html>
