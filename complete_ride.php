<?php
session_start();
include('db.php');

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['booking_id'])) {
    // Invalid request
    header("Location: driver_dashboard.php"); // Or another page
    exit();
}

$booking_id = intval($_POST['booking_id']);

// Check if booking exists, belongs to this driver, and status is accepted
$sql = "SELECT status FROM bookings WHERE id = ? AND driver_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $booking_id, $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Booking not found or doesn't belong to this driver
    $_SESSION['error'] = "Booking not found or not authorized.";
    header("Location: driver_dashboard.php");
    exit();
}

$booking = $result->fetch_assoc();

if ($booking['status'] !== 'accepted') {
    $_SESSION['error'] = "Cannot complete booking that is not accepted.";
    header("Location: driver_dashboard.php");
    exit();
}

// Update booking status to completed
$update_sql = "UPDATE bookings SET status = 'completed', updated_at = NOW() WHERE id = ?";
$update_stmt = $con->prepare($update_sql);
$update_stmt->bind_param("i", $booking_id);

if ($update_stmt->execute()) {
    $_SESSION['success'] = "Ride marked as completed successfully.";
    header("Location: driver_dashboard.php"); // Redirect where you want after completion
    exit();
} else {
    $_SESSION['error'] = "Failed to update booking status. Please try again.";
    header("Location: driver_dashboard.php");
    exit();
}
