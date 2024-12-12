<?php
header('Content-Type: application/json');
require_once '../auth/auth.php';

date_default_timezone_set('Asia/Jakarta');

try {
    // Read the raw POST data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validasi data yang diperlukan
    if (!isset($data['pegawai_id'])) {
        http_response_code(400);
        throw new Exception('pegawai_id diperlukan');
    }

    $pegawaiId = $data['pegawai_id'];

    // Validasi apakah pegawai_id ada di tabel pegawai
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pegawai WHERE id = :pegawai_id");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        http_response_code(400);
        throw new Exception('Invalid pegawai_id, no matching record found');
    }

    // Query untuk mengambil riwayat cuti pegawai
    $stmt = $pdo->prepare("SELECT * FROM cuti WHERE pegawai_id = :pegawai_id ORDER BY tanggal_mulai DESC");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->execute();
    
    $riwayatCuti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($riwayatCuti)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Tidak ada riwayat cuti ditemukan',
            'data' => []
        ]);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Riwayat cuti berhasil diambil',
            'data' => $riwayatCuti
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
