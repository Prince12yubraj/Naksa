<?php
include('db.php');

if (!isset($_GET['lat'], $_GET['lon'])) {
    echo json_encode(['error' => 'Missing coordinates']);
    exit;
}

$lat = floatval($_GET['lat']);
$lon = floatval($_GET['lon']);

// Haversine formula (Earth radius = 6371 km)
$query = "
    SELECT id,
    (6371 * acos(
        cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
        sin(radians(?)) * sin(radians(latitude))
    )) AS distance
    FROM drivers
    WHERE is_available = 1 AND status = 'approved'
    ORDER BY distance ASC
    LIMIT 1
";

$stmt = $con->prepare($query);
$stmt->bind_param("ddd", $lat, $lon, $lat);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['driver_id' => $row['id']]);
} else {
    echo json_encode(['driver_id' => null]);
}
?>
