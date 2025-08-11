<?php
session_start();
include('db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: manage_drivers.php');
    exit();
}

$driver_id = (int)$_GET['id'];

// Fetch existing driver data
$driver = $con->query("SELECT * FROM drivers WHERE id = $driver_id")->fetch_assoc();
if (!$driver) {
    echo "Driver not found.";
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $vehicle_name = trim($_POST['vehicle_name']);
    $vehicle_model = trim($_POST['vehicle_model']);
    $vehicle_reg = trim($_POST['vehicle_reg']);
    $status = $_POST['status'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Simple validation
    if (empty($name)) $errors[] = "Name is required.";
    if (empty($phone)) $errors[] = "Phone is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

    if (empty($errors)) {
        // Update DB (exclude photo updates here for simplicity)
        $stmt = $con->prepare("UPDATE drivers SET name=?, phone=?, email=?, vehicle_name=?, vehicle_model=?, vehicle_reg=?, status=?, is_available=? WHERE id=?");
        $stmt->bind_param("sssssssii", $name, $phone, $email, $vehicle_name, $vehicle_model, $vehicle_reg, $status, $is_available, $driver_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Driver updated successfully!";
            header("Location: manage_drivers.php");
            exit();
        } else {
            $errors[] = "Database update failed: " . $con->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Driver - Naksa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: var(--dark-color);
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        h1, h2 {
            color: var(--primary-color);
            margin: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn i {
            margin-right: 8px;
        }

        form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .checkbox-group input {
            margin-right: 10px;
            width: auto;
        }

        .btn-submit {
            background-color: var(--success-color);
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background-color: #3aa8d8;
            transform: translateY(-2px);
        }

        .error {
            color: var(--danger-color);
            background-color: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .error ul {
            margin: 0;
            padding-left: 20px;
        }

        .photo-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit Driver (ID: <?php echo $driver_id; ?>)</h1>
            <a href="admin_home.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach($errors as $e) echo "<li>" . htmlspecialchars($e) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="name"><i class="fas fa-user"></i> Name</label>
                <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($driver['name']); ?>" required />
            </div>

            <div class="form-group">
                <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($driver['phone']); ?>" required />
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($driver['email']); ?>" required />
            </div>

            <div class="form-group">
                <label for="vehicle_name"><i class="fas fa-car"></i> Vehicle Name</label>
                <input type="text" name="vehicle_name" id="vehicle_name" value="<?php echo htmlspecialchars($driver['vehicle_name']); ?>" />
            </div>

            <div class="form-group">
                <label for="vehicle_model"><i class="fas fa-car-side"></i> Vehicle Model</label>
                <input type="text" name="vehicle_model" id="vehicle_model" value="<?php echo htmlspecialchars($driver['vehicle_model']); ?>" />
            </div>

            <div class="form-group">
                <label for="vehicle_reg"><i class="fas fa-id-card"></i> Vehicle Registration</label>
                <input type="text" name="vehicle_reg" id="vehicle_reg" value="<?php echo htmlspecialchars($driver['vehicle_reg']); ?>" />
            </div>

            <div class="form-group">
                <label for="status"><i class="fas fa-info-circle"></i> Status</label>
                <select name="status" id="status">
                    <?php
                    $statuses = ['Pending', 'Approved', 'Rejected'];
                    foreach ($statuses as $s) {
                        $sel = (strtolower($driver['status']) == strtolower($s)) ? 'selected' : '';
                        echo "<option value=\"$s\" $sel>$s</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="is_available" id="is_available" <?php echo ($driver['is_available'] ? 'checked' : ''); ?> />
                <label for="is_available"><i class="fas fa-toggle-on"></i> Available (Online)</label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Update Driver
            </button>
        </form>
    </div>
</body>
</html>