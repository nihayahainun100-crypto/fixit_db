<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$email = $_POST['email'] ?? '';
$name = $_POST['name'] ?? '';
$role = $_POST['role'] ?? 'customer';

$query = "INSERT INTO users (name, email, role) VALUES (:name, :email, :role)
          ON DUPLICATE KEY UPDATE role = :role";
$stmt = $db->prepare($query);
$stmt->execute([':name' => $name, ':email' => $email, ':role' => $role]);

echo json_encode(['success' => true, 'message' => 'User saved']);
?>