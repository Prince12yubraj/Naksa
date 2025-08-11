<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include('db.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: managedriver.php");
    exit;
}

$driver_id = (int)$_GET['id'];

// First get driver files to delete
$stmt = $con->prepare("SELECT driver_photo, license_photo FROM drivers WHERE id = ?");
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows !== 1) {
    $stmt->close();
    $con->close();
    header("Location: managedriver.php");
    exit;
}
$driver = $result->fetch_assoc();
$stmt->close();

$photo_path = __DIR__ . '/uploads/photos/' . $driver['driver_photo'];
$license_path = __DIR__ . '/uploads/licenses/' . $driver['license_photo'];

// Delete driver record from DB
$del_stmt = $con->prepare("DELETE FROM drivers WHERE id = ?");
$del_stmt->bind_param("i", $driver_id);

if ($del_stmt->execute()) {
    // Delete files if exist
    if ($driver['driver_photo'] && file_exists($photo_path)) {
        unlink($photo_path);
    }
    if ($driver['license_photo'] && file_exists($license_path)) {
        unlink($license_path);
    }
}

$del_stmt->close();
$con->close();

header("Location: managedriver.php");
exit;
?>
