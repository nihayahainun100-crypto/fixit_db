<?php
require 'config/db.php';
$db = (new Database())->getConnection();
$q = $db->query("SELECT id_teknisi, nama_teknisi, email FROM teknisi");
print_r($q->fetchAll(PDO::FETCH_ASSOC));
?>
