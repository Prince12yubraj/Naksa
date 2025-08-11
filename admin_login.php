<?php
session_start();

// Check if the admin is already logged in
if(isset($_SESSION['admin_id'])) {
    header("Location: admin_home.php"); // Redirect to dashboard if already logged in
    exit;
}

require("db.php");

// Check if the login form is submitted
if(isset($_POST['login'])){
    $admin_name = $_POST['admin_name'];
    $admin_password = $_POST['admin_password'];

    // SQL injection prevention: use prepared statements
    $query = "SELECT * FROM `admin` WHERE `admin_name` = ? AND `admin_password` = ?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $admin_name, $admin_password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if login is successful
    if(mysqli_num_rows($result) == 1) {
        session_start();
        $_SESSION['admin_id'] = $admin_name;
        header('location: admin_home.php');
        exit();
    } else {
        $error_message = "Incorrect username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #0072ff 0%, #00c6ff 100%);
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-form {
    background-color: rgba(255, 255, 255, 0.95);
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    width: 320px;
    text-align: center;
    transition: transform 0.3s ease;
}

.login-form:hover {
    transform: translateY(-5px);
}

h2 {
    margin-bottom: 25px;
    font-weight: 700;
    color: #0072ff;
    letter-spacing: 1px;
}

input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 18px;
    border: 1.8px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

input[type="text"]:focus,
input[type="password"]:focus {
    outline: none;
    border-color: #0072ff;
    box-shadow: 0 0 6px #0072ffaa;
}

button {
    width: 100%;
    padding: 12px 0;
    border: none;
    border-radius: 8px;
    background: #0072ff;
    color: white;
    font-size: 17px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 5px 12px #0072ffcc;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

button:hover {
    background-color: #005bbb;
    box-shadow: 0 6px 15px #005bbbcc;
}

.error-message {
    margin-top: 15px;
    color: #e74c3c;
    font-weight: 600;
    letter-spacing: 0.03em;
}

    </style>
</head>
<body>
    <div class="login-form">
        <h2>Admin Login Panel</h2>
        <form method="post">
            <label for="admin_name">Admin Name:</label><br>
            <input type="text" id="admin_name" name="admin_name" placeholder="Admin Name"><br>

            <label for="admin_password">Password:</label><br>
            <input type="password" id="admin_password" name="admin_password" placeholder="Password"><br>

            <button type="submit" name="login">Login</button>
        </form>

        <?php if(isset($error_message)) { ?>
            <p class="error-message"><?php echo $error_message; ?></p>
        <?php } ?>
    </div>
</body>
</html>
