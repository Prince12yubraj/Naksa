<?php
session_start();
include('../db.php'); // Adjust path as needed

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all bookings with user and driver info
$sql = "
    SELECT b.*, 
           u.first_name, u.last_name, 
           d.name AS driver_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN drivers d ON b.driver_id = d.id
    ORDER BY b.created_at DESC
";
$result = $con->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Booking Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* same styles as in driver report, paste your existing CSS here */
        /* For brevity, only new/important parts are listed */
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; }
        th { background: #0072ff; color: #fff; }
        .btn { text-decoration: none; padding: 8px 14px; border-radius: 5px; color: #fff; }
        .btn-danger { background: #dc3545; }
        .btn-info { background: #17a2b8; }
        .route-link { color: #007bff; text-decoration: none; }
        .route-link:hover { text-decoration: underline; }
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

<div class="container">
    <h2><i class="fas fa-chart-bar"></i> Admin Booking Report</h2>
    <a href="admin_home.php" class="back-btn">← Back to Dashboard</a>

    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Driver</th>
                <th>Pickup</th>
                <th>Dropoff</th>
                <th>Fare (Rs)</th>
                <th>Status</th>
                <th>Time</th>
                <th>Route</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                <td><?= htmlspecialchars($row['driver_name']) ?></td>
                <td><?= htmlspecialchars($row['pickup_location']) ?></td>
                <td><?= htmlspecialchars($row['dropoff_location']) ?></td>
                <td>Rs <?= number_format($row['price'], 2) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                <td>
                    <?php if (strtolower($row['status']) === 'accepted' || strtolower($row['status']) === 'completed'): ?>
                        <a class="route-link" href="map.php?booking_id=<?= $row['id'] ?>"><i class="fas fa-route"></i> View</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No bookings found.</p>
    <?php endif; ?>
</div>

</body>
</html>
