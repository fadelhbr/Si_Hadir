<?php
header('Content-Type: application/json');
require_once '../auth/auth.php';

date_default_timezone_set('Asia/Jakarta');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validasi data
    if (!isset($data['pegawai_id']) || !isset($data['tanggal']) || !isset($data['jenis_izin']) || !isset($data['keterangan'])) {
        http_response_code(400);
        throw new Exception('Semua data diperlukan');
    }

    // Validasi format tanggal (YYYY-MM-DD)
    $tanggal = $data['tanggal'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        http_response_code(400);
        throw new Exception('Format tanggal tidak valid, gunakan format YYYY-MM-DD');
    }

    // Mendapatkan tanggal saat ini
    $currentDate = date('Y-m-d');

    // Validasi jika tanggal yang dimasukkan lebih kecil dari tanggal saat ini
    if ($tanggal < $currentDate) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Tidak diperbolehkan mengajukan izin sebelum tanggal hari ini'
        ]);
        exit;
    }

    // Menyimpan data izin ke dalam database
    $pegawaiId = $data['pegawai_id'];
    $jenisIzin = $data['jenis_izin'];
    $keterangan = $data['keterangan'];
    $status = 'pending';  // Status default

    // Validasi apakah pegawai_id ada di tabel pegawai dan terkait dengan user yang valid
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pegawai p JOIN users u ON p.user_id = u.id WHERE p.id = :pegawai_id");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid pegawai_id, no matching record found']);
        exit;
    }

    // Validasi apakah sudah ada izin atau cuti pada tanggal yang sama
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM izin WHERE pegawai_id = :pegawai_id AND tanggal = :tanggal
        UNION ALL
        SELECT COUNT(*) FROM cuti WHERE pegawai_id = :pegawai_id AND :tanggal BETWEEN tanggal_mulai AND tanggal_selesai
    ");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->bindParam(':tanggal', $tanggal);
    $stmt->execute();
    $conflictCount = array_sum($stmt->fetchAll(PDO::FETCH_COLUMN));

    if ($conflictCount > 0) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Tidak dapat mengajukan izin, sudah ada izin atau cuti pada tanggal tersebut'
        ]);
        exit;
    }

    // Query untuk menyimpan data izin
    $stmt = $pdo->prepare("INSERT INTO izin (pegawai_id, tanggal, jenis_izin, keterangan, status) 
                        VALUES (:pegawai_id, :tanggal, :jenis_izin, :keterangan, :status)");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->bindParam(':tanggal', $tanggal);
    $stmt->bindParam(':jenis_izin', $jenisIzin);
    $stmt->bindParam(':keterangan', $keterangan);
    $stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Izin berhasil diajukan'
        ]);
    } else {
        http_response_code(500);
        throw new Exception('Gagal mengajukan izin');
    }

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
