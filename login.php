<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0); // jangan tampil error di response JSON

require_once 'config/db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Request body tidak valid']);
    exit;
}

$email    = isset($data['email'])    ? trim($data['email'])    : '';
$password = isset($data['password']) ? trim($data['password']) : '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email dan password wajib diisi']);
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Email tidak terdaftar']);
    exit;
}

$passwordMatch = false;
if (password_verify($password, $user['password'])) {
    $passwordMatch = true;
} elseif ($user['password'] === $password) {
    $passwordMatch = true;
} elseif (md5($password) === $user['password']) {
    $passwordMatch = true;
}

if (!$passwordMatch) {
    echo json_encode(['success' => false, 'message' => 'Password salah']);
    exit;
}

$role = isset($user['role']) ? $user['role'] : 'customer';

$technicianData = null;
if ($role === 'teknisi') {
    $stmtTek = $db->prepare("SELECT * FROM teknisi WHERE id_user = :id_user LIMIT 1");
    $stmtTek->bindParam(':id_user', $user['id_user']);
    $stmtTek->execute();
    $tek = $stmtTek->fetch(PDO::FETCH_ASSOC);
    if ($tek) {
        $technicianData = [
            'id'         => $tek['id_teknisi'],
            'name'       => $tek['nama_teknisi'],
            'photo_url'  => $tek['photo_url'] ?? '',
            'rating'     => (float)($tek['rating'] ?? 0),
            'specialties'=> $tek['kategori'] ?? '',
            'lokasi'     => $tek['lokasi'] ?? '',
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Login berhasil',
    'user' => [
        'id'       => $user['id_user'],
        'name'     => $user['name'],
        'email'    => $user['email'],
        'phone'    => $user['phone'] ?? '',
        'role'     => $role,
    ],
    'technician' => $technicianData,
]);
?>
