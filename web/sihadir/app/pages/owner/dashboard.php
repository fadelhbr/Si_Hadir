<?php
session_start();
require_once '../../../app/auth/auth.php';

date_default_timezone_set('Asia/Jakarta');

// BULANAN
$queryBulanan = $pdo->query("SELECT MONTH(waktu_masuk) AS bulan, COUNT(*) AS total_kehadiran
                                FROM absensi
                                WHERE YEAR(waktu_masuk) = YEAR(NOW()) 
                                AND status_kehadiran IN ('hadir', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang', 'dalam_shift')
                                GROUP BY bulan
                                ORDER BY bulan;
");
$dataBulanan = $queryBulanan->fetchAll(PDO::FETCH_ASSOC);
$monthlyAttendance = array_fill(0, 12, 0); // Inisialisasi array dengan 12 elemen (0 untuk semua bulan)
$bulanLabel = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

foreach ($dataBulanan as $row) {
    // Set nilai kehadiran untuk bulan yang sesuai (kurangi 1 untuk indeks)
    $monthlyAttendance[$row['bulan'] - 1] = (int) $row['total_kehadiran'];
}


// MINGGUAN
// Mendapatkan hari ini dan tanggal awal minggu (Senin)
$today = new DateTime();
$startOfWeek = clone $today;
$startOfWeek->modify('monday this week'); // Mendapatkan tanggal Senin minggu ini

// Menghitung tanggal akhir minggu (Minggu)
$endOfWeek = clone $startOfWeek;
$endOfWeek->modify('+1 week');

// Siapkan array untuk kehadiran mingguan
$weeklyAttendance = array_fill(0, 7, 0); // Inisialisasi array dengan 7 elemen (0 untuk setiap hari)

// Query untuk mendapatkan kehadiran mingguan
$queryMingguan = $pdo->prepare("
    SELECT DAYOFWEEK(tanggal) AS hari, COUNT(*) AS total_kehadiran
    FROM absensi
    WHERE tanggal >=:startOfWeek
      AND tanggal < :endOfWeek
      AND status_kehadiran IN ('hadir', 'terlambat', 'pulang_dahulu', 'tidak_absen_pulang', 'dalam_shift')
    GROUP BY hari;
");

// Bind parameter dan execute
$queryMingguan->bindValue(':startOfWeek', $startOfWeek->format('Y-m-d')); // Hanya perlu format tanggal
$queryMingguan->bindValue(':endOfWeek', $endOfWeek->format('Y-m-d')); // Hanya perlu format tanggal
$queryMingguan->execute();

// Mengisi data kehadiran mingguan
$dataMingguan = $queryMingguan->fetchAll(PDO::FETCH_ASSOC);
foreach ($dataMingguan as $row) {
    $hariIndex = ($row['hari'] + 6) % 7; // Mengubah DAYOFWEEK ke indeks array (0 untuk Senin, 1 untuk Selasa, dst.)
    $weeklyAttendance[$hariIndex] = (int) $row['total_kehadiran'];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
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

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-bottom: 20px;
        }

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
                    Monitor Presensi
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
                    <div id="navbarSupportedContent"></div>
                </div>
            </nav>

            <!-- Page content-->
            <div class="container-fluid p-4">
                <h1 class="text-3xl font-semibold mb-4">Dashboard</h1>
                <!-- Charts Section -->
                <div class="grid grid-cols-2 gap-6">
                    <!-- Monthly Attendance Trend -->
                    <div class="bg-white shadow rounded-lg p-4 mb-4">
                        <h2 class="text-lg font-semibold mb-4">Presensi Bulanan</h2>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>

                    <!-- Weekly Attendance -->
                    <div class="bg-white shadow rounded-lg p-4 mb-4">
                        <h2 class="text-lg font-semibold mb-4">Presensi Seminggu Terakhir</h2>
                        <div class="chart-container">
                            <canvas id="weeklyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tabel Kehadiran -->
                <div id="activityTable" class="bg-white shadow rounded-lg p-4 mb-4">
                    <h2 class="text-lg font-semibold mb-4">Aktivitas Hari Ini</h2>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama Staff</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Divisi</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Shift</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktivitas</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status Presensi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Get current date
                            $today = date('Y-m-d');

                            // Query untuk mengambil data absensi hari ini
                            $stmt = $pdo->prepare("
                                SELECT 
                                    u.nama_lengkap AS nama_staff,
                                    d.nama_divisi AS divisi,
                                    u.role AS jabatan,
                                    s.nama_shift AS nama_shift,
                                    a.waktu_masuk,
                                    a.waktu_keluar,
                                    a.status_kehadiran,
                                    DATE(a.tanggal) as tanggal_absen
                                FROM 
                                    absensi a
                                JOIN 
                                    pegawai p ON a.pegawai_id = p.id
                                JOIN 
                                    users u ON p.user_id = u.id
                                LEFT JOIN 
                                    divisi d ON p.divisi_id = d.id
                                JOIN 
                                    jadwal_shift js ON a.jadwal_shift_id = js.id
                                JOIN 
                                    shift s ON js.shift_id = s.id
                                WHERE 
                                    DATE(a.tanggal) = :today
                                    AND (a.waktu_masuk != '00:00:00' OR a.waktu_keluar != '00:00:00')
                                    AND a.status_kehadiran != ''
                                ORDER BY 
                                    CASE 
                                        WHEN a.waktu_masuk != '00:00:00' THEN a.waktu_masuk
                                        ELSE a.waktu_keluar
                                    END DESC
                            ");

                            $stmt->execute(['today' => $today]);
                            $todayAbsences = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Jika tidak ada data hari ini
                            if (empty($todayAbsences)) {
                                echo "<tr><td colspan='5' class='px-6 py-4 text-center'>Belum Ada Aktivitas Hari Ini</td></tr>";
                            } else {
                                foreach ($todayAbsences as $absen) {
                                    // Handle waktu masuk dan keluar
                                    $aktivitas = "";
                                    if ($absen['waktu_masuk'] !== '00:00:00') {
                                        $aktivitas = "Absen Masuk (" . htmlspecialchars($absen['waktu_masuk']) . ")";
                                    }

                                    if ($absen['waktu_keluar'] !== '00:00:00') {
                                        $aktivitas = $aktivitas ? $aktivitas . "<br>" : "";
                                        $aktivitas .= "Absen Keluar (" . htmlspecialchars($absen['waktu_keluar']) . ")";
                                    }

                                    switch ($absen['status_kehadiran']) {
                                        case 'hadir':
                                            $statusKehadiran = "Hadir Tepat Waktu";
                                            $statusClass = "px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm";
                                            break;
                                        case 'terlambat':
                                            $statusKehadiran = "Terlambat";
                                            $statusClass = "px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm";
                                            break;
                                        case 'pulang_dahulu':
                                            $statusKehadiran = "Pulang Lebih Awal";
                                            $statusClass = "px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm";
                                            break;
                                        case 'dalam_shift':
                                            $statusKehadiran = "Hadir Tepat Waktu";
                                            $statusClass = "px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm";
                                            break;
                                        case 'tidak_absen_pulang':
                                            $statusKehadiran = "Tidak Absen Pulang";
                                            $statusClass = "px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm";
                                            break;
                                        default:
                                            $statusKehadiran = "";
                                            $statusClass = "";
                                            break;
                                    }

                                    echo "<tr>";
                                    echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($absen['nama_staff']) . "</td>";
                                    echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($absen['divisi']) . "</td>";
                                    echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . htmlspecialchars($absen['nama_shift']) . "</td>";
                                    echo "<td class='px-6 py-4 text-center whitespace-nowrap text-blue-500'>" . $aktivitas . "</td>";
                                    echo "<td class='px-6 py-4 text-center whitespace-nowrap'>" . "<span class='$statusClass'>" . htmlspecialchars($statusKehadiran) . "</span>" . "</td>";
                                    echo "</tr>";
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="../../../assets/js/scripts.js"></script>

        <!-- Custom JS to handle sidebar toggle -->


        <script>
            function checkReset() {
                const now = new Date();

                // Mendapatkan waktu saat ini dalam WIB (UTC+7)
                const currentHour = now.getUTCHours() + 7; // Mengonversi ke WIB
                const currentMinute = now.getUTCMinutes();

                // Memastikan jam tidak melebihi 24
                const adjustedHour = currentHour >= 24 ? currentHour - 24 : currentHour;

                // Cek apakah waktu saat ini adalah 10:15 WIB
                const isTenFifteen = adjustedHour === 12 && currentMinute === 25;

                const tbody = document.querySelector('#activityTable tbody');

                if (isTenFifteen) {
                    // Jika sudah jam 10:15, ubah isi tbody menjadi "Tidak ada aktivitas absen"
                    tbody.innerHTML = "<tr><td colspan='5' class='px-6 py-4 text-center'>Tidak ada aktivitas absen</td></tr>";
                } else {
                    // Logika untuk mengembalikan data jika perlu
                    // Misalnya, jika ingin mengupdate data dengan AJAX atau fetch data dari server
                    // Anda bisa memanggil fungsi untuk mengambil data baru atau memperbarui tampilan tabel
                }
            }

            // Jalankan sekali saat halaman dimuat
            checkReset();

            // Periksa setiap menit
            setInterval(checkReset, 60000); // 60000 ms = 1 menit
        </script>

        <script>
            // Data untuk grafik bulanan
            const monthlyData = <?php echo json_encode($monthlyAttendance); ?>;

            // Mengubah data bulanan menjadi format yang sesuai
            const monthlyLabels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

            // Grafik kehadiran bulanan
            const monthlyChartCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyChart = new Chart(monthlyChartCtx, {
                type: 'bar',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'Presensi Bulanan',
                        data: monthlyData, // Pastikan monthlyData berisi 12 angka, satu untuk setiap bulan
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Data untuk grafik mingguan
            const weeklyData = <?php echo json_encode($weeklyAttendance); ?>;

            // Grafik kehadiran mingguan
            const weeklyChartCtx = document.getElementById('weeklyChart').getContext('2d');
            const weeklyChart = new Chart(weeklyChartCtx, {
                type: 'bar',
                data: {
                    labels: ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
                    datasets: [{
                        label: 'Presensi Seminggu Terakhir',
                        data: weeklyData,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

        </script>


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