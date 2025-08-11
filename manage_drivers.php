<?php
session_start();
include('db.php');

// Handle status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $driver_id = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($action == 'approve') {
        $con->query("UPDATE drivers SET status = 'Approved' WHERE id = $driver_id");
    } elseif ($action == 'reject') {
        $con->query("UPDATE drivers SET status = 'Rejected' WHERE id = $driver_id");
    } elseif ($action == 'delete') {
        // Delete driver from DB and optionally delete photos from server
        // Fetch photos to delete files
        $driver = $con->query("SELECT driver_photo, license_photo FROM drivers WHERE id = $driver_id")->fetch_assoc();
        if ($driver) {
            if (!empty($driver['driver_photo'])) {
                $driverPhotoPath = __DIR__ . '/../uploads/drivers/' . $driver['driver_photo'];
                if (file_exists($driverPhotoPath)) unlink($driverPhotoPath);
            }
            if (!empty($driver['license_photo'])) {
                $licensePhotoPath = __DIR__ . '/../uploads/licenses/' . $driver['license_photo'];
                if (file_exists($licensePhotoPath)) unlink($licensePhotoPath);
            }
        }
        $con->query("DELETE FROM drivers WHERE id = $driver_id");
    }

    header("Location: manage_drivers.php");
    exit();
}

// Fetch all drivers
$drivers = $con->query("SELECT * FROM drivers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Manage Drivers - Naksa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #3498db;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --gray-color: #95a5a6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .page-title {
            color: var(--dark-color);
            margin: 0;
            font-size: 28px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        tr:hover {
            background-color: #f1f5f9;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-approved {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
        }
        
        .status-pending {
            background-color: rgba(243, 156, 18, 0.2);
            color: var(--warning-color);
        }
        
        .status-rejected {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }
        
        .action-btn {
            padding: 8px 12px;
            margin: 2px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
            color: white;
        }
        
        .approve-btn {
            background-color: var(--success-color);
        }
        
        .approve-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .reject-btn {
            background-color: var(--danger-color);
        }
        
        .reject-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .edit-btn {
            background-color: var(--primary-color);
        }
        
        .edit-btn:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .delete-btn {
            background-color: var(--danger-color);
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }
        
        .driver-photo, .license-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #eee;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: var(--gray-color);
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            th, td {
                padding: 10px;
            }
        }
    </style>
    <script>
        function confirmDelete(driverId) {
            if (confirm("Are you sure you want to delete this driver? This action cannot be undone.")) {
                window.location.href = '?action=delete&id=' + driverId;
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="page-title">Manage Drivers</h1>
            <a href="admin_home.php" class="btn btn-primary">
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
                                if (!empty($driverPhoto) && file_exists($driverPhotoPath)): ?>
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
                                if (!empty($licensePhoto) && file_exists($licensePhotoPath)): ?>
                                    <img class="license-photo" src="<?php echo htmlspecialchars($licensePhotoUrl); ?>" alt="License Photo" />
                                <?php else: ?>
                                    No photo
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($driver['vehicle_name'] . " " . $driver['vehicle_model'] . " (" . $driver['vehicle_reg'] . ")"); ?></td>
                            <td>
                                <?php 
                                $status = strtolower($driver['status']);
                                if ($status == 'approved') {
                                    echo '<span class="status status-approved">Approved</span>';
                                } elseif ($status == 'pending') {
                                    echo '<span class="status status-pending">Pending</span>';
                                } elseif ($status == 'rejected') {
                                    echo '<span class="status status-rejected">Rejected</span>';
                                } else {
                                    echo htmlspecialchars(ucfirst($driver['status']));
                                }
                                ?>
                            </td>
                            <td><?php echo $driver['is_available'] ? '<span style="color:var(--success-color);">Online</span>' : '<span style="color:var(--danger-color);">Offline</span>'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($driver['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($status != 'approved'): ?>
                                        <a class="action-btn approve-btn" href="?action=approve&id=<?php echo $driver['id']; ?>">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($status != 'rejected'): ?>
                                        <a class="action-btn reject-btn" href="?action=reject&id=<?php echo $driver['id']; ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </a>
                                    <?php endif; ?>
                                    <a class="action-btn edit-btn" href="edit_driver.php?id=<?php echo $driver['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $driver['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-slash" style="font-size: 50px; margin-bottom: 20px;"></i>
                <h3>No Drivers Found</h3>
                <p>There are currently no drivers registered in the system.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>