<?php
session_start();
include('db.php');  // Your database connection file

// Check if driver is logged in
if (!isset($_SESSION['driver_id'])) {
    header("Location: driver_login.php");
    exit();
}

$driver_id = $_SESSION['driver_id'];

// Prepare SQL to fetch all bookings for this driver, joining user and driver info
$sql = "
    SELECT b.*, 
           u.first_name, u.last_name, 
           d.name AS driver_name 
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN drivers d ON b.driver_id = d.id
    WHERE b.driver_id = ?
    ORDER BY b.created_at DESC
";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bookings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --light: #f8f9fa;
            --dark: #212529;
            --border-radius: 0.375rem;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background-color: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d1144a;
            transform: translateY(-2px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: var(--box-shadow);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }
        
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        tr:hover {
            background-color: #f1f3f5;
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
            text-align: center;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .route-link {
            color: var(--info);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .route-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .no-bookings {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2><i class="fas fa-list-alt"></i> Your Bookings</h2>
    
    <div class="action-buttons">
        <a href="driver_dashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="driver_logout.php" class="btn btn-danger">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <?php if ($result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>User Name</th>
                <th>Pickup Location</th>
                <th>Dropoff Location</th>
                <th>Price (Rs)</th>
                <th>Status</th>
                <th>Booking Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($booking = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($booking['id']); ?></td>
                <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                <td><?php echo htmlspecialchars($booking['dropoff_location']); ?></td>
                <td>Rs <?php echo number_format($booking['price'], 2); ?></td>
                <td>
                    <span class="status status-<?php echo strtolower($booking['status']); ?>">
                        <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></td>
                <td>
                    <?php if (strtolower($booking['status']) === 'accepted'): ?>
                        <a href="booking_map.php?booking_id=<?php echo $booking['id']; ?>" class="route-link">
                            <i class="fas fa-route"></i> View Route
                        </a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="no-bookings">
            <i class="fas fa-calendar-times fa-2x"></i>
            <p>No bookings found</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>