<?php
session_start();
require_once '../../../app/auth/auth.php';

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

// Function to get attendance statistics for the selected date and shift
function getAttendanceStats($pdo, $date, $shift = 'all')
{
    $params = ['date' => $date];
    $query = "SELECT status_kehadiran, COUNT(*) as count 
              FROM absensi a
              LEFT JOIN jadwal_shift js ON (a.pegawai_id = js.pegawai_id AND DATE(a.tanggal) = js.tanggal)
              WHERE DATE(a.tanggal) = :date";

    if ($shift !== 'all' && is_numeric($shift)) {
        $query .= " AND js.shift_id = :shift_id";
        $params['shift_id'] = $shift;
    }

    $query .= " GROUP BY status_kehadiran";

    $stats = array(
        'hadir' => 0,
        'alpha' => 0,
        'izin' => 0,
        'terlambat' => 0,
        'cuti' => 0,
        'pulang_dahulu' => 0
    );

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($stats[$row['status_kehadiran']])) {
                $stats[$row['status_kehadiran']] = $row['count'];
            }
        }
    } catch (PDOException $e) {
        error_log("Database error in getAttendanceStats: " . $e->getMessage());
    }

    return $stats;
}

// Function to get detailed attendance records
function getAttendanceRecords($pdo, $date, $search = '', $shift = 'all')
{
    $params = ['date' => $date];
    $query = "SELECT 
                a.*, 
                u.nama_lengkap as nama_pegawai,
                s.nama_shift,
                s.jam_masuk,
                s.jam_keluar
              FROM absensi a 
              LEFT JOIN pegawai p ON a.pegawai_id = p.id
              LEFT JOIN users u ON p.user_id = u.id
              LEFT JOIN jadwal_shift js ON (a.pegawai_id = js.pegawai_id AND DATE(a.tanggal) = js.tanggal)
              LEFT JOIN shift s ON js.shift_id = s.id
              WHERE DATE(a.tanggal) = :date";

    if (!empty($search)) {
        $query .= " AND (u.nama_lengkap LIKE :search OR u.email LIKE :search)";
        $params['search'] = "%$search%";
    }

    if ($shift !== 'all' && is_numeric($shift)) {
        $query .= " AND js.shift_id = :shift_id";
        $params['shift_id'] = $shift;
    }

    $query .= " ORDER BY a.waktu_masuk ASC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Database error in getAttendanceRecords: " . $e->getMessage());
        return $pdo->prepare("SELECT 1 WHERE 1=0");
    }
}

// Function to get shifts for the dropdown
function getShifts($pdo)
{
    try {
        $query = "SELECT id, nama_shift, TIME_FORMAT(jam_masuk, '%H:%i') as jam_masuk, 
                         TIME_FORMAT(jam_keluar, '%H:%i') as jam_keluar 
                  FROM shift 
                  ORDER BY jam_masuk";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getShifts: " . $e->getMessage());
        return array();
    }
}

// Input validation
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedShift = isset($_GET['shift']) ? $_GET['shift'] : 'all';

