<?php
session_start();
include('db.php');

// Handle status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $driver_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        // Approve driver
        $con->query("UPDATE drivers SET status = 'Approved' WHERE id = $driver_id");
    } elseif ($action == 'reject') {
        // Reject driver, set offline and delete all bookings
        $con->query("UPDATE drivers SET status = 'Rejected', is_available = 0 WHERE id = $driver_id");
        $con->query("DELETE FROM bookings WHERE driver_id = $driver_id");
    }
    header("Location: approvedriver.php");
    exit();
}

// Fetch all drivers
$drivers = $con->query("SELECT * FROM drivers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Approve Drivers - Naksa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 28px;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        .back-btn:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 15px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        th {
            background: #3498db;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        tr:hover {
            background-color: #f1f5f9;
        }
        img.driver-photo, img.license-photo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .status-approved {
            color: #27ae60;
            font-weight: bold;
        }
        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        .action-btn {
            padding: 8px 15px;
            margin: 4px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            color: white;
            display: inline-block;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .approve-btn {
            background-color: #27ae60;
        }
        .approve-btn:hover {
            background-color: #219653;
            transform: translateY(-2px);
        }
        .reject-btn {
            background-color: #e74c3c;
        }
        .reject-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        .no-drivers {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
            color: #7f8c8d;
        }
        @media (max-width: 1000px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr { 
                margin: 0 0 20px 0; 
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: left;
                background: white;
            }
            td:before {
                position: absolute;
                top: 15px;
                left: 15px;
                width: 45%;
                white-space: nowrap;
                font-weight: bold;
                color: #3498db;
            }
            td:nth-of-type(1):before { content: "ID"; }
            td:nth-of-type(2):before { content: "Name"; }
            td:nth-of-type(3):before { content: "Phone"; }
            td:nth-of-type(4):before { content: "Email"; }
            td:nth-of-type(5):before { content: "Driver Photo"; }
            td:nth-of-type(6):before { content: "License Photo"; }
            td:nth-of-type(7):before { content: "Vehicle"; }
            td:nth-of-type(8):before { content: "Status"; }
            td:nth-of-type(9):before { content: "Availability"; }
            td:nth-of-type(10):before { content: "Registered On"; }
            td:nth-of-type(11):before { content: "Actions"; }
            img.driver-photo, img.license-photo {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Approve Drivers</h2>
    <a href="admin_home.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php if ($drivers->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Driver Photo</th>
                <th>License Photo</th>
                <th>Vehicle</th>
                <th>Status</th>
                <th>Availability</th>
                <th>Registered On</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($driver = $drivers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $driver['id']; ?></td>
                    <td><?php echo htmlspecialchars($driver['name']); ?></td>
                    <td><?php echo htmlspecialchars($driver['phone']); ?></td>
                    <td><?php echo htmlspecialchars($driver['email']); ?></td>
                    <td>
                        <?php
                        $driverPhoto = trim($driver['driver_photo']);
                        $driverPhotoPath = __DIR__ . '/../uploads/drivers/' . $driverPhoto;
                        $driverPhotoUrl = '/naksa/uploads/drivers/' . $driverPhoto;
                        if (!empty($driverPhoto) && file_exists($driverPhotoPath)):
                        ?>
                            <img class="driver-photo" src="<?php echo htmlspecialchars($driverPhotoUrl); ?>" alt="Driver Photo" />
                        <?php else: ?>
                            No photo
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $licensePhoto = trim($driver['license_photo']);
                        $licensePhotoPath = __DIR__ . '/../uploads/licenses/' . $licensePhoto;
                        $licensePhotoUrl = '/naksa/uploads/licenses/' . $licensePhoto;
                        if (!empty($licensePhoto) && file_exists($licensePhotoPath)):
                        ?>
                            <img class="license-photo" src="<?php echo htmlspecialchars($licensePhotoUrl); ?>" alt="License Photo" />
                        <?php else: ?>
                            No photo
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($driver['vehicle_name'] . " " . $driver['vehicle_model'] . " (" . $driver['vehicle_reg'] . ")"); ?>
                    </td>
                    <td>
                        <?php 
                        $status = strtolower($driver['status']);
                        if ($status == 'approved') {
                            echo '<span class="status-approved">Approved</span>';
                        } elseif ($status == 'pending') {
                            echo '<span class="status-pending">Pending</span>';
                        } elseif ($status == 'rejected') {
                            echo '<span class="status-rejected">Rejected</span>';
                        } else {
                            echo htmlspecialchars(ucfirst($driver['status']));
                        }
                        ?>
                    </td>
                    <td>
                        <?php echo $driver['is_available'] ? '<span style="color:green;">Online</span>' : '<span style="color:red;">Offline</span>'; ?>
                    </td>
                    <td><?php echo date('Y-m-d', strtotime($driver['created_at'])); ?></td>
                    <td>
                        <?php if ($status != 'approved'): ?>
                            <a class="action-btn approve-btn" href="?action=approve&id=<?php echo $driver['id']; ?>">Approve</a>
                        <?php endif; ?>
                        <?php if ($status != 'rejected'): ?>
                            <a class="action-btn reject-btn" href="?action=reject&id=<?php echo $driver['id']; ?>">Reject</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="no-drivers">
        <p>No drivers found.</p>
    </div>
<?php endif; ?>

</body>
</html>