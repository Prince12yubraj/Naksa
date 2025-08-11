<?php
include('db.php');

if (!isset($_GET['type']) || !isset($_GET['id'])) {
    exit('Invalid request');
}

$type = $_GET['type']; // license_photo or driver_photo
$id = (int)$_GET['id'];

if ($type !== 'license_photo' && $type !== 'driver_photo') {
    exit('Invalid image type');
}

$query = "SELECT $type FROM drivers WHERE id = $id LIMIT 1";
$result = mysqli_query($con, $query);

if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_assoc($result);
    $imageData = $row[$type];

    // Try to detect image mime type (optional)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($imageData);
    if (!in_array($mime, ['image/jpeg','image/png','image/gif'])) {
        $mime = 'image/jpeg'; // fallback
    }

    header("Content-Type: $mime");
    echo $imageData;
    exit;
} else {
    exit('Image not found');
}
