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

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$queryCekUser = "SELECT id_user FROM users LIMIT 1";
$stmtCek = $db->prepare($queryCekUser);
$stmtCek->execute();
$userRow = $stmtCek->fetch(PDO::FETCH_ASSOC);

if (!$userRow) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada user di database']);
    exit;
}

$id_user = $userRow['id_user']; 

$nama_teknisi = isset($data['name']) ? trim($data['name']) : '';
$email        = isset($data['email']) ? trim($data['email']) : '';
$kategori     = isset($data['specialties']) ? trim($data['specialties']) : 'General';
$lokasi       = isset($data['location_area']) ? trim($data['location_area']) : 'Indramayu Kota';
$pengalaman   = isset($data['experience']) ? trim($data['experience']) : '';
$harga_mulai  = isset($data['price_estimate']) ? (int)$data['price_estimate'] : 0;
$deskripsi    = isset($data['shop_name']) ? trim($data['shop_name']) : '';
$is_premium   = isset($data['is_premium']) ? ($data['is_premium'] == true ? 1 : 0) : 0;
$status       = isset($data['is_available']) ? ($data['is_available'] == true ? 'Tersedia' : 'Tidak Tersedia') : 'Tersedia';
$photo_url    = isset($data['photo_url']) ? trim($data['photo_url']) : '';
$phone        = isset($data['phone']) ? trim($data['phone']) : '';
$address      = isset($data['address']) ? trim($data['address']) : '';

$id_user = null;
if (!empty($email)) {
    $stmtFindUser = $db->prepare("SELECT id_user FROM users WHERE email = :email LIMIT 1");
    $stmtFindUser->bindParam(':email', $email);
    $stmtFindUser->execute();
    $foundUser = $stmtFindUser->fetch(PDO::FETCH_ASSOC);

    if ($foundUser) {
        $id_user = $foundUser['id_user'];
    } else {
        $tempPassword = password_hash('teknisi123', PASSWORD_DEFAULT);
        $stmtInsertUser = $db->prepare(
            "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'technician')"
        );
        $stmtInsertUser->bindParam(':name', $nama_teknisi);
        $stmtInsertUser->bindParam(':email', $email);
        $stmtInsertUser->bindParam(':password', $tempPassword);
        $stmtInsertUser->execute();
        $id_user = $db->lastInsertId();
    }
}

if (!$id_user) {
    $stmtFirst = $db->prepare("SELECT id_user FROM users LIMIT 1");
    $stmtFirst->execute();
    $firstUser = $stmtFirst->fetch(PDO::FETCH_ASSOC);
    $id_user = $firstUser ? $firstUser['id_user'] : 1;
}

if (empty($nama_teknisi)) {
    echo json_encode(['success' => false, 'message' => 'Nama teknisi wajib diisi']);
    exit;
}

if ($harga_mulai <= 0) {
    echo json_encode(['success' => false, 'message' => 'Harga harus lebih dari 0']);
    exit;
}

$query = "INSERT INTO teknisi (id_user, nama_teknisi, phone, kategori, lokasi, pengalaman, harga_mulai, is_premium, status, deskripsi, address, photo_url) 
          VALUES (:id_user, :nama_teknisi, :phone, :kategori, :lokasi, :pengalaman, :harga_mulai, :is_premium, :status, :deskripsi, :address, :photo_url)";

$stmt = $db->prepare($query);

$stmt->bindParam(':id_user', $id_user);
$stmt->bindParam(':nama_teknisi', $nama_teknisi);
$stmt->bindParam(':phone', $phone);
$stmt->bindParam(':kategori', $kategori);
$stmt->bindParam(':lokasi', $lokasi);
$stmt->bindParam(':pengalaman', $pengalaman);
$stmt->bindParam(':harga_mulai', $harga_mulai);
$stmt->bindParam(':is_premium', $is_premium);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':deskripsi', $deskripsi);
$stmt->bindParam(':address', $address);
$stmt->bindParam(':photo_url', $photo_url);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Teknisi berhasil ditambahkan', 
        'id' => $db->lastInsertId()
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal menambahkan teknisi'
    ]);
}
?>