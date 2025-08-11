<?php
session_start();
include('db.php');  // Your DB connection file which defines $con

// Validation functions
function validateName($name) {
    return preg_match("/^[a-zA-Z ]{2,50}$/", $name);
}

function validatePhone($phone) {
    return preg_match("/^[0-9]{7,15}$/", $phone);
}

// Nepal Driving License format: exactly NN-NN-NNNNNNNN (mandatory hyphens)
function validateLicense($license) {
    return preg_match("/^\d{2}-\d{2}-\d{8}$/", $license);
}

// Nepal Vehicle Registration format: e.g. BA 1 PA 1234 or BA1PA1234
function validateVehicleRegistration($reg) {
    return preg_match("/^[A-Z]{2}\s?\d{1,2}\s?[A-Z]{2}\s?\d{1,4}$/i", $reg);
}

// Vehicle name: letters, digits, spaces, hyphens allowed, 1-50 chars
function validateVehicleName($name) {
    return preg_match("/^[a-zA-Z0-9\- ]{1,50}$/", $name);
}

// Vehicle model: must be a year between 1980 and next year
function validateVehicleModel($model) {
    $year = intval($model);
    $currentYear = intval(date("Y")) + 1;
    return ($year >= 1980 && $year <= $currentYear);
}

// Directories for uploaded files
$licenseUploadDir = 'uploads/licenses/';
$photoUploadDir = 'uploads/drivers/';

