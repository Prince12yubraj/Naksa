<?php
session_start();
$pageTitle = "Naksa - Book Your Ride";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.css" />
    <style>
        :root {
            --primary-color: #0072ff;
            --secondary-color: #28a745;
            --dark-bg: #333;
            --light-bg: #f8f8f8;
            --text-color: #333;
            --text-light: #777;
        }
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        nav {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-shrink: 0;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
        }
        nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        #map {
            flex-grow: 1;
            height: 600px;
            width: 90vw;
            max-width: 700px;
            margin: 10px auto 0 auto;
            border-radius: 10px;
            border: 1px solid #ccc;
            position: relative;
        }
        .search-container {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.15);
            padding: 6px 10px;
        }
        .search-container input.leaflet-control-geocoder-form-input {
            width: 100% !important;
            font-size: 1.1rem;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }
        .leaflet-control-geocoder {
            margin: 0 !important;
            float: none !important;
        }
        form#rideBookingForm {
            max-width: 700px;
            margin: 15px auto 30px auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            display: none;
        }
        form#rideBookingForm label {
            display: block;
            font-weight: 600;
            margin-top: 10px;
        }
        form#rideBookingForm input, form#rideBookingForm select, form#rideBookingForm textarea {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            margin-top: 6px;
        }
        form#rideBookingForm button {
            margin-top: 15px;
            background-color: var(--secondary-color);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
        }
        form#rideBookingForm p {
            margin: 6px 0 0 0;
            font-weight: 600;
        }
        footer {
            background-color: var(--dark-bg);
            color: white;
            text-align: center;
            padding: 15px 10px;
            font-size: 14px;
            flex-shrink: 0;
        }
        #locationMessage {
            max-width: 700px;
            margin: 10px auto;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 8px;
            color: #856404;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>

<nav>
    <a href="booking_status.php"><i class="fas fa-list"></i> Booking Status</a>
    <a href="home.php"><i class="fas fa-home"></i> Home</a>
    <a href="book_ride.php"><i class="fas fa-car"></i> Book Now</a>
    <a href="booking_report.php"><i class="fas fa-list"></i> My Bookings</a>
    <a href="register_driver.php"><i class="fas fa-id-card"></i> Drivers</a>
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    <?php else: ?>
        <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
    <?php endif; ?>
</nav>

<div id="locationMessage">Please allow location access so we can set your pickup location.</div>

<div id="map">
    <div class="search-container"></div>
</div>

<form id="rideBookingForm" method="POST" action="booking.php">
    <input type="hidden" name="pickup_location" id="pickup_location" required />
    <input type="hidden" name="dropoff_location" id="dropoff_location" required />

    <label for="driver_id">Select Driver:</label>
    <select name="driver_id" id="driver_id" required>
        <option value="">-- Select Driver --</option>
        <?php
        include('db.php');
        $driversForForm = $con->query("SELECT id, name, vehicle_name, vehicle_model, vehicle_reg FROM drivers WHERE is_available = 1 AND status = 'approved' ORDER BY name");
        while ($d = $driversForForm->fetch_assoc()):
        ?>
            <option value="<?php echo (int)$d['id']; ?>">
                <?php echo htmlspecialchars($d['name'] . " - " . $d['vehicle_name'] . " " . $d['vehicle_model'] . " (" . $d['vehicle_reg'] . ")"); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Distance:</label>
    <p id="distanceDisplay">-</p>

    <label>Estimated Time:</label>
    <p id="durationDisplay">-</p>

    <label>Price (Rs):</label>
    <p id="priceDisplay">-</p>

    <input type="hidden" name="distance_km" id="distance_km" />
    <input type="hidden" name="duration_sec" id="duration_sec" />
    <input type="hidden" name="price" id="price" />

    <button type="submit">Book Now</button>
