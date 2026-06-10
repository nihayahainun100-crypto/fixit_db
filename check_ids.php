<?php
require 'config/db.php';
$db = (new Database())->getConnection();

echo "Users:\n";
$stmt = $db->query("SELECT id_user, name, email FROM users WHERE email LIKE '%andyteknisi124%' OR id_user IN (58, 59)");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "Teknisi:\n";
$stmt = $db->query("SELECT id_teknisi, id_user, nama_teknisi FROM teknisi");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
