<?php
session_start();

require_once '../../../app/auth/auth.php';

try {
    $conn = new PDO("mysql:host=localhost;dbname=si_hadir", "root", "abc54321");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../../../login.php');
    exit;
}

// Check if the user role is employee
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'owner') {
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
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Si Hadir - Dashboard</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="../../../assets/icon/favicon.ico" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="../../../assets/css/styles.css" rel="stylesheet" />
        <!-- Link Google Fonts untuk Poppins -->
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        
        <style>
            /* Mengatur font Poppins hanya untuk <strong> di dalam sidebar-heading */
            #sidebar-wrapper .sidebar-heading strong {
                font-family: 'Poppins', sans-serif; /* Menggunakan font Poppins hanya untuk Si Hadir */
                font-weight: 900; /* Menebalkan tulisan */
                font-size: 28px;  /* Membesarkan ukuran font */
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
            </style>

<style>
    /* Style untuk switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
        margin: 0 10px; /* Menambahkan margin di sekitar switch */
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

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .hidden {
        display: none;
    }
</style>

<style>
    .btn-izin {
        background-color: #007bff; /* Warna biru */
        color: white;
        transition: background-color 0.3s, color 0.3s;
    }
    
    .btn-cuti {
        background-color: #6c757d; /* Warna abu-abu */
        color: white;
        transition: background-color 0.3s, color 0.3s;
    }

    .btn-active {
        background-color: #0056b3 !important; /* Warna tombol aktif */
        color: white !important;
    }
</style>


        
    </head>
    <body class="bg-blue-50">
        <div class="d-flex" id="wrapper">
            <!-- Sidebar-->
            <div class="border-end-0 bg-white" id="sidebar-wrapper">
              <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
                <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="dashboard.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                                <path d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z"/>
                            </svg>
                            Dashboard
                        </a>    
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="attendanceMonitor.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                                <path d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z"/>
                            </svg>
                            Monitor Absensi
                        </a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="schedule.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d">
                                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/>
                            </svg>
                            Jadwal Shift
                        </a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="manageMember.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d" width="24" height="24">
                                <path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 2.02 1.97 3.45V20h6v-3.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                            Manajemen Staff
                        </a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="permit.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                                <path d="M160-200v-440 440-15 15Zm0 80q-33 0-56.5-23.5T80-200v-440q0-33 23.5-56.5T160-720h160v-80q0-33 23.5-56.5T400-880h160q33 0 56.5 23.5T640-800v80h160q33 0 56.5 23.5T880-640v171q-18-13-38-22.5T800-508v-132H160v440h283q3 21 9 41t15 39H160Zm240-600h160v-80H400v80ZM720-40q-83 0-141.5-58.5T520-240q0-83 58.5-141.5T720-440q83 0 141.5 58.5T920-240q0 83-58.5 141.5T720-40Zm20-208v-112h-40v128l86 86 28-28-74-74Z"/>
                            </svg>
                            Cuti & Perizinan
                        </a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="report.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" width="24" height="24" stroke="#6c757d" fill="none" stroke-width="2">
                                <path d="M6 2C5.44772 2 5 2.44772 5 3V21C5 21.5523 5.44772 22 6 22H18C18.5523 22 19 21.5523 19 21V7L14 2H6Z" />
                                <path d="M13 2V7H19" />
                            </svg>
                            Laporan
                        </a>
                        <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0" href="logout.php">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon" fill="#6c757d">
                                <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/>
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
    <h1 class="text-3xl font-semibold mb-4">Cuti & Perizinan</h1>
    <div class="flex flex-col md:flex-row items-center justify-between mb-4 space-y-2 md:space-y-0 md:space-x-2">
        <div class="flex space-x-2">
            <button class="bg-gray-300 text-gray-700 px-4 py-2 rounded" id="historyToggle" data-bs-toggle="modal" data-bs-target="#approvalModal">Riwayat Persetujuan</button>
        </div>
        <input type="text" class="border border-gray-300 rounded px-2 py-1 w-full md:w-64" placeholder="Cari nama/email/kode staff">
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white shadow-50 p-6 rounded-lg shadow min-w-[300px] h-[200px] flex flex-col justify-center items-center">
        <h3 class="text-black text-center text-lg font-sans font-bold mt-5">SEDANG DALAM PERMOHONAN</h3>
        <p class="text-3xl font-extrabold text-yellow-500 text-center font-mono mt-4">1</p>
        <canvas id="pendingChart" class="h-20"></canvas>
    </div>
    <div class="bg-white shadow-50 p-6 rounded-lg shadow min-w-[300px] h-[200px] flex flex-col justify-center items-center">
        <h3 class="text-black text-center text-lg font-sans font-bold mt-5">TELAH DIJAWAB</h3>
        <p class="text-3xl font-extrabold text-green-500 text-center font-mono mt-4">4</p>
        <canvas id="approvedChart" class="h-20"></canvas>
    </div>
</div>


    
    <div class="bg-white shadow rounded-lg p-4 mb-4">
    <!-- Tombol switch untuk beralih antara tabel -->
    <div class="flex items-center mb-4">
        <label class="switch">
            <input type="checkbox" id="tableSwitch" onchange="toggleTable()">
            <span class="slider"></span>
        </label>
        <span id="tableLabel" class="ml-2">Tabel Izin</span> <!-- Label yang ditampilkan -->
    </div>
    <div id="izinTable" class="table-container">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Nama Staff</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Tanggal</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Jenis Izin</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Bukti Pendukung</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
                // Mengambil data dari perizinan_view
                $sql = "SELECT * FROM perizinan_view WHERE status = 'pending'";
                $stmt = $pdo->query($sql);
                $dataIzin = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($dataIzin as $row): ?>
                    <tr>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['Nama_Staff']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['tanggal']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['jenis_izin']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['bukti_pendukung']); ?></td>
                        </td>
                        <td class="text-center">
                            <button class="bg-green-500 text-white py-1 px-2 rounded">Disetujui</button>
                            <button class="bg-red-500 text-white py-1 px-2 rounded">Ditolak</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="cutiTable" class="table-container hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Nama Staff</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Tanggal Mulai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Tanggal Selesai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Durasi Cuti</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Keterangan</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
                // Mengambil data dari cuti_view
                $sql = "SELECT * FROM cuti_view WHERE status = 'pending'";
                $stmt = $pdo->query($sql);
                $dataCuti = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($dataCuti as $row): ?>
                    <tr>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['nama_staff']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['tanggal_mulai']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['tanggal_selesai']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['durasi_cuti']); ?></td>
                        <td class="text-center px-4 py-3"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                        <td class="text-center px-4 py-3">
                            <button class="bg-green-500 text-white py-1 px-2 rounded">Disetujui</button>
                            <button class="bg-red-500 text-white py-1 px-2 rounded">Ditolak</button>
                        </td>
                    </tr>
                <?php endforeach; ?>            
            </tbody>
        </table>
    </div>
