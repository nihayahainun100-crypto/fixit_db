<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$query = "SELECT t.*, u.email, 
                 (SELECT COUNT(*) FROM reviews r WHERE r.id_teknisi = t.id_teknisi) as total_reviews 
          FROM teknisi t 
          LEFT JOIN users u ON t.id_user = u.id_user 
          ORDER BY t.id_teknisi DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

print_r($row);
?>
