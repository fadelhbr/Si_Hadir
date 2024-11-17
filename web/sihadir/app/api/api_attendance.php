<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../auth/auth.php';
date_default_timezone_set('Asia/Jakarta');

function sendResponse($status, $message, $data = null) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

function checkHolidayStatus($pdo, $employeeId, $date) {
    $query = "SELECT status_kehadiran 
              FROM absensi 
              WHERE pegawai_id = ? 
              AND DATE(tanggal) = ? 
              AND status_kehadiran = 'libur'";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId, $date]);
    return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

function verifyUniqueCode($pdo, $uniqueCode) {
    $query = "SELECT * FROM qr_code WHERE kode_unik = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$uniqueCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getActiveShiftSchedule($pdo, $employeeId, $date) {
    $query = "SELECT 
                js.id as jadwal_shift_id, 
                js.status as jadwal_status, 
                s.id as shift_id, 
                s.nama_shift, 
                s.jam_masuk, 
                s.jam_keluar 
              FROM jadwal_shift js 
              JOIN shift s ON js.shift_id = s.id 
              WHERE js.pegawai_id = ? 
              AND js.tanggal = ? 
              AND js.status = 'aktif'";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId, $date]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOrCreateAttendanceRecord($pdo, $employeeId, $date, $shiftId) {
    // Check for leave/holiday status
    $checkQuery = "SELECT id, status_kehadiran 
                  FROM absensi 
                  WHERE pegawai_id = ? AND DATE(tanggal) = ? 
                  AND status_kehadiran IN ('cuti', 'izin', 'libur')";

    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$employeeId, $date]);
    $existingLeave = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingLeave) {
        return [
            'status' => 'unavailable',
            'message' => 'Tidak dapat melakukan absensi karena status ' . $existingLeave['status_kehadiran']
        ];
    }

    $query = "SELECT id, waktu_masuk, waktu_keluar, status_kehadiran, jadwal_shift_id, keterangan, kode_unik 
              FROM absensi 
              WHERE pegawai_id = ? AND DATE(tanggal) = ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId, $date]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        $query = "INSERT INTO absensi 
                  (pegawai_id, tanggal, waktu_masuk, waktu_keluar, status_kehadiran, jadwal_shift_id, kode_unik) 
                  VALUES (?, ?, '00:00:00', '00:00:00', '', ?, '000000')";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$employeeId, $date, $shiftId]);

        $stmt = $pdo->prepare("SELECT id, waktu_masuk, waktu_keluar, status_kehadiran, jadwal_shift_id, keterangan, kode_unik 
                              FROM absensi 
                              WHERE pegawai_id = ? AND DATE(tanggal) = ?");
        $stmt->execute([$employeeId, $date]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return ['status' => 'success', 'data' => $record];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Method not allowed', ['allowed_method' => 'POST']);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !isset($input['unique_code'])) {
    sendResponse('error', 'Missing required parameters: user_id and unique_code');
}

try {
    $userId = $input['user_id'];
    $uniqueCode = $input['unique_code'];
    $confirmEarlyLeave = $input['confirm_early_leave'] ?? false;
    $currentDate = date('Y-m-d');
    $currentTime = new DateTime();

    // Get employee data
    $stmt = $pdo->prepare("SELECT id, status_aktif FROM pegawai WHERE user_id = ?");
    $stmt->execute([$userId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        sendResponse('error', 'Data pegawai tidak ditemukan');
    }

    if ($employee['status_aktif'] !== 'aktif') {
        sendResponse('error', 'Status pegawai tidak aktif');
    }

    // Check holiday status
    if (checkHolidayStatus($pdo, $employee['id'], $currentDate)) {
        sendResponse('error', 'Tidak dapat melakukan absensi pada hari libur');
    }

    $pdo->beginTransaction();

    // Verify unique code
    $validCode = verifyUniqueCode($pdo, $uniqueCode);
    if (!$validCode) {
        throw new Exception('Kode unik tidak valid atau sudah tidak aktif');
    }

    $employeeId = $employee['id'];
    $shiftSchedule = getActiveShiftSchedule($pdo, $employeeId, $currentDate);

    if (!$shiftSchedule) {
        throw new Exception('Tidak ada shift untuk hari ini');
    }

    $attendance = getOrCreateAttendanceRecord($pdo, $employeeId, $currentDate, $shiftSchedule['jadwal_shift_id']);

    if ($attendance['status'] === 'unavailable') {
        throw new Exception($attendance['message']);
    }

    $attendance = $attendance['data'];

    if ($attendance['waktu_masuk'] != '00:00:00' && $attendance['waktu_keluar'] != '00:00:00') {
        throw new Exception('Sudah melakukan absensi masuk dan keluar untuk hari ini');
    }

    $shiftStart = new DateTime($currentDate . ' ' . $shiftSchedule['jam_masuk']);
    $shiftEnd = new DateTime($currentDate . ' ' . $shiftSchedule['jam_keluar']);

    // Handle check-in
    if ($attendance['waktu_masuk'] == '00:00:00') {
        $earliestCheckInTime = (clone $shiftStart)->modify('-45 minutes');

        if ($currentTime < $earliestCheckInTime) {
            throw new Exception('Terlalu awal untuk absen. Absensi dimulai 45 menit sebelum jadwal shift pada pukul ' . $earliestCheckInTime->format('H:i'));
        }

        if ($currentTime > $shiftEnd) {
            throw new Exception('Melewati jam keluar shift dan tidak diperbolehkan absen');
        }

        $status = ($currentTime <= $shiftStart) ? 'hadir' : 'terlambat';

        $stmt = $pdo->prepare("UPDATE absensi SET waktu_masuk = CURRENT_TIME(), status_kehadiran = ?, kode_unik = ? WHERE id = ?");
        if (!$stmt->execute([$status, $uniqueCode, $attendance['id']])) {
            throw new Exception('Gagal mencatat absensi masuk');
        }

        $responseData = [
            'attendance_id' => $attendance['id'],
            'check_in_time' => date('H:i:s'),
            'status' => $status
        ];
        
        $pdo->commit();
        sendResponse('success', 'Absensi masuk berhasil dicatat', $responseData);
    }
    // Handle check-out
    else if ($attendance['waktu_keluar'] == '00:00:00') {
        if ($currentTime < $shiftEnd && !$confirmEarlyLeave) {
            sendResponse('confirm_needed', 'Konfirmasi diperlukan untuk pulang lebih awal', [
                'attendance_id' => $attendance['id']
            ]);
        }

        $status = $currentTime < $shiftEnd ? 'pulang_dahulu' : 'hadir';
        $keterangan = $currentTime < $shiftEnd ? 'Pulang lebih awal dari jadwal' : null;

        $stmt = $pdo->prepare("UPDATE absensi SET waktu_keluar = CURRENT_TIME(), status_kehadiran = ?, keterangan = ?, kode_unik = ? WHERE id = ?");
        if (!$stmt->execute([$status, $keterangan, $uniqueCode, $attendance['id']])) {
            throw new Exception('Gagal mencatat absensi keluar');
        }

        $responseData = [
            'attendance_id' => $attendance['id'],
            'check_out_time' => date('H:i:s'),
            'status' => $status
        ];

        $pdo->commit();
        sendResponse('success', 'Absensi keluar berhasil dicatat', $responseData);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse('error', $e->getMessage());
}