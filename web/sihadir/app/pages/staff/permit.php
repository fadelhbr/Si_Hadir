<?php
session_start();
require_once '../../../app/auth/auth.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa autentikasi
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    session_unset();
    session_destroy();
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Location: ../../../login.php');
    exit;
}

// Pastikan id tersedia dalam session
if (!isset($_SESSION['id'])) {
    die("Error: User ID tidak ditemukan dalam session.");
}

$user_id = $_SESSION['id'];

try {
    // Dapatkan pegawai_id
    $queryPegawai = "SELECT p.id as pegawai_id 
                     FROM pegawai p 
                     WHERE p.user_id = :user_id";
    $stmtPegawai = $pdo->prepare($queryPegawai);
    $stmtPegawai->execute(['user_id' => $user_id]);
    $pegawaiData = $stmtPegawai->fetch(PDO::FETCH_ASSOC);

    if (!$pegawaiData) {
        die("Data pegawai tidak ditemukan");
    }

    $pegawai_id = $pegawaiData['pegawai_id'];

    // Handle POST requests untuk form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            // Handle form izin
            if ($_POST['form_type'] === 'izin') {
                $tanggal = $_POST['permitDate'];
                $jenis_izin = $_POST['permitType'];
                $keterangan = $_POST['permitDescription'];

                // Cek apakah sudah ada izin di tanggal yang sama
                $queryCheck = "SELECT COUNT(*) as total FROM izin 
                             WHERE pegawai_id = :pegawai_id 
                             AND tanggal = :tanggal";
                $stmtCheck = $pdo->prepare($queryCheck);
                $stmtCheck->execute([
                    'pegawai_id' => $pegawai_id,
                    'tanggal' => $tanggal
                ]);
                $existingPermit = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existingPermit['total'] > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Anda sudah mengajukan izin untuk tanggal tersebut!'
                    ]);
                    exit;
                }

                // Cek apakah sudah ada cuti di tanggal yang sama
                $queryCheckCuti = "SELECT COUNT(*) as total FROM cuti 
                                 WHERE pegawai_id = :pegawai_id 
                                 AND :tanggal BETWEEN tanggal_mulai AND tanggal_selesai";
                $stmtCheckCuti = $pdo->prepare($queryCheckCuti);
                $stmtCheckCuti->execute([
                    'pegawai_id' => $pegawai_id,
                    'tanggal' => $tanggal
                ]);
                $existingCuti = $stmtCheckCuti->fetch(PDO::FETCH_ASSOC);

                if ($existingCuti['total'] > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Anda sudah memiliki cuti yang mencakup tanggal tersebut!'
                    ]);
                    exit;
                }

                $query = "INSERT INTO izin (pegawai_id, tanggal, jenis_izin, keterangan, status, created_at) 
                         VALUES (:pegawai_id, :tanggal, :jenis_izin, :keterangan, 'pending', NOW())";

                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'pegawai_id' => $pegawai_id,
                    'tanggal' => $tanggal,
                    'jenis_izin' => $jenis_izin,
                    'keterangan' => $keterangan
                ]);

                if ($result) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Pengajuan izin berhasil disubmit!'
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Gagal menyimpan pengajuan izin'
                    ]);
                }
            }

            // Handle form cuti
            if ($_POST['form_type'] === 'cuti') {
                $tanggal_mulai = $_POST['leaveStartDate'];
                $tanggal_selesai = $_POST['leaveEndDate'];
                $keterangan = $_POST['leaveDescription'];

                // Cek apakah ada izin dalam rentang tanggal cuti
                $queryCheckIzin = "SELECT COUNT(*) as total FROM izin 
                                 WHERE pegawai_id = :pegawai_id 
                                 AND tanggal BETWEEN :tanggal_mulai AND :tanggal_selesai";
                $stmtCheckIzin = $pdo->prepare($queryCheckIzin);
                $stmtCheckIzin->execute([
                    'pegawai_id' => $pegawai_id,
                    'tanggal_mulai' => $tanggal_mulai,
                    'tanggal_selesai' => $tanggal_selesai
                ]);
                $existingIzin = $stmtCheckIzin->fetch(PDO::FETCH_ASSOC);

                if ($existingIzin['total'] > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Anda sudah memiliki izin dalam rentang tanggal cuti yang diajukan!'
                    ]);
                    exit;
                }

                // Cek apakah ada cuti yang overlap
                $queryCheckCuti = "SELECT COUNT(*) as total FROM cuti 
                                 WHERE pegawai_id = :pegawai_id 
                                 AND (
                                     (tanggal_mulai BETWEEN :tanggal_mulai AND :tanggal_selesai)
                                     OR (tanggal_selesai BETWEEN :tanggal_mulai AND :tanggal_selesai)
                                     OR (:tanggal_mulai BETWEEN tanggal_mulai AND tanggal_selesai)
                                     OR (:tanggal_selesai BETWEEN tanggal_mulai AND tanggal_selesai)
                                 )";
                $stmtCheckCuti = $pdo->prepare($queryCheckCuti);
                $stmtCheckCuti->execute([
                    'pegawai_id' => $pegawai_id,
                    'tanggal_mulai' => $tanggal_mulai,
                    'tanggal_selesai' => $tanggal_selesai
                ]);
                $existingCuti = $stmtCheckCuti->fetch(PDO::FETCH_ASSOC);

                if ($existingCuti['total'] > 0) {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Terdapat overlap dengan pengajuan cuti yang sudah ada!'
                    ]);
                    exit;
                }

                // Hitung durasi cuti
                $date1 = new DateTime($tanggal_mulai);
                $date2 = new DateTime($tanggal_selesai);
                $interval = $date1->diff($date2);
                $durasi_cuti = $interval->days + 1;

                $query = "INSERT INTO cuti (pegawai_id, tanggal_mulai, tanggal_selesai, durasi_cuti, keterangan, status, created_at) 
                         VALUES (:pegawai_id, :tanggal_mulai, :tanggal_selesai, :durasi_cuti, :keterangan, 'pending', NOW())";

                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([
                    'pegawai_id' => $pegawai_id,
                    'tanggal_mulai' => $tanggal_mulai,
                    'tanggal_selesai' => $tanggal_selesai,
                    'durasi_cuti' => $durasi_cuti,
                    'keterangan' => $keterangan
                ]);

                if ($result) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Pengajuan cuti berhasil disubmit!'
                    ]);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Gagal menyimpan pengajuan cuti'
                    ]);
                }
            }
            exit;
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Database Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Query untuk mengambil data izin
    $queryIzin = "SELECT i.*, u.nama_lengkap 
                  FROM izin i 
                  JOIN pegawai p ON i.pegawai_id = p.id 
                  JOIN users u ON p.user_id = u.id 
                  WHERE i.pegawai_id = :pegawai_id 
                  ORDER BY i.tanggal DESC";
    $stmtIzin = $pdo->prepare($queryIzin);
    $stmtIzin->execute(['pegawai_id' => $pegawai_id]);
    $dataIzin = $stmtIzin->fetchAll(PDO::FETCH_ASSOC);

    // Query untuk mengambil data cuti
    $queryCuti = "SELECT c.*, u.nama_lengkap 
                  FROM cuti c 
                  JOIN pegawai p ON c.pegawai_id = p.id 
                  JOIN users u ON p.user_id = u.id 
                  WHERE c.pegawai_id = :pegawai_id 
                  ORDER BY c.tanggal_mulai DESC";
    $stmtCuti = $pdo->prepare($queryCuti);
    $stmtCuti->execute(['pegawai_id' => $pegawai_id]);
    $dataCuti = $stmtCuti->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Si Hadir - Cuti & Perizinan</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="../../../assets/icon/favicon.ico" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="../../../assets/css/styles.css" rel="stylesheet" />
    <!-- Link Google Fonts untuk Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <style>
        .alert {
            margin-bottom: 1rem;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-dismissible .btn-close {
            padding: 0.75rem 1rem;
        }

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

        .status-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }

        .hidden {
            display: none;
        }

        /* Style untuk switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
            margin: 0 10px;
            /* Menambahkan margin di sekitar switch */
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            border-radius: 50%;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .hidden {
            display: none;
        }

        .btn-izin {
            background-color: #007bff;
            /* Warna biru */
            color: white;
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-cuti {
            background-color: #6c757d;
            /* Warna abu-abu */
            color: white;
            transition: background-color 0.3s, color 0.3s;
        }

        .btn-active {
            background-color: #0056b3 !important;
            /* Warna tombol aktif */
            color: white !important;
        }
    </style>
</head>

<body style="background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);">
    <?php if (isset($_GET['status'])): ?>
        <div class="alert <?php echo $_GET['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show"
            role="alert">
            <?php
            if ($_GET['status'] === 'success') {
                echo 'Pengajuan berhasil disubmit!';
            } else {
                echo 'Error: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
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
                    Presensi
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="schedule.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d"
                        width="24" height="24">
                        <path
                            d="M19 4h-1V3c0-.55-.45-1-1-1s-1 .45-1 1v1H8V3c0-.55-.45-1-1-1s-1 .45-1 1v1H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM9 14H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2zm-8 4H7v-2h2v2zm4 0h-2v-2h2v2zm4 0h-2v-2h2v2z" />
                    </svg>
                    Jadwal
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
            <!-- Page content-->
            <div class="container-fluid p-4">
                <div id="alertContainer">
                    <?php if (isset($_GET['status'])): ?>
                        <div class="alert <?php echo $_GET['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show"
                            role="alert">
                            <?php
                            if ($_GET['status'] === 'success') {
                                echo 'Pengajuan berhasil disubmit!';
                            } else {
                                echo 'Error: ' . (isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Unknown error');
                            }
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>
                <h1 class="text-3xl font-semibold mb-4">Cuti & Perizinan</h1>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex space-x-2">
                        <button class="bg-blue-500 text-white px-4 py-2 rounded" id="permitRequestBtn"
                            data-bs-toggle="modal" data-bs-target="#permitRequestModal">
                            Pengajuan Izin
                        </button>
                        <button class="bg-green-500 text-white px-4 py-2 rounded" id="leaveRequestBtn"
                            data-bs-toggle="modal" data-bs-target="#leaveRequestModal">
                            Pengajuan Cuti
                        </button>
                    </div>
                    <input type="text" id="searchDate" class="border border-gray-300 rounded px-2 py-1 w-full md:w-64"
                        placeholder="Cari Jadwal">
                </div>

                <div class="bg-white shadow rounded-lg p-4 mb-4">
                    <div class="flex items-center mb-4">
                        <label class="switch">
                            <input type="checkbox" id="tableSwitch" onchange="toggleTableswitch()">
                            <span class="slider"></span>
                        </label>
                        <span id="tableLabel" class="ml-2">Riwayat Izin</span>
                    </div>

                    <!-- IZIN TABEL -->
                    <div id="izinTable" class="table-container">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Tanggal</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Jenis Izin</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Keterangan</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($dataIzin)): ?>
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-center text-gray-500">Tidak ada data izin</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dataIzin as $row): ?>
                                        <tr>
                                            <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['tanggal']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php echo htmlspecialchars($row['jenis_izin']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php echo htmlspecialchars($row['keterangan']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="<?php echo $row['status'] == 'disetujui' ? 'bg-green-500' : ($row['status'] == 'pending' ? 'bg-yellow-500' : 'bg-red-500'); ?> text-white py-1 px-2 rounded">
                                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Leave History Table -->
                    <div id="cutiTable" class="table-container hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Tanggal Mulai</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Tanggal Selesai</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Durasi Cuti</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Keterangan</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($dataCuti)): ?>
                                    <tr>
                                        <td colspan="5" class="px-4 py-3 text-center text-gray-500">Tidak ada data cuti</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dataCuti as $row): ?>
                                        <tr>
                                            <td class="px-4 py-3 text-center">
                                                <?php echo htmlspecialchars($row['tanggal_mulai']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php echo htmlspecialchars($row['tanggal_selesai']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php echo htmlspecialchars($row['durasi_cuti']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php echo htmlspecialchars($row['keterangan']); ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="<?php echo $row['status'] == 'disetujui' ? 'bg-green-500' : ($row['status'] == 'pending' ? 'bg-yellow-500' : 'bg-red-500'); ?> text-white py-1 px-2 rounded">
                                                    <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal fade" id="permitRequestModal" tabindex="-1" aria-labelledby="permitRequestModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="permitRequestModalLabel">Pengajuan Izin</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="permitRequestForm" method="POST" action="schedule.php">
                                    <input type="hidden" name="form_type" value="izin">
                                    <div class="mb-3">
                                        <label for="permitDate" class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" id="permitDate" name="permitDate"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="permitType" class="form-label">Jenis Izin</label>
                                        <select class="form-select" id="permitType" name="permitType" required>
                                            <option value="" class="placeholder" hidden>Pilih Jenis Izin</option>
                                            <option value="dinas_luar">Dinas Luar</option>
                                            <option value="keperluan_pribadi">Keperluan Pribadi</option>
                                            <option value="sakit">Sakit</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="permitDescription" class="form-label">Keterangan</label>
                                        <textarea class="form-control" id="permitDescription" name="permitDescription"
                                            rows="3" required></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Submit Pengajuan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Request Modal -->
                <div class="modal fade" id="leaveRequestModal" tabindex="-1" aria-labelledby="leaveRequestModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title" id="leaveRequestModalLabel">Pengajuan Cuti</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="leaveRequestForm" method="POST" novalidate>
                                    <input type="hidden" name="form_type" value="cuti">
                                    <div class="mb-3">
                                        <label for="leaveStartDate" class="form-label">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="leaveStartDate"
                                            name="leaveStartDate" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="leaveEndDate" class="form-label">Tanggal Selesai</label>
                                        <input type="date" class="form-control" id="leaveEndDate" name="leaveEndDate"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="leaveDescription" class="form-label">Keterangan</label>
                                        <textarea class="form-control" id="leaveDescription" name="leaveDescription"
                                            rows="3" required></textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Submit Pengajuan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
                <script src="../../../assets/js/scripts.js"></script>

                <script>
                    $(document).ready(function () {
                        // Handle form izin submission

                        // Validasi tanggal cuti
                        $('#leaveStartDate, #leaveEndDate').on('change blur', function () {
                            var startDate = new Date($('#leaveStartDate').val());
                            var endDate = new Date($('#leaveEndDate').val());

                            console.log("Validasi perubahan tanggal - Start Date:", startDate);
                            console.log("Validasi perubahan tanggal - End Date:", endDate);

                            if (startDate > endDate) {
                                alert('Tanggal selesai tidak boleh lebih awal dari tanggal mulai!');
                                $('#leaveEndDate').val('');
                            }
                        });

                        // Reset form ketika modal ditutup
                        $('#permitModal').on('hidden.bs.modal', function () {
                            $('#permitRequestForm')[0].reset();
                        });

                        $('#leaveModal').on('hidden.bs.modal', function () {
                            $('#leaveRequestForm')[0].reset();
                        });

                        // Validasi input tanggal tidak boleh kurang dari hari ini
                        var today = new Date().toISOString().split('T')[0];
                        $('#permitDate').attr('min', today);
                        $('#leaveStartDate').attr('min', today);
                        $('#leaveEndDate').attr('min', today);
                    });

                    // Function untuk memformat tanggal
                    function formatDate(date) {
                        var d = new Date(date),
                            month = '' + (d.getMonth() + 1),
                            day = '' + d.getDate(),
                            year = d.getFullYear();

                        if (month.length < 2) month = '0' + month;
                        if (day.length < 2) day = '0' + day;

                        return [year, month, day].join('-');
                    }

                    // Function untuk menghitung durasi hari
                    function calculateDuration(startDate, endDate) {
                        const start = new Date(startDate);
                        const end = new Date(endDate);

                        // Pastikan tanggal akhir tidak lebih awal dari tanggal awal
                        if (end < start) {
                            return 0;
                        }

                        const diffTime = end.getTime() - start.getTime();
                        const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;
                        return diffDays;
                    }

                    // Tambahan validasi untuk form izin
                    $('#permitType').on('change', function () {
                        if ($(this).val() === 'lainnya') {
                            $('#permitDescription').attr('placeholder', 'Mohon jelaskan detail keperluan Anda');
                        } else {
                            $('#permitDescription').attr('placeholder', 'Tambahkan keterangan jika diperlukan');
                        }
                    });

                    // Validasi form sebelum submit
                    function validateForm(formType) {
                        if (formType === 'izin') {
                            if (!$('#permitDate').val()) {
                                alert('Tanggal harus diisi!');
                                return false;
                            }
                            if (!$('#permitType').val()) {
                                alert('Jenis izin harus dipilih!');
                                return false;
                            }
                            if (!$('#permitDescription').val().trim()) {
                                alert('Keterangan harus diisi!');
                                return false;
                            }
                        } else if (formType === 'cuti') {
                            if (!$('#leaveStartDate').val()) {
                                alert('Tanggal mulai harus diisi!');
                                return false;
                            }
                            if (!$('#leaveEndDate').val()) {
                                alert('Tanggal selesai harus diisi!');
                                return false;
                            }
                            if (!$('#leaveDescription').val().trim()) {
                                alert('Keterangan harus diisi!');
                                return false;
                            }
                        }
                        return true;
                    }
                </script>

                <script>
                    function toggleTableswitch() {
                        const isChecked = document.getElementById('tableSwitch').checked;
                        const tableLabel = document.getElementById('tableLabel');

                        if (isChecked) {
                            document.getElementById('izinTable').classList.add('hidden');
                            document.getElementById('cutiTable').classList.remove('hidden');
                            tableLabel.textContent = "Riwayat Cuti";
                        } else {
                            document.getElementById('izinTable').classList.remove('hidden');
                            document.getElementById('cutiTable').classList.add('hidden');
                            tableLabel.textContent = "Riwayat Izin";
                        }
                    }

                    // Sidebar toggle functionality
                    const sidebarToggle = document.getElementById('sidebarToggle');
                    const sidebarWrapper = document.getElementById('sidebar-wrapper');

                    sidebarToggle.addEventListener('click', function () {
                        sidebarWrapper.classList.toggle('collapsed');
                    });

                    function showAlert(message, type = 'success') {
                        const alertContainer = document.getElementById('alertContainer');
                        const alertDiv = document.createElement('div');
                        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
                        alertDiv.role = 'alert';

                        alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

                        alertContainer.appendChild(alertDiv);

                        setTimeout(() => {
                            const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                            alert.close();
                        }, 5000);
                    }
                </script>
                <script>
                    document.getElementById('permitRequestForm').addEventListener('submit', function (e) {
                        e.preventDefault();

                        const formData = new FormData(this);

                        fetch('permit.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json()) // Ubah ke json()
                            .then(data => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('permitRequestModal'));
                                modal.hide();

                                showAlert(data.message || 'Pengajuan izin berhasil disubmit!', data.status === 'success' ? 'success' : 'danger');

                                // Tambah delay sebelum reload
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showAlert('Terjadi kesalahan saat mengirim pengajuan', 'danger');
                            });
                    });

                    document.getElementById('leaveRequestForm').addEventListener('submit', function (e) {
                        e.preventDefault();

                        const formData = new FormData(this);

                        fetch('permit.php', {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.json()) // Ubah ke json()
                            .then(data => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('leaveRequestModal'));
                                modal.hide();

                                showAlert(data.message || 'Pengajuan cuti berhasil disubmit!', data.status === 'success' ? 'success' : 'danger');

                                // Tambah delay sebelum reload
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showAlert('Terjadi kesalahan saat mengirim pengajuan', 'danger');
                            });
                    });

                    // Handle URL parameters for alerts on page load
                    document.addEventListener('DOMContentLoaded', function () {
                        const urlParams = new URLSearchParams(window.location.search);
                        const status = urlParams.get('status');
                        const message = urlParams.get('message');

                        if (status === 'success') {
                            showAlert('Pengajuan berhasil disubmit!', 'success');
                        } else if (status === 'error') {
                            showAlert(`Error: ${message || 'Terjadi kesalahan'}`, 'danger');
                        }
                    });
                </script>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const searchInput = document.getElementById('searchDate');
                        const tableRows = document.querySelectorAll('table tbody tr');

                        searchInput.addEventListener('input', function () {
                            const searchTerm = this.value.trim().toLowerCase();

                            tableRows.forEach(row => {
                                const dateCell = row.querySelector('td:first-child');
                                if (dateCell) {
                                    const date = dateCell.textContent.trim().toLowerCase();
                                    if (date.includes(searchTerm)) {
                                        row.style.display = '';
                                    } else {
                                        row.style.display = 'none';
                                    }
                                }
                            });
                        });
                    });
                </script>
</body>

</html>