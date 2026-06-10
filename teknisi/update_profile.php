<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Email wajib diisi']);
    exit;
}

$stmtFind = $db->prepare(
    "SELECT t.id_teknisi FROM teknisi t JOIN users u ON t.id_user = u.id_user WHERE u.email = :email LIMIT 1"
);
$stmtFind->execute([':email' => $data['email']]);
$row = $stmtFind->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Teknisi tidak ditemukan']);
    exit;
}

$id_teknisi = $row['id_teknisi'];

$fields = [
    'name'           => 'nama_teknisi',
    'phone'          => 'phone',
    'shop_name'      => 'deskripsi',
    'address'        => 'address',
    'location_area'  => 'lokasi',
    'experience'     => 'pengalaman',
    'price_estimate' => 'harga_mulai',
    'photo_url'      => 'photo_url',
];

$setClauses = [];
$params = [':id_teknisi' => $id_teknisi];

foreach ($fields as $jsonKey => $dbCol) {
    if (array_key_exists($jsonKey, $data)) {
        $setClauses[] = "$dbCol = :$jsonKey";
        $params[":$jsonKey"] = $data[$jsonKey];
    }
}

if (array_key_exists('is_available', $data)) {
    $setClauses[] = "status = :status";
    $params[':status'] = $data['is_available'] ? 'Tersedia' : 'Tidak Tersedia';
}
if (array_key_exists('is_premium', $data)) {
    $setClauses[] = "is_premium = :is_premium";
    $params[':is_premium'] = $data['is_premium'] ? 1 : 0;
}
if (!empty($data['specialties'])) {
    $setClauses[] = "kategori = :kategori";
    $params[':kategori'] = is_array($data['specialties'])
        ? $data['specialties'][0]
        : $data['specialties'];
}

if (empty($setClauses)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada data untuk diupdate']);
    exit;
}

$sql = "UPDATE teknisi SET " . implode(', ', $setClauses) . " WHERE id_teknisi = :id_teknisi";
$stmt = $db->prepare($sql);

if ($stmt->execute($params)) {
    $stmtGet = $db->prepare(
        "SELECT t.*, u.email,
                (SELECT COUNT(*) FROM reviews r WHERE r.id_teknisi = t.id_teknisi) as total_reviews
         FROM teknisi t JOIN users u ON t.id_user = u.id_user
         WHERE t.id_teknisi = :id_teknisi"
    );
    $stmtGet->execute([':id_teknisi' => $id_teknisi]);
    $updated = $stmtGet->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Profil berhasil diupdate',
        'teknisi' => [
            'id'             => (int)$updated['id_teknisi'],
            'user_id'        => (int)$updated['id_user'],
            'name'           => $updated['nama_teknisi'],
            'email'          => $updated['email'],
            'phone'          => $updated['phone'] ?? '',
            'shop_name'      => $updated['deskripsi'] ?? '',
            'address'        => $updated['address'] ?? '',
            'location_area'  => $updated['lokasi'] ?? '',
            'specialties'    => [$updated['kategori'] ?? ''],
            'price_estimate' => (int)($updated['harga_mulai'] ?? 0),
            'is_premium'     => (bool)$updated['is_premium'],
            'is_available'   => ($updated['status'] == 'Tersedia'),
            'photo_url'      => $updated['photo_url'] ?? '',
            'rating'         => (float)($updated['rating'] ?? 0),
            'total_reviews'  => (int)($updated['total_reviews'] ?? 0),
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate profil']);
}
?>
