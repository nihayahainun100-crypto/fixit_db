<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'DB gagal connect']);
    exit;
}

try {
    $checkCol = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    $colExists = $checkCol->rowCount() > 0;

    if (!$colExists) {
        $db->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'customer' AFTER email");
        echo "Kolom role ditambahkan. ";
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Cek kolom gagal: ' . $e->getMessage()]);
    exit;
}

$stmt = $db->query("SELECT id_user, name, email, role FROM users ORDER BY id_user");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$emailTeknisi = 'teknisi@gmail.com';
$updateStmt = $db->prepare("UPDATE users SET role = 'teknisi' WHERE email = :email");
$updateStmt->execute([':email' => $emailTeknisi]);
$affected = $updateStmt->rowCount();

if ($affected == 0) {
    $pass = password_hash('123456', PASSWORD_DEFAULT);
    $insertStmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES ('Teknisi', :email, :pass, 'teknisi')");
    $insertStmt->execute([':email' => $emailTeknisi, ':pass' => $pass]);
    echo "User teknisi@gmail.com DIBUAT BARU dengan role teknisi. ";
} else {
    echo "Role teknisi@gmail.com diupdate ke 'teknisi'. ";
}

$stmt2 = $db->query("SELECT id_user, name, email, role FROM users ORDER BY id_user");
$usersAfter = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'message' => 'Role berhasil diset ke teknisi',
    'users_sebelum' => $users,
    'users_sesudah' => $usersAfter,
]);
?>
