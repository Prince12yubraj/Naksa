<?php
session_start();
include('db.php');

if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];

// AJAX endpoint to update driver location (latitude, longitude)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lat'], $_POST['update_lon'])) {
    $lat = floatval($_POST['update_lat']);
    $lon = floatval($_POST['update_lon']);
    
    $stmt = $con->prepare("UPDATE drivers SET current_latitude = ?, current_longitude = ? WHERE id = ?");
    $stmt->bind_param("ddi", $lat, $lon, $driver_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit();
}

// Auto-delete pending bookings older than 5 minutes for this driver
$con->query("DELETE FROM bookings WHERE status = 'pending' AND driver_id = $driver_id AND created_at < (NOW() - INTERVAL 5 MINUTE)");

// Fetch driver info including current latitude and longitude
$driver = $con->query("SELECT *, current_latitude, current_longitude FROM drivers WHERE id = $driver_id")->fetch_assoc();
if (!$driver) {
    echo "Driver not found.";
    exit();
}

// Automatically set is_available = 0 if status is rejected
if ($driver['status'] === 'rejected' && $driver['is_available'] == 1) {
    $con->query("UPDATE drivers SET is_available = 0 WHERE id = $driver_id");
    $driver['is_available'] = 0;
}

// Toggle online/offline
if (isset($_GET['toggle']) && $driver['status'] === 'approved') {
    $con->query("UPDATE drivers SET is_available = NOT is_available WHERE id = $driver_id");
    header("Location: driver_dashboard.php");
    exit();
}

// Handle booking response (accept/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['action']) && !isset($_POST['update_lat'])) {
    $booking_id = intval($_POST['booking_id']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $con->query("UPDATE bookings SET status = 'accepted' WHERE id = $booking_id AND status = 'pending'");
        $_SESSION['msg'] = "Booking accepted successfully.";
        header("Location: booking_map.php");
        exit();
    } elseif ($action === 'reject') {
        $con->query("UPDATE bookings SET status = 'rejected' WHERE id = $booking_id AND status = 'pending'");
        $_SESSION['msg'] = "Booking rejected successfully.";
    }

    header("Location: driver_dashboard.php");
    exit();
}

// Fetch booking stats
$bookings = $con->query("SELECT status, COUNT(*) as total FROM bookings WHERE driver_id = $driver_id GROUP BY status");
$bookingStats = ['pending' => 0, 'accepted' => 0, 'completed' => 0];
$totalBookings = 0;
while ($row = $bookings->fetch_assoc()) {
    $bookingStats[$row['status']] = $row['total'];
    $totalBookings += $row['total'];
}

// Check for newly cancelled bookings assigned to this driver, not yet notified
$newCancelledBookings = $con->query("SELECT id FROM bookings WHERE driver_id = $driver_id AND status = 'cancelled' AND cancellation_notified = 0");
$showCancellationPopup = false;
if ($newCancelledBookings && $newCancelledBookings->num_rows > 0) {
    $showCancellationPopup = true;
    $con->query("UPDATE bookings SET cancellation_notified = 1 WHERE driver_id = $driver_id AND status = 'cancelled' AND cancellation_notified = 0");
}

