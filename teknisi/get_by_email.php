<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$email = $_GET['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$query = "SELECT t.*, u.email,
                 (SELECT COUNT(*) FROM reviews r WHERE r.id_teknisi = t.id_teknisi) as total_reviews
          FROM teknisi t
          JOIN users u ON t.id_user = u.id_user
          WHERE u.email = :email
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute([':email' => $email]);
$teknisi = $stmt->fetch(PDO::FETCH_ASSOC);

if ($teknisi) {
    echo json_encode([
        'success'    => true,
        'is_teknisi' => true,
        'teknisi'    => [
            'id'             => (int)$teknisi['id_teknisi'],
            'user_id'        => (int)$teknisi['id_user'],
            'name'           => $teknisi['nama_teknisi'],
            'email'          => $teknisi['email'],
            'phone'          => $teknisi['phone'] ?? '',
            'shop_name'      => $teknisi['deskripsi'] ?? '',
            'address'        => $teknisi['address'] ?? '',
            'location_area'  => $teknisi['lokasi'] ?? '',
            'specialties'    => [$teknisi['kategori'] ?? ''],
            'price_estimate' => (int)($teknisi['harga_mulai'] ?? 0),
            'is_premium'     => (bool)$teknisi['is_premium'],
            'is_available'   => ($teknisi['status'] == 'Tersedia'),
            'photo_url'      => $teknisi['photo_url'] ?? '',
            'rating'         => (float)($teknisi['rating'] ?? 0),
            'total_reviews'  => (int)($teknisi['total_reviews'] ?? 0),
        ]
    ]);
} else {
    $query2 = "SELECT role FROM users WHERE email = :email";
    $stmt2 = $db->prepare($query2);
    $stmt2->execute([':email' => $email]);
    $user = $stmt2->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'    => true,
        'is_teknisi' => false,
        'role'       => $user ? $user['role'] : 'customer'
    ]);
}
?>
