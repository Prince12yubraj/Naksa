<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pickup = $_GET['pickup'] ?? '';

if (empty($pickup)) {
    die("Pickup location missing.");
}

// Geocode pickup location
function getCoordinates($location) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($location);
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    if ($data && count($data) > 0) {
        return ['lat' => $data[0]['lat'], 'lon' => $data[0]['lon']];
    }
    return null;
}

$pickupCoords = getCoordinates($pickup);
if (!$pickupCoords) {
    die("Invalid pickup address.");
}

// Haversine SQL to find nearest online driver
$query = "
    SELECT *, (
        6371 * acos(
            cos(radians({$pickupCoords['lat']})) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians({$pickupCoords['lon']})) +
            sin(radians({$pickupCoords['lat']})) *
            sin(radians(latitude))
        )
    ) AS distance
    FROM drivers
    WHERE status = 'online'
    ORDER BY distance ASC
    LIMIT 1
";
$result = $con->query($query);
$driver = $result->fetch_assoc();

if (!$driver) {
    die("No online driver available.");
}

// Update existing booking
$con->query("UPDATE bookings SET driver_id = {$driver['id']}, status = 'pending' WHERE user_id = $user_id ORDER BY id DESC LIMIT 1");

header("Location: booking_status.php");
exit();
