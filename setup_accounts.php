<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$results = [];


$email = 'teknisi@gmail.com';
$stmt = $db->prepare("SELECT id_user FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

$pass = password_hash('123456', PASSWORD_DEFAULT);

if ($existing) {
    $db->prepare("UPDATE users SET password=:p, role='technician' WHERE email=:e")
       ->execute([':p' => $pass, ':e' => $email]);
    $userId = $existing['id_user'];
    $results[] = "teknisi@gmail.com UPDATED -> role=technician, password=123456";
} else {
    $db->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (:n, :e, :p, 'technician', '08123456789')")
       ->execute([':n' => 'Teknisi Utama', ':e' => $email, ':p' => $pass]);
    $userId = $db->lastInsertId();
    $results[] = "teknisi@gmail.com CREATED -> role=technician, password=123456, id=$userId";
}


$checkTek = $db->prepare("SELECT id_teknisi FROM teknisi WHERE id_user = :id LIMIT 1");
$checkTek->execute([':id' => $userId]);
if (!$checkTek->fetch()) {
    $db->prepare("INSERT INTO teknisi (id_user, nama_teknisi, kategori, lokasi, harga_mulai, status, deskripsi, phone, address, is_premium, rating) VALUES (:id, 'Teknisi Utama', 'Laptop', 'Indramayu Kota', 50000, 'Tersedia', 'Service Laptop & PC', '08123456789', 'Indramayu', 0, 0)")
       ->execute([':id' => $userId]);
    $results[] = "Data di tabel teknisi CREATED";
}


$db->prepare("UPDATE users SET password=:p WHERE email='admin@fixit.com'")
   ->execute([':p' => password_hash('admin123', PASSWORD_DEFAULT)]);
$results[] = "admin@fixit.com password updated -> admin123";


$db->prepare("UPDATE users SET password=:p WHERE password IS NULL OR password=''")->execute([':p' => password_hash('123456', PASSWORD_DEFAULT)]);
$results[] = "Password NULL users updated -> 123456";


$all = $db->query("SELECT id_user, name, email, role FROM users ORDER BY id_user")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'results' => $results,
    'login_accounts' => [
        ['email' => 'teknisi@gmail.com', 'password' => '123456', 'role' => 'technician', 'dashboard' => 'Technician Dashboard'],
        ['email' => 'admin@fixit.com',   'password' => 'admin123', 'role' => 'admin', 'dashboard' => 'Admin Dashboard'],
        ['email' => 'customer@gmail.com','password' => '123456', 'role' => 'customer', 'dashboard' => 'Customer Dashboard'],
    ],
    'all_users' => $all,
], JSON_PRETTY_PRINT);
?>