// Get the statistics and records with error handling
try {
    $stats = getAttendanceStats($pdo, $selectedDate, $selectedShift);
    $records = getAttendanceRecords($pdo, $selectedDate, $searchQuery, $selectedShift);
    $shifts = getShifts($pdo);

    // Format date for display
    $displayDate = date('l, d F Y', strtotime($selectedDate));
} catch (Exception $e) {
    error_log("General error in attendance monitor: " . $e->getMessage());
    $stats = array('hadir' => 0, 'alpha' => 0, 'izin' => 0, 'terlambat' => 0, 'cuti' => 0, 'pulang_dahulu' => 0);
    $records = $pdo->prepare("SELECT 1 WHERE 1=0");
    $shifts = array();
    $displayDate = date('l, d F Y');
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

<body class="bg-blue-50">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar-->
        <div class="border-end-0 bg-white" id="sidebar-wrapper">
            <div class="sidebar-heading border-bottom-0"><strong>Si Hadir</strong></div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="dashboard.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M520-600v-240h320v240H520ZM120-440v-400h320v400H120Zm400 320v-400h320v400H520Zm-400 0v-240h320v240H120Zm80-400h160v-240H200v240Zm400 320h160v-240H600v240Zm0-480h160v-80H600v80ZM200-200h160v-80H200v80Zm160-320Zm240-160Zm0 240ZM360-280Z" />
                    </svg>
                    Dashboard
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="attendanceMonitor.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="sidebar-icon"
                        fill="#6c757d">
                        <path
                            d="M160-80q-33 0-56.5-23.5T80-160v-440q0-33 23.5-56.5T160-680h200v-120q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v120h200q33 0 56.5 23.5T880-600v440q0 33-23.5 56.5T800-80H160Zm0-80h640v-440H600q0 33-23.5 56.5T520-520h-80q-33 0-56.5-23.5T360-600H160v440Zm80-80h240v-18q0-17-9.5-31.5T444-312q-20-9-40.5-13.5T360-330q-23 0-43.5 4.5T276-312q-17 8-26.5 22.5T240-258v18Zm320-60h160v-60H560v60Zm-200-60q25 0 42.5-17.5T420-420q0-25-17.5-42.5T360-480q-25 0-42.5 17.5T300-420q0 25 17.5 42.5T360-360Zm200-60h160v-60H560v60ZM440-600h80v-200h-80v200Zm40 220Z" />
                    </svg>
                    Monitor Absensi
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="schedule.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d">
                        <path
                            d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z" />
                    </svg>
                    Jadwal Shift
                </a>
                <a class="list-group-item list-group-item-action list-group-item-light p-3 border-bottom-0"
                    href="manageMember.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" fill="#6c757d"
                        width="24" height="24">
                        <path
                            d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V20h14v-3.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 2.02 1.97 3.45V20h6v-3.5c0-2.33-4.67-3.5-7-3.5z" />
                    </svg>
                    Manajemen Staff
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
                    href="report.php">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="sidebar-icon" width="24"
                        height="24" stroke="#6c757d" fill="none" stroke-width="2">
                        <path
                            d="M6 2C5.44772 2 5 2.44772 5 3V21C5 21.5523 5.44772 22 6 22H18C18.5523 22 19 21.5523 19 21V7L14 2H6Z" />
                        <path d="M13 2V7H19" />
                    </svg>
                    Laporan
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
                <h1 class="text-3xl font-semibold mb-4">Monitor Absensi</h1>

                <!-- Search and filter form -->
                <form class="flex items-center justify-between mb-4" method="GET">
                    <div class="flex space-x-2">
                        <input type="date" name="date" class="border border-gray-300 rounded px-2 py-1"
                            value="<?php echo $selectedDate; ?>" onchange="this.form.submit()">
                        <select name="shift" class="border border-gray-300 rounded px-2 py-1"
                            onchange="this.form.submit()">
                            <option value="all" <?php echo ($selectedShift === 'all') ? 'selected' : ''; ?>>Semua Shift
                            </option>
                            <?php foreach ($shifts as $shift): ?>
                                <option value="<?php echo $shift['id']; ?>" <?php echo ($selectedShift == $shift['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($shift['nama_shift']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="text" name="search" id="searchInput" class="border border-gray-300 rounded px-2 py-1"
                        placeholder="Cari nama/email/kode staff" value="<?php echo htmlspecialchars($searchQuery); ?>">
                </form>

                <!-- Status cards -->
                <div class="bg-white shadow rounded-lg p-4 mb-4">
                    <h2 class="text-lg font-semibold mb-4">Status Kehadiran Staff Hari Ini (<?php echo $displayDate; ?>)
                    </h2>
                    <div class="grid grid-cols-6 gap-6 text-center">
                        <div class="status-card bg-green-50 p-4 rounded-lg">
                            <p class="text-sm">Hadir Tepat Waktu</p>
                            <p class="text-2xl font-bold text-green-500"><?php echo $stats['hadir']; ?></p>
                        </div>
                        <div class="status-card bg-red-50 p-4 rounded-lg">
                            <p class="text-sm">Tidak Masuk</p>
                            <p class="text-2xl font-bold text-red-500"><?php echo $stats['alpha']; ?></p>
                        </div>
                        <div class="status-card bg-purple-50 p-4 rounded-lg">
                            <p class="text-sm">Izin</p>
                            <p class="text-2xl font-bold text-purple-500"><?php echo $stats['izin']; ?></p>
                        </div>
                        <div class="status-card bg-yellow-50 p-4 rounded-lg">
                            <p class="text-sm">Terlambat</p>
                            <p class="text-2xl font-bold text-yellow-500"><?php echo $stats['terlambat']; ?></p>
                        </div>
                        <div class="status-card bg-orange-50 p-4 rounded-lg">
                            <p class="text-sm">Cuti</p>
                            <p class="text-2xl font-bold text-orange-500"><?php echo $stats['cuti']; ?></p>
                        </div>
                        <div class="status-card bg-blue-50 p-4 rounded-lg">
                            <p class="text-sm">Pulang Lebih Awal</p>
                            <p class="text-2xl font-bold text-blue-500"><?php echo $stats['pulang_dahulu']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Attendance table -->
                <div class="bg-white shadow rounded-lg p-4 mb-4">
                    <h2 class="text-lg font-semibold mb-4">Aktivitas</h2>
                    <table class="min-w-full divide-y divide-gray-200 text-center">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Staff</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Shift</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jam Masuk</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Jam Pulang</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($record = $records->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo htmlspecialchars($record['nama_pegawai']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        echo $record['nama_shift']
                                            ? htmlspecialchars($record['nama_shift']) . ' (' .
                                            substr($record['jam_masuk'], 0, 5) . ' - ' .
                                            substr($record['jam_keluar'], 0, 5) . ')'
                                            : 'Belum Ditentukan';
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        switch ($record['status_kehadiran']) {
                                            case 'hadir':
                                            case 'dalam_shift':
                                                $statusKehadiran = "Hadir Tepat Waktu";
                                                $statusClass = "px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm";
                                                break;
                                            case 'terlambat':
                                                $statusKehadiran = "Terlambat";
                                                $statusClass = "px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm";
                                                break;
                                            case 'pulang_dahulu':
                                                $statusKehadiran = "Pulang Lebih Awal";
                                                $statusClass = "px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm";
                                                break;
                                            case 'alpha':
                                                $statusKehadiran = "Tidak Masuk";
                                                $statusClass = "px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm";
                                                break;
                                            case 'izin':
                                                $statusKehadiran = "Izin";
                                                $statusClass = "px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm";
                                                break;
                                            case 'cuti':
                                                $statusKehadiran = "Cuti";
                                                $statusClass = "px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm";
                                                break;
                                            case 'tidak_absen_pulang':
                                                $statusKehadiran = "Tidak Absen Pulang";
                                                $statusClass = "px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm";
                                                break;
                                            default:
                                                $statusKehadiran = "Tidak Diketahui";
                                                $statusClass = "px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm";
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>">
                                            <?php echo $statusKehadiran; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo $record['waktu_masuk'] ? date('H:i', strtotime($record['waktu_masuk'])) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo $record['waktu_keluar'] ? date('H:i', strtotime($record['waktu_keluar'])) : '-'; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JS-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Core theme JS-->
    <script src="../../../assets/js/scripts.js"></script>

    <!-- Custom JS to handle sidebar toggle -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const dateInput = document.querySelector('input[name="date"]');
            const shiftSelect = document.querySelector('select[name="shift"]');

            function updateResults() {
                const searchQuery = searchInput.value;
                const selectedDate = dateInput.value;
                const selectedShift = shiftSelect.value;

                // Membuat URL dengan parameter pencarian
                const url = new URL(window.location.href);
                url.searchParams.set('search', searchQuery);
                url.searchParams.set('date', selectedDate);
                url.searchParams.set('shift', selectedShift);

                // Melakukan request AJAX
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Update tabel hasil
                        const newTable = doc.querySelector('.min-w-full');
                        const currentTable = document.querySelector('.min-w-full');
                        currentTable.innerHTML = newTable.innerHTML;

                        // Update status cards
                        const newStatusCards = doc.querySelectorAll('.status-card');
                        const currentStatusCards = document.querySelectorAll('.status-card');
                        currentStatusCards.forEach((card, index) => {
                            card.innerHTML = newStatusCards[index].innerHTML;
                        });
                    })
                    .catch(error => console.error('Error:', error));
            }

            // Menambahkan event listener untuk input pencarian
            searchInput.addEventListener('input', updateResults);

            // Menambahkan event listener untuk perubahan tanggal dan shift
            dateInput.addEventListener('change', updateResults);
            shiftSelect.addEventListener('change', updateResults);
        });

        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarWrapper = document.getElementById('sidebar-wrapper');

        sidebarToggle.addEventListener('click', function () {
            sidebarWrapper.classList.toggle('collapsed');
        });
    </script>
</body>

</html>