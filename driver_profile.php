<?php
session_start();
include('db.php'); // Your DB connection ($con)

$photoUploadDir = 'uploads/photos/';
$licenseUploadDir = 'uploads/licenses/';

// Validate driver_id from GET
if (!isset($_GET['driver_id']) || !is_numeric($_GET['driver_id'])) {
    die("Invalid driver ID.");
}

$driverId = (int)$_GET['driver_id'];

// Fetch driver data from DB safely using prepared statement
$stmt = $con->prepare("SELECT * FROM drivers WHERE driver_id = ? LIMIT 1");
$stmt->bind_param("i", $driverId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Driver not found.");
}

$driver = $result->fetch_assoc();
$stmt->close();

// Helper function for safe HTML output
function safeOutput($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Driver Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .profile-container {
            background: white;
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; margin-bottom: 20px; }
        .photo-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-bottom: 20px;
        }
        .photo-container img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .info { margin-bottom: 10px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>

<div class="profile-container">
    <h1>Driver Profile</h1>
    
    <div class="photo-container">
        <?php if (!empty($driver['driver_photo']) && file_exists($photoUploadDir . $driver['driver_photo'])): ?>
            <div>
                <div class="label">Driver Photo</div>
                <img src="<?php echo safeOutput($photoUploadDir . $driver['driver_photo']); ?>" alt="Driver Photo">
            </div>
        <?php else: ?>
            <div>No Driver Photo Available</div>
        <?php endif; ?>
        
        <?php if (!empty($driver['license_photo']) && file_exists($licenseUploadDir . $driver['license_photo'])): ?>
            <div>
                <div class="label">License Photo</div>
                <img src="<?php echo safeOutput($licenseUploadDir . $driver['license_photo']); ?>" alt="License Photo">
            </div>
        <?php else: ?>
            <div>No License Photo Available</div>
        <?php endif; ?>
    </div>
    
    <div class="info"><span class="label">Name:</span> <?php echo safeOutput($driver['name']); ?></div>
    <div class="info"><span class="label">Phone:</span> <?php echo safeOutput($driver['phone']); ?></div>
    <div class="info"><span class="label">Email:</span> <?php echo safeOutput($driver['email']); ?></div>
    <div class="info"><span class="label">License Number:</span> <?php echo safeOutput($driver['license']); ?></div>
    <div class="info"><span class="label">Vehicle Name:</span> <?php echo safeOutput($driver['vehicle_name']); ?></div>
    <div class="info"><span class="label">Vehicle Model:</span> <?php echo safeOutput($driver['vehicle_model']); ?></div>
    <div class="info"><span class="label">Vehicle Registration:</span> <?php echo safeOutput($driver['vehicle_reg']); ?></div>
    <div class="info"><span class="label">Status:</span> <?php echo safeOutput($driver['status']); ?></div>
    <div class="info"><span class="label">Availability:</span> <?php echo safeOutput($driver['availability']); ?></div>
</div>

</body>
</html>
