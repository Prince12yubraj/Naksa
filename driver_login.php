<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        echo "<script>alert('Please enter both email and password.')</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Please enter a valid email address.')</script>";
    } else {
        // Check if driver exists
        $query = "SELECT * FROM drivers WHERE email = '$email'";
        $result = mysqli_query($con, $query);

        if (mysqli_num_rows($result) == 1) {
            $driver = mysqli_fetch_assoc($result);

            // Verify password
            if (password_verify($password, $driver['password'])) {
                // Store driver info in session
                $_SESSION['driver_id'] = $driver['id'];
                $_SESSION['driver_name'] = $driver['name'];
                $_SESSION['driver_email'] = $driver['email'];

                // Redirect to driver dashboard or home page
                header("Location: driver_dashboard.php");
                exit();
            } else {
                echo "<script>alert('Incorrect password.')</script>";
            }
        } else {
            echo "<script>alert('No account found with this email.')</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Driver Login</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --success-hover: #059669;
            --gray-light: #f3f4f6;
            --gray-medium: #e5e7eb;
            --gray-dark: #6b7280;
            --text-dark: #1f2937;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --radius: 0.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #f8fafc;
            color: var(--text-dark);
            line-height: 1.5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .login-container {
            background-color: white;
            width: 100%;
            max-width: 24rem;
            padding: 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-form input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid var(--gray-medium);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .login-form input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .login-form button {
            width: 100%;
            background-color: var(--success);
            color: white;
            font-weight: 600;
            padding: 0.75rem;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
            margin-top: 0.5rem;
        }

        .login-form button:hover {
            background-color: var(--success-hover);
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray-dark);
            font-size: 0.95rem;
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .login-container {
                padding: 1.5rem;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <h1>Driver Login</h1>
        <p>Welcome back! Please sign in to continue</p>
    </div>
    
    <form class="login-form" method="POST" action="">
        <input type="email" name="email" placeholder="Enter your email" required>
        <input type="password" name="password" placeholder="Enter your password" required>
        <button type="submit">Login</button>
    </form>
    
    <div class="login-footer">
        Don't have an account? <a href="register_driver.php">Register Here</a>
    </div>
</div>
</body>
</html>