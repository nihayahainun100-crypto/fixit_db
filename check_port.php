<?php
require 'config/db.php';
$db = (new Database())->getConnection();

$stmt = $db->query("SHOW VARIABLES LIKE 'port'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
