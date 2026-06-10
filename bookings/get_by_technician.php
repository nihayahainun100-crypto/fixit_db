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

$technicianId = $_GET['technician_id'] ?? '';

if (empty($technicianId)) {
    echo json_encode(['success' => false, 'message' => 'technician_id is required', 'bookings' => []]);
    exit;
}

$query = "SELECT * FROM bookings WHERE technician_id = :technician_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':technician_id' => $technicianId]);

$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'bookings' => $bookings]);
?>