</div>


<!-- Modal for Approval History -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approvalModalLabel">Riwayat Persetujuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="tableSelect" class="form-label">Pilih Tabel:</label>
                     <select id="tableSelect" class="form-select" onchange="toggleTable()" style="margin-bottom: 20px;">
                        <option value="izin">Tabel Izin</option>
                        <option value="cuti">Tabel Cuti</option>
                    </select>
                    <label for="approvalFilter" class="form-label">Tampilkan:</label>
                    <select id="approvalFilter" class="form-select" onchange="filterTable()">
                        <option value="all">Semua</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                </div>

                <!-- Tombol Select All dan Deselect All -->
                
                <div class="mb-3">
                    <button class="btn btn-primary" onclick="selectAll()">Pilih Semua</button>
                    <button class="btn btn-secondary" onclick="deselectAll()">Batal Pilih Semua</button>
                    <button class="btn btn-danger" onclick="confirmDelete()">Hapus Data</button> <!-- Tombol Hapus -->
                </div>

                <!-- Tabel Izin -->
                    <div id="izinHistoryTable" class="table-container">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Nama Staff</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Tanggal</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Jenis Izin</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Keterangan</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Pilih</th>
                                </tr>
                            </thead>
                            <tbody>
        
                            <?php
                            // Mengambil data dari perizinan_view dengan status disetujui atau ditolak
                            $sql = "SELECT * FROM perizinan_view WHERE status IN ('disetujui', 'ditolak')";
                            $stmt = $pdo->query($sql);
                            $dataIzin = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($dataIzin as $row): ?>
                                <tr>
                                    <td class="px-4 py-3 text-center">Nama Staff</td>
                                    <td class="px-4 py-3 text-center">Tanggal</td>
                                    <td class="px-4 py-3 text-center">Jenis Izin</td>
                                    <td class="px-4 py-3 text-center">Keterangan</td>
                                    <td class="px-4 py-3 text-center">Status</td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" class="row-checkbox">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tabel Cuti -->
                <div id="cutiHistoryTable" class="table-container hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Nama Staff</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Tanggal Mulai</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Tanggal Selesai</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Durasi Cuti</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Keterangan</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider border-r border-gray-200">Pilih</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php
                            // Mengambil data dari cuti_view dengan status disetujui atau ditolak
                            $sql = "SELECT * FROM cuti_view WHERE status IN ('disetujui', 'ditolak')";
                            $stmt = $pdo->query($sql);
                            $dataCuti = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($dataCuti as $row): ?>
                                <tr>
                                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['nama_staff']); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['tanggal_mulai']); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['tanggal_selesai']); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['durasi_cuti']); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['keterangan']); ?></td>
                                    <td class="px-4 py-3 text-center"><?php echo htmlspecialchars($row['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    // Fungsi untuk membuka modal "Riwayat Persetujuan" dengan tombol izin sebagai default
    function openApprovalModal() {
        // Set tombol "Tabel Izin" sebagai aktif secara default
        document.getElementById("izinButton").classList.add("btn-active");
        document.getElementById("cutiButton").classList.remove("btn-active");

        // Menampilkan tabel izin dan menyembunyikan tabel cuti
        document.getElementById("izinHistoryTable").style.display = "block";
        document.getElementById("cutiHistoryTable").style.display = "none";

        // Buka modal menggunakan Bootstrap
        const approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
        approvalModal.show();
    }

    function showIzinTable() {
        document.getElementById("cutiButton").classList.remove("btn-active");
        document.getElementById("izinButton").classList.add("btn-active");
        
        document.getElementById("izinHistoryTable").style.display = "block";
        document.getElementById("cutiHistoryTable").style.display = "none";
    }

    function showCutiTable() {
        document.getElementById("izinButton").classList.remove("btn-active");
        document.getElementById("cutiButton").classList.add("btn-active");
        
        document.getElementById("cutiHistoryTable").style.display = "block";
        document.getElementById("izinHistoryTable").style.display = "none";
    }
</script>

    <script>
function filterTable() {
    const filterValue = document.getElementById("approvalFilter").value;
    const rows = document.querySelectorAll("#izinHistoryTable tbody tr");

    rows.forEach(row => {
        const statusCell = row.querySelector("td:last-child");
        const statusText = statusCell.textContent.trim();

        if (filterValue === "all" || (filterValue === "approved" && statusText === "approved") || (filterValue === "rejected" && statusText === "rejected")) {
            row.style.display = ""; // Tampilkan
        } else {
            row.style.display = "none"; // Sembunyikan
        }
    });
}
</script>

<script>
    function toggleTable() {
        const isChecked = document.getElementById('tableSwitch').checked;
        const tableLabel = document.getElementById('tableLabel');
        
        // Mengubah label teks sesuai dengan tabel yang aktif
        if (isChecked) {
            document.getElementById('izinTable').classList.add('hidden');
            document.getElementById('cutiTable').classList.remove('hidden');
            tableLabel.textContent = "Tabel Cuti"; // Ubah label menjadi Tabel Cuti
        } else {
            document.getElementById('izinTable').classList.remove('hidden');
            document.getElementById('cutiTable').classList.add('hidden');
            tableLabel.textContent = "Tabel Izin"; // Ubah label menjadi Tabel Izin
        }
    }
    </script>

<script>
function updateStatus(id, status) {
    // Cari elemen tombol berdasarkan ID
    const actionButtons = document.getElementById(`action-buttons-${id}`);
    
    // Mengubah isi tombol menjadi status yang sesuai
    if (status === 'disetujui') {
        actionButtons.innerHTML = '<div class="w-24 h-10 bg-green-300 text-green-700 rounded-full text-sm flex items-center justify-center">Disetujui</div>';
    } else if (status === 'ditolak') {
        actionButtons.innerHTML = '<div class="w-24 h-10 bg-red-300 text-red-700 rounded-full text-sm flex items-center justify-center">Ditolak</div>';
    }
}
</script>

<script>
function filterTable() {
    const filter = document.getElementById('approvalFilter').value;
    const rows = document.querySelectorAll('#approvalTable tbody tr');

    rows.forEach(row => {
        if (filter === 'all') {
            row.style.display = '';
        } else if (filter === 'approved' && row.classList.contains('approved')) {
            row.style.display = '';
        } else if (filter === 'rejected' && row.classList.contains('rejected')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function confirmDelete(button) {
    const row = button.closest('tr');
    const staffName = row.cells[0].textContent;

    if (confirm(`Apakah Anda yakin ingin menghapus data untuk ${staffName}?`)) {
        row.remove();
    }
}

function toggleTable() {
    const selectedTable = document.getElementById('tableSelect').value;
    const izinTable = document.getElementById('izinHistoryTable');
    const cutiTable = document.getElementById('cutiHistoryTable');

    if (selectedTable === 'izin') {
        izinTable.classList.remove('hidden');
        cutiTable.classList.add('hidden');
    } else if (selectedTable === 'cuti') {
        cutiTable.classList.remove('hidden');
        izinTable.classList.add('hidden');
    }
}

function selectAll() {
    document.querySelectorAll('.row-checkbox').forEach(checkbox => checkbox.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.row-checkbox').forEach(checkbox => checkbox.checked = false);
}
function confirmDelete() {
        // Ambil semua checkbox yang terpilih
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        
        // Jika tidak ada checkbox yang dipilih, tampilkan notifikasi
        if (checkboxes.length === 0) {
            alert('Silakan pilih data yang ingin dihapus.');
            return;
        }

        // Tampilkan konfirmasi penghapusan
        const confirmation = confirm(`Anda yakin ingin menghapus ${checkboxes.length} data yang dipilih?`);
        if (confirmation) {
            // Logika untuk menghapus data
            checkboxes.forEach(checkbox => {
                // Anda dapat menambahkan logika untuk menghapus data dari server
                // Misalnya, panggil AJAX atau redirect ke PHP script
                checkbox.closest('tr').remove(); // Hapus baris dari tabel
            });
            alert('Data berhasil dihapus.');
        }
    }

</script>


        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="../../../assets/js/scripts.js "></script>

        <!-- Custom JS to handle sidebar toggle -->
        <script>
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');

            sidebarToggle.addEventListener('click', function () {
                sidebarWrapper.classList.toggle('collapsed');
            });
        </script>
    </body>
</html>