<?php
session_start();
include('db.php');

// Fetch all drivers
$drivers = $con->query("SELECT * FROM drivers ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Drivers - Naksa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 0.9em;
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        
        thead tr {
            background-color: var(--primary);
            color: white;
            text-align: center;
            font-weight: 600;
        }
        
        th, td {
            padding: 15px;
            text-align: center;
            vertical-align: middle;
        }
        
        tbody tr {
            border-bottom: 1px solid #eee;
            transition: all 0.2s;
        }
        
        tbody tr:nth-of-type(even) {
            background-color: #f8fafc;
        }
        
        tbody tr:last-of-type {
            border-bottom: 2px solid var(--primary);
        }
        
        tbody tr:hover {
            background-color: #f1f5f9;
            transform: scale(1.005);
        }
        
        .driver-photo, .license-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: var(--box-shadow);
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8em;
            text-transform: capitalize;
        }
        
        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .online {
            color: #10b981;
            font-weight: 600;
        }
        
        .offline {
            color: #ef4444;
            font-weight: 600;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: var(--gray);
        }
        
        .no-data i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #cbd5e1;
        }
        
        .no-data p {
            font-size: 1.1em;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 0.8em;
            }
            
            .driver-photo, .license-photo {
                width: 50px;
                height: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Booking Report
        </a>
        
        <h1><i class="fas fa-users"></i> Registered Drivers</h1>

        <?php if ($drivers->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Photo</th>
                        <th>License</th>
                        <th>Vehicle</th>
                        <th>Status</th>
                        <th>Availability</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($driver = $drivers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $driver['id']; ?></td>
                            <td><?php echo htmlspecialchars($driver['name']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($driver['phone']); ?></div>
                                <div style="font-size:0.8em;color:var(--gray)"><?php echo htmlspecialchars($driver['email']); ?></div>
                            </td>
                            <td>
                                <?php
                                $driverPhoto = trim($driver['driver_photo']);
                                $driverPhotoPath = __DIR__ . '/../uploads/drivers/' . $driverPhoto;
                                $driverPhotoUrl = '/naksa/uploads/drivers/' . $driverPhoto;
                                if (!empty($driverPhoto) && file_exists($driverPhotoPath)): ?>
                                    <img class="driver-photo" src="<?php echo htmlspecialchars($driverPhotoUrl); ?>" alt="Driver Photo">
                                <?php else: ?>
                                    <div class="status status-pending">No Photo</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $licensePhoto = trim($driver['license_photo']);
                                $licensePhotoPath = __DIR__ . '/../uploads/licenses/' . $licensePhoto;
                                $licensePhotoUrl = '/naksa/uploads/licenses/' . $licensePhoto;
                                if (!empty($licensePhoto) && file_exists($licensePhotoPath)): ?>
                                    <img class="license-photo" src="<?php echo htmlspecialchars($licensePhotoUrl); ?>" alt="License Photo">
                                <?php else: ?>
                                    <div class="status status-pending">No Photo</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($driver['vehicle_name']); ?></strong>
                                <div><?php echo htmlspecialchars($driver['vehicle_model']); ?></div>
                                <div style="font-size:0.8em"><?php echo htmlspecialchars($driver['vehicle_reg']); ?></div>
                            </td>
                            <td>
                                <?php
                                $status = strtolower($driver['status']);
                                if ($status == 'approved') {
                                    echo '<span class="status status-approved"><i class="fas fa-check-circle"></i> Approved</span>';
                                } elseif ($status == 'pending') {
                                    echo '<span class="status status-pending"><i class="fas fa-clock"></i> Pending</span>';
                                } elseif ($status == 'rejected') {
                                    echo '<span class="status status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
                                } else {
                                    echo htmlspecialchars(ucfirst($status));
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo $driver['is_available'] ? 
                                    '<span class="online"><i class="fas fa-circle"></i> Online</span>' : 
                                    '<span class="offline"><i class="fas fa-circle"></i> Offline</span>'; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($driver['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fas fa-user-times"></i>
                <p>No drivers found in the database</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>