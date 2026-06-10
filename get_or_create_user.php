<?php
header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$name  = $_POST['name'] ?? '';
$role  = $_POST['role'] ?? 'customer'; 

if (!$email || !$name) {
    echo json_encode(['success' => false, 'message' => 'Email atau nama kosong']);
    exit;
}

$existingUsers = [
    'technician@demo.com' => ['name' => 'Pak Budi', 'role' => 'technician'],
    'customer@demo.com'   => ['name' => 'Nisa', 'role' => 'customer'],
];

if (isset($existingUsers[$email])) {
    $user = [
        'id_user' => array_search($email, array_keys($existingUsers)) + 1,
        'name'    => $existingUsers[$email]['name'],
        'email'   => $email,
        'role'    => $existingUsers[$email]['role'],
    ];
} else {

    $user = [
        'id_user' => 1, 
        'name'    => $name,
        'email'   => $email,
        'role'    => $role,
    ];
}

echo json_encode([
    'success' => true,
    'user' => $user
]);