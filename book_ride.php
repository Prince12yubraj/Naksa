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

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

    <!-- Style -->
    <style>
        :root {
            --primary: #0072ff;
            --secondary: #28a745;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fa;
            color: #333;
        }

        /* Navigation Styles */
        nav {
            background: var(--primary);
            display: flex;
            justify-content: center;
            padding: 15px;
            flex-wrap: wrap;
        }
        
        nav a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin: 0 5px;
            border-radius: 4px;
            transition: background 0.3s;
            display: flex;
            align-items: center;
        }
        
        nav a:hover {
            background: rgba(255,255,255,0.2);
        }
        
        nav a i {
            margin-right: 8px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 15px;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: var(--border-radius);
            margin-top: 20px;
            box-shadow: var(--box-shadow);
        }

        .search-section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            box-shadow: var(--box-shadow);
        }

        .geocoder-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .geocoder-container {
            flex: 1;
            min-width: 250px;
        }

        .leaflet-control-geocoder {
            width: 100% !important;
            border-radius: var(--border-radius) !important;
            box-shadow: none !important;
            border: 1px solid #e9ecef !important;
        }

        .leaflet-control-geocoder-form input {
            padding: 10px 15px !important;
            font-family: inherit !important;
        }

        .booking-form {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            margin: 20px 0;
            box-shadow: var(--box-shadow);
            display: none;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .form-value {
            padding: 10px;
            background: var(--light);
            border-radius: 4px;
            font-size: 15px;
        }

        .price-display {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            text-align: center;
            margin: 20px 0;
        }

        button {
            background: var(--secondary);
            color: white;
            padding: 12px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        @media (max-width: 768px) {
            nav {
                flex-direction: column;
                align-items: center;
            }
            
            nav a {
                margin: 5px 0;
                width: 100%;
                text-align: center;
                justify-content: center;
            }
            
            .geocoder-wrapper {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<nav>
    <a href="admin/admin_login.php" class="admin-link"><i class="fas fa-user-shield"></i> Admin</a>
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

<div class="container">
    <!-- Search Section -->
    <div class="search-section">
        <div class="geocoder-wrapper">
            <div class="geocoder-container">
                <div id="pickupSearch"></div>
            </div>
            <div class="geocoder-container">
                <div id="dropSearch"></div>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div id="map"></div>

    <!-- Ride Summary & Form -->
    <form method="POST" action="booking.php" id="rideBookingForm" class="booking-form">
        <div class="form-group">
            <label class="form-label">Pickup Location</label>
            <div class="form-value" id="pickupDisplay">-</div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Dropoff Location</label>
            <div class="form-value" id="dropoffDisplay">-</div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Distance</label>
            <div class="form-value" id="distanceDisplay">-</div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Estimated Time</label>
            <div class="form-value" id="durationDisplay">-</div>
        </div>
        
        <div class="price-display" id="priceDisplay">Rs 0.00</div>

        <input type="hidden" name="pickup_location" id="pickup_location" required />
        <input type="hidden" name="pickup_lat" id="pickup_lat" required />
        <input type="hidden" name="pickup_lng" id="pickup_lng" required />
        <input type="hidden" name="dropoff_location" id="dropoff_location" required />
        <input type="hidden" name="dropoff_lat" id="dropoff_lat" required />
        <input type="hidden" name="dropoff_lng" id="dropoff_lng" required />
        <input type="hidden" name="distance_km" id="distance_km" />
        <input type="hidden" name="duration_sec" id="duration_sec" />
        <input type="hidden" name="price" id="price" />

        <button type="submit">Book Ride</button>
    </form>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

<script>
// Initialize Map
const map = L.map('map').setView([27.7172, 85.3240], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// App State
let pickupMarker = null;
let dropoffMarker = null;
let routeControl = null;

// Custom Icons
const pickupIcon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/447/447031.png',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

const dropoffIcon = L.icon({
    iconUrl: 'https://cdn-icons-png.flaticon.com/512/447/447035.png',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

// Helper Functions
async function reverseGeocode(latlng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}`);
        const data = await response.json();
        return data.display_name || `${latlng.lat.toFixed(4)}, ${latlng.lng.toFixed(4)}`;
    } catch (error) {
        console.error('Geocoding error:', error);
        return `${latlng.lat.toFixed(4)}, ${latlng.lng.toFixed(4)}`;
    }
}

function setPickup(latlng, address) {
    if (!pickupMarker) {
        pickupMarker = L.marker(latlng, { 
            draggable: true, 
            icon: pickupIcon,
            zIndexOffset: 1000
        }).addTo(map);
        
        pickupMarker.bindPopup("<b>Pickup Location</b><br>" + address, {
            offset: [0, -32],
            className: 'map-popup'
        }).openPopup();
        
        pickupMarker.on('dragend', async () => {
            const pos = pickupMarker.getLatLng();
            const addr = await reverseGeocode(pos);
            setPickup(pos, addr);
            updateRoute();
        });
    } else {
        pickupMarker.setLatLng(latlng)
            .setPopupContent("<b>Pickup Location</b><br>" + address)
            .openPopup();
    }
    
    document.getElementById("pickup_location").value = address;
    document.getElementById("pickup_lat").value = latlng.lat;
    document.getElementById("pickup_lng").value = latlng.lng;
    document.getElementById("pickupDisplay").textContent = address;
    
    if (dropoffMarker) {
        updateRoute();
    }
}

function setDropoff(latlng, address) {
    if (!dropoffMarker) {
        dropoffMarker = L.marker(latlng, { 
            draggable: true, 
            icon: dropoffIcon,
            zIndexOffset: 1000
        }).addTo(map);
        
        dropoffMarker.bindPopup("<b>Dropoff Location</b><br>" + address, {
            offset: [0, -32],
            className: 'map-popup'
        }).openPopup();
        
        dropoffMarker.on('dragend', async () => {
            const pos = dropoffMarker.getLatLng();
            const addr = await reverseGeocode(pos);
            setDropoff(pos, addr);
            updateRoute();
        });
    } else {
        dropoffMarker.setLatLng(latlng)
            .setPopupContent("<b>Dropoff Location</b><br>" + address)
            .openPopup();
    }
    
    document.getElementById("dropoff_location").value = address;
    document.getElementById("dropoff_lat").value = latlng.lat;
    document.getElementById("dropoff_lng").value = latlng.lng;
    document.getElementById("dropoffDisplay").textContent = address;
    
    if (pickupMarker) {
        updateRoute();
    }
}

function updateRoute() {
    if (pickupMarker && dropoffMarker) {
        const waypoints = [pickupMarker.getLatLng(), dropoffMarker.getLatLng()];
        
        if (routeControl) {
            map.removeControl(routeControl);
        }
        
        routeControl = L.Routing.control({
            waypoints,
            routeWhileDragging: false,
            draggableWaypoints: false,
            show: false,
            addWaypoints: false,
            fitSelectedRoutes: 'smart',
            createMarker: () => null,
            lineOptions: {
                styles: [{color: '#0072ff', opacity: 0.7, weight: 5}]
            }
        }).addTo(map);
        
        routeControl.on('routesfound', e => {
            const r = e.routes[0];
            const dist = r.summary.totalDistance / 1000;
            const time = r.summary.totalTime / 60;
            const price = (dist * 15).toFixed(2);

            document.getElementById("distanceDisplay").textContent = dist.toFixed(2) + " km";
            document.getElementById("durationDisplay").textContent = time.toFixed(0) + " minutes";
            document.getElementById("priceDisplay").textContent = "Rs " + price;

            document.getElementById("distance_km").value = dist.toFixed(2);
            document.getElementById("duration_sec").value = r.summary.totalTime;
            document.getElementById("price").value = price;

            document.getElementById("rideBookingForm").style.display = "block";
            
            // Fit bounds with padding
            const bounds = L.latLngBounds(waypoints);
            map.fitBounds(bounds, {padding: [50, 50]});
        });
    }
}

// Initialize Geocoders
const pickupGeocoder = L.Control.geocoder({ 
    placeholder: "Enter pickup address", 
    defaultMarkGeocode: false,
    geocoder: L.Control.Geocoder.nominatim({
        geocodingQueryParams: {
            countrycodes: 'np',
            bounded: 1,
            viewbox: '80.0582,26.3479,88.2019,30.4470'
        }
    })
}).on('markgeocode', async e => {
    setPickup(e.geocode.center, e.geocode.name);
    map.setView(e.geocode.center, 15);
});

const dropoffGeocoder = L.Control.geocoder({ 
    placeholder: "Enter dropoff address", 
    defaultMarkGeocode: false,
    geocoder: L.Control.Geocoder.nominatim({
        geocodingQueryParams: {
            countrycodes: 'np',
            bounded: 1,
            viewbox: '80.0582,26.3479,88.2019,30.4470'
        }
    })
}).on('markgeocode', async e => {
    setDropoff(e.geocode.center, e.geocode.name);
    map.setView(e.geocode.center, 15);
});

// Add geocoders to the page
document.getElementById("pickupSearch").appendChild(pickupGeocoder.onAdd(map));
document.getElementById("dropSearch").appendChild(dropoffGeocoder.onAdd(map));

// Style the geocoder inputs
document.querySelectorAll('.leaflet-control-geocoder-form input').forEach(input => {
    input.style.paddingLeft = '35px';
    input.style.height = '40px';
});

// Ask for user location
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(async pos => {
        const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
        const addr = await reverseGeocode(latlng);
        setPickup(latlng, addr);
        map.setView(latlng, 15);
    });
}
</script>

</body>
</html>