<?php
session_start();
include('../db.php');

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle delete booking
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $con->prepare("DELETE FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    header("Location: managebooking.php");
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
    <title>Manage Bookings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; }
        th { background: #0072ff; color: #fff; }
        .btn-danger {
            background-color: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-danger:hover {
            background-color: #bd2130;
        }
        .route-link {
            color: #007bff;
            text-decoration: none;
        }
        .route-link:hover {
            text-decoration: underline;
        }
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
    <h2><i class="fas fa-tasks"></i> Manage Bookings</h2>
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
                <th>Action</th>
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
                <td>
                    <a class="btn-danger" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this booking?');">
                        <i class="fas fa-trash-alt"></i> Delete
                    </a>
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
