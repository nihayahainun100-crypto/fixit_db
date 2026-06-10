<?php
require 'config/db.php';
$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    
    $stmt1 = $db->prepare("UPDATE users SET email = 'andyteknisi124@gmail.com' WHERE email = 'anditeknisi124@gmail.com'");
    $stmt1->execute();

    $stmt2 = $db->prepare("SELECT id_user FROM users WHERE email = 'andyteknisi124@gmail.com' ORDER BY id_user ASC");
    $stmt2->execute();
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 1) {
        $old_id = $rows[0]['id_user']; 
        $new_id = $rows[1]['id_user']; 
        
        $stmt3 = $db->prepare("UPDATE teknisi SET id_user = :new_id WHERE id_user = :old_id");
        $stmt3->execute([':new_id' => $new_id, ':old_id' => $old_id]);
        
        $stmt4 = $db->prepare("UPDATE bookings SET technician_id = 'andyteknisi124@gmail.com' WHERE technician_id = 'anditeknisi124@gmail.com'");
        $stmt4->execute();
        
        $stmt5 = $db->prepare("DELETE FROM users WHERE id_user = :old_id");
        $stmt5->execute([':old_id' => $old_id]);
        
        echo "Berhasil menyatukan akun andi dan andy! Sekarang teknisi terhubung ke Google Login Anda.\n";
    } else {
        $stmt4 = $db->prepare("UPDATE bookings SET technician_id = 'andyteknisi124@gmail.com' WHERE technician_id = 'anditeknisi124@gmail.com'");
        $stmt4->execute();
        echo "Berhasil mengubah email teknisi menjadi andyteknisi124@gmail.com!\n";
    }
    
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    echo "Gagal: " . $e->getMessage() . "\n";
}
?>