// Create directories if not exist
if (!is_dir($licenseUploadDir)) mkdir($licenseUploadDir, 0755, true);
if (!is_dir($photoUploadDir)) mkdir($photoUploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $license = trim($_POST['license']);
    $vehicle_name = trim($_POST['vehicle_name']);
    $vehicle_model = trim($_POST['vehicle_model']);
    $vehicle_reg = strtoupper(str_replace(' ', '', $_POST['vehicle_reg']));

    $errors = [];

    if (!validateName($name)) $errors[] = "Invalid name format.";
    if (!validatePhone($phone)) $errors[] = "Invalid phone number.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if (!validateLicense($license)) $errors[] = "Invalid Nepal driving license number format. Example: 01-06-01407407";
    if (!validateVehicleName($vehicle_name)) $errors[] = "Invalid vehicle name.";
    if (!validateVehicleModel($vehicle_model)) $errors[] = "Invalid vehicle model year. Use year between 1980 and next year.";
    if (!validateVehicleRegistration($vehicle_reg)) $errors[] = "Invalid Nepal vehicle registration number format. Example: BA1PA1234";

    $licensePhoto = $_FILES['license_photo'] ?? null;
    $driverPhoto = $_FILES['driver_photo'] ?? null;

    if (!$licensePhoto || $licensePhoto['error'] !== UPLOAD_ERR_OK) $errors[] = "License photo is required.";
    if (!$driverPhoto || $driverPhoto['error'] !== UPLOAD_ERR_OK) $errors[] = "Driver photo is required.";

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    if ($licensePhoto && !in_array($licensePhoto['type'], $allowedTypes)) $errors[] = "Invalid license photo type.";
    if ($driverPhoto && !in_array($driverPhoto['type'], $allowedTypes)) $errors[] = "Invalid driver photo type.";

    if ($licensePhoto && $licensePhoto['size'] > 2 * 1024 * 1024) $errors[] = "License photo must be less than 2MB.";
    if ($driverPhoto && $driverPhoto['size'] > 2 * 1024 * 1024) $errors[] = "Driver photo must be less than 2MB.";

    if (count($errors) > 0) {
        echo "<script>alert('" . implode("\\n", array_map('addslashes', $errors)) . "');</script>";
    } else {
        $email_safe = mysqli_real_escape_string($con, $email);
        $license_safe = mysqli_real_escape_string($con, $license);

        $check_query = "SELECT * FROM drivers WHERE email='$email_safe' OR license='$license_safe'";
        $result = mysqli_query($con, $check_query);

        if (mysqli_num_rows($result) > 0) {
            echo "<script>alert('Email or License number already registered!');</script>";
        } else {
            $licenseExt = pathinfo($licensePhoto['name'], PATHINFO_EXTENSION);
            $driverExt = pathinfo($driverPhoto['name'], PATHINFO_EXTENSION);

            $licenseFileName = uniqid('license_') . '.' . strtolower($licenseExt);
            $driverFileName = uniqid('driver_') . '.' . strtolower($driverExt);

            $licensePath = $licenseUploadDir . $licenseFileName;
            $driverPath = $photoUploadDir . $driverFileName;

            if (!move_uploaded_file($licensePhoto['tmp_name'], $licensePath)) {
                echo "<script>alert('Failed to upload license photo.');</script>";
                exit;
            }

            if (!move_uploaded_file($driverPhoto['tmp_name'], $driverPath)) {
                echo "<script>alert('Failed to upload driver photo.');</script>";
                exit;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = 'active';
            $availability = 'offline';
            $is_available = 0;

            $name_safe = mysqli_real_escape_string($con, $name);
            $phone_safe = mysqli_real_escape_string($con, $phone);
            $vehicle_name_safe = mysqli_real_escape_string($con, $vehicle_name);
            $vehicle_model_safe = mysqli_real_escape_string($con, $vehicle_model);
            $vehicle_reg_safe = mysqli_real_escape_string($con, $vehicle_reg);

            $query = "INSERT INTO drivers 
                (name, phone, email, password, license, vehicle_name, vehicle_model, vehicle_reg, license_photo, driver_photo, status, availability, is_available) 
                VALUES 
                ('$name_safe', '$phone_safe', '$email_safe', '$hashed_password', '$license_safe', '$vehicle_name_safe', '$vehicle_model_safe', '$vehicle_reg_safe', '$licenseFileName', '$driverFileName', '$status', '$availability', $is_available)";

            if (mysqli_query($con, $query)) {
                echo "<script>alert('Driver Registered Successfully'); window.location.href='driver_login.php';</script>";
                exit;
            } else {
                echo "<script>alert('Database error: " . addslashes(mysqli_error($con)) . "');</script>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Driver Registration</title>
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
.signup {
    background-color: white;
    width: 100%;
    max-width: 28rem;
    padding: 2rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}
.signup h1 {
    text-align: center;
    color: var(--primary);
    font-size: 1.75rem;
    margin-bottom: 1.5rem;
    font-weight: 700;
}
label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-dark);
}
input[type="text"],
input[type="email"],
input[type="password"],
input[type="tel"],
input[type="file"] {
    width: 100%;
    padding: 0.75rem;
    margin-bottom: 1rem;
    border: 1px solid var(--gray-medium);
    border-radius: var(--radius);
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
input[type="file"] {
    padding: 0.5rem;
    background-color: var(--gray-light);
}
button {
    width: 100%;
    background-color: var(--success);
    color: white;
    font-weight: 600;
    padding: 0.75rem;
    margin-top: 0.5rem;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 1rem;
    transition: background-color 0.2s;
}
button:hover {
    background-color: var(--success-hover);
}
.login-link {
    text-align: center;
    margin-top: 1.5rem;
    color: var(--gray-dark);
    font-size: 0.95rem;
}
.login-link a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
}
.login-link a:hover {
    text-decoration: underline;
}
@media (max-width: 640px) {
    .signup {
        padding: 1.5rem;
    }
    .signup h1 {
        font-size: 1.5rem;
        margin-bottom: 1.25rem;
    }
}
</style>
</head>
<body>
<div class="signup">
    <form method="POST" enctype="multipart/form-data">
        <h1>Driver Registration</h1>
        
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Yubraj Shrestha" required>
        
        <label for="phone">Phone Number</label>
        <input type="tel" id="phone" name="phone" placeholder="9876543210" required>
        
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="yubraj@example.com" required>
        
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
        
        <label for="license">Nepal Driving License Number</label>
        <input type="text" id="license" name="license" placeholder="NN-NN-NNNNNNNN" required>
        
        <label for="license_photo">License Photo</label>
        <input type="file" id="license_photo" name="license_photo" accept="image/*" required>
        
        <label for="driver_photo">Driver Photo</label>
        <input type="file" id="driver_photo" name="driver_photo" accept="image/*" required>
        
        <label for="vehicle_name">Vehicle Name</label>
        <input type="text" id="vehicle_name" name="vehicle_name" placeholder="Toyota Innova" required>
        
        <label for="vehicle_model">Vehicle Model Year</label>
        <input type="text" id="vehicle_model" name="vehicle_model" placeholder="2020" required>
        
        <label for="vehicle_reg">Nepal Vehicle Registration Number</label>
        <input type="text" id="vehicle_reg" name="vehicle_reg" placeholder="BA1PA1234" required>
        
        <button type="submit">Register</button>
    </form>
    
    <p class="login-link">Already registered? <a href="driver_login.php">Login Here</a></p>
</div>
</body>
</html>
