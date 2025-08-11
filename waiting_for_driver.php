<?php
session_start();
include('db.php');

if (!isset($_SESSION['booking_id'])) {
    header("Location: book_ride.php");
    exit();
}

$booking_id = $_SESSION['booking_id'];

$sql = "SELECT b.*, d.name AS driver_name FROM bookings b JOIN drivers d ON b.driver_id = d.id WHERE b.id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo "Booking not found.";
    exit();
}

if (in_array($booking['status'], ['rejected', 'canceled'])) {
    unset($_SESSION['booking_id']);
    echo "<script>alert('Driver rejected or canceled your booking. Please try again.'); window.location='book_ride.php';</script>";
    exit();
}

if ($booking['status'] === 'accepted') {
    echo "<script>window.location='booking_accepted.php';</script>";
    exit();
}

$created_at = strtotime($booking['created_at']);
$time_left = 300 - (time() - $created_at);

if ($time_left <= 0) {
    $update = $con->prepare("UPDATE bookings SET status='canceled' WHERE id=?");
    $update->bind_param("i", $booking_id);
    $update->execute();
    unset($_SESSION['booking_id']);
    echo "<script>alert('Driver did not respond in time. Please try booking again.'); window.location='book_ride.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Waiting for Driver</title>
    <style>
        body { font-family: Arial; text-align: center; padding: 40px; }
        .countdown { font-size: 2em; color: green; }
    </style>
</head>
<body>
    <h2>Booking sent to <?php echo htmlspecialchars($booking['driver_name']); ?></h2>
    <p>Waiting for driver response...</p>
    <p>Time left: <span class="countdown" id="timer"><?php echo $time_left; ?></span> seconds</p>

    <script>
    let timeLeft = <?php echo $time_left; ?>;
    const timerEl = document.getElementById('timer');

    const countdown = setInterval(() => {
        timeLeft--;
        timerEl.textContent = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            alert("Driver did not respond in time.");
            window.location = "book_ride.php";
        }
    }, 1000);

    // Poll for booking status
    setInterval(() => {
        fetch('check_booking_status.php?booking_id=<?php echo $booking_id; ?>')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'accepted') {
                    window.location = 'booking_accepted.php';
                } else if (['rejected', 'canceled'].includes(data.status)) {
                    alert("Driver rejected or canceled the ride.");
                    window.location = 'book_ride.php';
                }
            });
    }, 5000);
    </script>
</body>
</html>
