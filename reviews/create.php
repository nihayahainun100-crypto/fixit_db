<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['booking_id'], $data['technician_id'], $data['user_id'], $data['rating'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

try {
    $stmtBooking = $db->prepare("SELECT id_booking FROM bookings WHERE booking_id = :booking_id LIMIT 1");
    $stmtBooking->execute([':booking_id' => $data['booking_id']]);
    $bookingRow = $stmtBooking->fetch(PDO::FETCH_ASSOC);
    if (!$bookingRow) {
        echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan']);
        exit;
    }
    $id_booking = $bookingRow['id_booking'];

    $email    = $data['user_id'];
    $name     = $data['user_name'] ?? 'Customer';
    $stmtUser = $db->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
    $stmtUser->execute([':email' => $email]);
    $userRow  = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($userRow) {
        $id_user = $userRow['id_user'];
    } else {
        $stmtIns = $db->prepare("INSERT INTO users (name, email, role) VALUES (:name, :email, 'customer')");
        $stmtIns->execute([':name' => $name, ':email' => $email]);
        $id_user = $db->lastInsertId();
    }

    $stmtT = $db->prepare(
        "SELECT t.id_teknisi FROM teknisi t JOIN users u ON t.id_user = u.id_user WHERE u.email = :email LIMIT 1"
    );
    $stmtT->execute([':email' => $data['technician_id']]);
    $teknisiRow = $stmtT->fetch(PDO::FETCH_ASSOC);
    if (!$teknisiRow) {
        echo json_encode(['success' => false, 'message' => 'Teknisi tidak ditemukan']);
        exit;
    }
    $id_teknisi = $teknisiRow['id_teknisi'];

    $rating   = (int)round($data['rating']);
    $komentar = $data['comment'] ?? '';
    $stmtIns  = $db->prepare(
        "INSERT INTO reviews (id_booking, id_user, id_teknisi, rating, komentar) VALUES (:id_booking, :id_user, :id_teknisi, :rating, :komentar)"
    );
    $stmtIns->execute([
        ':id_booking'  => $id_booking,
        ':id_user'     => $id_user,
        ':id_teknisi'  => $id_teknisi,
        ':rating'      => $rating,
        ':komentar'    => $komentar,
    ]);

    $stmtUpd = $db->prepare("UPDATE bookings SET is_rated = 1 WHERE booking_id = :booking_id");
    $stmtUpd->execute([':booking_id' => $data['booking_id']]);

    $stmtCalc = $db->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE id_teknisi = :id_teknisi");
    $stmtCalc->execute([':id_teknisi' => $id_teknisi]);
    $calcRow   = $stmtCalc->fetch(PDO::FETCH_ASSOC);
    $avgRating = $calcRow['avg_rating'] !== null ? round((float)$calcRow['avg_rating'], 1) : 0.0;

    $stmtUpdT = $db->prepare("UPDATE teknisi SET rating = :rating WHERE id_teknisi = :id_teknisi");
    $stmtUpdT->execute([':rating' => $avgRating, ':id_teknisi' => $id_teknisi]);

    echo json_encode([
        'success' => true,
        'message' => 'Review berhasil dikirim',
        'rating'  => $avgRating,
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
