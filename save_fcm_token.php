<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['email']) || empty($data['fcm_token'])) {
    echo json_encode(['success' => false, 'message' => 'email dan fcm_token wajib diisi']);
    exit;
}

try {

    $stmt = $db->prepare("UPDATE users SET fcm_token = :fcm_token WHERE email = :email");
    $stmt->execute([
        ':fcm_token' => $data['fcm_token'],
        ':email'     => $data['email'],
    ]);

    echo json_encode(['success' => true, 'message' => 'FCM token disimpan']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