</form>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
<script>
    const map = L.map('map').setView([27.7172, 85.3240], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const container = document.querySelector('.search-container');

    const geocoderControl = L.Control.geocoder({
        defaultMarkGeocode: false,
        placeholder: "Where do you want to go? (Dropoff location)"
    }).addTo(map);

    container.appendChild(geocoderControl.getContainer());

    const pickupInput = document.getElementById('pickup_location');
    const dropoffInput = document.getElementById('dropoff_location');
    const bookingForm = document.getElementById('rideBookingForm');
    const locationMessage = document.getElementById('locationMessage');

    let pickupMarker = null;
    let dropoffMarker = null;
    let routingControl = null;

    const pickupIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/190/190411.png',
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -35],
    });
    const dropoffIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/484/484167.png',
        iconSize: [30, 40],
        iconAnchor: [15, 40],
        popupAnchor: [0, -35],
    });

    async function reverseGeocode(latlng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}`;
        try {
            const res = await fetch(url);
            const data = await res.json();
            return data.display_name || `${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}`;
        } catch {
            return `${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}`;
        }
    }

    function setPickup(latlng, address) {
        if (pickupMarker) {
            pickupMarker.setLatLng(latlng).getPopup().setContent("Pickup: " + address);
        } else {
            pickupMarker = L.marker(latlng, { draggable:true, icon: pickupIcon })
                .addTo(map)
                .bindPopup("Pickup: " + address)
                .openPopup();

            pickupMarker.on('dragend', async () => {
                const pos = pickupMarker.getLatLng();
                const addr = await reverseGeocode(pos);
                pickupMarker.getPopup().setContent("Pickup: " + addr).openPopup();
                pickupInput.value = addr;
                updateRoute();
            });
        }
        pickupInput.value = address;
        map.setView(latlng, 15);
        updateRoute();
    }

    function setDropoff(latlng, address) {
        if (dropoffMarker) {
            dropoffMarker.setLatLng(latlng).getPopup().setContent("Dropoff: " + address);
        } else {
            dropoffMarker = L.marker(latlng, { draggable:true, icon: dropoffIcon })
                .addTo(map)
                .bindPopup("Dropoff: " + address)
                .openPopup();

            dropoffMarker.on('dragend', async () => {
                const pos = dropoffMarker.getLatLng();
                const addr = await reverseGeocode(pos);
                dropoffMarker.getPopup().setContent("Dropoff: " + addr).openPopup();
                dropoffInput.value = addr;
                updateRoute();
            });
        }
        dropoffInput.value = address;
        map.setView(latlng, 15);
        updateRoute();
    }

    function updateRoute() {
        if (pickupMarker && dropoffMarker) {
            const waypoints = [
                pickupMarker.getLatLng(),
                dropoffMarker.getLatLng()
            ];
            if (routingControl) {
                routingControl.setWaypoints(waypoints);
            } else {
                routingControl = L.Routing.control({
                    waypoints: waypoints,
                    router: L.Routing.osrmv1({
                        serviceUrl: 'https://router.project-osrm.org/route/v1'
                    }),
                    fitSelectedRoutes: true,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    routeWhileDragging: false,
                    createMarker: () => null
                }).addTo(map);

                routingControl.on('routesfound', function(e) {
                    const route = e.routes[0];
                    const distanceMeters = route.summary.totalDistance; // meters
                    const durationSeconds = route.summary.totalTime; // seconds

                    const distanceKm = (distanceMeters / 1000).toFixed(2);
                    const durationMin = Math.ceil(durationSeconds / 60);

                    const price = (distanceKm * 15).toFixed(2);

                    document.getElementById('distanceDisplay').textContent = `${distanceKm} km`;
                    document.getElementById('durationDisplay').textContent = `${durationMin} min`;
                    document.getElementById('priceDisplay').textContent = `Rs ${price}`;

                    document.getElementById('distance_km').value = distanceKm;
                    document.getElementById('duration_sec').value = durationSeconds.toFixed(0);
                    document.getElementById('price').value = price;
                });
            }
            bookingForm.style.display = 'block';
        }
    }

    function askForLocation() {
        if (!navigator.geolocation) {
            locationMessage.textContent = "Geolocation is not supported by your browser.";
            return;
        }
        locationMessage.textContent = "Please allow location access to set your pickup location.";

        navigator.geolocation.getCurrentPosition(async (position) => {
            const userLatLng = L.latLng(position.coords.latitude, position.coords.longitude);
            const address = await reverseGeocode(userLatLng);
            setPickup(userLatLng, address);
            locationMessage.style.display = "none";
        }, (err) => {
            locationMessage.textContent = "Location access denied or unavailable. Please click on the map to set your pickup location.";
            enablePickupSelection();
        }, { timeout: 10000 });
    }

    function enablePickupSelection() {
        let pickupSet = false;

        function onMapClick(e) {
            if (!pickupSet) {
                reverseGeocode(e.latlng).then(address => {
                    setPickup(e.latlng, address);
                    pickupSet = true;
                    locationMessage.style.display = "none";
                    map.off('click', onMapClick);
                });
            }
        }

        map.on('click', onMapClick);
    }

    geocoderControl.on('markgeocode', function(e) {
        const latlng = e.geocode.center;
        const address = e.geocode.name;

        setDropoff(latlng, address);
    });

    askForLocation();
</script>

<footer>
    &copy; <?php echo date("Y"); ?> Naksa Ride Hailing. All rights reserved.
</footer>

</body>
</html>
