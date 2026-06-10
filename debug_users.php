<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

// Cek tipe kolom role
$stmt = $db->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Kolom role info: " . json_encode($col) . "\n\n";

// Tampilkan semua users
$stmt2 = $db->query("SELECT id_user, name, email, role, password FROM users");
$users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "Semua user:\n";
foreach ($users as $u) {
    echo "  id={$u['id_user']} | {$u['name']} | {$u['email']} | role={$u['role']} | pass_len=" . strlen($u['password']) . "\n";
}
?>
