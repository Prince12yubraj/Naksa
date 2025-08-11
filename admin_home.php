<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #3f37c9;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --light-gray: #e9ecef;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 240px 1fr;
            grid-template-rows: 60px 1fr;
            min-height: 100vh;
        }

        /* Header Styles */
        .header {
            grid-column: 2 / 3;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
            z-index: 10;
        }

        .header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
        }

        .logout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: var(--primary-dark);
        }

        /* Sidebar Styles */
        .sidebar {
            grid-row: 1 / 3;
            background: white;
            box-shadow: 1px 0 3px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .sidebar-brand {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .sidebar-brand h2 {
            color: var(--primary);
            font-size: 1.25rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: var(--gray);
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--light);
            color: var(--primary);
        }

        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main {
            padding: 2rem;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .card-desc {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .card-link {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.2s;
        }

        .card-link:hover {
            background: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                grid-row: auto;
                display: none; /* Consider mobile menu toggle */
            }
            
            .header {
                grid-column: 1 / 2;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>Admin Panel</h2>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="manage_drivers.php" class="nav-link active">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Drivers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="approvedriver.php" class="nav-link">
                        <i class="fas fa-user-check"></i>
                        <span>Approve Drivers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="managebooking.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Manage Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="adddrivers.php" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Add Drivers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="view_drivers.php" class="nav-link">
                        <i class="fas fa-user"></i>
                        <span>View Drivers</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="bookingreport.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span>Booking Reports</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Header -->
        <header class="header">
            <h1>Dashboard Overview</h1>
            <button class="logout-btn" onclick="window.location.href='logout.php'">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </header>

        <!-- Main Content -->
        <main class="main">
            <div class="cards">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="card-title">Manage Drivers</h3>
                    <p class="card-desc">View, edit, and delete existing driver accounts</p>
                    <a href="manage_drivers.php" class="card-link">Go to Page</a>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <h3 class="card-title">Approve Drivers</h3>
                    <p class="card-desc">Review and approve new driver applications</p>
                    <a href="approvedriver.php" class="card-link">Go to Page</a>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="card-title">View Drivers</h3>
                    <p class="card-desc">View all currently available bikes</p>
                    <a href="view_drivers.php" class="card-link">Go to Page</a>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="card-title">Add New Driver</h3>
                    <p class="card-desc">Register a new driver to the system</p>
                    <a href="adddrivers.php" class="card-link">Go to Page</a>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Manage Users</h3>
                    <p class="card-desc">View and manage customer accounts</p>
                    <a href="manageuser.php" class="card-link">Go to Page</a>
                </div>

                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="card-title">Reports</h3>
                    <p class="card-desc">View system analytics and reports</p>
                    <a href="bookingreport.php" class="card-link">Go to Page</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>