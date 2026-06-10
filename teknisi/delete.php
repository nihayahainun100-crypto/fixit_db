<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Ambil data dari request
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Debug
error_log("Delete raw input: " . $rawInput);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid: ' . $rawInput]);
    exit;
}

$id = (int)$data['id'];

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID harus angka positif']);
    exit;
}

$query = "DELETE FROM teknisi WHERE id_teknisi = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Teknisi berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus teknisi: ' . implode(', ', $stmt->errorInfo())]);
}
?>