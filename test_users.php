<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT id_user, name, email, role FROM users WHERE email LIKE '%teknisi124%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($rows);
?>
