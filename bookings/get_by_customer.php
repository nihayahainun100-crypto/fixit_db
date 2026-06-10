<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed', 'bookings' => []]);
    exit;
}

$userId = $_GET['user_id'] ?? '';

if (empty($userId)) {
    echo json_encode(['success' => false, 'message' => 'user_id is required', 'bookings' => []]);
    exit;
}

$query = "SELECT * FROM bookings WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $userId]);

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'bookings' => $bookings]);
?>
