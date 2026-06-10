<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

$email = $_GET['email'] ?? '';

$query = "SELECT role FROM users WHERE email = :email";
$stmt = $db->prepare($query);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo json_encode(['success' => true, 'role' => $user['role']]);
} else {
    echo json_encode(['success' => false, 'role' => 'customer']);
}
?>