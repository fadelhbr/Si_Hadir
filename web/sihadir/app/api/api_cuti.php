<?php
header('Content-Type: application/json');
require_once '../auth/auth.php';

date_default_timezone_set('Asia/Jakarta');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validasi data yang diperlukan
    if (!isset($data['pegawai_id']) || !isset($data['tanggal_mulai']) || !isset($data['tanggal_selesai']) || !isset($data['keterangan'])) {
        http_response_code(400);
        throw new Exception('Semua data diperlukan');
    }

    // Validasi format tanggal (YYYY-MM-DD) untuk tanggal mulai dan tanggal selesai
    $tanggalMulai = $data['tanggal_mulai'];
    $tanggalSelesai = $data['tanggal_selesai'];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesai)) {
        http_response_code(400);
        throw new Exception('Format tanggal tidak valid, gunakan format YYYY-MM-DD');
    }

    // Mendapatkan tanggal saat ini
    $currentDate = date('Y-m-d');

    // Validasi jika tanggal mulai lebih kecil dari hari ini
    if ($tanggalMulai < $currentDate) {
        http_response_code(400);
        throw new Exception('Tanggal mulai cuti tidak boleh sebelum tanggal hari ini');
    }

    // Validasi jika tanggal selesai lebih kecil dari tanggal mulai
    if ($tanggalSelesai < $tanggalMulai) {
        http_response_code(400);
        throw new Exception('Tanggal selesai tidak boleh lebih awal dari tanggal mulai');
    }

    // Hitung durasi cuti
    $durasiCuti = (new DateTime($tanggalMulai))->diff(new DateTime($tanggalSelesai))->days + 1;

    $pegawaiId = $data['pegawai_id'];
    $keterangan = $data['keterangan'];
    $status = 'pending';

    // Validasi apakah pegawai_id ada di tabel pegawai
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pegawai WHERE id = :pegawai_id");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        http_response_code(400);
        throw new Exception('Invalid pegawai_id, no matching record found');
    }

    // Validasi apakah sudah ada cuti atau izin pada periode yang sama
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM cuti WHERE pegawai_id = :pegawai_id 
        AND ((tanggal_mulai BETWEEN :tanggal_mulai AND :tanggal_selesai) 
        OR (tanggal_selesai BETWEEN :tanggal_mulai AND :tanggal_selesai))
        UNION ALL
        SELECT COUNT(*) FROM izin WHERE pegawai_id = :pegawai_id 
        AND tanggal BETWEEN :tanggal_mulai AND :tanggal_selesai
    ");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->bindParam(':tanggal_mulai', $tanggalMulai);
    $stmt->bindParam(':tanggal_selesai', $tanggalSelesai);
    $stmt->execute();
    $conflictCount = array_sum($stmt->fetchAll(PDO::FETCH_COLUMN));

    if ($conflictCount > 0) {
        http_response_code(400);
        throw new Exception('Tidak dapat mengajukan cuti, sudah ada izin atau cuti pada periode tersebut');
    }

    // Query untuk menyimpan data cuti
    $stmt = $pdo->prepare("INSERT INTO cuti (pegawai_id, tanggal_mulai, tanggal_selesai, durasi_cuti, keterangan, status) 
                           VALUES (:pegawai_id, :tanggal_mulai, :tanggal_selesai, :durasi_cuti, :keterangan, :status)");
    $stmt->bindParam(':pegawai_id', $pegawaiId);
    $stmt->bindParam(':tanggal_mulai', $tanggalMulai);
    $stmt->bindParam(':tanggal_selesai', $tanggalSelesai);
    $stmt->bindParam(':durasi_cuti', $durasiCuti);
    $stmt->bindParam(':keterangan', $keterangan);
    $stmt->bindParam(':status', $status);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Cuti berhasil diajukan',
            'durasi_cuti' => $durasiCuti . ' hari'
        ]);
    } else {
        http_response_code(500);
        throw new Exception('Gagal mengajukan cuti');
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
