<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['booking_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$query = "UPDATE bookings SET status = :status WHERE booking_id = :booking_id";
$stmt = $db->prepare($query);
$result = $stmt->execute([
    ':status' => $data['status'],
    ':booking_id' => $data['booking_id']
]);

if ($result) {
    try {
        $userQuery = "SELECT user_id FROM bookings WHERE booking_id = :booking_id";
        $userStmt = $db->prepare($userQuery);
        $userStmt->execute([':booking_id' => $data['booking_id']]);
        $bookingInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
        if ($bookingInfo && !empty($bookingInfo['user_id'])) {
            $user_id = $bookingInfo['user_id'];
            
            $fp = @fsockopen('127.0.0.1', 8081, $errno, $errstr, 2);
            if ($fp) {
                $payload = [
                    'type' => 'status_update',
                    'user_id' => $user_id,
                    'booking_id' => $data['booking_id'],
                    'status' => $data['status'],
                    'notes' => $notes
                ];
                fwrite($fp, json_encode($payload));
                fclose($fp);
            }
        }
    } catch (Exception $e) {
        // Silently fail if WebSocket server is down
    }
}

echo json_encode(['success' => $result]);
?>
