<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    // Check if user already has an active booking
    $check_sql = "SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND status IN ('pending', 'accepted', 'ongoing')";
    $stmt_check = $con->prepare($check_sql);
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();

    if ($row_check['count'] > 0) {
        echo "<script>alert('You already have an active booking. Please complete or cancel it before creating a new one.'); history.back();</script>";
        exit();
    }

    // Sanitize and fetch POST data
    $pickup_location  = trim($_POST['pickup_location']);
    $pickup_lat       = floatval($_POST['pickup_lat']);
    $pickup_lng       = floatval($_POST['pickup_lng']);
    $dropoff_location = trim($_POST['dropoff_location']);
    $dropoff_lat      = floatval($_POST['dropoff_lat']);
    $dropoff_lng      = floatval($_POST['dropoff_lng']);
    $distance_km      = floatval($_POST['distance_km']);
    $duration_sec     = intval($_POST['duration_sec']);
    $price            = floatval($_POST['price']);

    // Validate inputs (basic)
    if (!$pickup_location || !$dropoff_location || !$pickup_lat || !$pickup_lng || !$dropoff_lat || !$dropoff_lng) {
        echo "<script>alert('Please provide valid pickup and dropoff locations.'); history.back();</script>";
        exit();
    }

    // Find nearest approved and available driver
    $sql = "
        SELECT id, (
            6371 * ACOS(
                COS(RADIANS(?)) *
                COS(RADIANS(current_latitude)) *
                COS(RADIANS(current_longitude) - RADIANS(?)) +
                SIN(RADIANS(?)) *
                SIN(RADIANS(current_latitude))
            )
        ) AS distance
        FROM drivers
        WHERE is_available = 1 
          AND status = 'approved'
          AND current_latitude IS NOT NULL 
          AND current_longitude IS NOT NULL
        ORDER BY distance ASC
        LIMIT 1
    ";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ddd", $pickup_lat, $pickup_lng, $pickup_lat);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $driver_id = $row['id'];

        $insert_sql = "INSERT INTO bookings 
            (user_id, driver_id, pickup_location, pickup_lat, pickup_lng, dropoff_location, dropoff_lat, dropoff_lng, distance_km, duration_sec, price, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $stmt_insert = $con->prepare($insert_sql);

        // Bind params - types: i (int), i (int), s (string), d (double), d (double), s (string), d, d, d, i, d
        $stmt_insert->bind_param(
            "iisddsdddid", 
            $user_id,
            $driver_id,
            $pickup_location,
            $pickup_lat,
            $pickup_lng,
            $dropoff_location,
            $dropoff_lat,
            $dropoff_lng,
            $distance_km,
            $duration_sec,
            $price
        );

        if ($stmt_insert->execute()) {
            $_SESSION['booking_id'] = $con->insert_id;
            echo "<script>window.location = 'booking_status.php';</script>";
            exit();
        } else {
            echo "<script>alert('Failed to create booking.'); history.back();</script>";
            exit();
        }
    } else {
        echo "<script>alert('No available drivers nearby.'); history.back();</script>";
        exit();
    }
} else {
    echo "<script>alert('Invalid access.'); window.location = 'book_ride.php';</script>";
    exit();
}

