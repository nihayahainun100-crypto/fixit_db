<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$status = ($data['is_available'] == true) ? 'Tersedia' : 'Tidak Tersedia';
$is_premium = ($data['is_premium'] == true) ? 1 : 0;
$photo_url = $data['photo_url'] ?? '';
$pengalaman = $data['experience'] ?? '';
$phone = $data['phone'] ?? '';
$address = $data['address'] ?? '';

$query = "UPDATE teknisi SET 
            nama_teknisi = :nama_teknisi,
            phone = :phone,
            kategori = :kategori,
            lokasi = :lokasi,
            pengalaman = :pengalaman,
            harga_mulai = :harga_mulai,
            is_premium = :is_premium,
            status = :status,
            deskripsi = :deskripsi,
            address = :address,
            photo_url = :photo_url
          WHERE id_teknisi = :id";

$stmt = $db->prepare($query);

$stmt->bindParam(':id', $data['id']);
$stmt->bindParam(':nama_teknisi', $data['name']);
$stmt->bindParam(':phone', $phone);
$stmt->bindParam(':kategori', $data['specialties']);
$stmt->bindParam(':lokasi', $data['location_area']);
$stmt->bindParam(':pengalaman', $pengalaman);
$stmt->bindParam(':harga_mulai', $data['price_estimate']);
$stmt->bindParam(':is_premium', $is_premium);
$stmt->bindParam(':status', $status);
$stmt->bindParam(':deskripsi', $data['shop_name']);
$stmt->bindParam(':address', $address);
$stmt->bindParam(':photo_url', $photo_url);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Teknisi berhasil diupdate']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate teknisi']);
}
?>