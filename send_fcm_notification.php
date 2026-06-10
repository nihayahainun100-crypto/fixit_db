<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config/db.php';
require_once 'fcm_helper.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['technician_email']) || empty($data['title']) || empty($data['body'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Ambil FCM token teknisi
$stmt = $db->prepare(
    "SELECT u.fcm_token FROM users u 
     JOIN teknisi t ON t.id_user = u.id_user 
     WHERE u.email = :email LIMIT 1"
);
$stmt->execute([':email' => $data['technician_email']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['fcm_token'])) {
    echo json_encode(['success' => false, 'message' => 'FCM token teknisi tidak tersedia']);
    exit;
}

$result = sendFcmNotification(
    $row['fcm_token'],
    $data['title'],
    $data['body'],
    $data['extra_data'] ?? []
);

echo json_encode($result);
?>
