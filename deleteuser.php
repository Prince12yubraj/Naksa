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

// Optionally check if user exists before deleting
$sql_check = "SELECT id FROM users WHERE id = ?";
$stmt_check = $con->prepare($sql_check);
$stmt_check->bind_param("i", $user_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: manageuser.php");
    exit;
}

// Delete user
$sql_delete = "DELETE FROM users WHERE id = ?";
$stmt_delete = $con->prepare($sql_delete);
$stmt_delete->bind_param("i", $user_id);

if ($stmt_delete->execute()) {
    $_SESSION['success_message'] = "User deleted successfully.";
} else {
    $_SESSION['error_message'] = "Failed to delete user: " . $con->error;
}

header("Location: manageuser.php");
exit;
