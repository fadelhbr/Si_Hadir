<?php 
header('Content-Type: application/json');
require_once '../auth/auth.php';

try {
    // Mendapatkan data pegawai_id dari input JSON
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['pegawai_id'])) {
        http_response_code(400);
        throw new Exception('pegawai_id diperlukan');
    }

    $pegawaiId = $data['pegawai_id'];

    // Query untuk mengambil riwayat izin berdasarkan pegawai_id
    $stmt = $pdo->prepare("
        SELECT i.id, i.tanggal, i.jenis_izin, i.keterangan, i.status
        FROM izin i
        WHERE i.pegawai_id = :pegawai_id
        ORDER BY i.tanggal DESC
    ");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->execute();

    $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Jika tidak ada riwayat izin
    if (empty($riwayat)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Tidak Ada Pengajuan Izin',
            'data' => []
        ]);
        exit;
    }

    // Jika ada riwayat izin
    echo json_encode([
        'status' => 'success',
        'message' => 'Riwayat Izin ditemukan',
        'data' => $riwayat
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
