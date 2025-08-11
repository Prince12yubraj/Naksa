<?php
session_start();
include('db.php');

if (!isset($_SESSION['booking_id'])) {
    header("Location: book_ride.php");
    exit();
}

$booking_id = $_SESSION['booking_id'];

$sql = "SELECT b.*, d.name AS driver_name, d.phone AS driver_phone 
        FROM bookings b
        JOIN drivers d ON b.driver_id = d.id 
        WHERE b.id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
if ($booking['status'] === 'completed') {
    
    header("Location: booking_report.php"); 
    exit();
}

if (!$booking || $booking['status'] !== 'accepted') {
    header("Location: waiting_for_driver.php");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Ride Tracking</title>
    <meta charset="UTF-8" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        body { font-family: Arial; padding: 20px; text-align: center; }
        #map { height: 450px; width: 100%; margin-top: 20px; }
        .info { font-size: 18px; background: #f2f2f2; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        #eta, #travel-time { font-size: 20px; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="info">
        <h2>Your Ride is Confirmed ✅</h2>
        <p><strong>Driver:</strong> <?= htmlspecialchars($booking['driver_name']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($booking['driver_phone']) ?></p>
        <p><strong>Pickup:</strong> <?= htmlspecialchars($booking['pickup_location']) ?></p>
        <p><strong>Dropoff:</strong> <?= htmlspecialchars($booking['dropoff_location']) ?></p>
        <p><strong>Price:</strong> Rs. <?= number_format($booking['price'], 2) ?></p>
        <div id="eta">Estimated Time to Pickup: Calculating...</div>
        <div id="travel-time" style="display:none;">Estimated Time to Dropoff: </div>
    </div>

    <div id="map"></div>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.min.js"></script>

    <script>
    const pickupLat = <?= $booking['pickup_lat'] ?>;
    const pickupLng = <?= $booking['pickup_lng'] ?>;
    const dropoffLat = <?= $booking['dropoff_lat'] ?>;
    const dropoffLng = <?= $booking['dropoff_lng'] ?>;

    const map = L.map('map').setView([pickupLat, pickupLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const pickupMarker = L.marker([pickupLat, pickupLng]).addTo(map).bindPopup("Pickup Location").openPopup();
    const dropoffMarker = L.marker([dropoffLat, dropoffLng]).addTo(map).bindPopup("Dropoff Location");

    let driverMarker = null;
    let routingControl = null;
    let hasArrivedAtPickup = false;

    function showETA(seconds) {
        const minutes = Math.round(seconds / 60);
        document.getElementById("eta").textContent = 
            "Estimated Time to Pickup: " + (minutes <= 1 ? "1 minute" : minutes + " minutes");
    }

    function showTravelTime(seconds) {
        const minutes = Math.round(seconds / 60);
        const travelDiv = document.getElementById("travel-time");
        travelDiv.textContent = "Estimated Time to Dropoff: " + (minutes <= 1 ? "1 minute" : minutes + " minutes");
        travelDiv.style.display = 'block';
    }

    function getETA(startLat, startLng, endLat, endLng, callback) {
        fetch(`https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${endLng},${endLat}?overview=false`)
            .then(res => res.json())
            .then(data => {
                if (data.routes && data.routes.length > 0) {
                    callback(data.routes[0].duration);
                }
            })
            .catch(err => {
                console.error("Failed to fetch route:", err);
            });
    }

    function isNear(lat1, lng1, lat2, lng2, radiusMeters = 50) {
        const R = 6371e3; // Earth radius in meters
        const rad = deg => deg * Math.PI / 180;
        const dLat = rad(lat2 - lat1);
        const dLon = rad(lng2 - lng1);
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(rad(lat1)) * Math.cos(rad(lat2)) *
                  Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c < radiusMeters;
    }

    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(position => {
            const driverLat = position.coords.latitude;
            const driverLng = position.coords.longitude;

            if (driverMarker) {
                driverMarker.setLatLng([driverLat, driverLng]);
            } else {
                driverMarker = L.marker([driverLat, driverLng], {
                    icon: L.icon({
                        iconUrl: 'https://cdn-icons-png.flaticon.com/512/149/149059.png',
                        iconSize: [30, 30],
                        iconAnchor: [15, 30]
                    })
                }).addTo(map).bindPopup("Driver");
            }

            // Update route
            if (routingControl) {
                routingControl.setWaypoints([
                    L.latLng(driverLat, driverLng),
                    L.latLng(pickupLat, pickupLng),
                    L.latLng(dropoffLat, dropoffLng)
                ]);
            } else {
                routingControl = L.Routing.control({
                    waypoints: [
                        L.latLng(driverLat, driverLng),
                        L.latLng(pickupLat, pickupLng),
                        L.latLng(dropoffLat, dropoffLng)
                    ],
                    routeWhileDragging: false,
                    draggableWaypoints: false,
                    createMarker: () => null
                }).addTo(map);
            }

            // If not yet arrived at pickup, show ETA to pickup
            if (!hasArrivedAtPickup) {
                getETA(driverLat, driverLng, pickupLat, pickupLng, showETA);

                // Check arrival at pickup
                if (isNear(driverLat, driverLng, pickupLat, pickupLng)) {
                    hasArrivedAtPickup = true;
                    document.getElementById("eta").textContent = "Driver has arrived at pickup!";

                    // Now show travel time to dropoff
                    getETA(pickupLat, pickupLng, dropoffLat, dropoffLng, showTravelTime);
                }
            }
        }, error => {
            alert("Unable to access your location.");
        }, {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 10000
        });
    } else {
        alert("Geolocation not supported.");
    }
    </script>
</body>
</html>