// Fetch pending bookings including pickup and dropoff lat/lng
$pendingBookings = $con->query("
    SELECT b.*, u.first_name, u.last_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    WHERE b.driver_id = $driver_id AND b.status = 'pending'
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Driver Dashboard - Naksa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }
        .dashboard {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .profile {
            margin-top: 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .profile img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ccc;
        }
        .stats {
            margin-top: 30px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        .stat-box {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 0 5px rgba(0,0,0,0.05);
        }
        .availability {
            margin-top: 30px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            color: white;
            background: #007bff;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }
        .btn-toggle { background: #28a745; }
        .btn-logout { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #f0f0f0; }
        .message { background: #e0ffe0; padding: 10px; border: 1px solid #b2ffb2; margin-bottom: 20px; }
        .stat-box a {
            display: block;
            font-size: 2rem;
            font-weight: bold;
            color: inherit;
            text-decoration: none;
        }
        .stat-box a:hover {
            text-decoration: underline;
        }
        #route-map {
            height: 400px;
            margin-top: 20px;
            display: none;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .btn-show-route {
            background-color: #3b82f6;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .btn-show-route:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="header">
        <h2>Welcome, <?= htmlspecialchars($driver['name']); ?> 👋</h2>
        <a href="driver_logout.php" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <?php if (isset($_SESSION['msg'])): ?>
        <div class="message"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <div class="profile">
        <img src="uploads/drivers/<?= htmlspecialchars($driver['driver_photo']); ?>" alt="Driver Photo" />
        <div>
            <p><strong>Phone:</strong> <?= htmlspecialchars($driver['phone']); ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($driver['email']); ?></p>
            <p><strong>Vehicle:</strong> <?= htmlspecialchars($driver['vehicle_name'] . ' ' . $driver['vehicle_model']); ?> (<?= htmlspecialchars($driver['vehicle_reg']); ?>)</p>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <a href="driver_list.php"><?= $totalBookings; ?></a>
            <p>Total Bookings</p>
        </div>
        <div class="stat-box">
            <a href="driver_list.php?status=pending"><?= $bookingStats['pending']; ?></a>
            <p>Pending</p>
        </div>
        <div class="stat-box">
            <a href="driver_list.php?status=accepted"><?= $bookingStats['accepted']; ?></a>
            <p>Accepted</p>
        </div>
        <div class="stat-box">
            <a href="driver_list.php?status=completed"><?= $bookingStats['completed']; ?></a>
            <p>Completed</p>
        </div>
    </div>

    <div class="availability">
        <p><strong>Status:</strong>
            <?php if ($driver['status'] === 'approved'): ?>
                <span style="color: green;">Approved</span>
            <?php else: ?>
                <span style="color: red;"><?= htmlspecialchars(ucfirst($driver['status'])); ?></span>
            <?php endif; ?>
        </p>

        <p><strong>Availability:</strong>
            <?php if ($driver['is_available']): ?>
                <span style="color: green;">Online</span>
            <?php else: ?>
                <span style="color: red;">Offline</span>
            <?php endif; ?>
        </p>

        <?php if ($driver['status'] === 'approved'): ?>
            <a href="?toggle=1" class="btn btn-toggle">
                <i class="fas fa-toggle-<?= $driver['is_available'] ? 'on' : 'off'; ?>"></i>
                Go <?= $driver['is_available'] ? 'Offline' : 'Online'; ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Pending Bookings -->
    <div class="pending-bookings" style="margin-top: 40px;">
        <h3>Pending Bookings</h3>
        <?php if ($pendingBookings->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Pickup</th>
                        <th>Drop</th>
                        <th>Price</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($booking = $pendingBookings->fetch_assoc()): ?>
                    <tr>
                        <td><?= $booking['id']; ?></td>
                        <td><?= htmlspecialchars($booking['first_name'].' '.$booking['last_name']); ?></td>
                        <td><?= htmlspecialchars($booking['pickup_location']); ?></td>
                        <td><?= htmlspecialchars($booking['dropoff_location']); ?></td>
                        <td><?= htmlspecialchars($booking['price']); ?></td>
                        <td>
                            <button 
                                class="btn-show-route" 
                                data-pickup-lat="<?= htmlspecialchars($booking['pickup_lat']); ?>" 
                                data-pickup-lng="<?= htmlspecialchars($booking['pickup_lng']); ?>"
                                data-dropoff-lat="<?= htmlspecialchars($booking['dropoff_lat']); ?>" 
                                data-dropoff-lng="<?= htmlspecialchars($booking['dropoff_lng']); ?>">
                                Show Route
                            </button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                                <input type="hidden" name="action" value="accept">
                                <button class="btn btn-toggle" type="submit">Accept</button>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="booking_id" value="<?= $booking['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button class="btn btn-logout" type="submit">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No pending bookings.</p>
        <?php endif; ?>
        
        <!-- Map container -->
        <div id="route-map"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const mapContainer = document.getElementById('route-map');
    let map;
    let pickupMarker, dropoffMarker, driverMarker, routeLayer1, routeLayer2;

    // Driver coords fallback from server
    let driverCoords = null;

    <?php if ($driver['current_latitude'] && $driver['current_longitude']): ?>
        driverCoords = [<?= $driver['current_latitude'] ?>, <?= $driver['current_longitude'] ?>];
    <?php endif; ?>

    async function getRoute(start, end) {
        const url = `https://router.project-osrm.org/route/v1/driving/${start[1]},${start[0]};${end[1]},${end[0]}?overview=full&geometries=geojson`;
        try {
            const response = await fetch(url);
            const data = await response.json();
            if (data.code === "Ok" && data.routes.length > 0) {
                return data.routes[0].geometry;
            }
            return null;
        } catch (error) {
            console.error('Routing error:', error);
            return null;
        }
    }

    async function showRoute(pickupLat, pickupLng, dropoffLat, dropoffLng) {
        mapContainer.style.display = 'block';

        if (!map) {
            let initCoords = driverCoords || [27.7172, 85.3240];
            map = L.map('route-map').setView(initCoords, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);
        }

        const pickupCoords = [parseFloat(pickupLat), parseFloat(pickupLng)];
        const dropoffCoords = [parseFloat(dropoffLat), parseFloat(dropoffLng)];

        [pickupMarker, dropoffMarker, driverMarker, routeLayer1, routeLayer2].forEach(layer => {
            if (layer) map.removeLayer(layer);
        });

        pickupMarker = L.marker(pickupCoords).addTo(map).bindPopup('Pickup Location').openPopup();
        dropoffMarker = L.marker(dropoffCoords).addTo(map).bindPopup('Dropoff Location');

        if (driverCoords) {
            driverMarker = L.marker(driverCoords, {
                icon: L.icon({
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/1946/1946429.png',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32]
                })
            }).addTo(map).bindPopup('Your Current Location');
        }

        if (driverCoords) {
            const routeDriverPickup = await getRoute(driverCoords, pickupCoords);
            if (routeDriverPickup) {
                routeLayer1 = L.geoJSON(routeDriverPickup, {
                    color: 'red', weight: 4, opacity: 0.7
                }).addTo(map);
            } else {
                routeLayer1 = L.polyline([driverCoords, pickupCoords], {
                    color: 'red', weight: 3, dashArray: '5,10'
                }).addTo(map);
            }
        }

        const routePickupDrop = await getRoute(pickupCoords, dropoffCoords);
        if (routePickupDrop) {
            routeLayer2 = L.geoJSON(routePickupDrop, {
                color: 'blue', weight: 5, opacity: 0.7
            }).addTo(map);
        } else {
            routeLayer2 = L.polyline([pickupCoords, dropoffCoords], {
                color: 'blue', weight: 3, dashArray: '5,10'
            }).addTo(map);
        }

        let group = new L.FeatureGroup([pickupMarker, dropoffMarker]);
        if (driverMarker) group.addLayer(driverMarker);
        if (routeLayer1) group.addLayer(routeLayer1);
        if (routeLayer2) group.addLayer(routeLayer2);

        map.fitBounds(group.getBounds(), {padding: [40, 40]});
    }

    function updateDriverLocation(position) {
        driverCoords = [position.coords.latitude, position.coords.longitude];
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `update_lat=${driverCoords[0]}&update_lon=${driverCoords[1]}`
        }).then(res => res.json())
        .catch(err => console.error('Failed to update location:', err));
    }

    if (navigator.geolocation) {
        navigator.geolocation.watchPosition(updateDriverLocation, err => {
            console.warn('Geolocation error:', err.message);
        }, {
            enableHighAccuracy: true,
            maximumAge: 0,
            timeout: 10000
        });
    } else {
        console.warn('Geolocation not supported by browser');
    }

    document.querySelectorAll('.btn-show-route').forEach(btn => {
        btn.addEventListener('click', () => {
            const pickupLat = btn.getAttribute('data-pickup-lat');
            const pickupLng = btn.getAttribute('data-pickup-lng');
            const dropoffLat = btn.getAttribute('data-dropoff-lat');
            const dropoffLng = btn.getAttribute('data-dropoff-lng');
            showRoute(pickupLat, pickupLng, dropoffLat, dropoffLng);
        });
    });

    <?php if ($showCancellationPopup): ?>
        alert("One or more of your bookings have been cancelled by the user.");
    <?php endif; ?>
});
</script>
</body>
</html>
