<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$targetDir = __DIR__ . "/";

if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $fileName = time() . '_' . basename($_FILES['photo']['name']);
    $targetFile = $targetDir . $fileName;
    
    if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFile)) {
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $photoUrl = "http://$host/fixit_api/uploads/$fileName";
        echo json_encode(['success' => true, 'photo_url' => $photoUrl]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Upload error']);
}
?>