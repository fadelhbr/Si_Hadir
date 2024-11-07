<?php
session_start();

include '../../../app/auth/auth.php';

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

// Query untuk mengambil data absensi user yang sedang login
$query = "
    SELECT 
        u.nama_lengkap,  -- Mengambil nama dari tabel users
        s.nama_shift,
        CONCAT(s.jam_masuk, ' - ', s.jam_keluar) as jadwal_shift,
        a.waktu_masuk,
        a.waktu_keluar,
        a.status_kehadiran,
        DATE(a.tanggal) as tanggal
    FROM absensi a
    JOIN pegawai p ON a.pegawai_id = p.id
    JOIN users u ON p.user_id = u.id  -- Join dengan tabel users
    JOIN jadwal_shift js ON a.jadwal_shift_id = js.id
    JOIN shift s ON js.shift_id = s.id
    WHERE p.user_id = :user_id
    ORDER BY a.tanggal DESC
";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $_SESSION['id']]);
    $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo "Terjadi kesalahan saat mengambil data: " . $e->getMessage();
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

        .card {
            border: none;
            border-radius: 10px;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
        }

        .badge {
            padding: 8px 12px;
            font-size: 0.75rem;
            font-weight: 500;
            border-radius: 6px;
            text-transform: capitalize;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #000;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .bg-primary {
            background-color: #007bff !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, .075);
            transition: background-color 0.2s ease-in-out;
        }

        h1 {
            color: #2c3e50;
            font-weight: 600;
        }

        .table-responsive {
            border-radius: 10px;
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
                    <span>Riwayat kehadiran</span>
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
            <div class="container-fluid">
                <h1 class="mt-4 mb-4">Riwayat Kehadiran</h1>
                <div class="card shadow">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jadwal Shift</th>
                                        <th>Waktu Masuk</th>
                                        <th>Waktu Keluar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendances)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4">Tidak ada data kehadiran</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendances as $attendance): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d-m-Y', strtotime($attendance['tanggal']))) ?>
                                                </td>
                                                <td><?= htmlspecialchars($attendance['jadwal_shift']) ?></td>
                                                <td><?= $attendance['waktu_masuk'] ? htmlspecialchars(date('H:i', strtotime($attendance['waktu_masuk']))) : '-' ?>
                                                </td>
                                                <td><?= $attendance['waktu_keluar'] ? htmlspecialchars(date('H:i', strtotime($attendance['waktu_keluar']))) : '-' ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= getBadgeClass($attendance['status_kehadiran']) ?>">
                                                        <?= htmlspecialchars(ucfirst($attendance['status_kehadiran'])) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
            <script src="../../../assets/js/scripts.js"></script>

            <script>

                // Sidebar toggle functionality
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebarWrapper = document.getElementById('sidebar-wrapper');

                sidebarToggle.addEventListener('click', function () {
                    sidebarWrapper.classList.toggle('collapsed');
                });

                <?php
                // Fungsi untuk menentukan class badge berdasarkan status kehadiran
                function getBadgeClass($status)
                {
                    switch ($status) {
                        case 'hadir':
                            return 'bg-success';
                        case 'terlambat':
                            return 'bg-warning';
                        case 'sakit':
                            return 'bg-info';
                        case 'izin':
                            return 'bg-primary';
                        case 'alpha':
                            return 'bg-danger';
                        case 'cuti':
                            return 'bg-secondary';
                        default:
                            return 'bg-secondary';
                    }
                }
                ?>

                // Di bagian script
                function refreshTable() {
                    location.reload();
                }

                // Refresh setiap 5 menit
                setInterval(refreshTable, 300000);
            </script>
            <style>
                .badge {
                    padding: 8px 12px;
                    font-size: 12px;
                    border-radius: 4px;
                    text-transform: capitalize;
                }

                .table th {
                    background-color: #343a40;
                    color: white;
                }

                .table-responsive {
                    margin-top: 20px;
                }
            </style>
</body>

</html>