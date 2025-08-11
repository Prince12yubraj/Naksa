<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include('db.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "User ID is required.";
    header("Location: manageuser.php");
    exit;
}

$user_id = intval($_GET['id']);

// Fetch existing user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manageuser.php");
    exit;
}

$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data and sanitize
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Simple validation (expand as needed)
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Update the user
        $update_sql = "UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=? WHERE id=?";
        $stmt_update = $con->prepare($update_sql);
        $stmt_update->bind_param("sssssi", $first_name, $last_name, $email, $phone, $address, $user_id);
        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "User updated successfully.";
            header("Location: manageuser.php");
            exit;
        } else {
            $error = "Error updating user: " . $con->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit User</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --success: #4cc9f0;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: var(--dark);
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        h1 {
            color: var(--primary);
            margin: 0;
            font-size: 28px;
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
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn i {
            margin-right: 8px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
            box-sizing: border-box;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn-submit {
            background-color: var(--success);
            color: white;
            padding: 12px 25px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-submit:hover {
            background-color: #3aa8d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .error {
            color: var(--danger);
            background-color: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .required:after {
            content: " *";
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-edit"></i> Edit User (ID: <?= $user_id ?>)</h1>
            <a href="admin_home.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="post" action="">
                <div class="form-group">
                    <label for="first_name" class="required">First Name</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?= htmlspecialchars($user['first_name']) ?>" required />
                </div>

                <div class="form-group">
                    <label for="last_name" class="required">Last Name</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?= htmlspecialchars($user['last_name']) ?>" required />
                </div>

                <div class="form-group">
                    <label for="email" class="required">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>" required />
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?= htmlspecialchars($user['phone']) ?>" />
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Update User
                </button>
            </form>
        </div>

        <a href="manageuser.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to User Management
        </a>
    </div>
</body>
</html>