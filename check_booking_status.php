<?php
include('db.php');

if (!isset($_GET['booking_id'])) {
    echo json_encode(['status' => 'invalid']);
    exit();
}

$booking_id = intval($_GET['booking_id']);
$stmt = $con->prepare("SELECT status FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode(['status' => $row['status'] ?? 'not_found']);
?>
