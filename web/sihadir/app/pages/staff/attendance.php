<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Check if the user role is employee
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'karyawan') {
    // Unset session variables and destroy session
    session_unset();
    session_destroy();

    // Set headers to prevent caching
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');

    header('Location: ../../../login.php');
    exit;
}

require_once '../../../app/auth/auth.php';

date_default_timezone_set('Asia/Jakarta');

function checkHolidayStatus($pdo, $employeeId, $date)
{
    $query = "SELECT status_kehadiran 
              FROM absensi 
              WHERE pegawai_id = ? 
              AND DATE(tanggal) = ? 
              AND status_kehadiran = 'libur'";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId, $date]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result !== false; // Return true if holiday record exists
}

function checkEmployeeRole($pdo, $userId)
{
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user && $user['role'] === 'karyawan';
}

function verifyUniqueCode($pdo, $uniqueCode)
{
    $query = "SELECT * FROM qr_code WHERE kode_unik = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$uniqueCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getActiveShiftSchedule($pdo, $employeeId, $date)
{
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

function getOrCreateAttendanceRecord($pdo, $employeeId, $date, $shiftId)
{
    // Check for leave/holiday status first
    $checkQuery = "SELECT id, status_kehadiran 
                  FROM absensi 
                  WHERE pegawai_id = ? AND DATE(tanggal) = ? 
                  AND status_kehadiran IN ('cuti', 'izin', 'libur')";

    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$employeeId, $date]);
    $existingLeave = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // If found record with leave/holiday status, return false
    if ($existingLeave) {
        return [
            'status' => 'unavailable', 
            'message' => 'Anda tidak dapat melakukan absensi karena status ' . $existingLeave['status_kehadiran']
        ];
    }

    // Continue with normal logic
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

        // Get the newly created record
        $stmt = $pdo->prepare("SELECT id, waktu_masuk, waktu_keluar, status_kehadiran, jadwal_shift_id, keterangan, kode_unik 
                              FROM absensi 
                              WHERE pegawai_id = ? AND DATE(tanggal) = ?");
        $stmt->execute([$employeeId, $date]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    return ['status' => 'success', 'data' => $record];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $uniqueCode = $_POST['unique_code'] ?? null;
        $confirmEarlyLeave = isset($_POST['confirm_early_leave']) && $_POST['confirm_early_leave'] === 'true';
        $attendanceId = $_POST['attendance_id'] ?? null;
        $userId = $_SESSION['id'];
        $currentDate = date('Y-m-d');
        $currentTime = new DateTime();

        // Get employee data before starting transaction
        $stmt = $pdo->prepare("SELECT id, status_aktif FROM pegawai WHERE user_id = ?");
        $stmt->execute([$userId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            throw new Exception('Data pegawai tidak ditemukan.');
        }

        if ($employee['status_aktif'] !== 'aktif') {
            throw new Exception('Status pegawai tidak aktif.');
        }

        // Check holiday status before starting transaction
        if (checkHolidayStatus($pdo, $employee['id'], $currentDate)) {
            throw new Exception('Anda tidak dapat melakukan absensi pada hari libur.');
        }

        $pdo->beginTransaction();

        // Verify unique code
        if (!$uniqueCode) {
            throw new Exception('Kode unik harus diisi.');
        }

        $validCode = verifyUniqueCode($pdo, $uniqueCode);
        if (!$validCode) {
            throw new Exception('Kode unik tidak valid atau sudah tidak aktif.');
        }

        if (!checkEmployeeRole($pdo, $userId)) {
            throw new Exception('Akses ditolak. Hanya karyawan yang dapat melakukan absensi.');
        }

        $employeeId = $employee['id'];
        $shiftSchedule = getActiveShiftSchedule($pdo, $employeeId, $currentDate);

        if (!$shiftSchedule) {
            throw new Exception('Tidak ada shift untuk hari ini.');
        }

        $attendance = getOrCreateAttendanceRecord($pdo, $employeeId, $currentDate, $shiftSchedule['jadwal_shift_id']);

        // Check attendance status
        if ($attendance['status'] === 'unavailable') {
            throw new Exception($attendance['message']);
        }

        $attendance = $attendance['data'];

        // Check if both check-in and check-out are already filled
        if ($attendance['waktu_masuk'] != '00:00:00' && $attendance['waktu_keluar'] != '00:00:00') {
            throw new Exception('Anda sudah melakukan absensi masuk dan keluar untuk hari ini.');
        }

        $shiftStart = new DateTime($currentDate . ' ' . $shiftSchedule['jam_masuk']);
        $shiftEnd = new DateTime($currentDate . ' ' . $shiftSchedule['jam_keluar']);

        // Combined check-in and check-out logic
        if ($uniqueCode) {
            // If waktu_masuk is still default, this is a check-in
            if ($attendance['waktu_masuk'] == '00:00:00') {
                // Calculate the earliest acceptable check-in time (45 minutes before shift start)
                $earliestCheckInTime = (clone $shiftStart)->modify('-45 minutes');

                // Check if the current time is before the earliest check-in time
                if ($currentTime < $earliestCheckInTime) {
                    throw new Exception('Terlalu awal untuk absen. Absensi dimulai 45 menit sebelum jadwal shift pada pukul ' . $earliestCheckInTime->format('H:i'));
                }

                // Check if the current time is after the shift end time
                if ($currentTime > $shiftEnd) {
                    throw new Exception('Anda melewati jam keluar shift dan tidak diperbolehkan absen. Anda melewatkan shift absen hari ini.');
                }

                // Determine attendance status
                $status = ($currentTime <= $shiftStart) ? 'hadir' : 'terlambat';

                // Update the attendance record for check-in
                $query = "UPDATE absensi SET waktu_masuk = CURRENT_TIME(), status_kehadiran = ?, kode_unik = ? WHERE id = ?";
                $stmt = $pdo->prepare($query);
                if (!$stmt->execute([$status, $uniqueCode, $attendance['id']])) {
                    throw new Exception('Gagal mencatat absensi masuk.');
                }

                $message = [
                    'status' => 'success',
                    'text' => 'Absensi masuk berhasil dicatat.'
                ];
            }
            // Handle check-out
            else if ($attendance['waktu_keluar'] == '00:00:00') {
                // If trying to leave early
                if ($currentTime < $shiftEnd) {
                    // If this is the initial early leave request
                    if (!isset($_POST['confirm_early_leave'])) {
                        $message = [
                            'status' => 'confirm',
                            'text' => 'Anda akan melakukan pulang lebih awal dari jadwal shift. Apakah Anda yakin?',
                            'attendance_id' => $attendance['id']
                        ];
                    }
                    // If early leave is confirmed
                    else {
                        $stmt = $pdo->prepare("UPDATE absensi SET waktu_keluar = CURRENT_TIME(), status_kehadiran = 'pulang_dahulu', keterangan = 'Pulang lebih awal dari jadwal', kode_unik = ? WHERE id = ?");
                        if (!$stmt->execute([$uniqueCode, $attendance['id']])) {
                            throw new Exception('Gagal mengupdate absensi pulang dahulu.');
                        }
                        $message = ['status' => 'success', 'text' => 'Absensi pulang dahulu berhasil dicatat.'];
                    }
                }
                // Normal check-out after shift end
                else {
                    $stmt = $pdo->prepare("UPDATE absensi SET waktu_keluar = CURRENT_TIME(), status_kehadiran = 'hadir', kode_unik = ? WHERE id = ?");
                    if (!$stmt->execute([$uniqueCode, $attendance['id']])) {
                        throw new Exception('Gagal mengupdate absensi keluar.');
                    }
                    $message = ['status' => 'success', 'text' => 'Absensi keluar berhasil dicatat.'];
                }
            }
        }
        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = ['status' => 'error', 'text' => $e->getMessage()];
    }

    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
    ) {
        header('Content-Type: application/json');
        echo json_encode($message);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Si Hadir - Absen</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="../../../assets/favicon.ico" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="../../../assets/css/styles.css" rel="stylesheet" />
    <!-- Link Google Fonts untuk Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        /* Mengatur font Poppins hanya untuk <strong> di dalam sidebar-heading */
        #sidebar-wrapper .sidebar-heading strong {
            font-family: 'Poppins', sans-serif;
            /* Menggunakan font Poppins hanya untuk Si Hadir */
            font-weight: 900;
            /* Menebalkan tulisan */
            font-size: 28px;
            /* Membesarkan ukuran font */
        }

        /* Menghilangkan tombol toggle navbar dan memastikan navbar selalu terlihat */
        .navbar-toggler {
            display: none;
        }

        #navbarSupportedContent {
            display: flex !important;
        }

        .sidebar-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }

        /* Menyesuaikan tampilan navbar untuk layar kecil */
        @media (max-width: 991.98px) {
            .navbar-nav {
                flex-direction: row;
            }

            .navbar-nav .nav-item {
                padding-right: 10px;
            }

            .navbar-nav .dropdown-menu {
                position: absolute;
            }
        }

        /* Base Styles */
        .attendance-wrapper {
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }

        /* Animated Header Gradient */
        .text-gradient {
            background: linear-gradient(45deg, #2d3748, #4a5568);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            position: relative;
        }

        .header-gradient {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.1) 0%, rgba(99, 102, 241, 0) 70%);
            border-radius: 50%;
            z-index: -1;
        }

        /* Glassmorphism Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        /* Card Content Styling */
        .card-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .icon-wrapper {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            padding: 1rem;
            border-radius: 12px;
            color: white;
        }

        .time-display {
            flex: 1;
        }

        .time-display .label {
            display: block;
            font-size: 0.875rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .time-display .value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
        }

        /* Modern Form Styling */
        .attendance-form-card {
            background: white;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .modern-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.75rem;
            display: block;
        }

        .modern-input-group {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .modern-input {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.125rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .modern-input:focus {
            outline: none;
            border-color: #6366f1;
            background: white;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-helper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        /* Submit Button */
        .submit-button {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }

        .button-content {
            position: relative;
            z-index: 1;
        }

        .button-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .submit-button:hover .button-overlay {
            opacity: 1;
        }

        /* Pulse Animation */
        .pulse-animation {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .attendance-form-card {
                padding: 2rem;
            }

            .time-display .value {
                font-size: 1.5rem;
            }

            .card-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>

</head>

<body>

    <div class="d-flex" id="wrapper">
        <!-- Sidebar-->
        <div class="border-end-0 bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="attendance.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z" />
                    </svg>
                    Absen
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="attendanceHistory.php" style="display: flex; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                        fill="#6c757d" style="margin-right: 8px;">
                        <path
                            d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z" />
                    </svg>
                    <span>Riwayat Kehadiran</span>
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="permit.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M160-200v-440 440-15 15Zm0 80q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v171q-18-13-38-22.5T800-508v-132H160v440h283q3 21 9 41t15 39H160Zm240-600h160v-80H400v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-112h-40v128l86 86 28-28-74-74Z" />
                    </svg>
                    Cuti & Perizinan
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="logout.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z" />
                    </svg>
                    Log out
                </a>
            </div>
        </div>
        <!-- Page content wrapper-->
        <div id="page-content-wrapper">
            <!-- Top navigation-->
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">☰</button>
                    <div id="navbarSupportedContent">
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <div class="attendance-wrapper">
                <div class="container-fluid p-4">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <!-- Display message if exists -->
                            <?php if (isset($message)): ?>
                                <div class="alert alert-<?php echo $message['status'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show"
                                    role="alert">
                                    <?php echo htmlspecialchars($message['text']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <!-- Header Section -->
                            <div class="text-center position-relative mb-5">
                                <div class="header-gradient"></div>
                                <h2 class="display-4 fw-bold text-gradient mb-3">Absensi Hari Ini</h2>
                                <p class="text-muted fs-5 mb-0">Masukkan kode unik Anda untuk melakukan absensi</p>
                            </div>

                            <!-- Time and Date Section -->
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <div class="time-card glass-card">
                                        <div class="card-content">
                                            <div class="icon-wrapper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10" />
                                                    <polyline points="12 6 12 12 16 14" />
                                                </svg>
                                            </div>
                                            <div class="time-display">
                                                <span class="label">Waktu Sekarang</span>
                                                <h3 class="value" id="currentTime">00:00</h3>
                                            </div>
                                        </div>
                                        <div class="pulse-animation"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="date-card glass-card">
                                        <div class="card-content">
                                            <div class="icon-wrapper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                                    <line x1="16" y1="2" x2="16" y2="6" />
                                                    <line x1="8" y1="2" x2="8" y2="6" />
                                                    <line x1="3" y1="10" x2="21" y2="10" />
                                                </svg>
                                            </div>
                                            <div class="time-display">
                                                <span class="label">Tanggal</span>
                                                <h3 class="value" id="currentDate">1 Jan 2024</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Form -->
                            <div class="attendance-form-card">
                                <div class="form-content">
                                    <form id="attendanceForm" method="POST">
                                        <div class="input-wrapper mb-4">
                                            <label for="unique_code" class="modern-label">Kode Unik</label>
                                            <div class="modern-input-group">
                                                <input type="text" class="modern-input" id="unique_code"
                                                    name="unique_code" placeholder="Masukkan 6 digit kode" required
                                                    maxlength="6" pattern="^[a-zA-Z0-9]{6}$" />
                                                <div class="input-focus-border"></div>
                                            </div>
                                            <div class="input-helper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                                </svg>
                                                <span>Kode terdiri dari 6 karakter kombinasi huruf dan angka</span>
                                            </div>
                                        </div>
                                        <button type="submit" class="submit-button">
                                            <span class="button-content">Submit Absensi</span>
                                            <div class="button-overlay"></div>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/scripts.js"></script>
    <script>
        // Update time and date
        function updateDateTime() {
            const now = new Date();
            const formattedTime = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
            });
            document.getElementById('currentTime').textContent = formattedTime;
            document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }

        // Update initially and then every second
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // Function to show alert
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

            // Insert alert before the form
            const formCard = document.querySelector('.attendance-form-card');
            formCard.insertAdjacentElement('beforebegin', alertDiv);

            // Auto-dismiss alert after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Create and show confirmation modal
        function showConfirmationModal(message, onConfirm) {
            // Remove any existing modals
            const existingModal = document.getElementById('confirmationModal');
            if (existingModal) {
                existingModal.remove();
            }

            const modalHtml = `
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Konfirmasi Pulang Dahulu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">${message}</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-primary confirm-leave">Ya, Pulang Sekarang</button>
                    </div>
                </div>
            </div>
        </div>
    `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));

            // Handle confirmation
            document.querySelector('.confirm-leave').addEventListener('click', () => {
                modal.hide();
                onConfirm();
            });

            // Clean up modal when hidden
            document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function () {
                this.remove();
            });

            modal.show();
        }

        // Form submission handler
        document.getElementById('attendanceForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const form = this;
            const submitButton = form.querySelector('.submit-button');
            submitButton.disabled = true;

            try {
                const formData = new FormData(form);
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.status === 'confirm') {
                    showConfirmationModal(data.text, async () => {
                        try {
                            const confirmResponse = await fetch(window.location.href, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: `confirm_early_leave=true&attendance_id=${data.attendance_id}&unique_code=${formData.get('unique_code')}`
                            });

                            const confirmData = await confirmResponse.json();
                            showAlert(confirmData.text, confirmData.status === 'success' ? 'success' : 'danger');

                            if (confirmData.status === 'success') {
                                form.reset();
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showAlert('Terjadi kesalahan saat memproses pulang dahulu.', 'danger');
                        } finally {
                            submitButton.disabled = false;
                        }
                    });
                } else {
                    showAlert(data.text, data.status === 'success' ? 'success' : 'danger');
                    if (data.status === 'success') {
                        form.reset();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
            } finally {
                if (submitButton.disabled && !document.getElementById('confirmationModal')) {
                    submitButton.disabled = false;
                }
            }
        });

        // Add input animation
        const input = document.querySelector('.modern-input');
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('focused');
        });
        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('focused');
        });
    </script>
</body>

</html>