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

function getEmployeeSchedule($pdo, $userId)
{
    try {
        // Get employee data
        $stmt = $pdo->prepare("SELECT id, hari_libur FROM pegawai WHERE user_id = ?");
        $stmt->execute([$userId]);
        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$employee) {
            return null;
        }

        // Get shift data
        $stmt = $pdo->prepare("
            SELECT s.nama_shift, s.jam_masuk, s.jam_keluar 
            FROM jadwal_shift js 
            JOIN shift s ON js.shift_id = s.id 
            WHERE js.pegawai_id = ? AND js.status = 'aktif' 
            LIMIT 1
        ");
        $stmt->execute([$employee['id']]);
        $shift = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shift) {
            return null;
        }

        // Create weekly schedule
        $weekDays = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
        $schedule = [];

        foreach ($weekDays as $day) {
            $schedule[$day] = [
                'status' => ($day === $employee['hari_libur']) ? 'Libur' : 'Masuk',
                'shift_name' => ($day === $employee['hari_libur']) ? '-' : $shift['nama_shift'],
                'jam_masuk' => ($day === $employee['hari_libur']) ? '-' : $shift['jam_masuk'],
                'jam_keluar' => ($day === $employee['hari_libur']) ? '-' : $shift['jam_keluar']
            ];
        }

        return $schedule;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return null;
    } catch (Exception $e) {
        error_log("General error: " . $e->getMessage());
        return null;
    }
}

// Initialize PDO connection from auth.php (assuming it's defined there)
try {
    // Get schedule using the user's session ID
    $schedule = isset($_SESSION['id']) ? getEmployeeSchedule($pdo, $_SESSION['id']) : null;
} catch (Exception $e) {
    error_log("Error getting schedule: " . $e->getMessage());
    $schedule = null;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>Si Hadir - Jadwal</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="../../../assets/favicon.ico" />
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="../../../assets/css/styles.css" rel="stylesheet" />
    <!-- Link Google Fonts untuk Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        #sidebar-wrapper .sidebar-heading strong {
            font-family: 'Poppins', sans-serif;
            /* Menggunakan font Poppins hanya untuk Si Hadir */
            font-weight: 900;
            /* Menebalkan tulisan */
            font-size: 28px;
            /* Membesarkan ukuran font */
        }

        /* Sidebar Styles */
        #sidebar-wrapper .sidebar-heading strong {
            font-family: 'Poppins', sans-serif;
            font-weight: 900;
            font-size: 28px;
        }

        .navbar-toggler {
            display: none;
        }

        #navbarSupportedContent {
            display: flex !important;
        }

        /* Main Content Styles */
        #page-content-wrapper {
            background: #f0f2f5;
        }

        .schedule-container {
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 2rem auto;
        }

        .schedule-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .schedule-header h2 {
            color: white;
            font-size: 1.75rem;
            font-weight: 600;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .schedule-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 60%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .table-container {
            padding: 2rem;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }

        th {
            padding: 1rem;
            text-align: left;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
        }

        td {
            padding: 1.25rem 1rem;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        tr:hover td {
            background: #f1f5f9;
            transform: scale(1.01);
        }

        .day-cell {
            font-weight: 600;
            color: #1f2937;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .status-masuk {
            background: #dcfce7;
            color: #166534;
        }

        .status-libur {
            background: #fee2e2;
            color: #991b1b;
        }

        .shift-name {
            color: #4f46e5;
            font-weight: 500;
        }

        .time-cell {
            font-family: 'Poppins', monospace;
            color: #374151;
            font-size: 0.95rem;
        }

        tr td:first-child {
            border-radius: 12px 0 0 12px;
        }

        tr td:last-child {
            border-radius: 0 12px 12px 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .schedule-container {
                margin: 1rem;
            }

            .schedule-header {
                padding: 1.5rem;
            }

            .table-container {
                padding: 1rem;
            }

            th,
            td {
                padding: 0.75rem;
            }

            .status-badge {
                padding: 0.35rem 0.75rem;
            }
        }

        /* Sidebar icon */
        .sidebar-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
            vertical-align: middle;
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 0;
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

            <!-- Page content -->
            <div class="container-fluid p-4">
                <div class="schedule-container">
                    <div class="schedule-header">
                        <h2>Jadwal Kerja Mingguan</h2>
                    </div>
                    <?php if ($schedule): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Hari</th>
                                        <th>Status</th>
                                        <th>Shift</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Keluar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedule as $day => $info): ?>
                                        <tr>
                                            <td class="day-cell"><?= ucfirst($day) ?></td>
                                            <td>
                                                <span
                                                    class="status-badge <?= $info['status'] === 'Libur' ? 'status-libur' : 'status-masuk' ?>">
                                                    <?= $info['status'] ?>
                                                </span>
                                            </td>
                                            <td class="shift-name"><?= $info['shift_name'] ?></td>
                                            <td class="time-cell"><?= $info['jam_masuk'] ?></td>
                                            <td class="time-cell"><?= $info['jam_keluar'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Tidak ada jadwal yang tersedia.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../assets/js/scripts.js"></script>
    <script>
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');

        sidebarToggle.addEventListener('click', function () {
            sidebarWrapper.classList.toggle('collapsed');
        });
    </script>
</body>

</html>