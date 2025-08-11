<?php
// Database configuration
$db_host = "localhost"; // Your database host
$db_username = "yubraj"; // Your database username
$db_password = "yubraj123"; // Your database password
$db_name = "naksa"; // Your database name

// Create connection
$con = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
?>
