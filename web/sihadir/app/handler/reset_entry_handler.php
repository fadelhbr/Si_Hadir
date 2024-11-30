<?php
// reset_entry_handler.php
session_start();
require_once '../auth/auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete from izin table first (if exists)
    $deleteIzinQuery = "DELETE FROM izin 
                       WHERE pegawai_id = :pegawai_id 
                       AND tanggal = CURRENT_DATE";
    
    $stmt = $pdo->prepare($deleteIzinQuery);
    $stmt->execute(['pegawai_id' => $_POST['employeeId']]);

    // Delete from absensi table
    $deleteAbsensiQuery = "DELETE FROM absensi 
                          WHERE pegawai_id = :pegawai_id 
                          AND DATE(tanggal) = CURRENT_DATE";
    
    $stmt = $pdo->prepare($deleteAbsensiQuery);
    $stmt->execute(['pegawai_id' => $_POST['employeeId']]);

    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Entry berhasil direset']);

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Database error in reset_entry_handler: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat mereset entry']);
}
?>