<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $license = $_POST['license'];
    $vehicle_name = $_POST['vehicle_name'];
    $vehicle_model = $_POST['vehicle_model'];
    $vehicle_reg = $_POST['vehicle_reg'];
    $status = $_POST['status'];
    $is_available = $_POST['is_available'];

    $stmt = $con->prepare("UPDATE drivers SET name=?, phone=?, email=?, license=?, vehicle_name=?, vehicle_model=?, vehicle_reg=?, status=?, is_available=? WHERE id=?");
    $stmt->bind_param("ssssssssii", $name, $phone, $email, $license, $vehicle_name, $vehicle_model, $vehicle_reg, $status, $is_available, $id);

    if ($stmt->execute()) {
        header("Location: manage_drivers.php?success=1");
    } else {
        echo "Update failed: " . $stmt->error;
    }
}
?>
