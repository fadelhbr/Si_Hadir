<?php
// process_emergency_leave.php
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
    // Check if employee already has leave request for today
    $checkQuery = "SELECT id FROM izin WHERE pegawai_id = :pegawai_id AND tanggal = CURRENT_DATE";
    $stmt = $pdo->prepare($checkQuery);
    $stmt->execute(['pegawai_id' => $_POST['employeeId']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Izin mendadak gagal, dikarenakan ada izin yang bersamaan hari ini'
        ]);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Insert into izin table
    $insertIzinQuery = "INSERT INTO izin (pegawai_id, tanggal, jenis_izin, keterangan, status) 
                       VALUES (:pegawai_id, CURRENT_DATE, :jenis_izin, :keterangan, 'disetujui')";
    
    $stmt = $pdo->prepare($insertIzinQuery);
    $stmt->execute([
        'pegawai_id' => $_POST['employeeId'],
        'jenis_izin' => $_POST['leaveType'],
        'keterangan' => $_POST['description']
    ]);

    // Update absensi table
    $updateAbsensiQuery = "UPDATE absensi 
                          SET status_kehadiran = 'izin', 
                              keterangan = :keterangan 
                          WHERE pegawai_id = :pegawai_id 
                          AND DATE(tanggal) = CURRENT_DATE";
    
    $stmt = $pdo->prepare($updateAbsensiQuery);
    $stmt->execute([
        'pegawai_id' => $_POST['employeeId'],
        'keterangan' => $_POST['description']
    ]);

    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Izin mendadak berhasil diproses']);

} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Database error in process_emergency_leave: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>