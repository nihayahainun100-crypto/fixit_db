<?php
require_once 'config/db.php';
$database = new Database();
$db = $database->getConnection();

try {
   
    $stmt = $db->prepare("UPDATE users SET role = 'customer' WHERE role = 'technician' AND email != :email");
    $stmt->execute([':email' => 'ainunnihayah204@gmail.com']);
    $affected = $stmt->rowCount();
    echo "Successfully updated $affected other technician(s) to customer.\n\n";
    
    
    $stmt2 = $db->query("SELECT id_user, name, email, role FROM users");
    $users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    echo "Daftar User Saat Ini:\n";
    foreach ($users as $u) {
        echo "  id={$u['id_user']} | Name: {$u['name']} | Email: {$u['email']} | Role: {$u['role']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
