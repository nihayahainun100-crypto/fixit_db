<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/db.php';
require_once '../fcm_helper.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    // 1. Simpan booking
    $stmt = $db->prepare("INSERT INTO bookings 
        (booking_id, user_id, technician_id, technician_name, service_type,
         base_price, service_fee, total_price, scheduled_date,
         customer_name, customer_phone, notes, status)
        VALUES 
        (:booking_id, :user_id, :technician_id, :technician_name, :service_type,
         :base_price, :service_fee, :total_price, :scheduled_date,
         :customer_name, :customer_phone, :notes, :status)");

    $stmt->execute([
        ':booking_id'      => $data['booking_id'],
        ':user_id'         => $data['user_id'],
        ':technician_id'   => $data['technician_id'],
        ':technician_name' => $data['technician_name'],
        ':service_type'    => $data['service_type'],
        ':base_price'      => $data['base_price'],
        ':service_fee'     => $data['service_fee'],
        ':total_price'     => $data['total_price'],
        ':scheduled_date'  => $data['scheduled_date'],
        ':customer_name'   => $data['customer_name'],
        ':customer_phone'  => $data['customer_phone'],
        ':notes'           => $data['notes'],
        ':status'          => $data['status'],
    ]);

    $stmtToken = $db->prepare(
        "SELECT u.fcm_token FROM users u 
         JOIN teknisi t ON t.id_user = u.id_user 
         WHERE u.email = :email LIMIT 1"
    );
    $stmtToken->execute([':email' => $data['technician_id']]);
    $tokenRow = $stmtToken->fetch(PDO::FETCH_ASSOC);

    $fcmResult = ['success' => false, 'message' => 'FCM token tidak tersedia'];

    if ($tokenRow && !empty($tokenRow['fcm_token'])) {
        $customerName  = $data['customer_name'] ?? 'Pelanggan';
        $serviceType   = $data['service_type'] ?? 'Servis';
        $scheduledDate = isset($data['scheduled_date'])
            ? date('d/m/Y', strtotime($data['scheduled_date']))
            : '';

        $fcmResult = sendFcmNotification(
            $tokenRow['fcm_token'],
            '🔔 Booking Baru Masuk!',
            "$customerName memesan $serviceType. Jadwal: $scheduledDate",
            [
                'type'       => 'new_booking',
                'booking_id' => $data['booking_id'],
                'customer'   => $customerName,
                'service'    => $serviceType,
            ]
        );
    }

    echo json_encode([
        'success'      => true,
        'message'      => 'Booking saved',
        'fcm_sent'     => $fcmResult['success'],
        'fcm_message'  => $fcmResult['message'],
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}
?>
