<?php
session_start();
include('db.php');

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];

$sql = "SELECT b.*, u.first_name AS user_name, u.phone AS user_phone, d.name AS driver_name, d.phone AS driver_phone
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN drivers d ON b.driver_id = d.id
        WHERE b.driver_id = ? AND b.status = 'accepted'
        ORDER BY b.updated_at DESC LIMIT 1";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header("Location: waiting_for_driver.php");
    exit();
}

$pickup_lat = $booking['pickup_lat'];
$pickup_lng = $booking['pickup_lng'];
$dropoff_lat = $booking['dropoff_lat'];
$dropoff_lng = $booking['dropoff_lng'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ride Accepted</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        body { font-family: Arial; padding: 20px; text-align: center; }
        #map { height: 450px; width: 100%; margin-top: 20px; }
        .info { font-size: 18px; background: #f2f2f2; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        #eta_driver, #eta_ride { font-size: 20px; font-weight: bold; margin-top: 10px; }
        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #0072ff;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <a href="bookingreport.php" class="back-btn">← Back to Booking Report</a>

    <div class="info">
        <h2>Your Ride is Confirmed ✅</h2>
        <p><strong>User:</strong> <?= htmlspecialchars($booking['user_name']) ?></p>
        <p><strong>User Phone:</strong> <?= htmlspecialchars($booking['user_phone']) ?></p>
        <p><strong>Driver:</strong> <?= htmlspecialchars($booking['driver_name']) ?></p>
        <p><strong>Driver Phone:</strong> <?= htmlspecialchars($booking['driver_phone']) ?></p>
        <p><strong>Pickup:</strong> <span id="pickupAddressDisplay">Loading...</span></p>
        <p><strong>Dropoff:</strong> <span id="dropoffAddressDisplay">Loading...</span></p>
        <p><strong>Price:</strong> Rs. <?= number_format($booking['price'], 2) ?></p>
        <div id="eta_driver">Estimated Time (Driver → Pickup): Calculating...</div>
        <div id="eta_ride">Estimated Time (Pickup → Dropoff): Calculating...</div>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>
    <script>
    const pickupLatLng = { lat: <?= $pickup_lat ?>, lon: <?= $pickup_lng ?> };
    const dropoffLatLng = { lat: <?= $dropoff_lat ?>, lon: <?= $dropoff_lng ?> };

    async function reverseGeocode(lat, lon) {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`);
        const data = await res.json();
        return data.display_name || `${lat}, ${lon}`;
    }

    function formatDuration(seconds) {
        const minutes = Math.round(seconds / 60);
        return minutes <= 1 ? "1 minute" : minutes + " minutes";
    }

    function updateETAs(driverLat, driverLng) {
        // Driver to Pickup
        fetch(`https://router.project-osrm.org/route/v1/driving/${driverLng},${driverLat};${pickupLatLng.lon},${pickupLatLng.lat}?overview=false`)
        .then(res => res.json())
        .then(data => {
            if (data.routes?.length > 0) {
                const seconds = data.routes[0].duration;
                document.getElementById("eta_driver").textContent = "Estimated Time (Driver → Pickup): " + formatDuration(seconds);
            }
        });

        // Pickup to Dropoff
        fetch(`https://router.project-osrm.org/route/v1/driving/${pickupLatLng.lon},${pickupLatLng.lat};${dropoffLatLng.lon},${dropoffLatLng.lat}?overview=false`)
        .then(res => res.json())
        .then(data => {
            if (data.routes?.length > 0) {
                const seconds = data.routes[0].duration;
                document.getElementById("eta_ride").textContent = "Estimated Time (Pickup → Dropoff): " + formatDuration(seconds);
            }
        });
    }

    async function initMap() {
        const pickupAddress = await reverseGeocode(pickupLatLng.lat, pickupLatLng.lon);
        const dropoffAddress = await reverseGeocode(dropoffLatLng.lat, dropoffLatLng.lon);
        document.getElementById("pickupAddressDisplay").textContent = pickupAddress;
        document.getElementById("dropoffAddressDisplay").textContent = dropoffAddress;

        const map = L.map('map').setView([pickupLatLng.lat, pickupLatLng.lon], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        L.marker([pickupLatLng.lat, pickupLatLng.lon]).addTo(map).bindPopup("Pickup");
        L.marker([dropoffLatLng.lat, dropoffLatLng.lon]).addTo(map).bindPopup("Dropoff");

        let driverMarker = null;
        let routingControl = null;

        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(position => {
                const driverLat = position.coords.latitude;
                const driverLon = position.coords.longitude;

                if (driverMarker) {
                    driverMarker.setLatLng([driverLat, driverLon]);
                } else {
                    driverMarker = L.marker([driverLat, driverLon], {
                        icon: L.icon({
                            iconUrl: 'https://cdn-icons-png.flaticon.com/512/149/149059.png',
                            iconSize: [30, 30],
                            iconAnchor: [15, 30]
                        })
                    }).addTo(map).bindPopup("Driver");
                }

                updateETAs(driverLat, driverLon);

                if (routingControl) {
                    routingControl.setWaypoints([
                        L.latLng(driverLat, driverLon),
                        L.latLng(pickupLatLng.lat, pickupLatLng.lon),
                        L.latLng(dropoffLatLng.lat, dropoffLatLng.lon)
                    ]);
                } else {
                    routingControl = L.Routing.control({
                        waypoints: [
                            L.latLng(driverLat, driverLon),
                            L.latLng(pickupLatLng.lat, pickupLatLng.lon),
                            L.latLng(dropoffLatLng.lat, dropoffLatLng.lon)
                        ],
                        routeWhileDragging: false,
                        draggableWaypoints: false,
                        createMarker: () => null
                    }).addTo(map);
                }
            }, () => alert("Geolocation not available."), {
                enableHighAccuracy: true,
                timeout: 10000
            });
        } else {
            alert("Geolocation is not supported.");
        }
    }

    initMap();
    </script>
</body>
</html>
