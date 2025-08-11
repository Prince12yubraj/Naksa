<?php
session_start();
include('db.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($email) && !empty($password) && filter_var($email, FILTER_VALIDATE_EMAIL)) {

        // Check if email already exists
        $check_query = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($con, $check_query);
        if (mysqli_num_rows($result) > 0) {
            echo "<script type='text/javascript'>alert('Email is already registered!')</script>";
        } else {
            $query = "INSERT INTO users(first_name,last_name,address,phone,email,password) VALUES('$first_name','$last_name','$address','$phone','$email','$password')";
            mysqli_query($con, $query);
            echo "<script type='text/javascript'>alert('Successfully Registered')</script>";
            // Redirect to login page
            header('Location: login.php');
            exit();
        }
    } else {
        echo "<script type='text/javascript'>alert('Please enter some valid information!')</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up</title>
    <style type="text/css">
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .signup {
            background-color: #ffffff;
            width: 100%;
            max-width: 400px;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .signup h1 {
            color: #2d3748;
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.75rem;
            text-align: center;
        }

        .signup label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
        }

        .signup input[type="text"],
        .signup input[type="email"],
        .signup input[type="password"],
        .signup input[type="tel"] {
            width: 100%;
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .signup input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15);
        }

        .signup button {
            width: 100%;
            padding: 0.75rem;
            background-color: #48bb78;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 0.5rem;
        }

        .signup button:hover {
            background-color: #38a169;
        }

        .signup p {
            color: #718096;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.95rem;
        }

        .signup p a {
            color: #48bb78;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .signup p a:hover {
            color: #38a169;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class= "signup">
        <form method="post">
            <h1>Signup</h1>
            <label>First Name: <input type="text" name="first_name" placeholder="Enter your first name" required></label><br><br>
            <label>Last Name: <input type="text" name="last_name" placeholder="Enter your last name" required></label><br><br>
            <label>Address: <input type="text" name="address" placeholder="Enter your address" required></label><br><br>
            <label>Phone Number: <input type="tel" name="phone" placeholder="Enter your phone number" required></label><br><br>
            <label>Email: <input type="email" name="email" placeholder="Enter your email" required></label><br><br>
            <label>Password: <input type="password" name="password" placeholder="Enter your password" required></label><br><br>
            <button>Signup</button> <br><br>
        </form>
        <p>Already have an account? <a href="login.php">Login Here</a></p>
    </div>
</body>
</html>