<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$query = "SELECT t.*, u.email, 
                 (SELECT COUNT(*) FROM reviews r WHERE r.id_teknisi = t.id_teknisi) as total_reviews 
          FROM teknisi t 
          LEFT JOIN users u ON t.id_user = u.id_user 
          ORDER BY t.id_teknisi DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$teknisi = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $teknisi[] = [
        'id' => $row['id_teknisi'],
        'user_id' => $row['id_user'],
        'name' => $row['nama_teknisi'],
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'shop_name' => $row['deskripsi'],
        'address' => $row['address'] ?? '',
        'location_area' => $row['lokasi'],
        'specialties' => [$row['kategori']],
        'price_estimate' => (int)$row['harga_mulai'],
        'is_premium' => (bool)$row['is_premium'],
        'is_available' => ($row['status'] == 'Tersedia'),
        'photo_url' => $row['photo_url'] ?? '',
        'rating' => (float)$row['rating'],
        'total_reviews' => (int)($row['total_reviews'] ?? 0)
    ];
}

echo json_encode(['success' => true, 'teknisi' => $teknisi]);
?>