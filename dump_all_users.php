<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SELECT id_user, name, email FROM users ORDER BY id_user");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
