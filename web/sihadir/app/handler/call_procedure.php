<?php
// Memasukkan file auth.php
require_once '../auth/auth.php'; // Sesuaikan path jika file ini ada di tempat berbeda

try {
    // Panggil stored procedure hanya sekali saat file dijalankan
    $stmt = $pdo->prepare("CALL update_attendance()"); // Ganti dengan nama prosedur Anda
    $stmt->execute();

    // Tampilkan waktu eksekusi
    echo "Prosedur dijalankan pada: " . date('Y-m-d H:i:s') . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
